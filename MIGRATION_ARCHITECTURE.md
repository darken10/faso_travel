# Plan de migration architecture — LIPTRA

But : faire évoluer l'architecture **sans réécriture**, par incréments livrables, ordonnés
par **ROI décroissant** et **risque croissant**. Chaque phase est autonome, mergeable seule,
et n'exige pas la suivante.

Légende effort : 🟢 < 1 j · 🟡 1–3 j · 🔴 > 3 j

| Phase | Sujet | Périmètre | Effort | Risque |
|---|---|---|---|---|
| 0 | Garde-fous (tests + conventions) | Backend | 🟡 | nul |
| 1 | `FinanceService` unique | Backend | 🟢 | faible |
| 2 | Actions métier (achat/transfert/remboursement) | Backend | 🟡 | moyen |
| 3 | Events/Listeners pour effets de bord | Backend | 🟢 | faible |
| 4 | Multi-tenant : global scope `compagnie_id` | Backend | 🟡 | moyen |
| 5 | Contrat API : Resources + dépréciation v1 | Backend + Mobile | 🟡 | moyen |
| 6 | React Query côté mobile | Mobile | 🟡 | faible |
| 7 | Monorepo + types générés (OpenAPI) | Cross | 🔴 | moyen |
| 8 | Organisation par domaine (modular monolith) | Backend | 🔴 | élevé |

> Règle d'or : **on ne commence une phase que quand la précédente est mergée et verte.**
> Les phases 0→3 apportent 80 % de la valeur pour 20 % de l'effort. Les phases 7–8 sont
> optionnelles (à faire seulement si le projet grossit).

---

## Phase 0 — Garde-fous (À FAIRE EN PREMIER)

**Pourquoi :** chaque phase suivante refactore du code critique (l'argent, l'achat). Sans
filet de tests, on régresse. Les bugs passés (`autre_personne`, bilan, `is_my_ticket`,
réduction promo) sont exactement les endroits à couvrir.

**Étapes**
1. Écrire des tests **Pest** de caractérisation (ils figent le comportement ACTUEL, même imparfait) :
   - `tests/Feature/Billetterie/PurchaseTicketTest.php` — achat pour soi / pour un tiers, `is_my_ticket`, siège.
   - `tests/Feature/Billetterie/TransferTicketTest.php` — Option A (réassignation), email rempli.
   - `tests/Feature/Finance/BilanTest.php` — recettes = `Payer + Valider`, réduction promo visible, pas de double comptage.
   - `tests/Feature/Finance/PromoTest.php` — application promo, `used_count`, `reduction` stockée.
2. Geler la **convention de nommage** dans `CLAUDE.md` : domaine en **français**, et lister
   les écarts connus à NE PAS propager (`care`→`vehicule`, `arriver`→`arrivee`, `chauffer`→`chauffeur`)
   — sans renommer encore (trop coûteux), juste documenter.
3. Activer la CI minimale (GitHub Actions) : `composer test` + `php artisan test`.

**Vérification :** `php artisan test` vert. Couverture des 4 flux critiques.

---

## Phase 1 — `FinanceService` unique 🟢

**Pourquoi :** l'ajout de la réduction promo a dû toucher `Dashboard`, `BilanFinancier` et
`ReportService`. Une seule définition de « recette » élimine cette classe de bugs.

**Cible**
```php
// app/Services/Finance/FinanceService.php
final class FinanceService
{
    public function recettesNettes(int $compagnieId, Carbon $start, Carbon $end): int;
    public function reductionsPromo(int $compagnieId, Carbon $start, Carbon $end): int;
    public function recettesBrutes(int $compagnieId, Carbon $start, Carbon $end): int; // nettes + réductions
    public function recettesManuelles(int $compagnieId, Carbon $start, Carbon $end): int;
    public function totalDepenses(int $compagnieId, Carbon $start, Carbon $end): int;
    public function serieMensuelle(int $compagnieId, int $mois = 6): array; // pour les charts
}
```

**Étapes**
1. Créer `FinanceService` en y **déplaçant** la logique existante (issue de `QueryHelpers::AllPaymentsOfMyCompagnie`, statuts `Payer + Valider`).
2. Brancher les consommateurs sur le service, **un par un** :
   - `app/Livewire/Compagnie/Finance/BilanFinancier.php`
   - `app/Livewire/Compagnie/Dashboard.php`
   - `app/Services/Report/ReportService.php`
