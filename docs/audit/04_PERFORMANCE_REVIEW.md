# 04 — PERFORMANCE REVIEW

---

## Goulots d'étranglement critiques

### PERF-01 · N+1 masqué dans `QueryHelpers` — chaque appel est O(n²)
**Impact** : 🔴 CRITIQUE pour un usage multi-compagnie

```php
// QueryHelpers::AllTicketOfMyCompagnie()
public static function AllTicketOfMyCompagnie(?StatutTicket $statutTicket = null)
{
    return Ticket::whereStatut($statutTicket)
        ->whereIn('voyage_instance_id',
            self::AllVoyagesInstanceOfMyCompagnie()->get()->pluck(['id'])->toArray()
            //                                    ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
            // Charge TOUTES les instances en mémoire pour extraire les IDs
        );
}

// AllVoyagesInstanceOfMyCompagnie() elle-même appelle :
$voyages = Voyage::whereBelongsTo(auth()->user()->compagnie)->get()->pluck(['id'])->toArray();
return VoyageInstance::whereIn('voyage_id', $voyages); // 2e chargement complet
```

**Résultat** : Pour récupérer les tickets d'une compagnie, le code exécute :
1. `SELECT * FROM voyages WHERE compagnie_id = ?` → charge N voyages en mémoire
2. `SELECT * FROM voyage_instances WHERE voyage_id IN (...)` → charge M instances en mémoire
3. `SELECT * FROM tickets WHERE voyage_instance_id IN (...)` → requête finale

**Fix avec une seule requête SQL** :
```php
public static function AllTicketOfMyCompagnie(?StatutTicket $status = null)
{
    $compagnieId = auth()->user()->compagnie_id;
    $query = Ticket::whereHas('voyageInstance.voyage', fn($q) => $q->where('compagnie_id', $compagnieId));
    return $status ? $query->where('statut', $status) : $query;
}
```

---

### PERF-02 · `getNumeroChaise()` — boucle SQL illimitée
**Impact** : 🔴 CRITIQUE en cas de concurrent élevé

```php
public static function getNumeroChaise(VoyageInstance $voyage): int
{
    $NTickets = Ticket::query()->where(...)->get()->count(); // 1 requête

    while (
        Ticket::query()->where('numero_chaise', $NTickets+1)->get()->count() !== 0
        //                                                   ^^^^
        // 1 requête SQL PAR ITÉRATION de boucle
    ) {
        $NTickets++;
    }
    return $NTickets + 1;
}
```

Pour un bus de 50 places presque complet, cette méthode peut exécuter 50 requêtes SQL. Et elle est appelée dans une transaction de paiement.

**Fix** :
```php
public static function getNextAvailableSeat(VoyageInstance $voyage): int
{
    $taken = Ticket::where('voyage_instance_id', $voyage->id)
        ->whereNotIn('statut', [StatutTicket::Annuler])
        ->pluck('numero_chaise')
        ->toArray();

    $max = $voyage->care?->number_place ?? 50;
    for ($i = 1; $i <= $max; $i++) {
        if (!in_array($i, $taken)) return $i;
    }
    throw new \RuntimeException('Aucune place disponible.');
}
```

---

### PERF-03 · `AllPostsOfMyCompagnie()` — chargement total en mémoire
**Impact** : 🟠 ÉLEVÉ

```php
public static function AllPostsOfMyCompagnie()
{
    return Post::whereIn('user_id',
        self::AllUsersOfMyCompagnie()->get()->pluck(['id'])->toArray()
        // ← charge TOUS les users de la compagnie en mémoire
    );
}
```

Avec 1000 users d'une compagnie, cela charge 1000 objets User en mémoire pour extraire des IDs.

**Fix** :
```php
Post::whereHas('user', fn($q) => $q->where('compagnie_id', auth()->user()->compagnie_id))
```

---

### PERF-04 · Listes sans pagination
**Impact** : 🟠 ÉLEVÉ

