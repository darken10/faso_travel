# 01 — ARCHITECTURE REVIEW

---

## Pattern détecté

L'application tente un **MVC hybride** avec des embryons de **Service Layer** et **CQRS léger** (TicketQueryService / TicketCommandService), mais sans cohérence d'application. Le résultat est une architecture à deux vitesses : propre en V2, anarchique en V1 et en web.

---

## Diagramme logique (état actuel)

```
Client Mobile / Web
       │
       ├── /api/v1/*  ──► UserController (auth mélangée)
       │                   VoyageApiController
       │                   TicketApiController (admin)
       │                   PaymentController2 (PaymentGatewayFactory v1)
       │
       ├── /api/v2/*  ──► AuthController V2
       │                   TicketController V2 ──► TicketQueryService
       │                                      ──► TicketCommandService
       │                   PaymentController V2 ──► OrangePayementHelper (FAKE)
       │                   BuyVoyageController ──► BuyVoyageService
       │
       ├── /           ──► Blade Controllers (Web)
       │                   TicketController (web) ──► TicketHelpers (statique)
       │                   OrangePayementController (paiement web)
       │                   PaymentController2 (dupliqué)
       │
       └── /admin, /compagnie  ──► Filament Resources
                                    Pages, Widgets
```

---

## Problèmes architecturaux détectés

### 1. Triple AuthService — violation DRY catastrophique

Trois implémentations d'`AuthService` coexistent :

- `App\Services\AuthService` — méthode `login(array $credentials)` mais appelée avec deux arguments séparés → TypeError garanti
- `App\Services\Auth\AuthService` — utilise DTOs, `hash_equals` sur OTP (sécurisé)
- `App\Services\V2\AuthService` — copier-coller de la V2 avec de légères variations

**Le fichier `App\Services\AuthService` est injecté dans `App\Http\Controllers\Api\AuthController`** qui appelle `$this->authService->login($request->email, $request->password)` — 2 args au lieu d'un array. **Crash au login.**

Correction :
```php
// AuthService::login doit être :
public function login(string $email, string $password): array
// ou appel correct :
$this->authService->login(['email' => $request->email, 'password' => $request->password]);
```

### 2. Deux PaymentGatewayFactory incompatibles

```
app/Features/Payement/PaymentGatewayFactory.php  → namespace App\features\payement (lowercase !)
app/Features/Payement/PaymentGatewayFactory2.php → namespace App\Features\Payement
```

`PaymentGatewayFactory` (utilisée par `PaymentController2`) a le **bon namespace** mais référence les bonnes classes.
`PaymentGatewayFactory2` (injectée dans `PaymentHelper`) référence `OrangePaymentGateway`, `MoovPaymentGateway`, `CorisPaymentGateway` qui **n'existent pas** — Fatal Error à la première instanciation.

### 3. Routes dupliquées et contradictoires

Dans `api.php` :
```php
Route::middleware('auth:sanctum')->prefix("voyages")... // line 45
Route::middleware('auth:sanctum')->prefix('voyages')... // line 52 — DOUBLON
```

Deux groupes de routes `/voyages` existent simultanément, le second écrase le premier.

Dans `web.php` :
```php
Route::get('/test', ...);  // line 118 — expose notification test
Route::get("/test", ...);  // line 126 — retourne une view test, écrase le précédent
```

### 4. V1 API : AuthController inexistant pour les routes auth

```php
// api.php
Route::prefix('/auth')->controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});
```

Les routes `/auth/register` et `/auth/login` sont géréees par `UserController`, pas `AuthController`. Pourtant `AuthController` existe. Le `UserController` agit simultanément comme contrôleur de profil ET d'authentification — violation SRP.

### 5. Logique métier dans les Helpers statiques

`TicketHelpers`, `TicketValidation`, `QueryHelpers`, `VoyageHelper` sont des classes full-static avec logique métier, accès DB, et appels d'événements. Cela :
- Rend les tests unitaires impossibles (impossible de mocker)
- Crée un couplage fort entre couches
- Empêche l'injection de dépendances

`QueryHelpers::AllTicketOfMyCompagnie()` charge en mémoire toutes les instances de voyage pour en extraire les IDs — N+1 masqué.

### 6. Aucun middleware de rôle fonctionnel sur les routes Admin/Compagnie

Les routes `/admin/*` Filament sont protégées par Filament. Mais les routes API admin (`/api/admin/*`) n'ont que `auth:sanctum` — **n'importe quel utilisateur authentifié peut appeler ces endpoints**.

La vérification de compagnie (`checkCompagnieAccess()`) est une méthode privée dans le contrôleur appelée manuellement, pas un middleware. Oubli = endpoint exposé.

### 7. Filament : pas de panneaux distincts correctement configurés

Il existe deux panels Filament (`admin` et `compagnie`) avec chacun leurs Resources, mais l'isolation des données repose sur la logique dans chaque Resource individuellement — pas de politique centralisée. Une Resource mal configurée expose toutes les données de toutes les compagnies.

---

## Ce qui est bien conçu

- `TicketQueryService` / `TicketCommandService` : bonne séparation CQRS, machine à états explicite dans `STATUS_TRANSITIONS`
- DTOs typés dans `app/DTOs/` — réduction du mass-assignment
- Enums PHP 8.1 utilisés correctement
- `TicketCommandService::changeStatus()` avec transitions explicites — extensible

---

## Recommandation d'architecture cible

```
app/
├── Http/Controllers/Api/V2/   ← SEULE surface API à garder et étendre
├── Services/                  ← UN seul AuthService, pas de duplication
│   ├── Auth/AuthService.php   ← garder celui-ci (DTOs + hash_equals)
│   ├── Ticket/TicketCommandService.php
│   ├── Ticket/TicketQueryService.php
│   └── Payment/PaymentService.php (nouveau)
├── Gateways/Payment/          ← remplace Features/Payement
│   ├── Contracts/PaymentGateway.php
│   ├── OrangeMoneyGateway.php (implémentation réelle)
│   └── PaymentGatewayFactory.php (une seule)
├── Policies/                  ← autorisation centralisée, pas dans les controllers
└── Jobs/                      ← PDF génération + envoi email en arrière-plan
```
