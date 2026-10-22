# REJCC — Architecture découplée

Ce dépôt contient trois applications Laravel indépendantes, communiquant
exclusivement en HTTP :

- **[`REJCC-Backend/`](REJCC-Backend)** — API REST pure (Laravel 12 + MySQL,
  auth par token Bearer maison), sur `api.rejcc.site`. Aucune vue : uniquement
  des contrôleurs API et des modèles Eloquent. Seule application avec une base
  de données.
- **[`REJCC-Frontend/`](REJCC-Frontend)** — site public et espace membre, sur
  `rejcc.site` (Blade + Livewire).
- **[`REJCC-Dashboard/`](REJCC-Dashboard)** — administration générale et
  espaces des 11 départements du Bureau, sur `dashboard.rejcc.site`
  (Blade + Livewire). Connexion réservée aux comptes d'administration.

Le site et le dashboard n'ont **aucun accès direct à la base de données** :
toutes les données passent par `App\Support\Api`, un client HTTP vers
`REJCC-Backend` (`BACKEND_API_URL`). Le dashboard partage les fichiers du site
(`SHARED_PRIVATE_ROOT`, `SHARED_UPLOADS_ROOT`) et sa clé `APP_KEY`.

Mise en ligne : [`DEPLOIEMENT_HOSTINGER.md`](DEPLOIEMENT_HOSTINGER.md) et
`./deploy.sh`. Développement local : voir le README de chaque sous-projet.
