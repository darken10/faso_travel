# Plan de travail — Habilitation LIPTRA (RBAC) & fonctionnalités métier

> **Destinataire : agent de codage (Codex).** Ce document est un plan d'exécution, pas une
> note d'intention. Chaque tâche est autonome, vérifiable et sort une branche.
>
> Référence fonctionnelle : [`ROLES_ET_PERMISSIONS.md`](./ROLES_ET_PERMISSIONS.md).
> Ce plan n'en répète pas les justifications : il en applique les décisions.
>
> Révision : 2026-10-03 · Dépôt : `faso_travel` (Laravel 11 · PHP 8.4 · Livewire 3)

---

## 0. À lire avant la première ligne de code

### 0.1 Conventions du dépôt — non négociables

| Sujet | Règle |
|---|---|
| Structure | Laravel 11 : **pas** de `app/Http/Kernel.php`. Les alias de middleware vont dans `bootstrap/app.php`. |
| Génération | Toujours `php artisan make:… --no-interaction`. Jamais de fichier créé à la main quand une commande existe. |
| Tests | **PHPUnit en classes**, pas Pest (aucun binaire `vendor/bin/pest`). `extends Tests\TestCase`, `use RefreshDatabase`. |
| Base de test | **MySQL**, base `faso_travel_test` (voir `phpunit.xml`). SQLite est inutilisable : des migrations font `ALTER … MODIFY ENUM`. Créer la base avant le premier run. |
| Dépendances | **Aucun ajout** à `composer.json` ni `package.json`. Tout ce qui suit est faisable avec l'existant. |
| Validation | Form Request par écriture. Jamais de `$request->validate()` dans un contrôleur. Les composants Livewire gardent `rules()`. |
| Typage | Types de retour explicites partout. Promotion de propriétés dans `__construct()`. `declare(strict_types=1)` non utilisé dans ce dépôt : ne pas l'introduire. |
| Commentaires | PHPDoc plutôt que commentaires en ligne. Les commentaires existants expliquent **pourquoi**, en français — garder ce registre. |
| Enums | Clés en TitleCase, valeurs en snake_case pour tout ce qui est nouveau. |
| Config | Jamais `env()` hors de `config/`. |
| Style | `vendor/bin/pint --dirty` avant chaque commit. |
| Branches | Une branche par tâche : `feat/rbac-T0X-<slug>` ou `fix/rbac-T0X-<slug>`. |
| Commits | Conventional Commits **en français sans accents dans le sujet** (convention du dépôt : `feat(compagnie): boite de messages pour repondre aux clients`). **Aucune ligne de co-signature.** |
| Push | **Ne jamais pousser.** Les branches restent locales ; le propriétaire du dépôt fusionne et pousse. |

### 0.2 Interdits absolus

1. **Ne pas activer de blocage d'autorisation avant la tâche T09.** Les tâches T02 à T08 sont additives : une fois livrées, un utilisateur existant doit garder exactement les accès qu'il avait la veille. Toute régression d'accès avant T09 est un échec de la tâche.
2. **Ne pas supprimer de colonne ni de table existante.** Les migrations ajoutent, renomment via nouvelle colonne + recopie, et laissent l'ancienne en place jusqu'à décision explicite.
3. **Ne pas modifier `users.role`** (enum `UserRole`) dans ce plan. Sa refonte est une décision ouverte (§ 10 du document de référence) et n'est le sujet d'aucune tâche ici.
4. **Ne pas élargir un périmètre de tâche.** Si une tâche révèle un défaut hors de son champ, l'écrire dans `## Observations` du rapport de fin et s'arrêter là.
5. **Ne pas désactiver un test existant** pour faire passer une suite. 70 tests sont en place, dont `tests/Feature/Security/*` : ils sont la baseline.

### 0.3 Préparation de l'environnement (une seule fois)

```bash
cd ~/Lab/liptra.net/faso_travel
mysql -e "CREATE DATABASE IF NOT EXISTS faso_travel_test CHARACTER SET utf8mb4"
php artisan test            # baseline : noter le nombre de tests verts AVANT de commencer
vendor/bin/pint --test      # état du style avant modification
```

Le résultat de ce `php artisan test` est la **baseline**. Toute tâche se termine avec au
moins autant de tests verts, plus les siens.

### 0.4 Définition de « terminé » — s'applique à chaque tâche

- [ ] `php artisan test` : tous les tests verts, y compris les nouveaux de la tâche
- [ ] `php artisan migrate:fresh --env=testing` passe, puis `php artisan migrate:rollback --env=testing` sur les migrations de la tâche
- [ ] `vendor/bin/pint --dirty` ne rapporte plus rien
- [ ] Aucun fichier `composer.json` / `package.json` modifié
- [ ] Un commit unique et atomique par tâche, sur sa branche
- [ ] Un rapport de fin au format du § 0.6

### 0.5 Gabarit de prompt pour lancer une tâche

```
Dépôt : ~/Lab/liptra.net/faso_travel

Lis PLAN_TRAVAIL_RBAC.md en entier, puis exécute UNIQUEMENT la tâche <ID>.
Respecte le § 0 (conventions, interdits, définition de terminé).
Ne touche à aucun fichier hors de la liste « Fichiers » de la tâche, sauf si la tâche
l'autorise explicitement.
Ne pousse rien. Termine par le rapport du § 0.6.
```

### 0.6 Gabarit de rapport de fin

```markdown
## Tâche <ID> — <titre>
Branche : <nom>          Commit : <sha court>
Tests : <N> verts (baseline <M>)   Nouveaux tests : <liste des fichiers>
Migrations : <liste>     Rollback vérifié : oui/non

### Fait
- …
### Écarts par rapport au plan
- … (ou « aucun »)
### Observations hors périmètre
- … (ou « aucune »)
```

---

## 1. Séquence et dépendances

```
T01 ──────────────────────────────────────────── correctifs bloquants (indépendant)

T02 ─→ T03 ─→ T04 ─→ T05 ─→ T08 ─→ T09 ─→ T11    moteur d'habilitation
        │       │                    ↑
        │       └─→ T06 ─────────────┤            portée « gare »
        └─────────→ T07 ─────────────┘            journal d'audit
                              T10 ───┘            refonte des policies

T12  T13  T14  T15                               fonctionnalités P1 (après T06 pour T15)
```

**T01 est à faire immédiatement et séparément** : il corrige trois défauts déjà en
production, dont une élévation de privilèges.

T12, T13 et T14 ne dépendent d'aucune tâche RBAC et peuvent être livrés en parallèle, à
droits inchangés. T15 a besoin de T06.

| Tâche | Titre | Dépend de | Taille |
|---|---|---|---|
| T01 | Correctifs bloquants d'autorisation | — | S |
| T02 | Schéma RBAC | — | M |
| T03 | Catalogue de permissions et rôles gabarits | T02 | M |
| T04 | Moteur de résolution et gates dynamiques | T03 | L |
| T05 | Migration des rôles existants | T04 | M |
| T06 | Portée « gare » | T04 | M |
| T07 | Journal d'audit | T02 | M |
| T08 | Mode observation | T04 | S |
| T09 | Blocage effectif | T05 T06 T08 | L |
| T10 | Refonte des policies | T04 | M |
| T11 | Administration des rôles et cumuls interdits | T09 | M |
| T12 | Manifeste de départ imprimable | — | S |
| T13 | Registre des départs réels | — | M |
| T14 | Versements en banque et validation des écarts | — | M |
| T15 | Tableau de bord de gare | T06 | M |

