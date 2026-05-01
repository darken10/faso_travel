# 00 — GLOBAL AUDIT SUMMARY
> Audit réalisé le 2026-04-30 · Laravel 11 · PHP 8.2 · Filament 3 · Sanctum 4

---

## Résumé exécutif

**LIPTRA / FasoTravel** est une application de réservation de tickets de bus (Burkina Faso) composée d'une API REST (v1 + v2), d'un backoffice Filament, et d'une interface web Blade. Le projet est en développement actif mais présente des problèmes **critiques** qui rendent le déploiement en production **dangereux en l'état**.

---

## Scores globaux

| Dimension          | Score | Verdict                            |
|--------------------|-------|------------------------------------|
| **Qualité code**   | 3/10  | Code dupliqué, bugs actifs, TODOs  |
| **Sécurité**       | 2/10  | Credentials exposés, middleware brisé, routes publiques dangereuses |
| **Architecture**   | 4/10  | Intentions correctes, cohérence absente |
| **Maintenabilité** | 3/10  | 0 test métier, 3 AuthService, 2 Factory |

**Niveau de risque global : 🔴 CRITIQUE**

---

## Priorités absolues (bloquantes avant toute mise en production)

### P0 — Sécurité immédiate

| # | Problème | Impact |
|---|----------|--------|
| 1 | **`.env` tracké dans Git** — credentials DB, Twilio, LigdiCash en clair dans l'historique | Compromission totale |
| 2 | **Middleware `Role` brisé** — bug d'opérateurs : `! $request->user()->role == $role` ne bloque jamais personne | Bypass d'autorisation universel |
| 3 | **Route `/process-payment/{provider}` sans authentification** | Paiements déclenchables sans être connecté |
| 4 | **Routes `/test` et `/create-all-voyages-instances` publiques sans auth** | Exposition de données, actions non protégées |
| 5 | **`APP_DEBUG=true` + `DEBUGBAR_ENABLED=true` en "production"** | Stack traces complètes exposées aux attaquants |
| 6 | **`SECRET_ACCESS_TOKEN_LOGIN_USER` = valeur identique à `APP_KEY`** | Réutilisation de secret critique |

### P0 — Bugs bloquants

| # | Problème | Impact |
|---|----------|--------|
| 7 | **`dd($voyage_instance)` dans `createTicketWithVoyageInstance()`** | Crash HTTP 500 garanti sur ce endpoint |
| 8 | **OTP incohérent** : `sendOtp()` stocke sous clé `otp_{user->id}`, `verifyOtp()` lit sous `otp_{phone_or_email}` | Vérification OTP impossible, fonctionnalité 100% cassée |
| 9 | **`TicketValidation::active()` met le ticket en `Pause`** au lieu de l'activer | Activation de ticket impossible |
| 10 | **`PaymentGatewayFactory2` référence des classes inexistantes** (`OrangePaymentGateway`, `MoovPaymentGateway`, etc.) | Fatal error à l'instanciation |
| 11 | **`callbackFunction()` vide** — les webhooks de paiement sont silencieusement ignorés | Paiements confirmés par la banque mais non crédités |

### P1 — Problèmes graves

- Triple duplication de `AuthService` (`App\Services`, `App\Services\Auth`, `App\Services\V2`)
- Deux `PaymentGatewayFactory` coexistent avec des namespaces incohérents
- `myTickets()` — requête Eloquent incorrecte (`orWhere` non groupé) fuite potentielle de tickets d'autres users
- Zéro test métier (les 18 tests existants sont des boilerplates Jetstream)
- Race condition sur l'attribution de numéro de chaise
- Paiement Orange Money entièrement simulé (hardcoded `70707070` / `123456`)

---

## Vue d'ensemble de l'application

```
FasoTravel
├── API REST v1 (/api/)          — Auth via UserController (non AuthController)
├── API REST v2 (/api/v2/)       — Architecture propre, DTOs, Services
├── Web Blade (/)                — Sessions Laravel, paiements Orange Money
├── Backoffice Filament (/admin, /compagnie) — Panel Filament 3
└── Base de données MySQL        — 55 migrations, entités: Ticket, Voyage, Compagnie, Paiement
```

**Stack technique** : Laravel 11, PHP 8.2, MySQL, Sanctum, Filament 3, Livewire 3, DomPDF, endroid/qr-code, Twilio, LigdiCash
