# Déploiement LIPTRA sur o2switch (cPanel) — Guide serveur

Ce document liste **tout ce qu'il faut configurer sur le serveur o2switch** pour que
le backend Laravel LIPTRA fonctionne complètement : déploiement, `.env`, **cron**,
**queue (file d'attente)**, **temps réel (Reverb/chat)**, stockage et maintenance.

> Remplacez partout :
> - `USER` → votre identifiant cPanel o2switch (ex. `liptr1234`)
> - `liptra.net` → votre domaine réel
> - `/home/USER/laravel` → le dossier où vous déposez le code Laravel
> - `php8.3` → la version PHP CLI choisie (voir §1)

---

## 0. Vue d'ensemble (ce qui DOIT tourner)

| Élément | Obligatoire ? | Comment sur o2switch |
|---|---|---|
| Application web (HTTP) | ✅ | Apache + document root sur `/public` |
| **Cron du planificateur** (`schedule:run`) | ✅ | 1 tâche cron chaque minute (§6) |
| **Worker de file d'attente** (`queue:work`) | ✅ | 1 tâche cron chaque minute (§7) — les notifications/mails/push sont en file d'attente |
| Base de données MySQL | ✅ | cPanel → Bases de données MySQL |
| Stockage public (`storage:link`) | ✅ | photos chauffeurs, etc. (§4) |
| **Reverb / WebSockets** (chat temps réel) | ⚠️ Optionnel | difficile en hébergement mutualisé (§8) |
| SMTP (mails) | ✅ pour mails/rapports | cPanel → Comptes de messagerie (§3) |
| SMS Twilio / Push Expo | Optionnel | clés API dans `.env` (§3) |

---

## 1. Prérequis PHP

1. cPanel → **Sélectionner une version de PHP** (PHP Selector / MultiPHP) → choisir **PHP 8.2 ou 8.3** (le projet exige `php >= 8.2`).
2. Activer les **extensions** suivantes (cocher dans le même écran) :
   ```
   bcmath  ctype  curl  fileinfo  gd  intl  json  mbstring
   openssl  pdo  pdo_mysql  tokenizer  xml  zip
   ```
   - `gd` → génération des PDF (DomPDF) et images
   - `zip` → exports Excel (maatwebsite/excel)
   - `intl` → formats de dates/nombres
3. Repérer le **chemin du binaire PHP CLI** (indispensable pour les crons). Sur o2switch (CloudLinux), c'est en général :
   ```
   /opt/alt/php83/usr/bin/php      (PHP 8.3)
   /opt/alt/php82/usr/bin/php      (PHP 8.2)
   ```
   Vérifiez via **cPanel → Terminal** :
   ```bash
   which php ; php -v
   ```
   Utilisez ce chemin exact dans toutes les commandes cron ci-dessous (remplace `php8.3`).

---

## 2. Déploiement du code

Via **cPanel → Terminal** (ou Git Version Control) :

```bash
cd /home/USER
git clone <URL_DU_REPO> laravel
cd laravel

# Composer (o2switch fournit composer)
composer install --no-dev --optimize-autoloader
```

> Si `composer` n'est pas trouvé : `php8.3 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader`
> (ou téléchargez `composer.phar` dans le projet).

---

## 3. Configuration `.env`

Copiez `.env.example` → `.env` puis éditez. **Valeurs importantes pour la production :**

```dotenv
APP_NAME=LIPTRA
APP_ENV=production
APP_DEBUG=false
APP_URL=https://liptra.net
APP_DOMAIN=liptra.net

# --- Base de données (créée dans cPanel → MySQL) ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=USER_liptra
DB_USERNAME=USER_liptra
DB_PASSWORD=********

# --- Sessions / cache / file d'attente ---
SESSION_DRIVER=database
SESSION_DOMAIN=.liptra.net      # point initial = partagé entre sous-domaines
CACHE_STORE=database
QUEUE_CONNECTION=database        # ⚠️ nécessite le worker cron (§7)

# --- Mail (SMTP o2switch) ---
MAIL_MAILER=smtp
MAIL_HOST=mail.liptra.net        # ou le serveur SMTP indiqué par o2switch
MAIL_PORT=465
MAIL_ENCRYPTION=ssl              # 465=ssl, 587=tls
MAIL_USERNAME=no-reply@liptra.net
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS="no-reply@liptra.net"
MAIL_FROM_NAME="LIPTRA"

# --- SMS Twilio (optionnel) ---
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_PHONE_NUMBER=

# --- Temps réel (chat). 'log' = désactivé. Voir §8 pour activer Reverb ---
BROADCAST_CONNECTION=log
```

> Créez d'abord la base et l'utilisateur MySQL dans **cPanel → Bases de données MySQL**, puis renseignez `DB_*`.
> Créez l'adresse `no-reply@liptra.net` dans **cPanel → Comptes de messagerie** pour le SMTP.

---

## 4. Commandes d'initialisation (une fois, dans Terminal)

```bash
cd /home/USER/laravel

php8.3 artisan key:generate          # génère APP_KEY
php8.3 artisan migrate --force       # crée/maj les tables (--force obligatoire en prod)
php8.3 artisan storage:link          # lien symbolique storage → public (photos)

# Caches de production (à refaire après CHAQUE déploiement / changement .env)
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
php8.3 artisan event:cache
```

> (Optionnel, première mise en service) Marquer les comptes existants comme vérifiés :
> `php8.3 artisan users:verify-existing`

---

## 5. Sous-domaines & document root

L'application utilise plusieurs sous-domaines (voir `routes/web.php`). Dans
**cPanel → Domaines / Sous-domaines**, créez-les et faites **pointer leur racine
documentaire (Document Root) vers le même dossier `/home/USER/laravel/public`** :

| Domaine | Rôle |
|---|---|
| `liptra.net` | Site client (racine) |
| `app.liptra.net` | Liens/redirections app |
| `admin.liptra.net` | Espace administration |
| `compagnie.liptra.net` | Espace compagnie (panel gérant/agent) |

> Le domaine principal `liptra.net` doit aussi avoir son Document Root sur `…/public`.
> L'**API mobile** est servie via `routes/api.php` sur ces mêmes domaines (préfixe `/api`).
> Activez **AutoSSL / Let's Encrypt** (cPanel → SSL/TLS Status) sur chaque sous-domaine.

---

## 6. CRON — Le planificateur Laravel (OBLIGATOIRE)

**Une seule tâche cron** pilote TOUTES les tâches planifiées de l'application.
cPanel → **Tâches Cron (Cron Jobs)** → ajouter :

- **Fréquence :** chaque minute → `* * * * *`
- **Commande :**

```bash
* * * * * cd /home/USER/laravel && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Ce cron déclenche automatiquement, aux bonnes heures, les tâches définies dans
`routes/console.php` :

| Tâche planifiée | Commande Artisan | Quand |
|---|---|---|
| Génération des instances de voyage (7 jours) | `voyages:generate-instances --days=7` | chaque jour à **22:00** |
| Annulation des tickets non payés (>24 h) | `tickets:clean-expired --hours=24` | **chaque heure** |
| Rapport **journalier** par email (gérants/admins) | `reports:send daily` | chaque jour à **20:00** |
| Rapport **hebdomadaire** par email | `reports:send weekly` | **lundi 07:00** |
| Rapport **mensuel** par email | `reports:send monthly` | **1er du mois 07:00** |
| Rappels de départ (push + email, ~2 h avant) | `notifications:departure-reminders --hours=2` | **toutes les 15 min** |

> ⚠️ N'ajoutez **pas** ces commandes une par une dans cPanel : seul `schedule:run`
> est nécessaire, Laravel gère le reste (heures, anti-chevauchement).

---

## 7. CRON — Worker de file d'attente (OBLIGATOIRE)

`QUEUE_CONNECTION=database` : les **mails, notifications push (Expo) et certains
traitements** sont mis en file d'attente (`ShouldQueue`). Sans worker, **ces emails
et notifications ne partiront jamais**.

En hébergement mutualisé, on ne lance pas de démon permanent : on utilise un cron
qui vide la file puis s'arrête. cPanel → **Tâches Cron** → ajouter :

- **Fréquence :** chaque minute → `* * * * *`
- **Commande :**

```bash
* * * * * cd /home/USER/laravel && /opt/alt/php83/usr/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> storage/logs/queue.log 2>&1
```

- `--stop-when-empty` : le process se termine quand la file est vide (pas de démon).
- `--max-time=55` : garde-fou, ne dépasse pas la minute.
- `--tries=3` : 3 tentatives avant de marquer le job en échec.

> **Après chaque déploiement de code**, relancez le worker pour qu'il prenne le nouveau code :
> `php8.3 artisan queue:restart`
>
> **Alternative simple (si vous ne voulez pas de worker) :** mettre `QUEUE_CONNECTION=sync`
> dans `.env`. Les jobs s'exécutent alors immédiatement pendant la requête web —
> plus simple, mais les pages qui envoient un mail/push seront **plus lentes**.
> Recommandé : garder `database` + le cron worker ci-dessus.

---

## 8. Temps réel / Chat (Reverb) — optionnel

L'app contient des events `ShouldBroadcast` (chat, mises à jour de tickets en direct)
via **Laravel Reverb** (WebSockets). Reverb nécessite un **processus permanent qui
écoute un port** — ce qui est **compliqué/non garanti en mutualisé o2switch**.

Trois options :

### Option A — Désactiver le temps réel (par défaut, recommandé en mutualisé)
Laisser `BROADCAST_CONNECTION=log` dans `.env`.
→ Le reste de l'application fonctionne normalement ; le chat ne se met juste pas à
jour « en direct » (il faut rafraîchir). **Aucune action serveur requise.**

### Option B — Pusher hébergé (temps réel sans démon)
Le projet inclut déjà `pusher/pusher-php-server`. Créez un compte Pusher Channels,
puis dans `.env` :
```dotenv
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=eu
```
→ Pas de processus à maintenir côté serveur. Idéal pour le mutualisé.

### Option C — Reverb auto-hébergé (si o2switch autorise un process long + port)
```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=liptra.net
REVERB_PORT=8080
REVERB_SCHEME=https
```
Maintenir le process via un cron « keep-alive » (relance s'il est tombé) :
```bash
* * * * * cd /home/USER/laravel && flock -n storage/reverb.lock /opt/alt/php83/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080 >> storage/logs/reverb.log 2>&1
```
> Nécessite que le port soit ouvert/reverse-proxifié — **à confirmer avec le support o2switch**.
> En cas de doute, préférez l'option B (Pusher).

---

## 9. Permissions des dossiers

```bash
cd /home/USER/laravel
chmod -R 775 storage bootstrap/cache
```
(Le propriétaire est déjà votre utilisateur cPanel ; pas de `chown` nécessaire.)

---

## 10. Procédure de mise à jour (déploiements suivants)

À refaire à chaque nouvelle version du code :

```bash
cd /home/USER/laravel
git pull
composer install --no-dev --optimize-autoloader
php8.3 artisan migrate --force

# Rafraîchir les caches
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
php8.3 artisan event:cache

# Prendre en compte le nouveau code dans le worker
php8.3 artisan queue:restart
```

---

## 11. Vérifications & dépannage

```bash
# Voir les tâches planifiées et leur prochaine exécution
php8.3 artisan schedule:list

# Tester une tâche manuellement
php8.3 artisan voyages:generate-instances --days=7
php8.3 artisan reports:send daily
php8.3 artisan notifications:departure-reminders --hours=2

# File d'attente
php8.3 artisan queue:work --stop-when-empty   # traiter maintenant
php8.3 artisan queue:failed                    # jobs en échec
php8.3 artisan queue:retry all                 # relancer les échecs

# Logs
tail -n 100 storage/logs/laravel.log
tail -n 100 storage/logs/queue.log
```

**Symptômes courants**
- *Les mails/rapports/push ne partent pas* → le **cron queue (§7)** est manquant, ou `queue:restart` non fait après déploiement. Vérifier `queue:failed` et `storage/logs/laravel.log`.
- *Les instances de voyage ne se génèrent pas / tickets non nettoyés* → le **cron schedule:run (§6)** est manquant ou pointe sur le mauvais binaire PHP. Vérifier `schedule:list`.
- *Erreur 500 après déploiement* → relancer `config:cache route:cache view:cache` ; vérifier `APP_KEY` et droits sur `storage/`.
- *Images/PDF cassés* → `php artisan storage:link` non exécuté, ou extension `gd`/`zip` désactivée.
- *Page blanche / route inconnue sur un sous-domaine* → Document Root du sous-domaine ne pointe pas sur `/public`.

---

## 12. Récapitulatif des CRON à créer dans cPanel

| # | Fréquence | Commande |
|---|---|---|
| 1 | `* * * * *` | `cd /home/USER/laravel && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1` |
| 2 | `* * * * *` | `cd /home/USER/laravel && /opt/alt/php83/usr/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> storage/logs/queue.log 2>&1` |
| 3 (opt. C) | `* * * * *` | `cd /home/USER/laravel && flock -n storage/reverb.lock /opt/alt/php83/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080 >> storage/logs/reverb.log 2>&1` |

> **Crons 1 et 2 = indispensables.** Le cron 3 uniquement si vous activez le temps réel auto-hébergé (sinon Option A ou B au §8).