S ≈ une demi-journée · M ≈ une journée · L ≈ deux jours

---

## T01 — Correctifs bloquants d'autorisation

**Branche** `fix/rbac-T01-correctifs-bloquants`

### Objectif
Supprimer trois défauts actifs : une référence à une constante inexistante qui lève une
erreur fatale, un attribut fantôme, et une élévation de privilèges dans l'écran d'équipe.

### Fichiers
- `app/Policies/CompagniePolicy.php`
- `app/Livewire/Compagnie/Compagnie/UserManager.php`
- `tests/Feature/Security/CompagnieRecordAccessTest.php` (ajouts)
- nouveau : `tests/Feature/Security/UserManagerRoleEscalationTest.php`

### Travail

1. **`CompanyRole::Directeur` n'existe pas.** `CompagniePolicy.php:45` et `:57` le
   référencent ; toute évaluation de `manageFinance()` ou `isOwner()` lève
   `Error: Undefined constant`. `$user->company_role` n'existe pas davantage — ni colonne,
   ni accesseur.
   Remplacer les deux méthodes par un test sur l'existant, sans inventer de champ :
   ```php
   private function isOwner(User $user, Compagnie $compagnie): bool
   {
       return $user->compagnie_id === $compagnie->id
           && ($user->role === UserRole::CompagnieBosse
               || $user->hasAnyRole([CompanyRole::Admin->value]));
   }

   public function manageFinance(User $user, Compagnie $compagnie): bool
   {
       return ($user->compagnie_id === $compagnie->id
               && ($user->role === UserRole::CompagnieBosse
                   || $user->hasAnyRole([
                       CompanyRole::Admin->value,
                       CompanyRole::Comptabilite->value,
                   ])))
           || $this->isAdmin($user);
   }
   ```
   C'est exactement la logique déjà retenue par le gate `manage-boarding-conflicts`
   (`AppServiceProvider.php:70`) : s'y aligner plutôt que d'inventer une troisième règle.
   Ne **pas** ajouter de case `Directeur` à l'enum : le rôle cible est `compagnie_dg`,
   traité en T03.

2. **Élévation de privilèges dans `UserManager`.** `UserManager.php:174` fait
   `Role::orderBy('label')->get()` : la liste déroulante expose `admin` et `root`. Un
   administrateur compagnie peut donc se fabriquer un compte plateforme.
   Restreindre aux seuls rôles compagnie :
   ```php
   $roles = Role::whereIn('name', CompanyRole::values())->orderBy('label')->get();
   ```
   Et **valider côté serveur**, car `$selectedRoles` est une propriété publique Livewire
   donc pilotable depuis le navigateur. Dans `rules()` :
   ```php
   'selectedRoles'   => 'required|array|min:1',
   'selectedRoles.*' => [
       'integer',
       Rule::exists('roles', 'id')->whereIn('name', CompanyRole::values()),
   ],
   ```

### Tests exigés
- `test_company_admin_cannot_assign_platform_roles` — un utilisateur compagnie qui soumet
  l'`id` du rôle `root` dans `selectedRoles` reçoit une erreur de validation et le pivot
  `role_user` reste vide.
- `test_role_list_excludes_platform_roles` — le rendu du composant ne contient ni `admin`
  ni `root`.
- `test_manage_finance_ne_leve_plus_d_erreur` — `Gate::forUser($user)->allows('manageFinance', $compagnie)`
  retourne un booléen pour un `CompagnieBosse`, un `comptabilite` et un `agent`, sans exception.

### Critères d'acceptation
- Aucune occurrence de `company_role` ni de `CompanyRole::Directeur` dans `app/`
  (`grep -rn "company_role\|CompanyRole::Directeur" app/` ne retourne rien).
- Les trois tests ci-dessus passent.
- Aucun changement de comportement pour les comptes plateforme.

---

## T02 — Schéma RBAC

**Branche** `feat/rbac-T02-schema`

### Objectif
Poser les tables du moteur. **Aucun contrôle n'est activé par cette tâche** : elle est
purement additive.

### Fichiers
- 5 nouvelles migrations dans `database/migrations/`
- `app/Models/Role.php`, nouveau `app/Models/Permission.php`
- nouveau `tests/Feature/Rbac/SchemaRbacTest.php`

### Migrations à créer

**1. `extend_roles_table_for_rbac`**

Colonnes ajoutées à `roles` : `compagnie_id` (unsignedBigInteger, nullable, index),
`scope` (enum `platform`/`compagnie`, défaut `compagnie`), `rang` (smallInteger, défaut 0),
`is_system` (boolean, défaut false), `description` (text, nullable).

Backfill dans la migration : `is_system = true` pour les lignes déjà présentes
(`user`, `admin`, `root` + les six `CompanyRole`), et `scope = 'platform'` pour
`user`, `admin`, `root`.

**Piège MySQL à traiter explicitement.** L'index unique actuel est sur `roles.name` seul ;
il faut le remplacer par `(name, compagnie_id)` pour permettre des rôles propres à une
compagnie. Mais **MySQL considère deux `NULL` comme distincts** dans un index unique : un
`UNIQUE(name, compagnie_id)` n'empêcherait donc pas deux rôles système homonymes. Utiliser
une colonne générée :

```php
DB::statement("ALTER TABLE roles
    ADD COLUMN compagnie_key BIGINT UNSIGNED
    AS (COALESCE(compagnie_id, 0)) STORED");
DB::statement("ALTER TABLE roles DROP INDEX roles_name_unique");
DB::statement("ALTER TABLE roles ADD UNIQUE KEY roles_name_compagnie_unique (name, compagnie_key)");
```

Le `down()` rétablit l'index d'origine et supprime la colonne générée.

**2. `create_permissions_table`**

`id` · `name` (string 120, unique) · `domaine` (string 40, index) · `label` (string 160) ·
`description` (text nullable) · `scope` (enum `platform`/`compagnie`/`client`) ·
`is_sensitive` (boolean, défaut false) · timestamps.

**3. `create_permission_role_table`**

`role_id` + `permission_id` (clé primaire composite, FK `cascadeOnDelete`) ·
`portee` (enum `all`/`compagnie`/`gare`/`own`, défaut `compagnie`).

**4. `create_permission_user_table`**

`user_id` + `permission_id` (clé primaire composite, FK `cascadeOnDelete`) ·
`accordee` (boolean, défaut true — `false` signifie retrait explicite) ·
`expire_at` (timestamp nullable) · timestamps.

**5. `add_compagnie_id_to_role_user_table`**

`compagnie_id` nullable sur `role_user`, et l'unique devient `(user_id, role_id, compagnie_id)`
— même précaution de colonne générée que pour `roles`. Backfill depuis
`users.compagnie_id` pour les lignes dont le rôle porte `scope = 'compagnie'`.

### Modèles
- `Permission` : `$fillable`, `casts()` (`is_sensitive` → bool), relation `roles()`.
- `Role` : ajouter `$fillable` étendu, `casts()`, relations `permissions(): BelongsToMany`
  (avec `withPivot('portee')`) et `compagnie(): BelongsTo`. Scopes `scopeSystem()`,
  `scopeOfCompagnie()`.

