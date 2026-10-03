# LIPTRA — Rôles & Matrice des permissions

> Document de référence pour l'habilitation (RBAC) de la plateforme LIPTRA : back-office
> plateforme, espace interne des compagnies de transport, application agent et application
> voyageur.
>
> Dernière révision : 2026-10-02 · Statut : **proposition à valider**

---

## 1. Périmètre

LIPTRA expose quatre surfaces d'accès distinctes, qui doivent être habilitées séparément :

| Surface | Hôte / client | Garde actuelle | Population |
|---|---|---|---|
| Back-office plateforme | `admin.{domain}` (Livewire) | `panel.admin` → `EnsureIsAdmin` | Équipe LIPTRA |
| Espace interne compagnie | `compagnie.{domain}` (Livewire) | `panel.compagnie` → `EnsureHasWebCompagnie` | Personnel des compagnies |
| Application agent terrain | `agent.mobile` → `/api/admin/*` | `admin.auth.login` + `auth:sanctum` | Agents d'embarquement |
| Application voyageur | `client.mobile` → `/api/v2/*` · `app.{domain}` | `auth` / `auth:sanctum` | Clients |

Le document couvre en particulier **la gestion interne des compagnies de transport
burkinabè** : organisation en gares/agences, guichet, caisse journalière, parc roulant,
embarquement, comptabilité et RH.

---

## 2. État des lieux de l'habilitation existante

### 2.1 Deux mécanismes concurrents, non reliés

1. **Rôle plateforme** — colonne `users.role`, enum `App\Enums\UserRole` :
   `Super User`, `Admin`, `User`, `Companie Bosse`.
2. **Rôles applicatifs** — table pivot `role_user` + table `roles`, alimentée depuis
   `App\Enums\CompanyRole` : `company_admin`, `agent`, `bagagiste`, `comptabilite`,
   `rh`, `caisse` (voir `database/seeders/RoleSeeder.php`).

Les deux ne communiquent pas : `EnsureHasWebCompagnie` ne teste que la présence de
`compagnie_id`, et **aucune des 32 routes du panneau compagnie n'est filtrée par rôle**,
à l'exception de `Conflits` (gate `manage-boarding-conflicts`).

### 2.2 Écarts à corriger

| # | Constat | Emplacement | Gravité |
|---|---|---|---|
| 1 | `CompanyRole::Directeur` est utilisé mais **n'existe pas** dans l'enum → `Error: Undefined constant` dès que l'ability est évaluée | `app/Policies/CompagniePolicy.php:45`, `:57` | Bloquant |
| 2 | `$user->company_role` n'existe ni en colonne, ni en accesseur → toujours `null` | `CompagniePolicy::isOwner()`, `manageFinance()` | Bloquant |
| 3 | Le formulaire d'équipe compagnie propose **tous** les rôles, y compris `admin` et `root` → élévation de privilèges par un administrateur compagnie | `app/Livewire/Compagnie/Compagnie/UserManager.php:174` | Critique |
| 4 | `roles` et `role_user` ne portent pas de `compagnie_id` : aucun rôle propre à une compagnie, aucun multi-appartenance | `database/migrations/2026_03_15_135243_*` | Majeur |
| 5 | Le login agent accepte **tout** utilisateur rattaché à une compagnie, sans contrôle de rôle → un comptable peut valider des tickets | `app/Http/Controllers/Api/Admin/AgentAuthController.php:38` | Critique |
| 6 | `CarePolicy` renvoie `true` sur toutes les abilities (squelette généré non implémenté) | `app/Policies/CarePolicy.php` | Majeur |
| 7 | Pas de table `permissions` : toute règle est codée en dur dans les policies, non administrable | — | Majeur |
| 8 | `TicketPolicy::validate()`/`block()` n'exigent qu'un rattachement compagnie, pas le rôle agent | `app/Policies/TicketPolicy.php:52`, `:57` | Majeur |
| 9 | `UserRole::CompagnieBosse` est le seul vecteur de « patron de compagnie », mais n'est pas attribué par le gestionnaire d'équipe | `UserManager.php:109` | Majeur |
| 10 | Les libellés de `UserRole` servent de valeurs d'enum SQL (`Companie Bosse`, faute incluse) → renommage coûteux | `0001_01_01_000000_create_users_table.php:31` | Mineur |

---

## 3. Modèle proposé

### 3.1 Principes

1. **Deux étages séparés.** Un rôle *plateforme* (LIPTRA) et des rôles *compagnie*. Un
   compte ne peut pas être les deux à la fois.
2. **Permissions atomiques, rôles composables.** Les contrôles d'accès s'écrivent
   toujours contre une *permission*, jamais contre un rôle. Les rôles ne sont que des
   paquets de permissions.
3. **Cloisonnement multi-tenant par défaut.** Toute permission compagnie est
   implicitement bornée à `compagnie_id` de l'utilisateur, via un *global scope* comme
   celui déjà en place sur `Caisse`.
4. **Second axe de portée : la gare.** Un chef de gare et un guichetier ne voient que
   l'activité de leur(s) gare(s) d'affectation. Cela impose une table
   `gare_user` (affectation) que le schéma actuel n'a pas.
5. **Rôles compagnie personnalisables.** Les rôles livrés sont des gabarits `is_system`;
   une compagnie peut en dériver les siens sans toucher au code.
6. **Séparation des pouvoirs.** Vendre, encaisser, contrôler et arbitrer sont quatre
   fonctions qui ne doivent pas se cumuler sur un même compte (§ 7).

### 3.2 Nomenclature des permissions

```
<domaine>.<ressource>.<action>
```

`domaine` ∈ `platform` · `compagnie` · `reseau` · `voyage` · `guichet` · `embarquement` ·
`caisse` · `finance` · `contenu` · `crm` · `client` · `bagage` · `colis` · `incident` ·
`chauffeur`

Les quatre derniers domaines n'existent pas encore en base : ils accompagnent les
fonctionnalités proposées au § 6.

`action` ∈ `view` · `create` · `update` · `delete` · plus les verbes métier explicites
(`validate`, `cancel`, `refund`, `approve`, `close`, `assign`, `export`, `resolve`…).

Les actions à portée réduite sont suffixées : `guichet.ticket.view.gare` (sa gare)
vs `guichet.ticket.view.all` (toute la compagnie).

---

## 4. Catalogue des rôles

### 4.1 Rôles plateforme LIPTRA

| Code | Libellé | Finalité |
|---|---|---|
| `root` | Super Administrateur | Compte technique. Référentiels, création de compagnies, paramètres globaux, usurpation d'identité pour support. |
| `platform_admin` | Administrateur plateforme | Exploitation courante : référentiel géographique, ouverture/suspension de compagnies, supervision. Pas d'accès aux secrets ni à l'usurpation. |
| `platform_support` | Support & Modération | Assistance voyageurs et compagnies : lecture des dossiers, modération du contenu, triage des bugs. Aucune écriture financière. |
| `client` | Voyageur | Rôle par défaut de tout compte créé depuis l'application ou le site. |

### 4.2 Rôles compagnie de transport

| Code | Libellé | Correspondance terrain (BF) | Portée |
|---|---|---|---|
| `compagnie_dg` | Direction Générale | DG / PDG / Gérant | Compagnie entière |
| `compagnie_admin` | Administrateur Compagnie | Secrétaire général, Directeur administratif | Compagnie entière |
| `exploitation` | Responsable Exploitation | Chef d'exploitation, Directeur technique | Compagnie entière |
| `chef_gare` | Chef de Gare / d'Agence | Chef d'agence, Responsable de gare | Sa ou ses gares |
| `guichetier` | Guichetier | Agent de vente, Billettiste | Sa gare + sa caisse |
| `chef_caisse` | Chef de Caisse | Caissier principal, Trésorier | Compagnie entière |
| `agent_embarquement` | Agent d'Embarquement | Contrôleur, Agent de quai, Receveur | Voyages de ses gares |
| `bagagiste` | Bagagiste / Agent Fret | Bagagiste, Agent colis | Sa gare |
| `chef_parc` | Chef de Parc | Chef de garage, Responsable logistique | Compagnie entière |
| `comptable` | Comptabilité | Comptable, Aide-comptable | Compagnie entière |
| `rh` | Ressources Humaines | Responsable du personnel | Compagnie entière |
| `communication` | Chargé de Communication | Community manager, Chargé marketing | Compagnie entière |
| `service_client` | Service Client | Standardiste, Chargé de clientèle | Compagnie entière |
| `auditeur` | Auditeur / Contrôle de gestion | Auditeur interne, Commissaire aux comptes | Compagnie entière, **lecture seule** |
| `chauffeur` | Chauffeur | Conducteur | Ses voyages affectés |

