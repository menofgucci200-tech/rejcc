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

## Mises à jour futures

Après chaque `git push` sur `main`, en SSH pour chaque site :

```bash
cd ~/domains/<site>/repo-.../REJCC-<Backend|Frontend>
git pull origin main
composer install --no-dev --optimize-autoloader   # si composer.json a changé
php artisan migrate --force                        # si une migration a été ajoutée
php artisan config:clear && php artisan config:cache
npm run build                                       # frontend uniquement, si les assets ont changé
```

Ceci reste manuel sur ce pack Business (pas d'automatisation façon Docker) —
si l'app passe un jour sur un VPS Hostinger, le `Dockerfile` +
`docker-entrypoint.sh` déjà présents dans chaque service pourront être
réutilisés tels quels.