### Tests exigés
- `test_les_tables_rbac_existent_avec_leurs_colonnes` via `Schema::hasColumns`.
- `test_deux_roles_systeme_homonymes_sont_refuses` — la seconde insertion lève
  `QueryException`. C'est le test qui prouve que la colonne générée fait son travail.
- `test_un_role_compagnie_peut_porter_le_meme_nom_dans_deux_compagnies`.
- `test_rollback_restaure_le_schema_precedent`.

### Critères d'acceptation
- `migrate:fresh` puis `migrate:rollback` passent sur `faso_travel_test`.
- Les 70 tests de la baseline restent verts : aucun comportement modifié.

---

## T03 — Catalogue de permissions et rôles gabarits

**Branche** `feat/rbac-T03-catalogue`

### Objectif
Alimenter `permissions`, les 19 rôles gabarits et leurs associations, depuis une source de
vérité unique en PHP — pas en SQL, pas dans un fichier de migration.

### Fichiers
- nouveau `app/Enums/PermissionDomaine.php`
- nouveau `app/Rbac/PermissionCatalogue.php` (classe, pas enum : ~180 entrées)
- nouveau `app/Rbac/RoleGabarits.php`
- nouveau `database/seeders/PermissionSeeder.php`
- `database/seeders/RoleSeeder.php` (réécriture)
- `database/seeders/DatabaseSeeder.php` (ajout de `PermissionSeeder` **avant** `RoleSeeder`)
- nouveau `tests/Feature/Rbac/CatalogueTest.php`

### Travail

1. **`PermissionCatalogue::toutes(): array`** retourne la liste complète issue du § 5 du
   document de référence, au format :
   ```php
   ['name' => 'guichet.ticket.sell', 'domaine' => 'guichet', 'label' => 'Vendre un ticket',
    'scope' => 'compagnie', 'is_sensitive' => false],
   ```
   `is_sensitive => true` pour exactement les 14 permissions listées au § 7.3 du document
   de référence (journalisation obligatoire). Ne pas en ajouter d'autres : la liste est
   fermée et T07 s'appuie dessus.

   Reprendre **les permissions du § 5 uniquement** (périmètre existant). Celles des
   fiches du § 6 seront ajoutées par les tâches fonctionnelles correspondantes.

2. **`RoleGabarits::tous(): array`** décrit les 19 rôles du § 4 avec, pour chacun, `name`,
   `label`, `scope`, `rang`, `description` et la liste `permissions` sous forme
   `['nom.de.permission' => 'portee']`. La traduction des symboles des matrices est :

   | Symbole | `portee` |
   |---|---|
   | `●` sur un rôle plateforme | `all` |
   | `●` sur un rôle compagnie | `compagnie` |
   | `◐` périmètre gare | `gare` |
   | `◐` propre enregistrement | `own` |
   | `○` | `compagnie`, sur la permission `.view` correspondante uniquement |
   | `—` | absente de la liste |

   Rangs proposés (ils servent à la règle « on n'attribue qu'un rang inférieur au sien »,
   appliquée en T11) : `root` 100 · `platform_admin` 90 · `platform_support` 50 ·
   `compagnie_dg` 80 · `compagnie_admin` 70 · `exploitation` 60 · `chef_caisse` 60 ·
   `comptable` 60 · `chef_parc` 50 · `rh` 50 · `chef_gare` 50 · `communication` 40 ·
   `service_client` 40 · `auditeur` 40 · `guichetier` 20 · `agent_embarquement` 20 ·
   `bagagiste` 20 · `chauffeur` 10 · `client` 0.

3. **Seeders idempotents.** `updateOrCreate` sur `name`, et `syncWithoutDetaching` pour les
   pivots — un second `db:seed` ne doit rien casser ni rien dupliquer.
   `RoleSeeder` **conserve les six rôles `CompanyRole` existants** en plus des nouveaux :
   ils portent encore les affectations en base et ne seront retirés qu'après T05.

### Tests exigés
- `test_le_catalogue_ne_contient_aucun_doublon` et `test_chaque_permission_respecte_la_nomenclature`
  (`/^[a-z]+\.[a-zA-Z]+\.[a-zA-Z.]+$/`).
- `test_chaque_permission_de_role_existe_dans_le_catalogue` — aucune référence orpheline.
- `test_le_seeder_est_idempotent` — deux exécutions, mêmes comptages.
- `test_dix_neuf_roles_gabarits_sont_crees` avec `is_system = true`.
- `test_les_quatorze_permissions_sensibles_sont_marquees`.
- `test_auditeur_n_a_aucune_permission_d_ecriture` — aucune permission du rôle `auditeur`
  ne finit par `create`, `update`, `delete`, `approve`, `validate`, `resolve`, `send`,
  `assign`, `close`, `adjust`. C'est la garantie « lecture scellée » du § 6.8.

### Critères d'acceptation
- `php artisan db:seed --class=PermissionSeeder` puis `RoleSeeder` sur une base fraîche
  produisent le catalogue complet, et sont rejouables.
- Toujours aucun contrôle actif.

---

## T04 — Moteur de résolution et gates dynamiques

**Branche** `feat/rbac-T04-moteur`

### Objectif
Rendre `$user->hasPermission()` et `Gate::allows('<permission>')` fonctionnels, avec les
quatre portées. Les gates sont **enregistrés mais pas encore appliqués** : rien n'appelle
`authorize()` à ce stade.

### Fichiers
- nouveau `app/Traits/HasPermissions.php`
- nouveau `app/Rbac/PermissionResolver.php`
- nouveau `app/Rbac/PorteeVerifier.php`
- `app/Models/User.php` (ajout du trait et des relations)
- `app/Providers/AppServiceProvider.php` (enregistrement dynamique)
- nouveau `config/rbac.php`
- nouveau `tests/Feature/Rbac/PermissionResolutionTest.php`
- nouveau `tests/Unit/Rbac/PorteeVerifierTest.php`

### Contrat public

```php
public function hasPermission(string $permission, mixed $sujet = null): bool;
public function permissionsEffectives(): array;   // ['nom' => 'portee']
public function porteePour(string $permission): ?string;
public function isRoot(): bool;
public function isPlatformUser(): bool;
public function invaliderCachePermissions(): void;
```

### Ordre de résolution — à respecter exactement

1. `isRoot()` → `true` sans autre test.
2. `permission_user` avec `accordee = false` et (`expire_at` nul ou futur) → **`false`**.
   *Le retrait explicite gagne sur tout le reste* : c'est ce qui permet de suspendre un
   droit sur un compte sans démonter son rôle.
3. `permission_user` avec `accordee = true` et non expiré → `true`, portée `all`.
4. Union des permissions des rôles de l'utilisateur. Si plusieurs rôles portent la même
   permission avec des portées différentes, **la plus large gagne**
   (`all` > `compagnie` > `gare` > `own`).
5. Sinon `false`.

### Vérification de portée — `PorteeVerifier`