Un déploiement minimal peut se limiter à 6 rôles : `compagnie_dg`, `chef_gare`,
`guichetier`, `agent_embarquement`, `comptable`, `communication`. Les autres se branchent
au fil de la croissance de la compagnie.

---

## 5. Matrices de permissions

**Légende** — `●` autorisé · `◐` autorisé sur son périmètre (sa gare, sa caisse, ses
propres enregistrements) · `○` lecture seule · `—` refusé.

Ces matrices couvrent le périmètre fonctionnel **existant**. Les permissions liées aux
fonctionnalités nouvelles sont listées fiche par fiche au § 6, et viendront s'y ajouter au
rythme des livraisons.

### 5.1 Domaine `platform`

| Permission | ROOT | PADM | PSUP | Client |
|---|:--:|:--:|:--:|:--:|
| `platform.pays.manage` | ● | ● | — | — |
| `platform.region.manage` | ● | ● | — | — |
| `platform.ville.manage` | ● | ● | — | — |
| `platform.compagnie.view` | ● | ● | ○ | — |
| `platform.compagnie.create` | ● | ● | — | — |
| `platform.compagnie.update` | ● | ● | — | — |
| `platform.compagnie.activate` | ● | ● | — | — |
| `platform.compagnie.suspend` | ● | ● | — | — |
| `platform.compagnie.delete` | ● | — | — | — |
| `platform.settings.manage` | ● | ◐ | — | — |
| `platform.user.view` | ● | ● | ○ | — |
| `platform.user.update` | ● | ● | — | — |
| `platform.user.suspend` | ● | ● | — | — |
| `platform.user.impersonate` | ● | — | — | — |
| `platform.stats.view` | ● | ● | ○ | — |
| `platform.audit.view` | ● | ● | ○ | — |

> `platform.settings.manage` en `◐` pour `platform_admin` : les clés sensibles (jetons de
> paiement, secrets OTP, HMAC de signature QR) restent réservées à `root`. C'est la même
> distinction que `compagnie-settings.updateAdvanced` dans `CompagnieSettingPolicy`.

### 5.2 Domaine `compagnie` — identité, paramètres, équipe

Colonnes : **DG** `compagnie_dg` · **ADM** `compagnie_admin` · **EXP** `exploitation` ·
**CGA** `chef_gare` · **GUI** `guichetier` · **CCA** `chef_caisse` ·
**EMB** `agent_embarquement` · **BAG** `bagagiste` · **PRC** `chef_parc` ·
**CPT** `comptable` · **RH** `rh` · **COM** `communication` · **SAV** `service_client` ·
**AUD** `auditeur` · **CHF** `chauffeur`

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `compagnie.dashboard.view` | ● | ● | ● | ◐ | ◐ | ● | — | — | ● | ● | ○ | ○ | ○ | ○ | — |
| `compagnie.profil.view` | ● | ● | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ● | ○ | ○ | ○ |
| `compagnie.profil.update` | ● | ● | — | — | — | — | — | — | — | — | — | ◐ | — | — | — |
| `compagnie.parametres.view` | ● | ● | ○ | ○ | — | ○ | — | — | ○ | ○ | ○ | — | — | ○ | — |
| `compagnie.parametres.update` | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `compagnie.parametres.updateAdvanced` | ● | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `compagnie.parametres.reset` | ● | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `compagnie.user.view` | ● | ● | ○ | ◐ | — | ○ | — | — | ○ | ○ | ● | — | — | ○ | — |
| `compagnie.user.create` | ● | ● | — | — | — | — | — | — | — | — | ● | — | — | — | — |
| `compagnie.user.update` | ● | ● | — | ◐ | — | — | — | — | — | — | ● | — | — | — | — |
| `compagnie.user.disable` | ● | ● | — | — | — | — | — | — | — | — | ● | — | — | — | — |
| `compagnie.user.resetPassword` | ● | ● | — | ◐ | — | — | — | — | — | — | ● | — | — | — | — |
| `compagnie.role.assign` | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `compagnie.role.manage` | ● | — | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `compagnie.gare.assignUser` | ● | ● | ● | ◐ | — | — | — | — | — | — | ● | — | — | — | — |

> `chef_gare` en `◐` sur `compagnie.user.*` : il administre les comptes **affectés à sa
> gare** et ne peut attribuer qu'un rôle de rang strictement inférieur au sien.

### 5.3 Domaine `reseau` — gares, véhicules, chauffeurs, documents

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `reseau.gare.view` | ● | ● | ● | ◐ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ |
| `reseau.gare.create` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `reseau.gare.update` | ● | ● | ● | ◐ | — | — | — | — | — | — | — | — | — | — | — |
| `reseau.gare.delete` | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `reseau.gare.setDefault` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `reseau.vehicule.view` | ● | ● | ● | ◐ | ○ | — | ○ | ○ | ● | ○ | — | — | — | ○ | ◐ |
| `reseau.vehicule.create` | ● | ● | ● | — | — | — | — | — | ● | — | — | — | — | — | — |
| `reseau.vehicule.update` | ● | ● | ● | — | — | — | — | — | ● | — | — | — | — | — | — |
| `reseau.vehicule.delete` | ● | ● | — | — | — | — | — | — | ● | — | — | — | — | — | — |
| `reseau.vehicule.setStatut` | ● | ● | ● | — | — | — | — | — | ● | — | — | — | — | — | — |
| `reseau.chauffeur.view` | ● | ● | ● | ◐ | ○ | — | ○ | — | ● | ○ | ● | — | — | ○ | — |
| `reseau.chauffeur.create` | ● | ● | ● | — | — | — | — | — | ● | — | ● | — | — | — | — |
| `reseau.chauffeur.update` | ● | ● | ● | — | — | — | — | — | ● | — | ● | — | — | — | — |
| `reseau.chauffeur.delete` | ● | ● | — | — | — | — | — | — | ● | — | ● | — | — | — | — |
| `reseau.document.view` | ● | ● | ● | ◐ | — | — | — | — | ● | ○ | ● | — | — | ○ | ◐ |
| `reseau.document.upload` | ● | ● | ● | ◐ | — | — | — | — | ● | — | ● | — | — | — | — |
| `reseau.document.delete` | ● | ● | — | — | — | — | — | — | ◐ | — | ◐ | — | — | — | — |
| `reseau.document.manageRappel` | ● | ● | ● | — | — | — | — | — | ● | — | ● | — | — | — | — |

> Les documents sont polymorphes (`documentable_type`) : pièces du véhicule (visite
> technique, assurance) pour `chef_parc`, pièces du personnel (permis, contrat, visite
> médicale) pour `rh`. La portée `◐` doit filtrer sur `documentable_type`, sans quoi le
> chef de parc lit les contrats de travail.

### 5.4 Domaine `voyage` — offre et programmation

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `voyage.trajet.view` | ● | ● | ● | ○ | ○ | ○ | ○ | ○ | ○ | ○ | — | ○ | ○ | ○ | ○ |
| `voyage.trajet.create` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.trajet.update` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.trajet.delete` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.classe.manage` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.confort.manage` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.voyage.view` | ● | ● | ● | ◐ | ◐ | ○ | ◐ | ◐ | ○ | ○ | — | ○ | ○ | ○ | ◐ |
| `voyage.voyage.create` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.voyage.update` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.voyage.delete` | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.voyage.publish` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.tarif.update` | ● | ● | ◐ | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.instance.view` | ● | ● | ● | ◐ | ◐ | ○ | ◐ | ◐ | ○ | ○ | — | — | ○ | ○ | ◐ |
| `voyage.instance.generate` | ● | ● | ● | — | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.instance.update` | ● | ● | ● | ◐ | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.instance.assignVehicule` | ● | ● | ● | ◐ | — | — | — | — | ● | — | — | — | — | — | — |
| `voyage.instance.assignChauffeur` | ● | ● | ● | ◐ | — | — | — | — | ● | — | — | — | — | — | — |
| `voyage.instance.cancel` | ● | ● | ● | ◐ | — | — | — | — | — | — | — | — | — | — | — |
| `voyage.instance.close` | ● | ● | ● | ◐ | — | — | ◐ | — | — | — | — | — | — | — | — |

