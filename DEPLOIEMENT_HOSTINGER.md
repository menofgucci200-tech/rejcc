# Déploiement sur Hostinger (pack Business) — guide détaillé, clic par clic

Ce guide explique comment mettre en ligne les deux services de l'application
(`REJCC-Backend` et `REJCC-Frontend`) sur l'hébergement **Business** Hostinger,
avec le nom de domaine **rejcc.site**.

> **Note d'honnêteté :** je n'ai pas d'accès visuel direct à ton compte
> Hostinger. Les noms de menus et boutons ci-dessous correspondent à la
> structure actuelle et documentée de hPanel, mais Hostinger fait parfois de
> petits changements d'interface. Si un intitulé exact ne correspond pas,
> cherche l'équivalent le plus proche — et si tu bloques vraiment sur un
> écran, envoie-moi une capture, je t'aiderai à repérer où cliquer.

## Vue d'ensemble

| Service | Adresse | Contenu |
|---|---|---|
| REJCC-Frontend | `rejcc.site` (domaine principal) | Site public + espace membre + admin |
| REJCC-Backend | `api.rejcc.site` (sous-domaine) | API pure, jamais visitée directement |

---

## Étape 0 — Connecter le domaine rejcc.site à Hostinger

1. Connecte-toi sur **hpanel.hostinger.com**.
2. Dans le menu de gauche, clique sur **Domaines**.
3. Clique sur **Ajouter un domaine** (bouton en haut à droite en général).
4. Choisis « J'ai déjà un domaine » et saisis `rejcc.site`, puis suis les
   instructions pour l'associer à ton abonnement Business.
5. Si `rejcc.site` a été acheté **ailleurs que chez Hostinger** : va dans
   **Domaines → rejcc.site → Serveurs de noms (Nameservers)**. Hostinger
   affiche là deux adresses du type `ns1.dns-parking.com` /
   `ns2.dns-parking.com`. Il faut aller les renseigner chez le registrar où le
   domaine a été acheté (dans la section « DNS » ou « Serveurs de noms » de
   ce site-là), à la place des serveurs actuels.
6. Patiente — la propagation peut prendre de quelques minutes à 48h. Tu peux
   vérifier sur whatsmydns.net en tapant `rejcc.site` que ça pointe bien vers
   l'IP Hostinger avant de continuer.

---

## Étape 1 — Créer le sous-domaine api.rejcc.site

1. Dans hPanel, clique sur **Sites web** dans le menu de gauche.
2. À côté de `rejcc.site`, clique sur **Gérer** (ou **Tableau de bord**).
3. Dans le menu de gauche de la page qui s'ouvre, cherche **Domaines** puis
   clique sur **Sous-domaines**.
4. Dans le champ « Sous-domaine », tape `api`.
5. Laisse le dossier par défaut proposé (on le changera à l'étape 4), et
   clique sur **Créer**.
6. Attends quelques minutes que `api.rejcc.site` devienne actif.

---

## Étape 2 — Vérifier PHP, activer SSH, créer les 2 bases de données

**Version PHP** (à faire pour le site principal ET si possible pour le
sous-domaine, selon la version proposée par hPanel) :
1. `Sites web → rejcc.site → Gérer`.
2. Menu de gauche → **Avancé → PHP Configuration**.
3. Vérifier/sélectionner **PHP 8.2** ou plus récent, puis **Sauvegarder**.

**Activer SSH :**
1. Toujours dans `rejcc.site → Gérer`, menu de gauche → **Avancé → Accès
   SSH**.
2. Basculer l'interrupteur sur **Activé**.
3. Définir un mot de passe SSH si demandé (à conserver précieusement, ne pas
   le partager en clair).
4. Noter l'**hôte SSH** et le **port** affichés (souvent `ssh.hostinger.com`
   et un port du type `65002`) — ils serviront à se connecter en terminal.

**Créer les 2 bases de données MySQL :**
1. Menu de gauche → **Bases de données → Gestion des bases de données**
   (ou juste « Bases de données MySQL »).