3. Garder `QueryHelpers::AllPaymentsOfMyCompagnie` comme **détail interne** du service (ou le déprécier).

**Vérification :** les tests `BilanTest`/`PromoTest` (phase 0) restent verts ; chiffres
identiques avant/après sur un jeu de données réel.

---

## Phase 2 — Actions métier 🟡

**Pourquoi :** la logique d'achat est dupliquée/divergente entre `PaymentController` (mobile),
le guichet `VenteTicket` (Livewire) et `BuyVoyageController`. Source directe des incohérences
`is_my_ticket` et des validations différentes.

**Cible** — une action par cas d'usage, invoquée des deux côtés :
```php
app/Actions/Billetterie/
  PurchaseTicketAction.php      // soi / tiers, siège, promo, paiement
  TransferTicketAction.php      // Option A (réassignation propriété)
  RefundTicketAction.php        // remboursement → Annuler, pas de double comptage
  ApplyPromoAction.php          // validation + calcul réduction
```
Chaque action reçoit un **DTO** (`PurchaseTicketData`) construit depuis un **Form Request**
(web) ou un **API Request** (mobile) → validation unique.

**Étapes**
1. Extraire `PurchaseTicketAction` à partir du code de `PaymentController::orangeMoney`.
2. Faire pointer `PaymentController`, `VenteTicket` (guichet) et `BuyVoyageController` dessus.
3. Idem pour `TransferTicketAction` / `RefundTicketAction`.
4. Centraliser les règles dans des Form Requests partagés (`StorePurchaseRequest`).

**Vérification :** `PurchaseTicketTest` / `TransferTicketTest` verts depuis les **deux**
entrées (web + API). Comparer un achat guichet vs mobile → même état final du ticket.

---

## Phase 3 — Events / Listeners 🟢

**Pourquoi :** fidélité, notifications, `used_count` promo sont inline dans l'achat. Les
isoler les rend testables et réutilisables, et allège les Actions.

**Cible**
```php
// Émis par PurchaseTicketAction
event(new TicketPurchased($ticket));

// Listeners (queued)
AwardLoyaltyPoints::class       // LoyaltyService->award()
IncrementPromoUsage::class      // promo->used_count++
SendTicketNotification::class   // mail + Expo push
```

**Étapes**
1. Créer l'event `TicketPurchased` + les 3 listeners (déplacer le code depuis l'action/contrôleur).
2. Mapper dans `EventServiceProvider` (ou auto-discovery).
3. Marquer les listeners `ShouldQueue` (cohérent avec le worker cron en prod).

**Vérification :** `Event::fake()` dans les tests d'achat ; un achat émet bien `TicketPurchased` ;
les points fidélité ne sont attribués **que** sur achat mobile (acheteur identifié).

---

## Phase 4 — Multi-tenant : global scope 🟡