> `voyage.tarif.update` en `◐` pour `exploitation` : modification possible dans une
> fourchette paramétrée (`compagnie_settings`), au-delà de quoi la validation DG est
> requise. C'est le garde-fou contre la braderie de sièges en fin de remplissage.

### 5.5 Domaine `guichet` — billetterie

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `guichet.ticket.view.gare` | ● | ● | ● | ● | ● | ● | ◐ | ◐ | — | ● | — | — | ● | ○ | — |
| `guichet.ticket.view.all` | ● | ● | ● | — | — | ● | — | — | — | ● | — | — | ○ | ○ | — |
| `guichet.ticket.sell` | — | — | — | ● | ● | ● | — | — | — | — | — | — | — | — | — |
| `guichet.ticket.print` | ● | ● | — | ● | ● | ● | — | — | — | — | — | — | ● | — | — |
| `guichet.ticket.reprint` | ● | ● | — | ● | ◐ | ● | — | — | — | — | — | — | ◐ | — | — |
| `guichet.ticket.changeDate` | ● | ● | — | ● | ◐ | ● | — | — | — | — | — | — | ● | — | — |
| `guichet.ticket.transfer` | ● | ● | — | ● | ◐ | — | — | — | — | — | — | — | ● | — | — |
| `guichet.ticket.pause` | ● | ● | — | ● | ◐ | — | — | — | — | — | — | — | ● | — | — |
| `guichet.ticket.cancel` | ● | ● | — | ● | — | ● | — | — | — | ● | — | — | ◐ | — | — |
| `guichet.ticket.block` | ● | ● | — | ● | — | — | ● | — | — | ● | — | — | — | — | — |
| `guichet.ticket.unblock` | ● | ● | — | ◐ | — | — | — | — | — | ● | — | — | — | — | — |
| `guichet.ticket.export` | ● | ● | ● | ◐ | — | ● | — | — | — | ● | — | — | — | ● | — |
| `guichet.siege.reassign` | ● | ● | — | ● | ◐ | — | ◐ | — | — | — | — | — | — | — | — |
| `guichet.reservation.hold` | ● | ● | — | ● | ● | — | — | — | — | — | — | — | ● | — | — |

> `guichet.ticket.sell` est refusé au DG et à l'administrateur **volontairement** : la
> vente crée un mouvement de caisse imputé à une session (`caisses.user_id`). Un compte de
> direction qui vend fabrique une recette sans caisse rattachée, donc non rapprochable. Si
> le DG doit vendre, il lui faut un second compte `guichetier` nominatif.
>
> `guichet.ticket.reprint` en `◐` pour le guichetier : réimpression limitée à ses propres
> ventes de la journée courante, avec traçabilité. Au-delà, c'est le chef de gare — sinon
> la réimpression devient un canal de fraude (vendre un siège deux fois).

### 5.6 Domaine `embarquement` — contrôle, scan, synchronisation hors ligne

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `embarquement.app.login` | — | — | — | ● | — | — | ● | ● | — | — | — | — | — | — | — |
| `embarquement.manifeste.view` | ● | ● | ● | ● | ◐ | — | ● | ● | — | ○ | — | — | ○ | ○ | ◐ |
| `embarquement.ticket.scan` | — | — | — | ● | — | — | ● | — | — | — | — | — | — | — | — |
| `embarquement.ticket.validate` | — | — | — | ● | — | — | ● | — | — | — | — | — | — | — | — |
| `embarquement.ticket.verifyByPhone` | — | — | — | ● | ● | — | ● | — | — | — | — | — | ● | — | — |
| `embarquement.ticket.markAbsent` | — | — | — | ● | — | — | ● | — | — | — | — | — | — | — | — |
| `embarquement.ticket.block` | — | — | — | ● | — | — | ● | — | — | — | — | — | — | — | — |
| `embarquement.bagage.register` | — | — | — | ● | — | — | ◐ | ● | — | — | — | — | — | — | — |
| `embarquement.sync.pull` | — | — | — | ● | — | — | ● | ● | — | — | — | — | — | — | — |
| `embarquement.sync.push` | — | — | — | ● | — | — | ● | ● | — | — | — | — | — | — | — |
| `embarquement.conflit.view` | ● | ● | ● | ◐ | — | — | — | — | — | ● | — | — | ○ | ○ | — |
| `embarquement.conflit.resolve` | ● | ● | — | — | — | — | — | — | — | ● | — | — | — | — | — |

> `embarquement.app.login` matérialise le contrôle manquant du § 2.2 n° 5 : seul un
> porteur de cette permission obtient un jeton `agent.mobile`.
>
> `embarquement.conflit.resolve` reste fermé à l'agent et au guichetier : ils ne peuvent
> pas classer sans suite un refus qui les concerne. C'est exactement la règle déjà portée
> par le gate `manage-boarding-conflicts` (`AppServiceProvider.php:70`) — elle est ici
> généralisée et ouverte au DG, qui en était exclu par oubli.
>
> Les issues de conflit (`ConflictResolution`) méritent une granularité propre :
> `Dismissed` et `Regularized` pour la comptabilité, `Fraud` réservée à la direction, car
> la qualification de fraude déclenche une procédure disciplinaire.

### 5.7 Domaine `caisse` — session journalière

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `caisse.session.open` | — | — | — | ● | ● | ● | — | — | — | — | — | — | — | — | — |
| `caisse.session.close` | — | — | — | ● | ◐ | ● | — | — | — | — | — | — | — | — | — |
| `caisse.session.view.own` | — | — | — | ● | ● | ● | — | — | — | — | — | — | — | — | — |
| `caisse.session.view.gare` | ● | ● | ○ | ● | — | ● | — | — | — | ● | — | — | — | ○ | — |
| `caisse.session.view.all` | ● | ● | ○ | — | — | ● | — | — | — | ● | — | — | — | ○ | — |
| `caisse.session.forceClose` | ● | ● | — | ◐ | — | ● | — | — | — | — | — | — | — | — | — |
| `caisse.ecart.justify` | — | — | — | ● | ● | ● | — | — | — | — | — | — | — | — | — |
| `caisse.ecart.validate` | ● | ● | — | ◐ | — | ● | — | — | — | ● | — | — | — | — | — |
| `caisse.historique.view` | ● | ● | ○ | ◐ | ◐ | ● | — | — | — | ● | — | — | — | ○ | — |
| `caisse.historique.export` | ● | ● | — | ◐ | — | ● | — | — | — | ● | — | — | — | ● | — |

> `caisse.session.close` en `◐` pour le guichetier : il clôture **sa** session en
> déclarant le montant compté ; l'écart entre `montant_attendu` et `montant_fermeture`
> reste à valider par le chef de caisse ou le chef de gare. Le guichetier ne valide jamais
> son propre écart — c'est le cœur du contrôle de caisse.
>
> `caisse.session.forceClose` sert aux sessions oubliées ouvertes la veille. Chaque usage
> doit être journalisé nominativement.

### 5.8 Domaine `finance`

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `finance.bilan.view` | ● | ● | ○ | — | — | ○ | — | — | — | ● | — | — | — | ● | — |
| `finance.recette.view` | ● | ● | ○ | ◐ | — | ● | — | — | — | ● | — | — | — | ● | — |
| `finance.recette.create` | — | — | — | — | — | ● | — | — | — | ● | — | — | — | — | — |
| `finance.recette.update` | — | — | — | — | — | ◐ | — | — | — | ● | — | — | — | — | — |
| `finance.recette.delete` | ● | — | — | — | — | — | — | — | — | ◐ | — | — | — | — | — |
| `finance.depense.view` | ● | ● | ○ | ◐ | — | ○ | — | — | ○ | ● | ○ | — | — | ● | — |
| `finance.depense.create` | ● | ● | ◐ | ◐ | — | ● | — | — | ◐ | ● | ◐ | — | — | — | — |
| `finance.depense.update` | ● | ● | — | — | — | ◐ | — | — | — | ● | — | — | — | — | — |
| `finance.depense.delete` | ● | — | — | — | — | — | — | — | — | ◐ | — | — | — | — | — |
| `finance.depense.approve` | ● | ◐ | — | — | — | — | — | — | — | — | — | — | — | — | — |
| `finance.categorie.manage` | ● | ● | — | — | — | — | — | — | — | ● | — | — | — | — | — |
| `finance.promo.view` | ● | ● | ○ | ○ | ○ | ○ | — | — | — | ● | — | ● | ○ | ○ | — |
| `finance.promo.create` | ● | ● | — | — | — | — | — | — | — | ◐ | — | ● | — | — | — |
| `finance.promo.update` | ● | ● | — | — | — | — | — | — | — | ◐ | — | ● | — | — | — |
| `finance.promo.deactivate` | ● | ● | — | ◐ | — | — | — | — | — | ● | — | ● | — | — | — |
| `finance.rapport.view` | ● | ● | ● | ◐ | — | ● | — | — | ○ | ● | ○ | — | — | ● | — |
| `finance.rapport.export` | ● | ● | ◐ | ◐ | — | ● | — | — | — | ● | — | — | — | ● | — |
| `finance.remboursement.request` | ● | ● | — | ● | ◐ | ● | — | — | — | ● | — | — | ● | — | — |
| `finance.remboursement.approve` | ● | ◐ | — | — | — | — | — | — | — | ● | — | — | — | — | — |