```php
// TicketController::myTickets() — tous les tickets de l'user
$tickets = Ticket::query()->...->latest()->get(); // → peut retourner 10 000 lignes

// TicketQueryService::getAllValidatedTickets()
return Ticket::with([...])->where('statut', StatutTicket::Valider)->get(); // illimité
```

En production avec des milliers de tickets, ces requêtes chargent toute la table en mémoire. Timeout garanti.

**Fix minimal** : Ajouter `.paginate(20)` ou `.cursorPaginate(20)`.

---

### PERF-05 · Génération PDF et QR code synchrone dans la transaction de paiement
**Impact** : 🟠 ÉLEVÉ

```php
// Dans PaymentController V2 — après DB::commit()
try {
    PayementEffectuerEvent::dispatch($ticket);       // → génère QR code (IO)
    SendClientTicketByMailEvent::dispatch($ticket);   // → génère PDF (CPU) + envoi email (réseau)
} catch (\Throwable $e) { ... }
```

Ces opérations (génération PDF avec DomPDF/Browsershot, envoi SMTP) peuvent prendre 2-10 secondes. Elles bloquent le thread PHP de la requête HTTP client. Si l'envoi mail échoue, l'utilisateur attend pour rien.

**Fix** : Passer en jobs asynchrones :
```php
GenerateTicketJob::dispatch($ticket)->onQueue('tickets');
SendTicketEmailJob::dispatch($ticket)->onQueue('emails');
```

---

### PERF-06 · Notifications chargées en totalité
**Impact** : 🟡 MOYEN  
**Fichier** : `app/Services/NotificationService.php`

```php
$notifications = $user->notifications;         // charge TOUTES les notifs
$unreadCount = $user->unreadNotifications->count(); // 2e chargement
```

Un utilisateur avec 500 notifications charge 500 objets en mémoire à chaque appel. Et `unreadNotifications` est chargé séparément alors qu'on pourrait le dériver du premier chargement.

**Fix** :
```php
$notifications = $user->notifications()->paginate(20);
$unreadCount = $user->unreadNotifications()->count();
```

---

### PERF-07 · `VoyageHelper::getVoyagesByDay()` — LIKE sur colonne JSON
**Impact** : 🟡 MOYEN

```php
Voyage::query()->whereLike('days', "%" . $day . "%")->get();
```

La colonne `days` est un tableau JSON. Un `LIKE` avec wildcards de deux côtés (`%..%`) ne peut pas utiliser d'index. **Full table scan** garanti.

**Fix** : Utiliser `whereJsonContains` (MySQL 5.7+) :
```php
Voyage::whereJsonContains('days', $day)->get();
```

---

## Analyse d'impact à 1 million d'utilisateurs

| Problème | Impact à 1M users |
|----------|-------------------|
| PERF-01 : N+1 QueryHelpers | Timeout DB, CPU saturé dès 10K tickets |
| PERF-02 : Boucle SQL siège | Deadlocks, timeouts paiement pendant les pics |
| PERF-03 : Chargement total | OOM errors sur les serveurs app |
| PERF-04 : Pas de pagination | Requêtes de 100MB+, crash PHP |
| PERF-05 : PDF synchrone | 10s par paiement → abandon cart massif |
| PERF-06 : Notifs complètes | Chaque page profil = requête de 1000+ lignes |
| PERF-07 : LIKE sur JSON | 100% CPU sur MySQL lors des recherches |

**Conclusion** : L'application **ne passera pas 1000 utilisateurs simultanés** sans optimisations de base.

---

## Recommandations prioritaires

1. Ajouter un système de queues (Redis + Laravel Horizon) pour PDF/email
2. Implémenter la pagination sur toutes les listes
3. Remplacer les chaînes `.get().pluck()` par des jointures ou `whereHas`
4. Ajouter des index DB sur : `tickets.statut`, `tickets.voyage_instance_id`, `tickets.user_id`, `tickets.transferer_a_user_id`
5. Mettre en cache les listes de compagnie/voyage (TTL 5 min) avec Redis
6. Utiliser `DB::select()` ou Eloquent chunking pour les agrégats financiers (BilanFinancier)
