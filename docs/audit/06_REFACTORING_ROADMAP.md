# 06 — REFACTORING ROADMAP

---

## Phase 1 — CRITIQUE (Semaine 1-2) : Mettre en sécurité

Ces corrections sont **bloquantes avant tout déploiement**. Elles ne nécessitent pas de refactoring architectural.

---

### 1.1 · Purger les credentials du repo Git

```bash
# 1. Révoquer tous les secrets sur leurs dashboards respectifs (LigdiCash, Twilio, reCAPTCHA)
# 2. Supprimer .env du tracking
git rm --cached .env
echo ".env" >> .gitignore
git commit -m "chore: remove .env from git tracking"

# 3. Purger l'historique avec BFG
bfg --delete-files .env --no-blob-protection
git reflog expire --expire=now --all && git gc --prune=now --aggressive
git push origin --force --all
```

---

### 1.2 · Corriger le middleware Role

```php
// app/Http/Middleware/Role.php
public function handle(Request $request, Closure $next, string $role): Response
{
    if (!$request->user() || $request->user()->role !== $role) {
        abort(403, 'Accès interdit');
    }
    return $next($request);
}
```

---

### 1.3 · Corriger les bugs actifs bloquants

```php
// BUG-01 : Supprimer le dd() dans TicketController.php:64
// Avant :
dd($voyage_instance);
// Après : supprimer cette ligne, corriger la variable $voyage → $voyage_instance

// BUG-03 : TicketValidation::active()
public static function active(Ticket $ticket): bool
{
    DB::beginTransaction();
    $ticket->statut = StatutTicket::Payer; // ← était Pause
    $ticket->save();
    DB::commit();
    TicketActiveEvent::dispatch($ticket);
    return true;
}

// BUG-04 : Uniformiser la clé OTP dans AuthService
// Stocker et lire avec la même clé :
Cache::put('otp_' . $user->id, $otp, now()->addMinutes(10));
// Dans verifyOtp() — récupérer d'abord l'user, puis utiliser son ID
$user = User::where('email', $dto->phone_or_email)->orWhere('numero', $dto->phone_or_email)->first();
$storedOtp = Cache::get('otp_' . $user->id);

// BUG-06 : Ajouter try/catch dans TicketValidation::valider()
public static function valider(Ticket $ticket): bool
{
    DB::beginTransaction();
    try {
        // ... modifications ...
        $ticket->save();
        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
    // ...
}
```

---

### 1.4 · Sécuriser les routes

```php
// routes/api.php — ajouter auth:sanctum
Route::middleware('auth:sanctum')->post('/process-payment/{provider}', ...);

// routes/web.php — supprimer ou protéger
// Supprimer les deux routes /test
// Protéger create-all-voyages-instances
Route::middleware(['auth', 'can:admin'])->get("create-all-voyages-instances", ...);
```

---

### 1.5 · Désactiver debug et debugbar

```bash
# .env de production
APP_DEBUG=false
DEBUGBAR_ENABLED=false
```

---

### 1.6 · Corriger la requête myTickets()

```php
// app/Http/Controllers/Ticket/TicketController.php
$tickets = Ticket::query()
    ->where(function ($q) {
        $q->where('user_id', Auth::id())
          ->where('transferer_a_user_id', null);
    })
    ->orWhere('transferer_a_user_id', Auth::id())
    ->latest()
    ->paginate(20);
```

---

## Phase 2 — IMPORTANT (Semaine 3-6) : Stabiliser l'architecture

---

### 2.1 · Unifier les AuthService

Conserver uniquement `App\Services\Auth\AuthService`. Corriger `AuthService::login()` pour accepter les bons paramètres. Mettre à jour les injections dans les contrôleurs.

```php
// Supprimer :
// app/Services/AuthService.php
// app/Services/V2/AuthService.php

// Dans AuthController (V1) — corriger l'injection :
use App\Services\Auth\AuthService;

// Corriger l'appel login :
// Avant : $this->authService->login($request->email, $request->password)
// Après en utilisant un DTO :
$result = $this->authService->login(new LoginDTO($request->email, $request->password));
```

---

### 2.2 · Unifier les PaymentGatewayFactory

```php
// Supprimer PaymentGatewayFactory2.php
// Corriger le namespace dans PaymentGatewayFactory.php :
namespace App\Features\Payement; // majuscule

// Implémenter les vrais gateways :
class OrangeMoneyPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, Ticket $ticket, User $user, array $details = []): bool|string
    {
        // Vraie intégration API Orange Money
        // Retourner l'URL de redirection ou true/false
    }
}
```

---

### 2.3 · Implémenter le callback de paiement

```php
public function callbackFunction(Request $request, Ticket $ticket): \Illuminate\Http\JsonResponse
{
    $provider = PaymentProvider::from($request->route('provider'));
    $gateway = $this->paymentGatewayFactory->getPaymentGateway($provider);
    $status = $gateway->getStatus($request->all());

    DB::transaction(function () use ($ticket, $status) {
        $ticket->payements()->latest()->first()?->update(['statut' => $status]);
        if ($status === StatutPayement::Complete) {
            $ticket->update(['statut' => StatutTicket::Payer]);
            PayementEffectuerEvent::dispatch($ticket);
        }
    });

    return response()->json(['received' => true]);
}
```

---

### 2.4 · Passer PDF et email en jobs asynchrones

```bash
php artisan make:job GenerateTicketPdfJob
php artisan make:job SendTicketEmailJob
```