> `finance.depense.approve` / `finance.remboursement.approve` sont séparées de `create` :
> **celui qui saisit n'approuve pas**. Le contrôle applicatif doit refuser l'approbation
> par l'auteur de la pièce, même si le compte porte les deux permissions.
>
> Les `delete` financiers sont en `◐` ou fermés : en comptabilité on contre-passe, on ne
> supprime pas. Prévoir `finance.*.reverse` plutôt que d'ouvrir `delete`.
>
> L'auditeur a `finance.rapport.export` en `●` : un contrôle sans extraction de données
> n'est pas un contrôle. Mais il n'a aucune écriture, nulle part.

### 5.9 Domaine `contenu` — articles & communication

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `contenu.article.view` | ● | ● | ○ | ○ | ○ | — | — | — | — | — | — | ● | ○ | ○ | — |
| `contenu.article.create` | ● | ● | — | — | — | — | — | — | — | — | — | ● | — | — | — |
| `contenu.article.update` | ● | ● | — | — | — | — | — | — | — | — | — | ● | — | — | — |
| `contenu.article.publish` | ● | ● | — | — | — | — | — | — | — | — | — | ◐ | — | — | — |
| `contenu.article.delete` | ● | ● | — | — | — | — | — | — | — | — | — | ◐ | — | — | — |
| `contenu.categorie.manage` | ● | ● | — | — | — | — | — | — | — | — | — | ● | — | — | — |
| `contenu.tag.manage` | ● | ● | — | — | — | — | — | — | — | — | — | ● | — | — | — |
| `contenu.commentaire.moderate` | ● | ● | — | — | — | — | — | — | — | — | — | ● | ● | — | — |

> `contenu.article.publish` en `◐` : publication possible, sauf si le paramètre compagnie
> « validation éditoriale » est actif — auquel cas la publication retombe au DG.

### 5.10 Domaine `crm` — relation client, avis, fidélité

| Permission | DG | ADM | EXP | CGA | GUI | CCA | EMB | BAG | PRC | CPT | RH | COM | SAV | AUD | CHF |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| `crm.conversation.view` | ● | ● | — | ◐ | ◐ | — | — | — | — | — | — | ● | ● | ○ | — |
| `crm.conversation.reply` | ● | ● | — | ◐ | — | — | — | — | — | — | — | ● | ● | — | — |
| `crm.conversation.assign` | ● | ● | — | ◐ | — | — | — | — | — | — | — | — | ● | — | — |
| `crm.conversation.close` | ● | ● | — | ◐ | — | — | — | — | — | — | — | — | ● | — | — |
| `crm.rating.view` | ● | ● | ○ | ◐ | — | — | — | — | — | — | — | ● | ● | ○ | — |
| `crm.rating.reply` | ● | ● | — | — | — | — | — | — | — | — | — | ● | ● | — | — |
| `crm.rating.report` | ● | ● | — | ◐ | — | — | — | — | — | — | — | ● | ● | — | — |
| `crm.bugreport.view` | ● | ● | ○ | — | — | — | — | — | — | — | — | — | ● | ○ | — |
| `crm.bugreport.triage` | ● | ● | — | — | — | — | — | — | — | — | — | — | ● | — | — |
| `crm.fidelite.view` | ● | ● | — | ◐ | ◐ | ○ | — | — | — | ● | — | — | ● | ○ | — |
| `crm.fidelite.adjust` | ● | ◐ | — | — | — | — | — | — | — | ◐ | — | — | — | — | — |

> `crm.fidelite.adjust` crée de la valeur monétisable (points convertibles). À traiter avec
> la même rigueur qu'une écriture de caisse : plafond par opération, journalisation, et
> jamais au guichet.

### 5.11 Domaine `client` — espace voyageur

| Permission | Client | PSUP | SAV compagnie |
|---|:--:|:--:|:--:|
| `client.profil.view` | ● | ○ | ○ |
| `client.profil.update` | ● | — | — |
| `client.ticket.buy` | ● | — | — |
| `client.ticket.view.own` | ● | ○ | ◐ |
| `client.ticket.pdf` | ● | ◐ | ◐ |
| `client.ticket.regenerate` | ● | ◐ | ◐ |
| `client.ticket.transfer` | ● | — | ◐ |
| `client.ticket.pause` | ● | — | ◐ |
| `client.ticket.changeDate` | ● | — | ◐ |
| `client.ticket.buyForOther` | ● | — | — |
| `client.paiement.initiate` | ● | — | — |
| `client.post.comment` | ● | ◐ | — |
| `client.post.like` | ● | — | — |
| `client.rating.create` | ● | — | — |
| `client.bugreport.create` | ● | — | — |
| `client.conversation.create` | ● | — | — |
| `client.fidelite.view.own` | ● | ○ | ○ |
| `client.notification.manage` | ● | — | — |

> `client.ticket.buy` doit rester conditionné à la vérification du compte, comme
> aujourd'hui (`TicketPolicy::create()` → `hasVerifiedEmail()`). À noter : depuis
> l'ajout de `phone_verified_at`, la bonne condition est `isVerified()` (email **ou**
> téléphone) et non `hasVerifiedEmail()` — sinon tout compte créé par OTP téléphone est
> bloqué à l'achat.

---

## 6. Fonctionnalités à ouvrir par rôle

Un rôle sans écran utile est une case vide dans une matrice. Cette section décrit, pour
chaque rôle **nouveau ou aujourd'hui inexploité**, les fonctionnalités qui justifient son
existence dans le fonctionnement interne d'une compagnie de transport burkinabè : ce
qu'elles apportent, ce qui manque dans le modèle actuel pour les porter, et les
permissions qu'elles ajoutent aux matrices du § 5.

Chaque fiche distingue :

- **Socle** — indispensable pour que le rôle serve à quelque chose. Sans cela, ne pas créer le rôle.
- **Extension** — gain opérationnel net, à planifier après le socle.

### 6.1 `chef_gare` — Chef de Gare / d'Agence

C'est le rôle dont l'absence coûte le plus cher aujourd'hui : une gare est un point de
vente avec du personnel, de l'argent liquide et des départs à l'heure, et **aucun écran de
LIPTRA n'est borné à une gare**. Le chef d'agence de Bobo voit, et peut modifier,
l'activité de Ouaga.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Tableau de bord de gare** — départs du jour, taux de remplissage, sièges restants, caisses ouvertes, écarts en attente, retards | Une seule page remplace le tour des guichets et les appels téléphoniques | Table `gare_user`, scope « gare » sur tickets / caisses / instances |
| **Feuille de départ imprimable** (manifeste) — passagers, sièges, pièces d'identité, contacts d'urgence, visa agent + chauffeur | Document exigé en cas de contrôle ou d'accident ; aujourd'hui seule l'impression ticket par ticket existe | Vue d'impression par `voyage_instance` (le PDF par ticket existe déjà via `PdfService`) |
| **Registre des départs réels** — heure de départ effective, nombre d'embarqués, motif de retard | Rend la ponctualité mesurable. Le statut `RETARDE` existe mais **ne porte aucune donnée** : ni durée, ni cause, ni responsable | `voyage_instances` : `heure_depart_reel`, `motif_retard`, `nb_embarques` |
| **Validation des écarts de caisse de sa gare** + relance des sessions laissées ouvertes | Le contrôle de caisse se fait là où l'argent est compté, pas au siège | Rien — repose sur `caisses` existant |
| **Affectation du personnel du jour** — qui tient quel guichet, qui embarque quel départ | Sans affectation, impossible de savoir à qui imputer un écart ou un refus d'embarquement | `gare_user` + table d'affectation journalière |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Journal d'incidents de gare** — passager refusé, altercation, panne, litige de caisse, avec pièce jointe | Un incident non écrit est un incident non arbitrable trois semaines plus tard | Table `incidents` (type, gravité, gare, instance, véhicule, auteur, pièces) |
| **Liste d'attente sur départ complet** | Un départ affiché complet est aujourd'hui une impasse ; la liste d'attente capte la demande et remplit les désistements | Table `listes_attente` |
| **Réaffectation en masse** lors d'une annulation de départ | Annuler un car de 70 places impose aujourd'hui **70 changements de date un par un** | Action de lot sur les tickets d'une instance |
| **Comparaison inter-gares** (remplissage, écarts, ponctualité) | Permet au siège d'animer le réseau sur des chiffres, pas sur des impressions | Agrégations dans `ReportService` |