| `portee` | Règle | Sujet `null` |
|---|---|---|
| `all` | toujours vrai | vrai |
| `compagnie` | `$sujet->compagnie_id === $user->compagnie_id` | vrai |
| `gare` | la gare du sujet appartient aux gares de l'utilisateur (T06) | vrai |
| `own` | `$sujet->user_id === $user->id` | vrai |

Un sujet `null` passe : il s'agit d'un contrôle d'accès à un **écran** (`viewAny`), pas à
un enregistrement. Le filtrage des lignes est l'affaire des scopes de modèle, pas du gate.
Avant T06, la portée `gare` **se comporte comme `compagnie`** et un commentaire le dit
explicitement dans le code.

### Cache

Les stores `file` et `database` ne gèrent pas les tags. Utiliser un compteur de version
par utilisateur, qui fonctionne avec n'importe quel driver :

```php
$version = Cache::get("user:{$this->id}:perm_version", 1);
return Cache::remember(
    "user:{$this->id}:permissions:v{$version}",
    config('rbac.cache_ttl'),
    fn () => app(PermissionResolver::class)->resolve($this),
);
```

`invaliderCachePermissions()` fait `Cache::increment("user:{$id}:perm_version")`. L'appeler
depuis des observateurs sur `role_user`, `permission_role` et `permission_user`.

### `config/rbac.php`

```php
return [
    'enforce'   => env('RBAC_ENFORCE', false),   // T08/T09
    'cache_ttl' => env('RBAC_CACHE_TTL', 600),
    'log_channel' => env('RBAC_LOG_CHANNEL', 'rbac'),
];
```

Documenter les trois variables dans `.env.example`, avec `RBAC_ENFORCE=false`.

### Enregistrement des gates

Dans `AppServiceProvider::registerGates()`, après les gates existants :

```php
Gate::before(fn (User $user, string $ability) => $user->isRoot() ? true : null);

foreach (Permission::pluck('name') as $name) {
    Gate::define($name, fn (User $user, mixed $sujet = null) => $user->hasPermission($name, $sujet));
}
```

**Attention** : cette boucle interroge la base à chaque requête, et au `boot` d'une
commande `migrate` sur une base vide elle échoue. Encadrer par un `Schema::hasTable('permissions')`
et mettre la liste en cache. Conserver intacts les gates `compagnie-settings.*` et
`manage-boarding-conflicts` : T09 et T10 s'en occuperont, pas celle-ci.

### Tests exigés
- Les cinq étapes de l'ordre de résolution, une par test, dont
  `test_un_retrait_explicite_prime_sur_le_role`.
- `test_la_portee_la_plus_large_gagne_entre_deux_roles`.
- `test_root_obtient_tout_sans_permission_en_base`.
- `test_une_permission_expiree_est_ignoree`.
- `test_le_cache_est_invalide_quand_un_role_change`.
- `test_portee_compagnie_refuse_un_sujet_d_une_autre_compagnie`.
- `test_portee_own_refuse_l_enregistrement_d_un_autre`.

### Critères d'acceptation
- Baseline des 70 tests toujours verte : **aucun écran ne change de comportement**.
- `Gate::allows('guichet.ticket.sell')` renvoie un booléen cohérent pour un compte à qui on
  a attaché le rôle `guichetier` à la main.

---

## T05 — Migration des rôles existants

**Branche** `feat/rbac-T05-migration-donnees`

### Objectif
Faire porter aux comptes réels les nouveaux rôles, sans perdre les anciens, via une
commande rejouable et vérifiable à blanc.

### Fichiers
- nouveau `app/Console/Commands/RbacMigrateRoles.php` (signature `rbac:migrate-roles {--dry-run} {--compagnie=}`)
- nouveau `tests/Feature/Rbac/MigrateRolesCommandTest.php`

### Correspondance à appliquer

| Source | Cible |
|---|---|
| `users.role = Super User` | rôle `root` |
| `users.role = Admin` | rôle `platform_admin` |
| `users.role = User` et `compagnie_id` nul | rôle `client` |
| `users.role = Companie Bosse` | rôle `compagnie_dg` |
| pivot `company_admin` | `compagnie_admin` |
| pivot `agent` | `agent_embarquement` |
| pivot `caisse` | `guichetier` |
| pivot `comptabilite` | `comptable` |
| pivot `rh` | `rh` |
| pivot `bagagiste` | `bagagiste` |
| `compagnie_id` non nul **et aucun rôle pivot** | `guichetier` + **à signaler dans le rapport** |

Le dernier cas est le plus important : ce sont les comptes qui accèdent aujourd'hui à tout
le panneau sans aucun rôle. Les lister nominativement en fin de commande pour arbitrage
humain. Ne rien deviner d'autre.

### Exigences
- `--dry-run` **par défaut sur affichage** : la commande imprime un tableau
  `utilisateur → rôles ajoutés` et ne touche à la base que sans l'option.
- Idempotente : `syncWithoutDetaching`, deux exécutions = même état.
- **N'enlève aucun ancien rôle pivot.** Le nettoyage est une tâche ultérieure, après
  validation en production.
- Transaction par lot de 500 comptes, et sortie non nulle en cas d'échec.

### Tests exigés
- `test_dry_run_ne_modifie_rien`.
- `test_chaque_correspondance_est_appliquee` (un cas par ligne du tableau).
- `test_un_compte_de_compagnie_sans_role_est_signale`.
- `test_la_commande_est_idempotente`.
- `test_les_anciens_roles_sont_conserves`.

---

## T06 — Portée « gare »

**Branche** `feat/rbac-T06-portee-gare`

### Objectif
Donner un second axe de cloisonnement aux rôles de terrain. Sans cette tâche, la moitié des
`◐` des matrices sont inapplicables.

### Fichiers
- nouvelle migration `create_gare_user_table`
- `app/Models/User.php` (relation `gares()`, `garePrincipale()`)
- `app/Models/Compagnie/Gare.php` (relation `agents()`)
- nouveau `app/Traits/ScopedToGare.php` — à écrire **sur le modèle de `app/Traits/ScopedToCompagnie.php` existant**, même registre de commentaire
- `app/Rbac/PorteeVerifier.php` (activation de la portée `gare`)
- `app/Livewire/Compagnie/Compagnie/UserManager.php` (affectation des gares)
- nouveau `tests/Feature/Rbac/PorteeGareTest.php`

### Schéma
`gare_user` : `id` · `user_id` (FK cascade) · `gare_id` (FK cascade) ·
`is_principale` (boolean, défaut false) · timestamps · unique `(user_id, gare_id)`.

### Résolution de la gare d'un sujet
`PorteeVerifier` doit savoir remonter du sujet à sa gare. Les chemins ne sont pas uniformes
dans ce modèle — les écrire explicitement :

| Sujet | Chemin |
|---|---|
| `Gare` | lui-même |
| `Caisse` | `$caisse->user->gares` (la session appartient à un agent, pas à une gare) |
| `Ticket` | `$ticket->voyageInstance->voyage->depart_id` |
| `VoyageInstance` | `$instance->voyage->depart_id` |
| `Voyage` | `$voyage->depart_id` |

Un sujet dont la gare est indéterminable (relations non chargées, données incomplètes)
**retourne `false`**, pas `true` : en cas de doute, on refuse.

