# Déploiement sur Hostinger (pack Business)

Ce guide explique comment mettre en ligne les deux services de l'application
(`REJCC-Backend` et `REJCC-Frontend`) sur un hébergement **Business** Hostinger
(mutualisé/cloud, sans accès root ni Docker — contrairement à Render).

## Vue d'ensemble

Le pack Business inclut 50 « sites web », ce qui permet d'héberger les deux
services comme deux sites distincts sur le même compte :

| Service | Adresse suggérée | Contenu |
|---|---|---|
| REJCC-Frontend | `rejcc.org` (domaine principal) | Le site public + espace membre + admin |
| REJCC-Backend | `api.rejcc.org` (sous-domaine) | L'API pure, jamais visitée directement |

Chaque service reste un site Hostinger séparé, avec son propre déploiement Git,
son propre `.env` et sa propre base de données MySQL.

## Prérequis à vérifier dans hPanel

- **Version PHP 8.2 ou supérieure** pour chacun des deux sites
  (`hPanel → [site] → Avancé → PHP Configuration`).
- **Une base de données MySQL par service** (`hPanel → Bases de données`) —
  ne pas partager la même base entre les deux, ce sont deux applications
  distinctes.
- **Accès SSH activé** (`hPanel → [site] → Avancé → Accès SSH`) — nécessaire
  pour lancer Composer et les commandes `artisan`.

## Étape 1 — Créer les deux sites

1. Domaine principal `rejcc.org` → pointer vers le dossier du frontend.
2. Sous-domaine `api.rejcc.org` (`hPanel → Domaines → Sous-domaines`) →
   pointer vers le dossier du backend.

## Étape 2 — Déployer le code (Git)

Dans `hPanel → Avancé → Git`, pour **chaque site** :

- Dépôt : `https://github.com/menofgucci200-tech/rejcc.git`
- Branche : `main`
- Répertoire d'installation : un dossier **hors de `public_html`**, par
  exemple `repo-frontend/` pour le site frontend et `repo-backend/` pour le
  site backend (chaque site clone donc l'intégralité du monorepo dans son
  propre dossier — c'est volontairement redondant mais plus simple à
  maintenir que de partager un seul clone entre les deux sites).

Si l'option Git de hPanel ne permet pas de cloner en dehors de
`public_html`, cloner directement via SSH à la place :

```bash
cd ~/domains/rejcc.org
git clone https://github.com/menofgucci200-tech/rejcc.git repo-frontend

cd ~/domains/api.rejcc.org
git clone https://github.com/menofgucci200-tech/rejcc.git repo-backend
```

## Étape 3 — Faire pointer `public_html` vers le bon dossier `public/`

Laravel doit servir son dossier `public/`, pas la racine du projet. Depuis le
terminal SSH de chaque site :

```bash
# Site frontend (rejcc.org)
cd ~/domains/rejcc.org
rm -rf public_html
ln -s repo-frontend/REJCC-Frontend/public public_html

# Site backend (api.rejcc.org)
cd ~/domains/api.rejcc.org
rm -rf public_html
ln -s repo-backend/REJCC-Backend/public public_html
```

> Si Hostinger ne permet pas de supprimer `public_html` (dossier protégé),
> utiliser l'option « Modifier le dossier racine du site » dans
> `hPanel → Domaines → [sous-domaine] → Modifier`, en indiquant directement
> le chemin `repo-frontend/REJCC-Frontend/public` — cela évite le symlink.

## Étape 4 — Installer les dépendances et configurer chaque `.env`

Pour **chaque** site (adapter les chemins) :

```bash
cd ~/domains/rejcc.org/repo-frontend/REJCC-Frontend
composer install --no-dev --optimize-autoloader
cp .env.example .env   # puis éditer les valeurs ci-dessous
php artisan key:generate
```

### `.env` du backend (`REJCC-Backend`)

```env
APP_NAME="REJCC API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.rejcc.org
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<nom_base_hostinger>
DB_USERNAME=<utilisateur_hostinger>
DB_PASSWORD=<mot_de_passe>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack

ADMIN_EMAIL=<email_admin_initial>
ADMIN_PASSWORD=<mot_de_passe_temporaire>
```

### `.env` du frontend (`REJCC-Frontend`)

```env
APP_NAME=REJCC
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rejcc.org
APP_LOCALE=fr

SESSION_DRIVER=database
CACHE_STORE=database

BACKEND_API_URL=https://api.rejcc.org/api
```

`BACKEND_API_URL` est la variable la plus importante : c'est elle qui permet
au frontend de trouver l'API (voir `REJCC-Frontend/app/Support/Api.php`).
Ne pas oublier le `/api` à la fin.

## Étape 5 — Migrations et build des assets

```bash
# Backend
cd ~/domains/api.rejcc.org/repo-backend/REJCC-Backend
php artisan migrate --force
php artisan app:seed-if-empty   # crée le premier compte admin (ADMIN_EMAIL/ADMIN_PASSWORD du .env)
php artisan config:cache
php artisan route:cache

# Frontend
cd ~/domains/rejcc.org/repo-frontend/REJCC-Frontend
php artisan migrate --force
php artisan config:cache
php artisan route:cache
npm install && npm run build   # si Node.js est disponible en SSH
```

Si Node.js n'est pas disponible côté serveur, générer `public/build/` en
local (`npm run build`) et le committer/uploader manuellement, car c'est le
seul artefact que Hostinger ne peut pas construire lui-même.

## Étape 6 — Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

## Étape 7 — Vérification

- `https://api.rejcc.org/api/auth/login` (en POST) doit répondre en JSON.
- `https://rejcc.org/connexion` doit afficher la page de connexion et
  réussir à contacter l'API.
- Vérifier `https://rejcc.org/admin` après connexion avec un compte admin.

## Mises à jour futures

Après chaque `git push` sur `main`, pour chaque site :

```bash
cd ~/domains/<site>/repo-.../REJCC-<Backend|Frontend>
git pull origin main
composer install --no-dev --optimize-autoloader   # si composer.json a changé
php artisan migrate --force                        # si une migration a été ajoutée
php artisan config:clear && php artisan config:cache
npm run build                                       # frontend uniquement, si les assets ont changé
```

Ceci reste manuel sur ce pack (pas d'équivalent au script Docker qui migrait
automatiquement sur Render) — si un jour l'app passe sur un VPS Hostinger,
l'architecture Docker actuelle (`Dockerfile` + `docker-entrypoint.sh` dans
chaque service) pourra être réutilisée telle quelle.