**Permissions ajoutées** : `reseau.gare.dashboard`, `embarquement.manifeste.print`,
`voyage.instance.declareDepart`, `compagnie.affectation.manage`, `incident.view`,
`incident.create`, `incident.close`, `guichet.listeAttente.manage`,
`guichet.ticket.reaffecterLot`.

### 6.2 `exploitation` — Responsable Exploitation

Il décide quoi fait rouler, quand, avec quel véhicule et quel chauffeur. Le modèle porte
déjà `care_id` et `chauffer_id` sur `Voyage` et `VoyageInstance` — mais **sans aucun
contrôle de cohérence** : rien n'empêche d'affecter le même car à deux départs simultanés.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Plan de transport en vue calendrier** — par ligne et par gare, sur 7 à 30 jours | Voir le réseau au lieu de parcourir une liste d'instances | Rien — lecture de `voyage_instances` |
| **Détection des conflits d'affectation** — même véhicule ou même chauffeur sur deux départs qui se chevauchent | Un double engagement se découvre aujourd'hui le matin du départ, sur le quai | Validation croisée sur `care_id` / `chauffer_id` + durée (`voyages.temps`) |
| **Alerte de sur-capacité** — tickets vendus > places du véhicule affecté | `voyage_instances.nb_place` et `cares.number_place` peuvent diverger silencieusement : des passagers debout, ou refusés | Contrôle à l'affectation et à la vente |
| **Génération assistée des instances** — horizon, aperçu avant écriture, calendrier d'exception | La génération existe mais comme commande globale (`create-all-voyages-instances`), sans aperçu ni exception | Table `calendrier_exceptions` (fêtes, Tabaski, Ramadan, 11-Décembre, route coupée) |
| **Suspension d'une ligne sur une période** | Saison des pluies, route impraticable : aujourd'hui il faut annuler les instances une par une | `voyages.date_debut` / `date_fin` existent ; il manque la suspension partielle |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Tableau de rentabilité par ligne** — remplissage, recette, recette par place offerte | Décider d'ouvrir, renforcer ou fermer une ligne sur des chiffres | Agrégations `ReportService` |
| **Yield management encadré** — ajuster le prix d'une instance dans une fourchette paramétrée | Remplir un départ creux sans brader le réseau. Garde-fou : au-delà de la fourchette, accord DG | Clés `compagnie_settings` de fourchette + historique de prix sur `voyage_instances` |
| **Réaffectation en cascade** — un véhicule tombe en panne, on le remplace sur toutes ses instances à venir | Aujourd'hui : autant d'opérations manuelles que d'instances | Action de lot |
| **Ponctualité par ligne et par chauffeur** | Alimentée par le registre des départs réels (§ 6.1) | Dépend de `heure_depart_reel` |

**Permissions ajoutées** : `voyage.plan.view`, `voyage.instance.detectConflits`,
`voyage.ligne.suspend`, `voyage.exception.manage`, `voyage.instance.reaffecterLot`,
`voyage.rentabilite.view`.

### 6.3 `chef_caisse` — Chef de Caisse / Trésorier

Le module `Caisse` gère l'ouverture et la fermeture d'une session, mais **s'arrête là où le
risque commence** : ce que devient l'argent après la clôture n'est nulle part. C'est la
première fuite de trésorerie d'une compagnie de transport.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Consolidation journalière** — toutes les sessions, attendu vs compté, par gare et par guichetier | Le point de trésorerie du jour en une page | Rien — lecture de `caisses` |
| **Validation des écarts avec motif et seuil de tolérance** | Un écart de 500 F et un écart de 50 000 F ne suivent pas la même procédure | `caisses` : `ecart_valide_par`, `ecart_valide_at`, `ecart_motif` ; seuil en paramètre compagnie |
| **Versements en banque** — montant, banque, n° de bordereau, justificatif, sessions rattachées | **Chaînon manquant du circuit de l'argent** : aujourd'hui l'espèce sort de la caisse et disparaît du système. `Recette` ne trace pas la remise en banque | Table `versements` + pivot `caisse_versement` |
| **Fonds de caisse** — montant d'ouverture théorique par guichet, suivi de la monnaie | Un guichet sans monnaie bloque la vente le matin ; un fonds non suivi se confond avec la recette | `gares` ou table dédiée `fonds_caisse` |
| **Application du plafond de vente journalier** | La clé `PLAFOND_VENTE_JOURNALIER` **existe en paramètre mais n'est appliquée nulle part** | Contrôle à la vente + alerte au chef de caisse |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Rapprochement espèces / mobile money** — par `MoyenPayment` et `PaymentProvider` | Détecte les paiements marqués encaissés sans contrepartie réelle chez l'opérateur | Rapprochement sur `payements` + relevé opérateur importé |
| **Historique nominatif par guichetier** — écarts récurrents, courbe par agent | Détection douce : ce n'est pas l'écart ponctuel qui révèle la fraude, c'est sa répétition | Agrégations sur `caisses` |
| **Clôture forcée tracée** des sessions oubliées | Nécessaire en exploitation réelle, dangereux sans journal nominatif | Journal d'audit (§ 7.3) |
| **Prévision d'encaisse** à partir des départs programmés | Anticiper les besoins de monnaie et les remises en banque | Croisement instances × prix × historique de remplissage |

**Permissions ajoutées** : `caisse.consolidation.view`, `caisse.versement.view`,
`caisse.versement.create`, `caisse.versement.validate`, `caisse.fonds.manage`,
`caisse.plafond.view`, `caisse.rapprochement.view`.

### 6.4 `chef_parc` — Chef de Parc / Garage

`Care` ne porte que l'immatriculation, le numéro, le nombre de places, un statut et un
état. Pour un transporteur, **le parc et le carburant sont les deux premiers postes de
charge** — et ils sont aujourd'hui aveugles.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Fiche véhicule complète** — marque, modèle, année, mise en circulation, kilométrage, capacité soute | Impossible aujourd'hui de dire quel car est vieux, lequel consomme, lequel rapporte | Colonnes sur `cares` (`marque`, `modele`, `annee`, `km_actuel`, …) |
| **Carnet d'entretien** — intervention, kilométrage, coût, garage, pièces remplacées | `StatutCare::EnPanne` existe **sans aucun historique** : on sait qu'un car est en panne, jamais combien de fois ni pour quel montant | Table `maintenances` |
| **Maintenance préventive** — échéance au kilométrage ou à la date, avec alerte | Un entretien anticipé coûte une fraction d'une panne en ligne, immobilisation et transbordement des passagers inclus | `maintenances` (type préventif, périodicité) |
| **Échéancier des pièces du véhicule** — visite technique, assurance, carte grise, patente | Rouler sans visite technique, c'est l'immobilisation au premier contrôle routier. `Document` + `DocumentRappel` existent déjà : il suffit d'une vue filtrée sur `documentable_type = Care` | Rien — vue à construire sur l'existant |
| **Verrou d'affectation** — un véhicule `EnPanne` n'est plus affectable à une instance | Aujourd'hui rien ne l'empêche | Contrôle à l'affectation |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Suivi carburant** — litres, montant, kilométrage, station, chauffeur, justificatif | Le carburant n'existe aujourd'hui que comme `Depense` **sans rattachement au véhicule** : la consommation aux 100 km est incalculable, et le détournement de bons invisible | Table `pleins` + imputation polymorphe sur `depenses` |
| **Coût au kilomètre par véhicule** — maintenance + carburant / km, face à la recette des instances | La décision de renouveler ou de réformer un car, chiffrée | Croisement `maintenances` × `pleins` × `voyage_instances` |
| **Calendrier de disponibilité du parc** | Planifier les entretiens hors des pics de trafic (fêtes, rentrée, Tabaski) | Vue calendrier sur immobilisations |
| **Dotation en pièces de rechange** | Les compagnies stockent pneus, plaquettes, filtres ; aucune trace aujourd'hui | Table `stock_pieces` (optionnel) |