### Attention
Un utilisateur **sans aucune gare affectée** et porteur d'une permission en portée `gare`
ne voit rien. C'est le comportement voulu, mais il casserait la production si T09 était
livré avant que T05 ait affecté les gares. Le test ci-dessous l'ancre.

### Tests exigés
- `test_un_agent_ne_voit_que_les_tickets_de_sa_gare`.
- `test_un_agent_affecte_a_deux_gares_voit_les_deux`.
- `test_un_agent_sans_gare_ne_voit_rien_en_portee_gare`.
- `test_une_gare_d_une_autre_compagnie_ne_peut_pas_etre_affectee`.
- `test_un_sujet_dont_la_gare_est_indeterminable_est_refuse`.

---

## T07 — Journal d'audit

**Branche** `feat/rbac-T07-audit`

### Objectif
Rendre traçable nominativement chaque action sensible. Aucune action de ce dépôt ne laisse
aujourd'hui de trace : ni l'annulation d'un ticket, ni la validation d'un écart de caisse.

### Fichiers
- nouvelle migration `create_audit_logs_table`
- nouveau `app/Models/AuditLog.php`
- nouveau `app/Services/Audit/AuditLogger.php`
- nouveau `app/Traits/Auditable.php`
- nouveau `tests/Feature/Audit/AuditLoggerTest.php`

### Schéma
`audit_logs` : `id` · `user_id` (nullable, FK `nullOnDelete`) · `compagnie_id` (nullable,
index) · `action` (string 120, index) · `auditable_type` / `auditable_id` (nullable, index
composite) · `avant` (json nullable) · `apres` (json nullable) · `motif` (text nullable) ·
`ip` (string 45 nullable) · `user_agent` (string 255 nullable) · `created_at` (index).

**Pas de `updated_at`, pas de `deleted_at`** : une ligne d'audit ne se modifie pas. Le
modèle surcharge `update()` et `delete()` pour lever `LogicException`, et c'est testé.

### API
```php
public function log(string $action, ?Model $cible = null, array $avant = [], array $apres = [], ?string $motif = null): AuditLog;
```
Le service prend l'acteur dans `Auth::user()`, l'IP et l'agent dans la requête courante,
et **ne lève jamais d'exception** : un échec d'écriture d'audit ne doit pas annuler
l'opération métier. En cas d'échec, écrire sur le canal `rbac` de `config/logging.php`
(canal `daily` dédié, à ajouter).

### Couverture exigée
Les 14 actions marquées `is_sensitive` en T03. Ne pas en faire plus. Points d'appel
concrets dans cette tâche :

| Action | Où |
|---|---|
| `guichet.ticket.cancel` · `.reprint` · `.unblock` | `app/Livewire/Compagnie/Ticket/TicketManager.php`, `TicketShow.php` |
| `caisse.session.forceClose` · `caisse.ecart.validate` | `app/Livewire/Compagnie/Caisse/GestionCaisse.php`, `DetailCaisse.php` |
| `embarquement.conflit.resolve` | `app/Livewire/Compagnie/Ticket/ConflitManager.php` |
| `compagnie.parametres.updateAdvanced` | `app/Services/Compagnie/CompagnieSettingService.php` |
| `finance.*.delete` · `finance.depense.approve` | `app/Livewire/Compagnie/Finance/DepenseManager.php`, `RecetteManager.php` |
| `crm.fidelite.adjust` | `app/Services/Loyalty/LoyaltyService.php` |
| `compagnie.role.assign` | `app/Livewire/Compagnie/Compagnie/UserManager.php` |

### Tests exigés
- `test_une_ligne_d_audit_ne_peut_pas_etre_modifiee_ni_supprimee`.
- `test_l_annulation_d_un_ticket_est_journalisee_avec_avant_apres`.
- `test_un_echec_d_ecriture_d_audit_n_annule_pas_l_operation` (mock en échec).
- `test_l_acteur_l_ip_et_l_agent_sont_enregistres`.

---

## T08 — Mode observation

**Branche** `feat/rbac-T08-observation`

### Objectif
Mesurer avant de bloquer. Aucune des 32 routes du panneau n'étant filtrée aujourd'hui,
l'usage réel de chaque écran par chaque métier est inconnu : bloquer sans l'avoir mesuré
arrêterait des guichets en production.

### Fichiers
- nouveau `app/Http/Middleware/AuthorizeOrObserve.php`
- `bootstrap/app.php` (alias `can.rbac`)
- `config/logging.php` (canal `rbac`)
- nouveau `app/Console/Commands/RbacRefusalsReport.php` (`rbac:refusals --days=14`)
- nouveau `tests/Feature/Rbac/ObservationModeTest.php`

### Comportement
Le middleware prend une permission en paramètre. Si l'utilisateur l'a, il passe. Sinon :

- `config('rbac.enforce') === false` → **laisse passer** et journalise sur le canal `rbac` :
  utilisateur, rôles, permission refusée, route, méthode, horodatage.
- `config('rbac.enforce') === true` → `abort(403)`.

La commande `rbac:refusals` agrège le journal : permission × rôle × nombre, triée par
fréquence. C'est la liste de travail avant de basculer `RBAC_ENFORCE=true`.

### Tests exigés
- `test_en_mode_observation_un_refus_passe_et_est_journalise`.
- `test_en_mode_blocage_un_refus_renvoie_403`.
- `test_un_utilisateur_autorise_passe_dans_les_deux_modes`.
- `test_le_rapport_agrege_les_refus_par_permission_et_par_role`.

---

## T09 — Blocage effectif

**Branche** `feat/rbac-T09-application`

### Objectif
Appliquer les contrôles aux quatre surfaces. **Tâche la plus risquée du plan** : elle se
livre après deux semaines d'exploitation en mode observation, et elle doit être réversible
par une seule variable d'environnement.

### Fichiers
- `routes/web.php` (lignes 174-243)
- `resources/views/layouts/compagnie-panel.blade.php`
- `resources/views/layouts/` (layout admin, même traitement)
- les 38 composants de `app/Livewire/Admin/` et `app/Livewire/Compagnie/`
- `app/Http/Controllers/Api/Admin/AgentAuthController.php`
- nouveau `tests/Feature/Rbac/RouteAuthorizationTest.php`
- nouveau `tests/Feature/Rbac/AgentLoginAuthorizationTest.php`

### 1. Routes — correspondance à appliquer telle quelle

Panneau admin (`admin.{domain}`, `routes/web.php:174-184`) :

| Route | Permission |
|---|---|
| `panel.admin.dashboard` | `platform.stats.view` |
| `panel.admin.pays` | `platform.pays.manage` |
| `panel.admin.regions` | `platform.region.manage` |
| `panel.admin.villes` | `platform.ville.manage` |
| `panel.admin.compagnies` | `platform.compagnie.view` |
| `panel.admin.settings` | `platform.settings.manage` |
| `create-all-voyages-instances` | `voyage.instance.generate` |

Panneau compagnie (`compagnie.{domain}`, `routes/web.php:190-243`) :

