# 03 — SECURITY AUDIT

---

## CRITIQUE — Gravité maximale

### SEC-01 · Credentials exposés dans Git
**Gravité** : 🔴 CRITIQUE  
**Fichier** : `.env` (tracké dans git — confirmé : `git ls-files .env` retourne `.env`)

Les credentials suivants sont dans l'historique Git et accessibles à quiconque a accès au repo :

```
DB_USERNAME=afri.........
DB_PASSWORD=afri.......
LIGDICASH_API_KEY=MAGP......
LIGDICASH_AUTH_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
TWILIO_SID=AC4b......
TWILIO_TOKEN=85d.......
TWILIO_PHONE_NUMBER=+18.......
RECAPTCHA_SECRET_KEY=6LelIBU........
```

**Action immédiate** :
1. Révoquer TOUS ces tokens sur les dashboards LigdiCash, Twilio, Google ReCAPTCHA
2. Changer les mots de passe DB
3. Supprimer `.env` de Git : `git rm --cached .env && git commit -m "remove .env from tracking"`
4. Vérifier que `.env` est dans `.gitignore` (il y est, mais trop tard)

---

### SEC-02 · Middleware `Role` — bypass d'autorisation universel
**Gravité** : 🔴 CRITIQUE  
**Fichier** : `app/Http/Middleware/Role.php:17`

```php
// Code actuel — BUG d'opérateur :
if (! $request->user() || ! $request->user()->role == $role) {
//                         ^^^^^^^^^^^^^^^^^^^^^^^^
// PHP évalue : (!$request->user()->role) == $role
// Résultat : false == $role  → toujours false
// La condition d'abort n'est JAMAIS vraie pour un user connecté
```

**Explication** : L'opérateur `!` a une précédence plus haute que `==`. L'expression est évaluée comme `(!$request->user()->role) == $role`, ce qui convertit le rôle en bool (`false` pour une string non vide), puis compare avec `$role` (string). Le résultat est toujours `false`. **Le middleware laisse passer tout le monde.**

**Correction** :
```php
if (!$request->user() || $request->user()->role !== $role) {
    abort(403, 'Accès interdit');
}
```

---

### SEC-03 · Route de paiement sans authentification
**Gravité** : 🔴 CRITIQUE  
**Fichier** : `routes/api.php:68`

```php
// Sans middleware auth:sanctum !
Route::post('/process-payment/{provider}', [PaymentController2::class, 'processPayment']);
```

N'importe qui, sans être connecté, peut appeler ce endpoint et déclencher une tentative de paiement avec n'importe quel `provider`.

**Correction** :
```php
Route::middleware('auth:sanctum')->post('/process-payment/{provider}', [PaymentController2::class, 'processPayment']);
```

---

### SEC-04 · Route publique de création de voyages
**Gravité** : 🔴 CRITIQUE  
**Fichier** : `routes/web.php:148`

```php
// Aucun middleware auth !
Route::get("create-all-voyages-instances", [VoyageInstanceController::class, 'createAllInstance']);
```

N'importe qui peut déclencher la création de toutes les instances de voyage (opération potentiellement lourde, déstabilisante).

---

## ÉLEVÉ

### SEC-05 · `APP_DEBUG=true` et Debugbar activé
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `.env:3,70`

```
APP_DEBUG=true
DEBUGBAR_ENABLED=true
```

En cas d'exception, Laravel retourne la stack trace complète incluant les chemins de fichiers, valeurs de variables, et configuration. La Debugbar expose les requêtes SQL dans les headers HTTP. Cela aide un attaquant à cartographier l'application.

---

### SEC-06 · Routes de test en production
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `routes/web.php:118,126`

```php
Route::get('/test', function () {
    $user = User::findOrFail(1);
    $user->notify(new TicketTestNotification());
    dd($user); // ← expose toutes les données de l'user #1
});

Route::get("/test", function (){
    $tk = \App\Models\Ticket\Ticket::all()->first();
    return view('test.test'); // ← expose données de ticket
});
```

Ces routes exposent des données d'utilisateurs réels sans authentification.

---

### SEC-07 · Réutilisation du secret applicatif
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `.env:29`

```
APP_KEY=base64:Vfx+IJJGZeU7QodZhIBx+FStR1VCeSR0OnEuJeDgCqI=
SECRET_ACCESS_TOKEN_LOGIN_USER=Vfx+IJJGZeU7QodZhIBx+FStR1VCeSR0OnEuJeDgCqI=
```

La valeur de `SECRET_ACCESS_TOKEN_LOGIN_USER` est identique à `APP_KEY`. Si ce token est utilisé pour signer des données, compromettre l'un compromet l'autre.

---

### SEC-08 · OTP non envoyé — fonctionnalité placebo
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `app/Services/Auth/AuthService.php:55`