**Permissions ajoutées** : `reseau.vehicule.viewFiche`, `reseau.maintenance.view`,
`reseau.maintenance.create`, `reseau.maintenance.update`, `reseau.maintenance.planifier`,
`reseau.carburant.view`, `reseau.carburant.create`, `reseau.parc.disponibilite.view`,
`reseau.parc.cout.view`.

### 6.5 `bagagiste` — Bagagiste / Agent Fret

Le rôle existe dans `CompanyRole` **depuis le début, sans un seul écran**. Pire : les clés
`BAGAGE_GRATUIT_KG` et `PRIX_KG_SUPPLEMENTAIRE` sont déjà paramétrables dans
`CompagnieSettingKey` — mais il n'y a **aucune table bagage** sur laquelle les appliquer.
Le supplément de bagage et le fret de colis, qui sont une ligne de revenu réelle et
significative des compagnies burkinabè, sont donc intégralement hors système.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Enregistrement des bagages** — ticket, nombre de colis, poids, franchise, supplément calculé | Applique enfin les deux paramètres existants ; transforme une recette informelle en recette comptabilisée | Table `bagages` |
| **Étiquetage et QR bagage** — étiquette imprimée, remise contre scan à l'arrivée | Le bagage perdu ou remis à la mauvaise personne est le premier litige client. `QrCodeService` est déjà là | Réutilisation de `QrCodeService` + `bagages.code` |
| **Encaissement du supplément rattaché à une session de caisse** | Sans rattachement, le supplément n'est pas rapprochable : autant ne pas l'encaisser dans l'application | Lien `bagages` → `caisses` / `payements` |
| **Manifeste bagages par départ** — nombre de colis, poids total | Sécurité et charge à l'essieu, et liste de contrôle à la soute | Vue par `voyage_instance` |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Fret / messagerie de colis sans passager** — expéditeur, destinataire, valeur déclarée, paiement au départ ou à l'enlèvement | Vraie activité des compagnies, aujourd'hui tenue sur cahier. Revenu additionnel sur des départs déjà payés | Table `colis` + bordereau d'expédition |
| **Suivi d'acheminement du colis** — enregistré → embarqué → arrivé → retiré | Permet de répondre au client sans appeler la gare d'arrivée | Statuts + notification au destinataire (`PushToken`, SMS existants) |
| **Objets déclarés interdits / refus tracé** | Protège la compagnie en cas de contrôle | Journal d'incidents (§ 6.1) |

**Permissions ajoutées** : `bagage.view`, `bagage.register`, `bagage.update`,
`bagage.print`, `bagage.remise`, `bagage.manifeste.view`, `colis.view`, `colis.create`,
`colis.update`, `colis.remise`, `colis.encaisser`.

### 6.6 `communication` — Chargé de Communication

Le module `Post` publie des articles ; rien ne permet de **parler aux passagers d'un
départ précis**. Or `PushToken`, `NotificationService` et les canaux SMS / WhatsApp /
Telegram sont déjà en place : il manque la cible et l'écran.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Annonces de service ciblées** — pousser un message aux détenteurs de tickets d'une ligne, d'une gare ou d'une instance | Retard, changement de gare de départ, route coupée par les pluies, barrage : prévenir 60 passagers par notification plutôt que par 60 appels | Segmentation + table `annonces`. Les canaux existent déjà |
| **Programmation de publication** | `Post` publie immédiatement ; préparer la veille les annonces de fêtes et de rentrée | `posts.published_at` |
| **Gestion des avis clients** — réponse publique, note moyenne par ligne et par gare | `Rating` existe, sans écran d'animation ni suivi de tendance | Agrégations + `ratings.reponse` |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Campagnes promo mesurées** — un `PromoCode` lié à une annonce, avec usage et recette générée | Les codes promo existent et sont déjà posés sur `tickets` ; leur rendement n'est pas mesuré | Lien `promo_codes` → `annonces` + rapport d'usage |
| **Médiathèque** — logos, visuels, bannières réutilisables | Chaque upload est aujourd'hui isolé ; les visuels sont réimportés à chaque article | Table `medias` |
| **Statistiques d'audience** — vues, likes, commentaires par article | `Like` et `Comment` sont modélisés mais jamais agrégés | Agrégations + compteur de vues |
| **Modèles de message** par type d'événement (retard, annulation, promotion) | Un message de crise rédigé dans l'urgence est un message raté | Table `modeles_message` |

**Permissions ajoutées** : `contenu.annonce.view`, `contenu.annonce.create`,
`contenu.annonce.send`, `contenu.annonce.sendUrgent`, `contenu.media.manage`,
`contenu.audience.view`, `contenu.modele.manage`.

### 6.7 `service_client` — Service Client

Aujourd'hui, répondre à un passager impose de naviguer entre la messagerie, la liste des
tickets, les avis et les signalements de bug — quatre écrans sans lien, pour une seule
personne au téléphone.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Fiche client 360** — recherche par téléphone : tickets, paiements, litiges, avis, points de fidélité sur un écran | Le passager appelle avec un numéro, pas avec un identifiant de ticket | Vue agrégée ; `numero` est déjà sur `users` |
| **Boîte unifiée** — `Conversation`, `Rating` et `BugReport` dans une file unique, avec assignation, statut et délai de réponse | `Messagerie` existe mais ne couvre que les conversations, sans assignation ni suivi | Champs d'assignation + statut sur les trois modèles |
| **Réclamations tracées** — motif, montant réclamé, décision, pièces, échéance | Une demande de remboursement n'a aujourd'hui **aucun objet en base** : elle est accordée à l'oral, et personne ne peut la retrouver | Table `reclamations` |
| **Workflow de remboursement** — demandé → approuvé → payé, approbation par la comptabilité | Les champs de remboursement existent sur `tickets` depuis juin 2026, sans parcours ni traçabilité | Statuts + lien `reclamations` → `tickets` |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Réponses types** | Dix fois la même question par jour ; la réponse type garantit aussi le ton de la compagnie | `modeles_message` (partagé avec § 6.6) |
| **Gestes commerciaux encadrés** — avoir ou points de fidélité, plafonnés et tracés | Apaiser un litige sans ouvrir une porte à la fraude. Les points sont monétisables : plafond par opération, journal obligatoire | `loyalty_transactions` existe ; ajouter plafond et motif |
| **Rappel automatique d'engagement** — réclamation sans réponse au-delà du délai | Ce qui n'est pas relancé n'est pas traité | Échéance + notification interne |
| **Statistiques de qualité** — délai moyen de réponse, motifs les plus fréquents | Dit à l'exploitation quelles lignes génèrent les litiges | Agrégations |

**Permissions ajoutées** : `crm.client.view360`, `crm.boite.view`, `crm.boite.assign`,
`crm.reclamation.view`, `crm.reclamation.create`, `crm.reclamation.decide`,
`crm.geste.accorder`, `crm.qualite.view`.

### 6.8 `auditeur` — Auditeur / Contrôle de gestion

Parler de rôles sensibles sans piste d'audit n'a pas de sens. Aujourd'hui, **une annulation
de ticket, une réimpression ou un écart de caisse validé ne laissent aucune trace
nominative** : il n'existe aucun moyen de reconstituer qui a fait quoi.

**Socle**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Piste d'audit consultable** — acteur, action, cible, valeurs avant/après, horodatage, IP | Préalable technique à tout le reste de ce document | Table `audit_logs` + observateurs Eloquent |
| **Rapports d'exception** — tickets annulés après validation, réimpressions, écarts validés au-delà du seuil, clôtures forcées, remboursements approuvés par leur auteur | **La valeur d'un audit est dans les exceptions, pas dans les totaux.** Ces cinq rapports couvrent l'essentiel du risque interne | Requêtes dédiées sur `audit_logs` |
| **Rapprochement triple** — tickets vendus × paiements encaissés × sessions de caisse | Les trois sources doivent concorder ; l'écart désigne le point à instruire | Vue de rapprochement |
| **Lecture scellée** — aucune écriture possible techniquement | Un auditeur qui peut écrire n'audite plus. À garantir par le code, pas par la consigne | Rôle sans aucune permission d'écriture + refus au niveau du gate |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Export normalisé horodaté** (CSV / XLSX) | `maatwebsite/excel` est déjà installé | Classes d'export |
| **Revue des habilitations** — qui porte quel rôle, cumuls interdits détectés, comptes dormants | Les droits dérivent avec le temps bien plus vite que le code | Rapport sur `role_user` + règles du § 7.2 |
| **Alertes de seuil** — notification à la direction au-delà d'un volume d'exceptions | Transforme l'audit annuel en contrôle continu | Tâche planifiée |