| Route | Permission |
|---|---|
| `dashboard` | `compagnie.dashboard.view` |
| `trajets` | `voyage.trajet.view` |
| `voyages` · `voyages.show` | `voyage.voyage.view` |
| `voyages.create` | `voyage.voyage.create` |
| `voyages.edit` | `voyage.voyage.update` |
| `classes` | `voyage.classe.manage` |
| `instances` · `instances.show` | `voyage.instance.view` |
| `vente-ticket` | `guichet.ticket.sell` |
| `tickets` · `tickets.show` | `guichet.ticket.view.gare` |
| `tickets.print` | `guichet.ticket.print` |
| `conflits` | `embarquement.conflit.view` |
| `messages` | `crm.conversation.view` |
| `caisse` · `caisse.detail` | `caisse.session.view.own` |
| `caisses-historique` | `caisse.historique.view` |
| `gares` | `reseau.gare.view` |
| `cares` · `cares.show` | `reseau.vehicule.view` |
| `chauffeurs` · `chauffeurs.show` | `reseau.chauffeur.view` |
| `users` | `compagnie.user.view` |
| `posts` | `contenu.article.view` |
| `posts.create` | `contenu.article.create` |
| `posts.edit` | `contenu.article.update` |
| `documents` | `reseau.document.view` |
| `rapports` | `finance.rapport.view` |
| `bilan` | `finance.bilan.view` |
| `depenses` | `finance.depense.view` |
| `recettes` | `finance.recette.view` |
| `categories` | `finance.categorie.manage` |
| `promos` · `promos.show` | `finance.promo.view` |
| `parametres` | `compagnie.parametres.view` |

### 2. Composants Livewire
Un middleware de route ne protège pas les **actions** d'un composant : toutes les
propriétés publiques sont pilotables depuis le navigateur, et une méthode Livewire est un
point d'entrée HTTP à part entière. Donc, dans chaque composant :
- `authorize('<permission de lecture>')` dans `mount()`,
- `authorize('<permission d'écriture>')` en **première ligne de chaque action d'écriture**
  (`save`, `delete`, `toggle`, `valider`, `annuler`…).

Le trait `ScopedToCompagnie` existant reste en place : il traite le cloisonnement des
identifiants, pas l'autorisation. Les deux sont complémentaires.

### 3. Navigation
Généraliser la clé `can` déjà présente dans `$compagnieNav`
(`compagnie-panel.blade.php:71-110`) : **chaque** entrée porte sa permission, avec la même
mécanique de filtrage. Les séparateurs `section` ne s'affichent plus si toutes les entrées
qui les suivent sont masquées.

### 4. Login agent
`AgentAuthController::login()` accepte aujourd'hui tout compte ayant un `compagnie_id` : un
comptable peut obtenir un jeton et valider des tickets. Ajouter, après la vérification du
mot de passe :

```php
if (! $user->hasPermission('embarquement.app.login')) {
    return response()->json([
        'success' => false,
        'message' => "Ce compte n'est pas autorisé à utiliser l'application agent.",
    ], 403);
}
```

Et attacher les abilities au jeton Sanctum plutôt que `['*']` :
`$user->createToken($name, $user->abilitesAgent())`, où la méthode retourne les
permissions du domaine `embarquement` que le compte détient. Vérifier la compatibilité
avec `app/Models/Auth/PersonalAccessToken.php` et le flux de `refresh`.

### Tests exigés
- Un test paramétré qui, **pour chaque ligne des deux tableaux de correspondance**, vérifie
  qu'un compte sans la permission reçoit 403 et qu'un compte avec la permission reçoit 200.
- `test_une_action_livewire_est_refusee_sans_permission` sur au moins trois composants
  sensibles (`TicketManager`, `GestionCaisse`, `DepenseManager`).
- `test_la_navigation_masque_les_entrees_non_autorisees`.
- `test_un_comptable_ne_peut_pas_se_connecter_a_l_application_agent`.
- `test_un_agent_d_embarquement_obtient_un_jeton_avec_les_bonnes_abilites`.
- `test_rbac_enforce_a_false_laisse_tout_passer` — la soupape de sécurité.

### Procédure de bascule — à écrire dans le commit
1. Déployer avec `RBAC_ENFORCE=false`.
2. `php artisan rbac:refusals --days=14`, corriger les attributions de rôles.
3. Passer `RBAC_ENFORCE=true` pour une seule compagnie pilote si le code le permet, sinon
   en heure creuse.
4. Rollback = remettre `RBAC_ENFORCE=false` et `php artisan config:clear`. **Aucun rollback
   de migration n'est nécessaire.**

---

## T10 — Refonte des policies

**Branche** `feat/rbac-T10-policies`

### Objectif
Faire porter les décisions par les permissions, et non par des tests de rôle en dur.

### Fichiers
`app/Policies/*.php` (6 fichiers), `app/Providers/AppServiceProvider.php`,
nouveau `tests/Feature/Rbac/PoliciesTest.php`.

### Travail
1. Remplacer chaque `in_array($user->role, [UserRole::Admin, UserRole::Root])` par la
   permission correspondante. `Gate::before` traite déjà le cas `root` : ne pas le
   dupliquer.
2. **`CarePolicy` retourne `true` sur toutes ses abilities** — c'est un squelette généré
   jamais implémenté. L'écrire pour de bon avec les permissions `reseau.vehicule.*`.
3. `TicketPolicy::validate()` et `block()` n'exigent qu'un rattachement compagnie :
   remplacer par `embarquement.ticket.validate` / `.block`, en conservant le test
   d'appartenance du ticket à la compagnie (`isCompagnieAgent`).
4. `TicketPolicy::create()` utilise `hasVerifiedEmail()`. Depuis l'ajout de
   `phone_verified_at` (juin 2026), **tout compte créé par OTP téléphone est bloqué à
   l'achat**. Utiliser `isVerified()`, qui existe déjà sur le modèle `User`.
5. Conserver les gates `compagnie-settings.*` comme alias vers les nouvelles permissions,
   pour ne pas casser les appels existants dans les vues.

### Tests exigés
- Une méthode par ability de chaque policy, avec un cas autorisé et un cas refusé.
- `test_un_compte_verifie_par_telephone_peut_acheter_un_ticket` — la régression du point 4.
- `test_care_policy_refuse_un_vehicule_d_une_autre_compagnie`.

---

## T11 — Administration des rôles et cumuls interdits

**Branche** `feat/rbac-T11-administration`

### Objectif
Rendre les habilitations administrables sans déploiement, et refuser les cumuls dangereux à
l'attribution.

### Fichiers
- nouveau `app/Livewire/Compagnie/Habilitation/RoleManager.php` + vue
- nouveau `app/Rbac/ReglesSeparation.php`
- `app/Livewire/Compagnie/Compagnie/UserManager.php`
- `routes/web.php`, `resources/views/layouts/compagnie-panel.blade.php`
- nouveau `tests/Feature/Rbac/SeparationDesPouvoirsTest.php`

### Fonctionnalités
1. **Écran des rôles compagnie** : lister les rôles système en lecture, dériver un rôle
   propre à la compagnie, cocher ses permissions et leur portée. Un rôle `is_system` n'est
   jamais modifiable depuis cet écran.
2. **Règle de rang** : on n'attribue qu'un rôle de rang strictement inférieur au sien.
3. **Cumuls interdits** — refuser les six paires du § 7.2 du document de référence :
   `guichetier`+`chef_caisse` · `guichetier`+`comptable` · `agent_embarquement`+`comptable` ·
   `guichetier`+`agent_embarquement` · `comptable`+`auditeur` · tout rôle compagnie + tout
   rôle plateforme. Message d'erreur explicite citant la raison, pas un simple « interdit ».
