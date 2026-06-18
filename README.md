# REJCC API — Backend Laravel

API REST du REJCC (Laravel 12 + MySQL). Reçoit et enregistre les soumissions
du site (adhésions, contacts, newsletter, partenariats) et prépare les paiements.

> Projet **distinct** du site Next.js (`C:\projet\REJCC`) et du backend d'un autre
> projet (`EPCCI_BACKEND`, non utilisé ici).

## Prérequis
- PHP 8.2+ et Composer (présents via XAMPP)
- MySQL (XAMPP) démarré

## Installation / démarrage
```bash
# Dépendances (déjà installées au scaffold)
composer install

# Base de données : créer la base 'rejcc' puis migrer
#   (la base 'rejcc' a déjà été créée)
php artisan migrate

# Lancer le serveur de dev
php artisan serve --host=127.0.0.1 --port=8000
```
Sous Windows/XAMPP, utilisez `C:\xampp\php\php.exe artisan ...` si `php` n'est pas dans le PATH.

## Configuration (`.env`)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rejcc
DB_USERNAME=root
DB_PASSWORD=
```

## Endpoints
| Méthode | URL | Rôle |
|---|---|---|
| GET | `/api/health` | Vérification de service |
| POST | `/api/adhesion` | Adhésion → tables `members` + `payments` |
| POST | `/api/contact` | Message de contact → `contacts` |
| POST | `/api/newsletter` | Inscription newsletter → `newsletter_subscribers` |
| POST | `/api/partenariat` | Demande de partenariat → `partnership_requests` |
| POST | `/api/auth/register` | Inscription membre → `users` + renvoie un token |
| POST | `/api/auth/login` | Connexion → renvoie un token |
| GET | `/api/auth/me` | Membre connecté (Bearer token) |
| PUT | `/api/auth/profile` | Mise à jour du profil (Bearer) |
| POST | `/api/auth/logout` | Déconnexion / révocation du token (Bearer) |
| GET | `/api/members` | Annuaire des membres (Bearer) |

Réponses JSON : `{ "ok": true, ... }` ou `{ "ok": false, "message": "..." }` (422/401).

**Authentification** : par **token** (table `api_tokens`, SHA-256). Après `register`/`login`,
le client envoie le token reçu dans l'en-tête `Authorization: Bearer <token>` sur les routes protégées.

## Schéma de base
- **members** : adhésions (profil, identité, secteur, paiement, `statut`, `reference`)
- **payments** : un paiement par adhésion (`provider` wave/orange/djamo, `amount` 10000, `status` pending)
- **contacts**, **newsletter_subscribers**, **partnership_requests**

## Connexion avec le site Next.js
Le site relaie automatiquement vers cette API **si** la variable d'environnement
`REJCC_API_URL` est définie côté Next.js (Vercel) :
```
REJCC_API_URL=https://api.rejcc.ci   # URL publique du backend une fois hébergé
```
Sans cette variable, le site fonctionne en autonomie (validation + accusé de réception).

## À venir (quand disponibles)
- **Paiement réel** Wave / Orange Money / Djamo (identifiants marchands requis) — à brancher dans `AdhesionController` + une route webhook de confirmation.
- **E-mails** transactionnels (confirmation d'adhésion, accusés).
- **Authentification** (espace membre, Phase 3) — Laravel Sanctum.
- **Hébergement** PHP/MySQL à définir.