**Permissions ajoutées** : `platform.audit.view` (déjà en § 5.1), `compagnie.audit.view`,
`compagnie.audit.export`, `compagnie.exception.view`, `compagnie.habilitation.review`,
`finance.rapprochement.view`.

### 6.9 `chauffeur` — Chauffeur

`Chauffer` est aujourd'hui une **fiche de personnel, pas un compte**. Deux options se
présentent, et le choix détermine tout le reste (voir § 10, décision 3).

**Option A — sans compte applicatif.** Le chauffeur n'a pas d'accès ; sa feuille de route
est imprimée par la gare, et ses déclarations sont saisies par le chef de gare. Coût nul,
mais l'information arrive en différé et par ressaisie.

**Option B — compte nominatif.** Un rôle `chauffeur` très étroit, consultable depuis un
téléphone.

**Socle de l'option B**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Feuille de route** — ses départs du jour, véhicule, gare, heure, nombre de passagers | Évite l'appel du matin à la gare pour savoir ce qu'il conduit | Vue filtrée sur `voyage_instances.chauffer_id` (le lien existe déjà) |
| **Déclaration départ / arrivée** — heure réelle, kilométrage | Alimente la ponctualité **et** la consommation sans aucune ressaisie : la même saisie sert à l'exploitation et au chef de parc | `heure_depart_reel`, `heure_arrivee_reel`, `km_depart`, `km_arrivee` |
| **Signalement d'incident en route** — panne, accident, contrôle routier, route coupée, géolocalisé | L'exploitation et la gare d'arrivée apprennent le problème pendant le trajet, pas à l'arrivée du car. C'est ce qui permet d'organiser un transbordement | Table `incidents` (§ 6.1) + position |
| **Ses documents et échéances** — permis, visite médicale, avec rappel | `Document` est déjà polymorphe sur `Chauffer` ; `DocumentRappel` existe | Rien — vue à construire |

**Extension**

| Fonctionnalité | Apport | Ce qui manque au modèle |
|---|---|---|
| **Déclaration de plein carburant** avec photo du justificatif | Rapproche le bon de carburant de la consommation réelle | Table `pleins` (§ 6.4) |
| **Liste des passagers de son départ** (lecture seule) | Appel nominatif avant départ, utile en cas d'accident | Vue restreinte au manifeste |
| **Historique personnel** — km parcourus, ponctualité, incidents | Base objective d'une prime ou d'un entretien d'évaluation | Agrégations |

**Permissions ajoutées** : `chauffeur.feuilleRoute.view`, `voyage.instance.declareDepart`
(partagée avec le chef de gare), `voyage.instance.declareArrivee`, `incident.create`,
`reseau.carburant.create`, `reseau.document.view.own`.

### 6.10 Chantiers transverses — ce qui débloque plusieurs rôles à la fois

Cinq objets de données reviennent dans plusieurs fiches. Les livrer tôt sert quatre à six
rôles d'un coup ; les livrer tard rend chaque fiche incomplète.

| Objet | Rôles servis | Pourquoi il revient partout |
|---|---|---|
| **`gare_user` + scope « gare »** | chef_gare, guichetier, agent_embarquement, bagagiste, chef_caisse | Sans portée géographique, la moitié des `◐` des matrices du § 5 sont inapplicables |
| **`audit_logs`** | auditeur, DG, chef_caisse, comptable, service_client | Toute action sensible du § 7.3 en dépend ; sans lui, le RBAC n'est pas contrôlable |
| **`incidents`** | chef_gare, exploitation, chauffeur, bagagiste, service_client | Le même événement concerne la gare, l'exploitation, le client et parfois l'assurance |
| **Déclaration des temps réels** (`heure_depart_reel`, `km_depart` / `km_arrivee`) | exploitation, chef_gare, chef_parc, chauffeur | Une saisie unique alimente la ponctualité, la consommation et le coût au kilomètre |
| **Imputation polymorphe des dépenses** (`depenses.imputable`) | chef_parc, comptable, chef_gare, DG | Une dépense non imputée à un véhicule, une gare ou un départ ne devient jamais un coût analytique |

### 6.11 Priorisation proposée

| Priorité | Chantier | Rôles débloqués | Effort |
|---|---|---|---|
| **P1** | `gare_user` + scope gare + tableau de bord de gare | chef_gare, guichetier, agent_embarquement | Moyen |
| **P1** | `audit_logs` + rapports d'exception | auditeur, DG, chef_caisse | Moyen |
| **P1** | Versements en banque + validation des écarts | chef_caisse, comptable | Faible |
| **P1** | Manifeste imprimable + registre des départs réels | chef_gare, exploitation, chauffeur | Faible |
| **P2** | Module bagages (les paramètres existent déjà) | bagagiste, chef_caisse | Moyen |
| **P2** | Détection des conflits d'affectation + alerte de sur-capacité | exploitation | Faible |
| **P2** | Carnet d'entretien + échéancier des pièces véhicule | chef_parc | Moyen |
| **P2** | Annonces de service ciblées (les canaux existent déjà) | communication, service_client | Faible |
| **P2** | Réclamations + workflow de remboursement | service_client, comptable | Moyen |
| **P3** | Suivi carburant + coût au kilomètre | chef_parc, DG | Moyen |
| **P3** | Fret / messagerie de colis | bagagiste | Élevé |
| **P3** | Liste d'attente, yield management, prévision d'encaisse | exploitation, chef_caisse | Élevé |
| **P3** | Compte chauffeur (option B) | chauffeur | Moyen |

Quatre chantiers P1 à effort faible ou moyen ouvrent six rôles sur quinze, et trois d'entre
eux ne demandent aucune nouvelle table métier. Deux chantiers P2 — bagages et annonces —
se contentent d'exploiter des paramètres et des canaux **déjà présents dans le code** et
aujourd'hui inutilisés.

---

## 7. Règles transverses

### 7.1 Cloisonnement

| Axe | Règle | Mise en œuvre |
|---|---|---|
| Compagnie | Aucune lecture ni écriture hors de `compagnie_id` de l'utilisateur, hors rôles plateforme | Global scope sur tous les modèles portant `compagnie_id` (modèle : `Caisse::booted()`) |
| Gare | `chef_gare`, `guichetier`, `agent_embarquement`, `bagagiste` bornés à leurs gares d'affectation | Table `gare_user` + scope dédié |
| Propriété | Une session de caisse n'est modifiable que par son titulaire | `caisses.user_id` |
| Temporalité | Une session clôturée est immuable ; un ticket validé ne redevient pas vendable | Vérification d'état avant toute écriture |

### 7.2 Séparation des pouvoirs — cumuls interdits

Ces combinaisons doivent être refusées à l'attribution, pas seulement déconseillées :

| Rôle A | Rôle B | Raison |
|---|---|---|
| `guichetier` | `chef_caisse` | Le vendeur validerait ses propres écarts de caisse |
| `guichetier` | `comptable` | Encaissement et écriture comptable sur le même compte |
| `agent_embarquement` | `comptable` | L'agent arbitrerait les conflits qu'il a lui-même générés |
| `guichetier` | `agent_embarquement` | Vendre et contrôler le même siège : siège revendu non détectable |
| `comptable` | `auditeur` | Un auditeur qui écrit n'audite plus |
| tout rôle compagnie | `platform_admin` / `root` | Un employé de compagnie ne doit jamais porter un rôle plateforme |

Règles supplémentaires, applicatives :

- **Quatre-yeux financier** : `finance.depense.approve` et `finance.remboursement.approve`
  sont refusées sur une pièce dont `created_by` est l'utilisateur courant.
- **Rang** : on ne peut attribuer qu'un rôle de rang strictement inférieur au sien.
  Un `chef_gare` ne crée pas de `chef_gare`.
- **Auto-administration** : personne ne modifie ses propres rôles, ni ne lève sa propre
  suspension.

### 7.3 Journalisation obligatoire

Les actions suivantes doivent produire une entrée d'audit immuable (acteur, horodatage,
IP, valeurs avant/après) :

`platform.user.impersonate` · `platform.compagnie.suspend` · `compagnie.role.assign` ·
`compagnie.parametres.updateAdvanced` · `guichet.ticket.cancel` · `guichet.ticket.reprint` ·
`guichet.ticket.unblock` · `caisse.session.forceClose` · `caisse.ecart.validate` ·
`finance.*.delete` · `finance.depense.approve` · `finance.remboursement.approve` ·
`crm.fidelite.adjust` · `embarquement.conflit.resolve`