```php
// jobs/GenerateTicketPdfJob.php
class GenerateTicketPdfJob implements ShouldQueue
{
    public function __construct(public Ticket $ticket) {}

    public function handle(PdfGeneratorHelper $pdfGenerator): void
    {
        $pdfGenerator->generate($this->ticket);
    }
}

// Dans PaymentController V2 après DB::commit() :
GenerateTicketPdfJob::dispatch($ticket)->chain([
    new SendTicketEmailJob($ticket, TypeNotification::TICKET_PAYER)
]);
```

---

### 2.5 · Ajouter la pagination sur toutes les listes

Toutes les méthodes retournant des collections sans pagination :
- `TicketQueryService::getUserTickets()` → `paginate(20)`
- `TicketQueryService::getAllValidatedTickets()` → `paginate(50)`
- `NotificationService::getUserNotifications()` → `paginate(30)`

---

### 2.6 · Corriger les N+1 dans QueryHelpers

Remplacer les chaînes `.get().pluck()` par des sous-requêtes :

```php
// Avant :
Ticket::whereIn('voyage_instance_id',
    self::AllVoyagesInstanceOfMyCompagnie()->get()->pluck('id')->toArray()
);

// Après :
Ticket::whereHas('voyageInstance.voyage', function ($q) {
    $q->where('compagnie_id', auth()->user()->compagnie_id);
});
```

---

### 2.7 · Centraliser l'autorisation compagnie dans un middleware

```php
// Nouveau middleware : app/Http/Middleware/RequiresCompagnie.php
public function handle(Request $request, Closure $next): Response
{
    if (!$request->user()?->compagnie_id) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }
    return $next($request);
}

// Enregistrer dans bootstrap/app.php et utiliser :
Route::middleware(['auth:sanctum', 'requires.compagnie'])->group(function () {
    Route::get('/today-passengers', ...);
    // ...
});
```

---

## Phase 3 — OPTIMISATION (Semaine 7-12) : Scalabilité

---

### 3.1 · Tests automatisés

Écrire des tests pour les flux critiques (minimum viable) :

```php
// tests/Feature/Ticket/TicketCreationTest.php
test('user can create a ticket for available voyage instance', function () {
    $user = User::factory()->create();
    $voyageInstance = VoyageInstance::factory()->create(['statut' => StatutVoyageInstance::Actif]);
    
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v2/tickets', ['voyage_instance_id' => $voyageInstance->id, ...]);
    
    $response->assertCreated();
    $this->assertDatabaseHas('tickets', ['user_id' => $user->id, 'statut' => 'En attente']);
});

test('cannot create duplicate pending ticket for same voyage', function () { ... });
test('seat number is atomic under concurrent bookings', function () { ... });
test('payment callback updates ticket status', function () { ... });
test('transfer requires correct password', function () { ... });
```

---

### 3.2 · Implémenter un vrai système d'attribution de siège atomique

```php
// Utiliser un verrou DB pour éviter les race conditions
public static function getNextAvailableSeat(VoyageInstance $voyage): int
{
    return DB::transaction(function () use ($voyage) {
        // Verrouiller la ligne de l'instance pour éviter les lectures concurrentes
        $instance = VoyageInstance::lockForUpdate()->findOrFail($voyage->id);
        
        $takenSeats = Ticket::where('voyage_instance_id', $instance->id)
            ->whereNotIn('statut', [StatutTicket::Annuler])
            ->pluck('numero_chaise')
            ->toArray();
        
        $maxSeats = $instance->nb_place ?? $instance->care?->number_place ?? 50;
        
        for ($seat = 1; $seat <= $maxSeats; $seat++) {
            if (!in_array($seat, $takenSeats)) return $seat;
        }
        
        throw new \RuntimeException('Aucune place disponible.');
    });
}
```

---

### 3.3 · Caching avec Redis

```php
// Cache des compagnies actives
$voyages = Cache::remember("compagnie.{$compagnieId}.voyages", 300, function () use ($compagnieId) {
    return Voyage::where('compagnie_id', $compagnieId)->with('trajet')->get();
});

// Invalider le cache lors des modifications :
Cache::forget("compagnie.{$compagnieId}.voyages");
```

---

### 3.4 · Index de base de données

```sql
-- Migrations à ajouter :
ALTER TABLE tickets ADD INDEX idx_user_statut (user_id, statut);
ALTER TABLE tickets ADD INDEX idx_voyage_instance (voyage_instance_id, statut);
ALTER TABLE tickets ADD INDEX idx_transfere (transferer_a_user_id);
ALTER TABLE voyage_instances ADD INDEX idx_date_voyage (date, voyage_id);
ALTER TABLE payements ADD INDEX idx_ticket_statut (ticket_id, statut);
```

---

### 3.5 · Supprimer les classes mortes

```bash
# Supprimer définitivement :
rm app/Services/AuthService.php                    # remplacé par Auth/AuthService
rm app/Services/V2/AuthService.php                 # duplication
rm app/Features/Payement/PaymentGatewayFactory2.php # classes référencées inexistantes
rm app/Http/Controllers/Ticket/Payement/PayementTest.php # test controller
rm app/Http/Controllers/Api/V2/ArticleControllerV2.php   # duplication dans V2
```

---

## Résumé du plan

| Phase | Durée | Impact | Risque |
|-------|-------|--------|--------|
| 1 · Critique | 1-2 semaines | Sécurité & bugs bloquants | Très faible (corrections ciblées) |
| 2 · Important | 3-4 semaines | Stabilité & architecture | Moyen (refactoring) |
| 3 · Optimisation | 6-8 semaines | Scalabilité & maintenabilité | Faible (ajouts) |

**Avant tout déploiement en production : Phase 1 est obligatoire.**
