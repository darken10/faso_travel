# 02 — CODE QUALITY REVIEW

---

## Bugs actifs (crash confirmés)

### BUG-01 · `dd()` en production
**Fichier** : `app/Http/Controllers/Ticket/TicketController.php:64`
```php
function createTicketWithVoyageInstance(CreateTicketRequest $request, VoyageInstance $voyage_instance, ...)
{
    $data = $request->validated();
    dd($voyage_instance); // ← CRASH HTTP 500 garanti
    $this->ticketService->createTicket($voyage->id, $data); // $voyage n'existe même pas
}
```
La variable `$voyage` est utilisée mais n'est pas dans la signature — double bug.

### BUG-02 · `throw` suivi d'un `return` inaccessible
**Fichier** : `app/Http/Controllers/Ticket/Payement/OrangePayementController.php:65`
```php
catch(Exception $e){
    DB::rollBack();
    throw new Exception($e->getMessage()); // ← exception relancée
    return back()->with('error', '...'); // ← JAMAIS EXÉCUTÉ
}
```
L'erreur est relancée non gérée, ce qui produit un 500 non formaté en production.

### BUG-03 · `TicketValidation::active()` met en Pause
**Fichier** : `app/Helper/TicketValidation.php:85`
```php
public static function active(Ticket $ticket): bool
{
    DB::beginTransaction();
    $ticket->statut = StatutTicket::Pause; // ← devrait être Active ou Payer
    $ticket->save();
    DB::commit();
    if ($ticket->statut === StatutTicket::Pause){ // ← condition toujours vraie
        TicketActiveEvent::dispatch($ticket);
        return true;
    }
    return false;
}
```
Un ticket "activé" est mis en Pause. La méthode retourne toujours `true`.

### BUG-04 · OTP : clé de cache incohérente entre `sendOtp` et `verifyOtp`
**Fichier** : `app/Services/Auth/AuthService.php`
```php
// sendOtp() — stocke sous l'ID utilisateur
Cache::put('otp_' . $user->id, $otp, ...); // clé: "otp_42"

// verifyOtp() — lit sous email/phone
$storedOtp = Cache::get("otp_{$dto->phone_or_email}"); // clé: "otp_user@mail.com"
```
Ces deux clés ne correspondent jamais. OTP toujours `null` → vérification impossible.

### BUG-05 · `AuthService::login()` — mauvaise signature
**Fichier** : `app/Services/AuthService.php:38` appelé depuis `app/Http/Controllers/Api/AuthController.php:56`
```php
// Service définit :
public function login(array $credentials): array

// Controller appelle :
$result = $this->authService->login($request->email, $request->password);
// → TypeError: Argument #1 must be array, string given
```

### BUG-06 · `TicketValidation::valider()` sans try/catch
**Fichier** : `app/Helper/TicketValidation.php:20`
```php
public static function valider(Ticket $ticket): bool
{
    DB::beginTransaction();
    // ... modifications ...
    $ticket->save();    // ← si exception ici
    DB::commit();       // ← jamais atteint
    // Pas de catch → transaction jamais rollbackée → DB en état inconsistant
}
```

---

## Code mort et inutile

| Fichier | Élément | Raison |
|---------|---------|--------|
| `TicketController.php:165` | `updateTicket()` | Corps vide, jamais implémentée |
| `PaymentController2::callbackFunction()` | Corps vide | Webhooks de paiement ignorés silencieusement |
| `PayementTest.php` | Classe entière | Retourne une view de test, aucune utilité en prod |
| `app/Helper/Payement/Payement.php::verificationPayementStatutByPayementApi()` | Méthode | Retourne toujours `StatutPayement::Complete` sans aucune logique |
| `PaymentGatewayFactory2.php` | Classe entière | Référence des classes inexistantes, ne peut pas fonctionner |
| `app/Helper/QueryHelpers.php::AllGaresOfMyCompagnie()` | Méthode | Requête incohérente (filtre par `user_id` sur les gares, pas `compagnie_id`) |
| `OrangeMoneyPaymentGateway::processPayment()` et `getStatus()` | Méthodes | `// TODO: Implement` — jamais implémentées |
| `app/Services/AuthService.php` | Classe entière | Remplacée par `App\Services\Auth\AuthService` mais toujours injectée |

---

## Code dupliqué

### Duplication 1 : Triple AuthService
Les fichiers suivants sont quasi-identiques (95% de code commun) :
- `app/Services/AuthService.php`
- `app/Services/Auth/AuthService.php`
- `app/Services/V2/AuthService.php`