---

## 8. Mise en œuvre technique proposée

### 8.1 Schéma

```sql
-- Rôles : système (gabarits) ou propres à une compagnie
ALTER TABLE roles
  ADD COLUMN compagnie_id BIGINT UNSIGNED NULL,       -- NULL = rôle système
  ADD COLUMN scope ENUM('platform','compagnie') NOT NULL DEFAULT 'compagnie',
  ADD COLUMN rang SMALLINT NOT NULL DEFAULT 0,        -- hiérarchie d'attribution
  ADD COLUMN is_system BOOLEAN NOT NULL DEFAULT 0,
  ADD COLUMN description TEXT NULL,
  DROP INDEX roles_name_unique,
  ADD UNIQUE KEY roles_name_compagnie_unique (name, compagnie_id);

CREATE TABLE permissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) UNIQUE NOT NULL,        -- domaine.ressource.action
  domaine VARCHAR(40) NOT NULL,
  label VARCHAR(160) NOT NULL,
  description TEXT NULL,
  scope ENUM('platform','compagnie','client') NOT NULL,
  is_sensitive BOOLEAN NOT NULL DEFAULT 0,  -- impose la journalisation
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);

CREATE TABLE permission_role (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  portee ENUM('all','compagnie','gare','own') NOT NULL DEFAULT 'compagnie',
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Affectation d'un agent à une ou plusieurs gares
CREATE TABLE gare_user (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  gare_id BIGINT UNSIGNED NOT NULL,
  is_principale BOOLEAN NOT NULL DEFAULT 0,
  UNIQUE KEY (user_id, gare_id)
);

-- Dérogation ponctuelle : permission accordée ou retirée à un compte
CREATE TABLE permission_user (
  user_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  accordee BOOLEAN NOT NULL DEFAULT 1,     -- 0 = retrait explicite
  expire_at TIMESTAMP NULL,
  PRIMARY KEY (user_id, permission_id)
);
```

La colonne `role_user` gagne `compagnie_id` pour supporter un jour la multi-appartenance,
même si le cas n'est pas ouvert immédiatement.

`portee` dans `permission_role` est la traduction SQL du `◐` des matrices : la même
permission peut être accordée avec un rayon différent selon le rôle.

### 8.2 API applicative

```php
// Un seul point d'entrée, contre une permission — jamais contre un rôle
$user->hasPermission('guichet.ticket.cancel');            // bool
$user->hasPermission('guichet.ticket.cancel', $ticket);    // + portée

// Gate dynamique enregistré une fois pour toutes
Gate::before(fn (User $user, string $ability) =>
    $user->isRoot() ? true : null
);

foreach (Permission::pluck('name') as $name) {
    Gate::define($name, fn (User $user, mixed $sujet = null) =>
        $user->hasPermission($name, $sujet)
    );
}
```

Les permissions effectives d'un compte sont mises en cache (`cache()->tags(...)`) et
invalidées sur toute mutation de `role_user`, `permission_role` ou `permission_user`.

### 8.3 Points d'application

| Couche | Action |
|---|---|
| Routes panneau compagnie | Ajouter `->middleware('can:<permission>')` sur **chacune** des 32 routes de `routes/web.php:190-243` |
| Navigation | Généraliser la clé `can` du tableau `$compagnieNav` à tous les items (aujourd'hui seul `Conflits` la porte) |
| Composants Livewire | `authorize()` dans `mount()` **et** dans chaque action d'écriture — un composant Livewire est un point d'entrée HTTP à part entière |
| API agent | Contrôler `embarquement.app.login` dans `AgentAuthController::login()` ; attacher les abilities au jeton Sanctum (`createToken($name, $abilities)`) |
| API v2 client | Vérifier que les permissions `client.*` ne fuient pas vers les comptes compagnie |
| Policies | Remplacer les tests `in_array($user->role, [Admin, Root])` par des permissions nommées |

---

## 9. Plan de migration

| Lot | Contenu | Risque |
|---|---|---|
| **0** | Corriger les trois bugs bloquants : `CompanyRole::Directeur`, `$user->company_role`, liste des rôles exposée par `UserManager` | Faible, à faire immédiatement |
| **1** | Tables `permissions`, `permission_role`, `permission_user`, `gare_user` + extension de `roles`. Seeder du catalogue de permissions et des 19 rôles gabarits. Aucun contrôle activé. | Faible — additif |
| **2** | `HasPermissions` sur `User`, enregistrement dynamique des gates, cache. Tests Pest sur la résolution de permissions et de portées. | Faible |
| **3** | Migration des données : mapping des rôles existants (§ 10), attribution des gares. Script idempotent et rejouable. | Moyen — vérifier compte par compte |
| **4** | Application des contrôles en **mode observation** : on journalise les refus sans bloquer, pendant deux semaines d'exploitation réelle. | Faible, révèle les trous |
| **5** | Blocage effectif : middlewares `can:`, `authorize()` Livewire, filtrage de la navigation, contrôle du login agent. | Élevé — prévoir une procédure de déblocage d'urgence |
| **6** | Écran d'administration des rôles compagnie, journal d'audit, contrôles de cumul interdit. | Faible |
| **7+** | Ouverture des fonctionnalités métier par rôle, dans l'ordre du § 6.11. Chaque livraison ajoute ses permissions au catalogue, sans toucher au moteur. | Variable |

Les lots 0 à 6 rendent l'habilitation **correcte** ; le lot 7 la rend **utile**. Les deux
moitiés sont indépendantes : le moteur de permissions n'attend aucune fonctionnalité
nouvelle, et quatre des chantiers P1 du § 6.11 — manifeste imprimable, registre des départs
réels, versements en banque, validation des écarts — peuvent être livrés avant le lot 5 à
droits inchangés.

Le lot 4 n'est pas une précaution de confort : aucune des 32 routes du panneau n'étant
filtrée aujourd'hui, l'usage réel des écrans par chaque métier est inconnu. Bloquer sans
l'avoir mesuré, c'est arrêter des guichets en production.

---

## 10. Annexe — correspondance avec l'existant

| Existant | Cible | Note |
|---|---|---|
| `UserRole::Root` (`Super User`) | `root` | — |
| `UserRole::Admin` | `platform_admin` | — |
| `UserRole::User` | `client` | — |
| `UserRole::CompagnieBosse` | `compagnie_dg` | Corrige au passage la faute « Companie Bosse » |
| `CompanyRole::Admin` (`company_admin`) | `compagnie_admin` | — |
| `CompanyRole::Agent` (`agent`) | `agent_embarquement` | Lever l'ambiguïté : « agent » désignait indifféremment le guichet et le quai |
| `CompanyRole::Caisse` (`caisse`) | `guichetier` | Le rôle couvrait de fait la vente |
| *(aucun)* | `chef_caisse` | Nouveau — porteur du contrôle de caisse |
| `CompanyRole::Comptabilite` | `comptable` | — |
| `CompanyRole::RH` | `rh` | — |
| `CompanyRole::Bagagiste` | `bagagiste` | — |
| *(aucun)* | `exploitation`, `chef_gare`, `chef_parc`, `communication`, `service_client`, `auditeur`, `chauffeur` | Nouveaux |
| `CompanyRole::Directeur` | — | **N'a jamais existé** : référence fantôme à supprimer de `CompagniePolicy` |

### Décisions à arbitrer

1. **`users.role` reste-t-il ?** Proposition : le conserver comme discriminant de surface
   (plateforme / compagnie / client) et déplacer toute la finesse dans `role_user`. Deux
   sources de vérité sur la même question sont la cause des bugs du § 2.2.
2. **Granularité de la portée « gare »** — l'ouvrir dès le lot 1, ou se contenter du
   cloisonnement compagnie en première version ? Une compagnie mono-agence ne la nécessite
   pas ; TCV, STAF ou Rakieta, oui.
3. **Rôle `chauffeur`** — compte applicatif nominatif, ou simple fiche de personnel sans
   accès ? Le modèle `Chauffer` est aujourd'hui une fiche, pas un compte.
4. **Plafonds paramétrables** — les `◐` adossés à un seuil (`voyage.tarif.update`,
   `crm.fidelite.adjust`, `finance.depense.create`) supposent de nouvelles clés dans
   `compagnie_settings`. À cadrer avec `CompagnieSettingKey`.
