# REJCC — Architecture découplée

Ce dépôt contient deux applications Laravel indépendantes, communiquant
exclusivement en HTTP :

- **[`REJCC-Backend/`](REJCC-Backend)** — API REST pure (Laravel 12 + MySQL,
  auth par token Bearer maison). Aucune vue, aucun Livewire : uniquement des
  contrôleurs API et des modèles Eloquent.
- **[`REJCC-Frontend/`](REJCC-Frontend)** — site web (vitrine, espace membre,
  admin) en Blade + Livewire. **N'a aucun accès direct à une base de
  données** : toutes les données transitent par `App\Support\Api`, un client
  HTTP vers `REJCC-Backend`.

Chaque application est déployée comme un service Render distinct (voir
`render.yaml`). Le frontend doit connaître l'URL publique du backend via la
variable d'environnement `BACKEND_API_URL`.

Pour le développement local, voir le README de chaque sous-projet.