4. **Règle des quatre yeux** : `finance.depense.approve` et
   `finance.remboursement.approve` sont refusées sur une pièce dont l'utilisateur courant
   est l'auteur. À implémenter dans `PorteeVerifier` ou en policy dédiée, et tester.
5. **Auto-administration interdite** : personne ne modifie ses propres rôles ni ne lève sa
   propre suspension.

### Tests exigés
- Un test par paire de cumul interdit.
- `test_un_chef_de_gare_ne_peut_pas_creer_un_chef_de_gare` (règle de rang).
- `test_l_auteur_d_une_depense_ne_peut_pas_l_approuver`.
- `test_un_utilisateur_ne_peut_pas_modifier_ses_propres_roles`.
- `test_un_role_systeme_n_est_pas_modifiable`.

---

## T12 — Manifeste de départ imprimable

**Branche** `feat/rbac-T12-manifeste`

### Objectif
Produire la feuille de départ par voyage : document exigé en cas de contrôle routier ou
d'accident. Aujourd'hui seule l'impression ticket par ticket existe.

### Fichiers
- nouveau `app/Http/Controllers/Compagnie/ManifesteController.php`
- nouvelle vue `resources/views/compagnie/manifeste.blade.php`
- `app/Services/Ticket/PdfService.php` (réutilisation)
- `routes/web.php`
- `app/Rbac/PermissionCatalogue.php` (+ `embarquement.manifeste.print`, `embarquement.manifeste.view`)
- nouveau `tests/Feature/Compagnie/ManifesteTest.php`

### Contenu du document
En-tête : compagnie (logo via `ReportService::logoDataUri()`), trajet, date, heure,
immatriculation, chauffeur, nombre de places, nombre de vendus.
Corps : siège, nom du passager, téléphone, numéro de pièce d'identité, statut du ticket,
case d'émargement.
Pied : totaux, visas agent et chauffeur, horodatage d'édition.

### Exigences
- Réutiliser `barryvdh/laravel-dompdf` via `PdfService`, comme le PDF de ticket existant.
- Inclure les `AutrePersonne` (tickets achetés pour un tiers) : le passager réel n'est pas
  toujours l'acheteur.
- Trier par numéro de siège, les tickets sans siège en fin de liste.
- Pagination correcte à 70 lignes : un car de 70 places ne tient pas sur une page.

### Tests exigés
- `test_le_manifeste_liste_tous_les_passagers_du_depart`.
- `test_les_passagers_tiers_apparaissent_avec_leur_propre_nom`.
- `test_un_depart_d_une_autre_compagnie_renvoie_403`.
- `test_le_manifeste_est_un_pdf_valide`.

---

## T13 — Registre des départs réels

**Branche** `feat/rbac-T13-departs-reels`

### Objectif
Rendre la ponctualité mesurable. Le statut `RETARDE` existe sur `VoyageInstance` mais ne
porte aucune donnée : ni heure réelle, ni durée, ni cause.

### Fichiers
- nouvelle migration `add_declaration_reelle_to_voyage_instances_table`
- `app/Models/Voyage/VoyageInstance.php`
- nouvel enum `app/Enums/MotifRetard.php`
- `app/Livewire/Compagnie/Voyage/VoyageInstanceShow.php` + vue
- nouveau `app/Http/Requests/Compagnie/DeclarerDepartRequest.php`
- `app/Rbac/PermissionCatalogue.php` (+ `voyage.instance.declareDepart`, `.declareArrivee`)
- nouveau `tests/Feature/Voyage/DeclarationDepartTest.php`

### Colonnes
`heure_depart_reel` (datetime nullable) · `heure_arrivee_reel` (datetime nullable) ·
`motif_retard` (string 60 nullable) · `commentaire_retard` (text nullable) ·
`nb_embarques` (unsignedSmallInteger nullable) · `km_depart` / `km_arrivee`
(unsignedInteger nullable) · `declare_par` (FK users nullOnDelete).

`MotifRetard` : `Technique`, `Trafic`, `Meteo`, `Attente passagers`, `Chargement`,
`Controle routier`, `Chauffeur`, `Autre` — valeurs en snake_case, clés en TitleCase.

### Règles métier
- Le retard se calcule, il ne se saisit pas : `heure_depart_reel` moins l'heure théorique
  (`voyage_instances.date` + `heure`). Un accesseur `retardMinutes`.
- `motif_retard` devient obligatoire au-delà d'un seuil paramétrable (nouvelle clé
  `compagnie_settings`, défaut 15 minutes).
- `nb_embarques` ne peut dépasser le nombre de places du véhicule affecté.
- `km_arrivee` doit être supérieur à `km_depart`. Ces deux champs servent au suivi de
  consommation (§ 6.4 du document de référence) : les saisir ici évite toute ressaisie.
- La déclaration est **idempotente par instance** : une seconde déclaration met à jour et
  journalise, elle ne crée pas de doublon.

### Tests exigés
- `test_le_retard_est_calcule_depuis_l_heure_theorique`.
- `test_le_motif_est_obligatoire_au_dela_du_seuil`.
- `test_le_nombre_d_embarques_ne_depasse_pas_la_capacite`.
- `test_le_kilometrage_d_arrivee_doit_etre_superieur_au_depart`.
- `test_une_seconde_declaration_met_a_jour_sans_dupliquer`.

---

## T14 — Versements en banque et validation des écarts

**Branche** `feat/rbac-T14-versements`

### Objectif
Fermer le circuit de l'argent. `Caisse` gère l'ouverture et la clôture, mais ce que devient
l'espèce ensuite n'est nulle part : `Recette` ne trace pas la remise en banque.

### Fichiers
- nouvelles migrations `create_versements_table`, `create_caisse_versement_table`,
  `add_validation_ecart_to_caisses_table`
