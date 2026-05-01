# 05 — TECHNICAL DEBT

---

## Inventaire priorisé

### DETTE-01 · Credentials en Git (historique)
**Gravité** : Critique | **Effort de correction** : 2h | **Impact long terme** : Irréversible si non corrigé

Le `.env` commité dans git expose tous les secrets dans l'historique. Même après suppression du fichier, les commits précédents contiennent les credentials. Les secrets doivent être révoqués IMMÉDIATEMENT.

**Actions** :
1. Révoquer tous les tokens (LigdiCash, Twilio, reCAPTCHA)
2. `git rm --cached .env`
3. `git filter-branch` ou BFG Repo Cleaner pour purger l'historique
4. Ajouter `.env` dans `.gitignore` (déjà présent mais fichier déjà tracké)
5. Implémenter un système de secrets (Laravel Vault, AWS Secrets Manager, ou fichiers `.env` sur le serveur uniquement)

---

### DETTE-02 · Triple AuthService
**Gravité** : Critique | **Effort** : 4h | **Impact long terme** : Bugs divergents, maintenance x3

Trois AuthService avec une logique quasi-identique divergeront au fil du temps. Des bugs corrigés dans l'un ne seront pas corrigés dans les autres.

**Actions** :
1. Conserver uniquement `App\Services\Auth\AuthService` (le plus propre, utilise DTOs + hash_equals)
2. Supprimer `App\Services\AuthService` et `App\Services\V2\AuthService`
3. Corriger les injection dans les contrôleurs concernés

---

### DETTE-03 · Implémentations de paiement fictives
**Gravité** : Critique | **Effort** : Selon chaque provider | **Impact long terme** : Aucun paiement réel ne fonctionne

Tous les gateways de paiement retournent des valeurs hardcodées ou des TODO :
- `OrangeMoneyPaymentGateway::processPayment()` → vide (retourne `null` implicitement)
- `OrangePayementHelper::payement()` → accepte uniquement `70707070` / `123456`
- `CorisMoneyPaymentGateway`, `MoovMoneyPaymentGateway`, `WavePaymentGateway` → à implémenter
- `Payement::verificationPayementStatutByPayementApi()` → retourne toujours `Complete`

**L'application ne peut pas traiter de vrais paiements en production.**

---

### DETTE-04 · Zéro test métier
**Gravité** : Élevée | **Effort** : 2-3 semaines | **Impact long terme** : Régression à chaque PR

Les 18 tests existants sont des copies des tests Jetstream (équipes, tokens API). Il n'existe aucun test pour :
- Création de ticket
- Processus de paiement
- Validation de ticket
- Transfert de ticket
- Attribution de siège
- Machine à états

**Les bugs critiques identifiés dans cet audit n'auraient pas existé si des tests avaient été écrits.**

---

### DETTE-05 · Deux architectures API coexistantes sans plan de migration
**Gravité** : Élevée | **Effort** : 1-2 semaines | **Impact long terme** : Divergence croissante

L'API V1 et V2 exposent des endpoints similaires avec des comportements différents. Aucune stratégie de dépréciation n'est visible. V1 restera indéfiniment en production.

---

### DETTE-06 · Logique métier dans les Helpers statiques
**Gravité** : Élevée | **Effort** : 1 semaine | **Impact long terme** : Testabilité nulle

`TicketHelpers`, `TicketValidation`, `QueryHelpers` mélangent statique, accès DB, et événements. Impossible à tester unitairement, impossible à mocker.

---

### DETTE-07 · `preline` committé à la racine
**Gravité** : Faible | **Effort** : 5min | **Impact long terme** : Pollution du repo

Un fichier `preline` (402KB) est à la racine du projet. Ce n'est ni un fichier source PHP, ni une dépendance gérée — vraisemblablement un artefact de build commité par erreur.

---

### DETTE-08 · Migrations incohérentes (dates dans le futur)
**Gravité** : Moyenne | **Effort** : 10min | **Impact long terme** : Confusion pour les nouveaux devs

Plusieurs migrations ont des dates en `2026-03-15`, une date dans le futur (audit en `2026-04-30`). Cela indique que les dates de migration ne correspondent pas aux dates réelles de création.

---

### DETTE-09 · `SESSION_ENCRYPT=false`
**Gravité** : Moyenne | **Effort** : 1 ligne | **Impact long terme** : Données de session lisibles

Les sessions stockées en base ne sont pas chiffrées. Mettre `SESSION_ENCRYPT=true`.

---

### DETTE-10 · Absence de rate limiting sur les endpoints sensibles
**Gravité** : Moyenne | **Effort** : 2h | **Impact long terme** : Brute-force, spam

Aucun rate limit visible sur :
- `/api/v2/auth/login` — brute-force de mots de passe
- `/api/v2/auth/send-otp` — spam SMS/email
- `/api/v2/auth/forgot-password` — spam email

Laravel Sanctum fournit un middleware `throttle:api` à activer.

---

### DETTE-11 · Absence de soft deletes sur les entités critiques
**Gravité** : Moyenne | **Effort** : 1 jour | **Impact long terme** : Données perdues irrecouvrables

Les tickets, paiements, et compagnies n'utilisent pas `SoftDeletes`. Une suppression accidentelle est irréversible.

---

## Estimation globale de la dette

| Catégorie | Effort estimé |
|-----------|---------------|
| Sécurité (P0) | 1-2 jours |
| Bugs actifs | 1 jour |
| Tests | 3 semaines |
| Refactoring architecture | 2 semaines |
| Performance | 1 semaine |
| **Total minimal pour aller en prod** | **~1 mois de travail** |