2. Section « Créer une nouvelle base de données » : donner un nom
   (ex. `u123_rejcc_backend`), créer un utilisateur associé et un mot de
   passe (Hostinger génère souvent tout automatiquement — bien copier le nom
   de la base, le nom d'utilisateur et le mot de passe affichés, ils ne sont
   montrés qu'une fois).
3. Recommencer pour une **deuxième** base (ex. `u123_rejcc_frontend`) —
   une base par service, ne pas partager la même base entre les deux.

---

## Étape 3 — Se connecter en SSH et cloner le dépôt

Depuis un terminal (PowerShell, Terminal macOS, ou un client SSH) :

```bash
ssh <ton_utilisateur>@ssh.hostinger.com -p <le_port_noté_plus_haut>
```
(mot de passe = celui défini à l'étape 2 pour SSH)

Une fois connecté :

```bash
cd ~/domains/rejcc.site
git clone https://github.com/menofgucci200-tech/rejcc.git repo-frontend

cd ~/domains/api.rejcc.site
git clone https://github.com/menofgucci200-tech/rejcc.git repo-backend
```

> Alternative sans terminal : `Sites web → rejcc.site → Gérer → Avancé →
> Git`, coller l'URL du dépôt `https://github.com/menofgucci200-tech/rejcc.git`,
> choisir la branche `main`, et surtout changer le **répertoire cible**
> proposé par défaut (`public_html`) pour `repo-frontend` (et `repo-backend`
> pour l'autre site). Cliquer sur **Créer/Déployer**.

---

## Étape 4 — Faire pointer public_html vers le dossier public/ de Laravel

Toujours en SSH :

```bash
cd ~/domains/rejcc.site
rm -rf public_html
ln -s repo-frontend/REJCC-Frontend/public public_html

cd ~/domains/api.rejcc.site
rm -rf public_html
ln -s repo-backend/REJCC-Backend/public public_html
```

> Si `rm -rf public_html` refuse de s'exécuter (dossier protégé), utiliser
> plutôt : `Sites web → [site] → Gérer → Domaines → rejcc.site → Modifier`,
> chercher un champ **Racine du document / Dossier racine du site**, et
> indiquer directement `domains/rejcc.site/repo-frontend/REJCC-Frontend/public`
> (chemin équivalent pour le backend) — ça évite le symlink.

---

## Étape 5 — Installer les dépendances et configurer les `.env`

En SSH, pour le **backend** :

```bash
cd ~/domains/api.rejcc.site/repo-backend/REJCC-Backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
```

Dans `nano` (`Ctrl+O` puis Entrée pour sauvegarder, `Ctrl+X` pour quitter),
renseigner :

```env
APP_NAME="REJCC API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.rejcc.site
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<nom de la base backend créée à l'étape 2>
DB_USERNAME=<utilisateur créé à l'étape 2>
DB_PASSWORD=<mot de passe créé à l'étape 2>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack

ADMIN_EMAIL=<ton email admin>
ADMIN_PASSWORD=<un mot de passe temporaire de ton choix>
```

Puis générer la clé de chiffrement propre à cet environnement :

```bash
php artisan key:generate
```

Répéter pour le **frontend** :

```bash
cd ~/domains/rejcc.site/repo-frontend/REJCC-Frontend
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
```

```env
APP_NAME=REJCC
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rejcc.site
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<nom de la base frontend créée à l'étape 2>
DB_USERNAME=<utilisateur créé à l'étape 2>
DB_PASSWORD=<mot de passe créé à l'étape 2>

SESSION_DRIVER=database
CACHE_STORE=database

BACKEND_API_URL=https://api.rejcc.site/api
```

```bash
php artisan key:generate
```

`BACKEND_API_URL` est la variable la plus importante côté frontend : sans
elle (ou avec une mauvaise valeur), le site ne pourra jamais parler à l'API.

---

## Étape 6 — Migrations, compte admin, cache

```bash
# Backend
cd ~/domains/api.rejcc.site/repo-backend/REJCC-Backend
php artisan migrate --force
php artisan app:seed-if-empty
php artisan config:cache
php artisan route:cache
chmod -R 775 storage bootstrap/cache

# Frontend
cd ~/domains/rejcc.site/repo-frontend/REJCC-Frontend
php artisan migrate --force
php artisan config:cache
php artisan route:cache
chmod -R 775 storage bootstrap/cache
```

Pour construire les fichiers CSS/JS du frontend, deux options :

- **Si Node.js est disponible en SSH** (vérifier avec `node -v`) :
  ```bash
  npm install && npm run build
  ```
- **Sinon**, sur ta machine locale :
  ```bash
  npm run build
  ```
  puis envoyer le dossier `public/build/` généré vers le serveur via
  `Sites web → rejcc.site → Gérer → Fichiers → Gestionnaire de fichiers`
  (naviguer jusqu'à `repo-frontend/REJCC-Frontend/public/`, créer/uploader
  le dossier `build`).

---

## Étape 7 — Activer le HTTPS (SSL gratuit)

Pour **chacun des deux sites** :
1. `Sites web → [site] → Gérer`.
2. Menu de gauche → **Sécurité → SSL**.
3. Sélectionner le domaine/sous-domaine concerné dans la liste.
4. Cliquer sur **Installer le SSL** (certificat Let's Encrypt gratuit,
   renouvellement automatique).
5. Attendre 1-2 minutes que le statut passe à « Installé » / « Actif ».

---

## Étape 8 — Vérification finale

- Ouvrir `https://api.rejcc.site/api/auth/login` dans le navigateur : une
  page qui affiche du texte JSON (même une erreur du type
  `{"message":"The GET method is not supported..."}`) confirme que l'API
  répond bien.
- Ouvrir `https://rejcc.site/connexion`, se connecter avec l'`ADMIN_EMAIL` /
  `ADMIN_PASSWORD` du `.env` backend.
- Vérifier `https://rejcc.site/admin` après connexion.

---

## Mises à jour futures : `./deploy.sh`

Une fois l'installation initiale faite (étapes 0 à 8), chaque mise en ligne se
fait en **une seule commande**. Le script `deploy.sh`, à la racine du dépôt,
enchaîne toutes les étapes dans le bon ordre et s'arrête proprement au moindre
problème.

### Ce que fait le script

1. **Contrôles** : dossiers, version de PHP, Composer, Node.js, réglages
   indispensables des `.env` (`APP_KEY`, `BACKEND_API_URL`, `APP_DEBUG`,
   `CERTIFICATS_CLE_SIGNATURE`, `MAIL_MAILER`). Il refuse aussi de continuer
   si des fichiers ont été modifiés à la main sur le serveur.
2. **Sauvegarde** des deux bases de données et des deux `.env` dans
   `~/sauvegardes-rejcc/`. Les 10 dernières sauvegardes sont conservées.
3. **Page de maintenance** pendant la mise à jour, qui dure 1 à 2 minutes.
4. **Code** : `git pull` de la branche `main`, sans jamais écraser d'historique.
5. **Dépendances** : `composer install` dans les deux services.
6. **Migrations** : `php artisan migrate --force` dans les deux services.
7. **CSS/JS** : `npm run build`, sur le serveur si Node.js y est installé,
   sinon sur votre ordinateur, puis envoi automatique des fichiers.
8. **Caches Laravel**, lien `storage`, et sécurisation des anciennes pièces
   d'identité (`pieces:securiser`).
9. **Réouverture du site**. En cas d'erreur, le site est rouvert
   automatiquement.
10. **Tâche planifiée** (`schedule:run` chaque minute) : elle est ajoutée si
    elle manque. Si l'hébergeur refuse, le script affiche la commande à
    coller dans hPanel → Avancé → Tâches Cron.
11. **Vérification** que `rejcc.site` et `api.rejcc.site` répondent bien.

Un journal de chaque mise en ligne est gardé dans
`~/sauvegardes-rejcc/journaux/`.

### Option A : lancer depuis votre ordinateur (recommandé)

Une seule fois, dans le dossier du dépôt cloné sur votre ordinateur :

```bash
cp deploy.config.example deploy.config
```

Ouvrez `deploy.config` et renseignez `SSH_HOTE`, `SSH_PORT` et
`SSH_UTILISATEUR`. Ces valeurs se trouvent dans hPanel → Avancé → Accès SSH.

Ensuite, à chaque mise en ligne :

```bash
./deploy.sh
```

Le mot de passe SSH vous est demandé. Pour ne plus le saisir, installez une
fois votre clé SSH : `ssh-copy-id -p <port> <utilisateur>@<hôte>`, ou
hPanel → Accès SSH → Clés SSH.

Sous Windows, lancez la commande dans **Git Bash**, installé avec Git.

### Option B : lancer directement sur le serveur

```bash
ssh <utilisateur>@<hôte> -p <port>
cd ~/domains/rejcc.site/repo-frontend
git pull origin main
./deploy.sh
```

Sans fichier `deploy.config`, le script utilise les emplacements de ce guide.
Si Node.js n'est pas installé sur le serveur, il faut passer par l'option A.

### Options utiles

| Commande | Effet |
|---|---|
| `./deploy.sh --verifier` | Contrôle tout et montre ce qui serait mis en ligne, sans rien modifier. |
| `./deploy.sh --forcer` | Relance toutes les étapes, même si le serveur est déjà à jour. |
| `./deploy.sh --sans-maintenance` | Laisse le site ouvert pendant la mise à jour. |
| `./deploy.sh --aide` | Liste des options. |

### En cas de problème

- Le script affiche l'étape qui a échoué et le chemin du journal complet.
- Pour restaurer une base MySQL à partir de la sauvegarde faite juste avant :
  ```bash
  gunzip -c ~/sauvegardes-rejcc/<date>-backend.sql.gz | mysql -u <utilisateur> -p <base>
  ```
- Pour revenir au code précédent, le commit d'avant figure au début du
  journal :
  ```bash
  git -C <dépôt> reset --hard <commit>
  ```
  Relancez ensuite `composer install` et les caches, ou demandez-moi.

Le `Dockerfile` et le `docker-entrypoint.sh` de chaque service restent
utilisables tels quels si l'application passe un jour sur un VPS.