- nouveaux `app/Models/Finance/Versement.php`, `app/Enums/StatutVersement.php`
- nouveau `app/Livewire/Compagnie/Caisse/VersementManager.php` + vue
- `app/Livewire/Compagnie/Caisse/DetailCaisse.php` (validation d'écart)
- `app/Rbac/PermissionCatalogue.php` (+ `caisse.versement.*`, `caisse.ecart.validate`)
- nouveau `tests/Feature/Caisse/VersementTest.php`
- nouveau `tests/Feature/Caisse/ValidationEcartTest.php`

### Schéma
`versements` : `id` · `compagnie_id` · `user_id` (déposant) · `montant` (integer) ·
`banque` (string) · `numero_bordereau` (string, unique par compagnie) ·
`date_versement` (date) · `justificatif_path` (string nullable) ·
`statut` (enum `brouillon`/`depose`/`valide`/`rejete`) · `valide_par` · `valide_at` ·
`note` · timestamps. Global scope compagnie, **sur le modèle de `Caisse::booted()`**.

`caisse_versement` : pivot `caisse_id` + `versement_id` + `montant_impute`.

`caisses` : `ecart_valide_par` (FK users nullOnDelete) · `ecart_valide_at` ·
`ecart_motif` (text nullable).

### Règles métier
- La somme des `montant_impute` d'un versement est égale à son `montant`.
- Une caisse ne peut être rattachée qu'une fois à un versement, et seulement si elle est
  `Fermee`.
- **Le titulaire d'une session ne valide jamais son propre écart.** C'est le cœur du
  contrôle de caisse : le test ci-dessous n'est pas facultatif.
- Seuil de tolérance d'écart paramétrable (nouvelle clé `compagnie_settings`) : au-delà, le
  motif est obligatoire et l'action journalisée via `AuditLogger` (T07).
- Un versement `valide` est immuable.

### Tests exigés
- `test_un_guichetier_ne_peut_pas_valider_son_propre_ecart`.
- `test_la_somme_des_imputations_egale_le_montant_du_versement`.
- `test_une_caisse_ouverte_ne_peut_pas_etre_versee`.
- `test_un_versement_valide_est_immuable`.
- `test_le_motif_est_obligatoire_au_dela_du_seuil`.
- `test_un_versement_d_une_autre_compagnie_est_invisible`.

---

## T15 — Tableau de bord de gare

**Branche** `feat/rbac-T15-dashboard-gare`

### Objectif
Donner au chef de gare la page qui remplace le tour des guichets et les appels
téléphoniques. **Dépend de T06** : sans portée gare, l'écran n'a pas de périmètre.

### Fichiers
- nouveau `app/Livewire/Compagnie/Gare/DashboardGare.php` + vue
- nouveau `app/Services/Gare/GareDashboardService.php`
- `routes/web.php`, `resources/views/layouts/compagnie-panel.blade.php`
- `app/Rbac/PermissionCatalogue.php` (+ `reseau.gare.dashboard`)
- nouveau `tests/Feature/Gare/DashboardGareTest.php`

### Contenu
- **Départs du jour** : heure, trajet, véhicule, chauffeur, vendus / capacité, taux de
  remplissage, statut, heure réelle si déclarée (T13).
- **Caisses** : sessions ouvertes avec leur titulaire et leur encours, sessions de la
  veille restées ouvertes (alerte), écarts en attente de validation.
- **Alertes** : départ dans moins d'une heure sans véhicule ou sans chauffeur affecté ;
  tickets vendus supérieurs à la capacité du véhicule affecté — ce dernier cas est
  silencieux aujourd'hui alors que `voyage_instances.nb_place` et `cares.number_place`
  peuvent diverger.
- **Sélecteur de gare** si l'utilisateur est affecté à plusieurs.

### Exigences de performance
Un chef de gare recharge cette page toutes les cinq minutes. Compter les requêtes : une
par bloc, pas une par ligne. Eager loading systématique
(`voyage.gareDepart`, `care`, `chauffer`), agrégats en SQL et non en PHP.
Le test de non-régression N+1 ci-dessous est obligatoire.

### Tests exigés
- `test_le_tableau_de_bord_ne_montre_que_la_gare_de_l_utilisateur`.
- `test_une_alerte_est_levee_pour_un_depart_sans_vehicule`.
- `test_une_alerte_est_levee_quand_les_ventes_depassent_la_capacite`.
- `test_les_sessions_de_caisse_ouvertes_de_la_veille_sont_signalees`.
- `test_le_nombre_de_requetes_reste_sous_quinze` via `DB::listen`.

---

## 2. Backlog — après les quinze tâches

Priorisation du § 6.11 du document de référence. Ces chantiers ne sont **pas** spécifiés au
niveau de détail des tâches ci-dessus : chacun demandera sa propre fiche avant exécution.

| Priorité | Chantier | Pourquoi il vient après | Taille |
|---|---|---|---|
| P2 | **Module bagages** — table `bagages`, étiquette QR, encaissement rattaché à une caisse | Les clés `BAGAGE_GRATUIT_KG` et `PRIX_KG_SUPPLEMENTAIRE` existent déjà sans rien à piloter ; c'est du revenu aujourd'hui hors système | L |
| P2 | **Conflits d'affectation** — détection véhicule/chauffeur sur départs qui se chevauchent | Rien n'empêche aujourd'hui le double engagement, découvert le matin sur le quai | M |
| P2 | **Carnet d'entretien** — table `maintenances`, échéances préventives, vue des pièces véhicule | `StatutCare::EnPanne` existe sans aucun historique | L |
| P2 | **Annonces de service ciblées** — pousser un message aux détenteurs de tickets d'une ligne ou d'un départ | `PushToken`, `NotificationService` et les canaux SMS/WhatsApp sont déjà en place ; il manque la cible et l'écran | M |
| P2 | **Réclamations et workflow de remboursement** — table `reclamations` | Les champs de remboursement sont sur `tickets` depuis juin 2026, sans parcours | L |
| P3 | **Suivi carburant** — table `pleins`, imputation polymorphe de `depenses`, coût au kilomètre | Premier poste de charge, aujourd'hui invisible. Les km viennent de T13 | L |
| P3 | **Fret / colis** | Nouvelle activité, pas une correction | XL |
| P3 | **Liste d'attente, yield management, prévision d'encaisse** | Optimisation, après que la mesure existe | XL |
| P3 | **Compte chauffeur** (option B du § 6.9) | Décision ouverte : `Chauffer` est une fiche, pas un compte | M |

---

## 3. Registre des risques

| Risque | Tâche | Parade |
|---|---|---|
| Un métier perd l'accès à un écran qu'il utilisait | T09 | Mode observation (T08) pendant deux semaines, puis `rbac:refusals` |
| Un compte de compagnie sans rôle se retrouve sans accès | T05 | La commande les signale nominativement pour arbitrage humain |
| Un agent sans gare affectée ne voit plus rien | T06 T09 | Test dédié + vérification des affectations avant bascule |
| Les gates dynamiques cassent `artisan migrate` sur base vide | T04 | Garde `Schema::hasTable('permissions')` + mise en cache |
| L'index unique `(name, compagnie_id)` n'empêche pas les doublons système | T02 | Colonne générée `compagnie_key` + test qui attend l'échec |
| Un échec d'écriture d'audit annule une vente | T07 | `AuditLogger` n'exception jamais, replie sur le canal de log |
| Perte de jetons agent au déploiement | T09 | Vérifier `PersonalAccessToken` et le flux `refresh` ; un correctif existe déjà en historique sur ce sujet |
| Lenteur du panneau après ajout des contrôles | T04 T09 | Cache versionné des permissions, test de comptage de requêtes en T15 |

### Rollback

- **T02 à T08** : additifs. Rollback = `migrate:rollback` des migrations de la tâche.
- **T09** : `RBAC_ENFORCE=false` + `php artisan config:clear`. Aucun rollback de schéma.
- **T05** : la commande n'enlève aucun ancien rôle ; l'état antérieur reste reconstituable.

---

## 4. Ce que ce plan ne traite pas

1. **Le sort de `users.role`.** Décision ouverte (§ 10 du document de référence). Le plan
   le laisse en place comme discriminant de surface.
2. **La suppression des six anciens rôles `CompanyRole`.** À faire après validation en
   production de T05, dans une tâche dédiée.
3. **Le nettoyage des `tests/*/ExampleTest.php`** générés par Laravel.
4. **Les trois autres décisions ouvertes** : granularité réelle de la portée gare, compte
   chauffeur, plafonds paramétrables. Elles conditionnent le backlog, pas les quinze tâches.