### Duplication 2 : Double ArticleController en V2
```
app/Http/Controllers/Api/V2/ArticleController.php
app/Http/Controllers/Api/V2/ArticleControllerV2.php
```
Deux contrôleurs d'articles dans le même namespace V2. Lequel est utilisé ?

### Duplication 3 : Logique de ticket dans V2 TicketController ET TicketCommandService
`TicketController.php` (V2) contient encore directement des appels `DB::transaction`, `Ticket::create`, `TicketHelpers::regenerateTicket` en parallèle avec les méthodes du `TicketCommandService`. La refactorisation est incomplète.

### Duplication 4 : Génération de ticket en 3 endroits
La logique de création de ticket (génération de numéro, code QR, statut initial) est dupliquée dans :
- `TicketController.php` (web)
- `TicketCommandService::createFromVoyageInstance()`
- `V2\PaymentController::orangeMoney()` (via `TicketService::createTicket()`)

---

## Code smells

### Nommage incohérent (Français/Anglais/Fautes)
```
VoyageApiContoller.php    → faute: "Contoller" (r manquant)
tansferer (route name)    → faute: "transferer"
payerAutrePersonneTicket  → mélange français/anglais
getNumeroChaise()         → fr
availableSeats()          → en
```

### Méthode `getNumeroChaise()` — algorithme dangereux
```php
public static function getNumeroChaise(VoyageInstance $voyage): int
{
    $NTickets = Ticket::query()
        ->whereBelongsTo($voyage)
        ->where('statut', StatutTicket::Payer)
        ->get()->count();

    while (
        Ticket::query()->whereBelongsTo($voyage)
            ->where('statut', StatutTicket::Payer)
            ->where('numero_chaise', $NTickets+1)
            ->get()->count() !== 0
        // ...
    ) {
        $NTickets++;
    }
    return $NTickets+1;
}
```
- **N+1 dans une boucle while** : une requête SQL par itération
- **Race condition** : deux appels simultanés retournent le même numéro
- Le comptage de tickets payés ne garantit pas que le numéro n'est pas pris par un ticket en attente

### `myTickets()` — fuite de données
```php
$tickets = Ticket::query()
    ->where('transferer_a_user_id', null)
    ->whereBelongsTo(Auth::user())
    ->orWhere('transferer_a_user_id', Auth::user()->id) // ← sans groupement
    ->latest()->get();
```
Sans parenthèses, le `orWhere` est au niveau global. La requête SQL générée est :
```sql
WHERE (transferer_a_user_id IS NULL AND user_id = ?) OR transferer_a_user_id = ?
```
Cela retourne **tous les tickets sans destinataire de transfert** (pas seulement ceux de l'utilisateur), potentiellement les tickets de n'importe qui.

Correction :
```php
$tickets = Ticket::query()
    ->where(function ($q) {
        $q->where('user_id', Auth::id())
          ->where('transferer_a_user_id', null);
    })
    ->orWhere('transferer_a_user_id', Auth::id())
    ->latest()->get();
```

### `\Db::commit()` vs `\DB::beginTransaction()` (casse)
```php
// TicketHelpers::regenerateTicket()
\DB::beginTransaction(); // ← uppercase
\Db::commit();           // ← lowercase d
```
Sur les systèmes de fichiers insensibles à la casse (macOS), ce code fonctionne. Sur Linux (prod), **Fatal Error**. La classe `\Db` n'existe pas.

### `VoyageHelper::getVoyagesByDay()` — `whereLike` sur JSON
```php
return Voyage::query()
    ->whereLike('days', "%" . $day . "%")
    ->get();
```
Le champ `days` est un tableau JSON. Un `LIKE` sur un JSON sérialisé n'est pas fiable. `"Lundi"` peut être contenu dans `"LundiMatin"` ou dans une valeur différente si l'ordre de sérialisation change.

---

## Violations SOLID

| Principe | Violation | Localisation |
|----------|-----------|--------------|
| **S** (Single Responsibility) | `UserController` gère l'authentification ET le profil | `Api/V1/UserController` |
| **S** | `OrangePayementController` fait : validation, paiement, DB, génération PDF, envoi mail | `Ticket/Payement/OrangePayementController.php` |
| **O** (Open/Closed) | `PaymentGatewayFactory` utilise un `match()` — ajouter un provider nécessite de modifier la classe | `Features/Payement/PaymentGatewayFactory.php` |
| **D** (Dependency Inversion) | `TicketHelpers`, `TicketValidation` sont des classes statiques appelées directement — impossible à abstraire | Partout |
| **I** (Interface Segregation) | `PaymentGatewayInterface` non implémentée correctement par `OrangeMoneyPaymentGateway` (méthodes vides) | `Features/Payement/` |