**Pourquoi :** `where('compagnie_id', …)` + `withoutGlobalScopes()` partout = oublis et
fuites de données entre compagnies. À faire **après** les Actions (qui deviennent le point
d'écriture maîtrisé).

**Cible**
```php
// app/Support/Tenancy/Tenant.php      → résout la compagnie courante
// app/Models/Concerns/BelongsToCompagnie.php
trait BelongsToCompagnie {
    protected static function bootBelongsToCompagnie(): void {
        static::addGlobalScope(new CompagnieScope);
        static::creating(fn ($m) => $m->compagnie_id ??= Tenant::id());
    }
}
```
Un **middleware** résout `Tenant` depuis le sous-domaine `compagnie.{domain}` ou `auth()->user()->compagnie_id`.

**Étapes (modèle par modèle, pas en bloc)**
1. Implémenter `Tenant` + `CompagnieScope` + middleware.
2. Appliquer le trait d'abord à **un** modèle peu risqué (`PromoCode`), retirer le `where`
   manuel correspondant, vérifier.
3. Étendre progressivement : `Ticket`, `Voyage`, `Depense`, `Recette`, `Care`, `Chauffeur`…
4. Conserver `withoutGlobalScopes()` **uniquement** pour les écrans admin cross-compagnie
   (super-user), de façon explicite et rare.

**Vérification :** test « isolation » — un user compagnie A ne voit jamais les données de B,
même via les endpoints API. Les listings web n'ont plus de `where('compagnie_id')` manuel.

---

## Phase 5 — Contrat API stable 🟡

**Pourquoi :** le mobile lit le **modèle brut** (`normalizeTicket` mappe `autre_personne`,
`promo_code`…), donc chaque colonne ajoutée casse potentiellement le client.

**Étapes**
1. Faire transiter **toutes** les réponses tickets par une `TicketResource` unique (le contrat).
   Aujourd'hui `Api/V2/TicketController` renvoie `['data' => $tickets]` brut → l'envelopper.
2. Adapter `src/services/TicketService.ts` (`normalizeTicket`) pour consommer la **Resource**
   (clés stables) au lieu des attributs bruts.
3. Marquer l'API **v1** dépréciée (header `Sunset`), supprimer les contrôleurs doublons
   (`Api/TicketController` vs `Api/V2/TicketController`) une fois le mobile 100 % v2.

**Vérification :** test API qui assert la **forme** de `TicketResource` (snapshot) ; le mobile
build sans toucher aux mappings bruts.

---

## Phase 6 — React Query (mobile) 🟡

**Pourquoi :** `useState/loading/error` réécrits dans chaque hook (`useTickets`, `useVoyage`…),
pas de cache ni de mutations optimistes (transfert/pause/remboursement). React Query n'est
**pas** un store global → compatible avec la règle « pas de Redux/Zustand » du `CLAUDE.md`
(garde Context pour Auth/Toast/Notification).

**Étapes**
1. Ajouter `@tanstack/react-query`, wrapper `QueryClientProvider` dans `app/_layout.tsx`.
2. Migrer **un** écran pilote : la liste des tickets (`useTickets` → `useQuery(['tickets'])`).
3. Convertir les actions en **mutations** avec invalidation/optimistic update :
   `transferTicket`, `pauseTicket`, `activateTicket`, remboursement.
4. Étendre à voyages, loyalty, profil.

**Vérification :** la liste tickets se met à jour seule après un transfert (sans refetch manuel) ;
suppression du boilerplate `loading/error` dans les hooks migrés.

> ⚠️ Mettre à jour `CLAUDE.md` : « état **serveur** → React Query ; état **client** → Context ».

---

## Phase 7 — Monorepo + types générés 🔴 (optionnel)

**Pourquoi :** `faso_travel/`, `client.mobile/`, `agent.mobile/` partagent le même contrat.
Aujourd'hui les types TS sont écrits à la main des deux côtés (cf. `reduction`/`promoCode`
ajoutés en double).

**Étapes**
1. Exposer un **OpenAPI** depuis Laravel (`dedoc/scramble` ou `knuckleswtf/scribe`).
2. Initialiser un monorepo **pnpm workspaces** englobant les deux apps mobiles.
3. Package `@liptra/contracts` : types TS **générés** depuis l'OpenAPI (CI), consommés par
   `client.mobile` et `agent.mobile`.

**Vérification :** modifier un champ d'une Resource → régénération → erreur de type côté mobile
**au build** si non géré (le bug devient visible avant le runtime).

---

## Phase 8 — Organisation par domaine 🔴 (optionnel, si le projet grossit)

**Pourquoi :** confort de navigation et frontières explicites quand le nombre de modules
augmente. À ne tenter qu'avec les phases 0–5 solides (tests + actions + services).

**Cible**
```
app/Domain/
  Billetterie/  Voyage/  Finance/  Fidelite/  Identite/  Notification/
    ├── Models/  Actions/  Services/  DTOs/  Events/  Resources/
```

**Étapes**
1. Déplacer module par module (commencer par `Finance`, déjà bien isolé après phase 1).
2. Ajuster les namespaces + `composer dump-autoload`.
3. Interdire les dépendances croisées non désirées (revue de code / `deptrac` si besoin).

**Vérification :** suite de tests verte ; aucun import « sauvage » entre domaines.

---

## Récapitulatif d'exécution

```
Sprint 1 : Phase 0 (tests + conventions)        ← filet de sécurité
Sprint 2 : Phase 1 (FinanceService) + Phase 3 (Events)
Sprint 3 : Phase 2 (Actions)                     ← supprime les duplications web/API
Sprint 4 : Phase 4 (multi-tenant scope)
Sprint 5 : Phase 5 (Resources) + Phase 6 (React Query)
Plus tard : Phases 7–8 si croissance
```

**Ne pas faire :** tout réécrire d'un coup, ou attaquer la phase 8 (réorganisation) avant
d'avoir le filet de tests (phase 0) et les actions (phase 2). Le risque ne vaut pas le gain.
