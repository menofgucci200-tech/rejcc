# Déploiement sur Hostinger (pack Business)

Ce guide explique comment mettre en ligne les deux services de l'application
(`REJCC-Backend` et `REJCC-Frontend`) sur un hébergement **Business** Hostinger
(mutualisé/cloud, sans accès root ni Docker — contrairement à Render), avec le
nom de domaine **rejcc.site**.

## Vue d'ensemble

Le pack Business inclut 50 « sites web », ce qui permet d'héberger les deux
services comme deux sites distincts sur le même compte :

| Service | Adresse | Contenu |
|---|---|---|
| REJCC-Frontend | `rejcc.site` (domaine principal) | Le site public + espace membre + admin |
| REJCC-Backend | `api.rejcc.site` (sous-domaine) | L'API pure, jamais visitée directement |

Chaque service reste un site Hostinger séparé, avec son propre déploiement Git,
son propre `.env` et sa propre base de données MySQL.

## Étape 0 — Connecter le nom de domaine à Hostinger

Si `rejcc.site` a été acheté **ailleurs que chez Hostinger** (ou même chez
Hostinger mais pas encore associé à l'hébergement), il faut d'abord le relier :

1. Dans `hPanel → Domaines → Ajouter un domaine`, ajouter `rejcc.site` à
   l'abonnement Business.
2. Si le domaine est enregistré chez un autre registrar : mettre à jour les
   **serveurs de noms (nameservers)** chez ce registrar pour pointer vers ceux
   de Hostinger (indiqués dans `hPanel → Domaines → rejcc.site → Serveurs de
   noms`, généralement `ns1.dns-parking.com` / `ns2.dns-parking.com` ou
   équivalent — Hostinger affiche les valeurs exactes à utiliser).
3. La propagation DNS peut prendre de quelques minutes à 24-48h. Pas la peine
   d'avancer sur les étapes suivantes tant que `rejcc.site` ne résout pas déjà
   vers l'IP Hostinger (`ping rejcc.site` ou un site comme whatsmydns.net).

## Prérequis à vérifier dans hPanel

- **Version PHP 8.2 ou supérieure** pour chacun des deux sites
  (`hPanel → [site] → Avancé → PHP Configuration`).
- **Une base de données MySQL par service** (`hPanel → Bases de données`) —
  ne pas partager la même base entre les deux, ce sont deux applications
  distinctes.
- **Accès SSH activé** (`hPanel → [site] → Avancé → Accès SSH`) — nécessaire
  pour lancer Composer et les commandes `artisan`.

## Étape 1 — Créer les deux sites

1. Domaine principal `rejcc.site` → pointer vers le dossier du frontend.
2. Sous-domaine `api.rejcc.site` (`hPanel → Domaines → Sous-domaines`) →
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
cd ~/domains/rejcc.site
git clone https://github.com/menofgucci200-tech/rejcc.git repo-frontend

cd ~/domains/api.rejcc.site
git clone https://github.com/menofgucci200-tech/rejcc.git repo-backend
```

## Étape 3 — Faire pointer `public_html` vers le bon dossier `public/`

Laravel doit servir son dossier `public/`, pas la racine du projet. Depuis le
terminal SSH de chaque site :

```bash
# Site frontend (rejcc.site)
cd ~/domains/rejcc.site
rm -rf public_html
ln -s repo-frontend/REJCC-Frontend/public public_html

# Site backend (api.rejcc.site)
cd ~/domains/api.rejcc.site
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
cd ~/domains/rejcc.site/repo-frontend/REJCC-Frontend
composer install --no-dev --optimize-autoloader
cp .env.example .env   # puis éditer les valeurs ci-dessous
php artisan key:generate
```

### `.env` du backend (`REJCC-Backend`)

```env
APP_NAME="REJCC API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.rejcc.site
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

`DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` : valeurs données par Hostinger
au moment de créer la base dans `hPanel → Bases de données` — jamais des
valeurs à réutiliser d'ailleurs.

`ADMIN_EMAIL` / `ADMIN_PASSWORD` : à choisir soi-même, c'est le tout premier
compte admin qui sera créé automatiquement (voir Étape 5). Une fois connecté,
il est possible d'en créer d'autres depuis Admin → Membres.

### `.env` du frontend (`REJCC-Frontend`)

```env
APP_NAME=REJCC
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rejcc.site
APP_LOCALE=fr

SESSION_DRIVER=database
CACHE_STORE=database

BACKEND_API_URL=https://api.rejcc.site/api
```

`BACKEND_API_URL` est la variable la plus importante : c'est elle qui permet
au frontend de trouver l'API (voir `REJCC-Frontend/app/Support/Api.php`).
Ne pas oublier le `/api` à la fin.

## Étape 5 — Migrations et build des assets

```bash
# Backend
cd ~/domains/api.rejcc.site/repo-backend/REJCC-Backend
php artisan migrate --force
php artisan app:seed-if-empty   # crée le premier compte admin (ADMIN_EMAIL/ADMIN_PASSWORD du .env)
php artisan config:cache
php artisan route:cache

# Frontend
cd ~/domains/rejcc.site/repo-frontend/REJCC-Frontend
php artisan migrate --force
php artisan config:cache
php artisan route:cache
npm install && npm run build   # si Node.js est disponible en SSH
```

Si Node.js n'est pas disponible côté serveur, générer `public/build/` en
local (`npm run build`) et l'uploader manuellement (FTP/gestionnaire de
fichiers hPanel) dans `repo-frontend/REJCC-Frontend/public/build/`, car
c'est le seul artefact que Hostinger ne peut pas construire lui-même.

## Étape 6 — Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

À faire pour les deux sites (backend et frontend).

## Étape 7 — Vérification

- `https://api.rejcc.site/api/auth/login` (en POST) doit répondre en JSON
  (une erreur de validation `{"ok":false,...}` est normale sans identifiants
  — c'est le signe que l'API répond bien).
- `https://rejcc.site/connexion` doit afficher la page de connexion et
  réussir à contacter l'API (se connecter avec `ADMIN_EMAIL`/`ADMIN_PASSWORD`
  du `.env` backend).
- Après connexion, vérifier `https://rejcc.site/admin`.
- Vérifier que les certificats SSL (HTTPS) sont bien actifs sur les deux
  sites (`hPanel → [site] → Sécurité → SSL` — Hostinger fournit un certificat
  Let's Encrypt gratuit, à activer si ce n'est pas déjà automatique).

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