```php
// Generate OTP
Cache::put('otp_' . $user->id, $otp, Carbon::now()->addMinutes(10));

// TODO: Send OTP via email or SMS
return true;
```

L'OTP est généré mais **jamais envoyé à l'utilisateur**. La fonctionnalité de vérification OTP est exposée mais non fonctionnelle. Une vérification OTP cassée est pire qu'une absence de vérification — elle donne un faux sentiment de sécurité.

---

### SEC-09 · Webhook de paiement vide — déni de service financier
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `app/Http/Controllers/Ticket/Payement/PaymentController2.php:68`

```php
public function callbackFunction(Request $request, Ticket $ticket)
{
    // Vide — rien ne se passe
}
```

Les callbacks de paiement (notifications asynchrones des providers) sont ignorés silencieusement. Un paiement confirmé côté bank peut ne jamais être enregistré en base.

---

### SEC-10 · Paiement Orange Money hardcodé (fake)
**Gravité** : 🟠 ÉLEVÉ  
**Fichier** : `app/Helper/Payement/OrangePayementHelper.php:21`

```php
if ($this->numero == "70707070" and $this->otp == "123456") {
    return [...$transData]; // ← accepté comme paiement valide
}
```

En production, seul le numéro `70707070` avec le code `123456` sera accepté. Tout autre paiement échouera silencieusement. Il est également possible qu'un attaquant qui connaît ces credentials simule des paiements.

---

## MOYEN

### SEC-11 · Contrôle d'accès par vérification dans le contrôleur (non middleware)
**Gravité** : 🟡 MOYEN  
**Fichier** : `app/Http/Controllers/Api/TicketController.php:24`

```php
private function checkCompagnieAccess()
{
    if (!Auth::user()->compagnie_id) {
        abort(403, 'Accès non autorisé');
    }
}
```

Cette méthode doit être appelée manuellement dans chaque action. Si un développeur ajoute une nouvelle action sans l'appeler, l'endpoint est public. Un middleware dédié garantirait l'application systématique.

### SEC-12 · Sessions non chiffrées
**Gravité** : 🟡 MOYEN  
**Fichier** : `.env:19`

```
SESSION_ENCRYPT=false
SESSION_DOMAIN=null
```

Les sessions ne sont pas chiffrées. Avec `SESSION_DOMAIN=null`, le cookie de session est disponible sur tous les sous-domaines.

### SEC-13 · Mass assignment potentiel dans `register()`
**Gravité** : 🟡 MOYEN  
**Fichier** : `app/Http/Controllers/Api/AuthController.php:25`

```php
public function register(Request $request)
{
    $request->validate([...]); // validation OK
    $result = $this->authService->register($request->all()); // ← $request->all() !
```

`$request->all()` passe tous les paramètres HTTP au service. Si un attaquant envoie `role=admin` ou `compagnie_id=1`, ces valeurs arrivent dans `$data` du service. Le service lui-même fait `User::create([...])` avec des champs explicites, donc l'impact est limité ici — mais le pattern est dangereux.

### SEC-14 · Timing attack possible sur vérification OTP
**Gravité** : 🟡 MOYEN  
**Fichier** : `app/Services/V2/AuthService.php:90`

```php
// V2 AuthService — comparaison non constante :
if (!$storedOtp || $storedOtp !== $dto->otp) {
```

Vs le service V1 qui utilise correctement `hash_equals` :
```php
if (!$storedOtp || !hash_equals($storedOtp, $dto->otp)) {
```

La comparaison `!==` est vulnérable aux timing attacks. Utiliser `hash_equals()` partout.

---

## Matrice de risque résumée

| ID | Vulnérabilité | Gravité | CVSS approx. |
|----|--------------|---------|--------------|
| SEC-01 | Credentials en Git | Critique | 9.8 |
| SEC-02 | Middleware Role brisé | Critique | 9.1 |
| SEC-03 | Route paiement sans auth | Critique | 8.6 |
| SEC-04 | Route admin publique | Critique | 8.2 |
| SEC-05 | Debug mode actif | Élevé | 7.5 |
| SEC-06 | Routes de test publiques | Élevé | 6.5 |
| SEC-07 | Secret réutilisé | Élevé | 6.0 |
| SEC-08 | OTP non envoyé | Élevé | 5.8 |
| SEC-09 | Callback paiement vide | Élevé | 7.0 |
| SEC-10 | Paiement hardcodé | Élevé | 6.8 |
| SEC-11 | Auth dans contrôleur | Moyen | 5.0 |
| SEC-12 | Session non chiffrée | Moyen | 4.3 |
| SEC-13 | Mass assignment risqué | Moyen | 4.0 |
| SEC-14 | Timing attack OTP | Moyen | 3.7 |
