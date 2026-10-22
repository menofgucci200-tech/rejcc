# REJCC — Dashboard

Administration générale du REJCC et espaces des 11 départements du Bureau
exécutif, sur `dashboard.rejcc.site` (Laravel + Livewire). Aucune base de
données : tout passe par l'API (`REJCC-Backend`).

- **Connexion** : `/connexion`, réservée aux comptes d'administration
  (rôle « admin »). Un compte membre est refusé et renvoyé vers `rejcc.site`.
- **Administrateur général** (accès complet) : toutes les rubriques et la page
  « Départements ».
- **Compte de département** (`bureau_departement`) : arrive dans l'espace de
  son département (`/departements/{slug}`) et ne voit que ses rubriques.
  Les départements et leurs rubriques par défaut sont définis dans l'API
  (`App\Support\Departements`).

## Développement local

```bash
composer install && npm install && npm run build
cp .env.example .env   # puis adapter les adresses et les chemins partagés
php artisan serve --port=8031
```

Variables propres au dashboard : `SITE_URL` (liens vers le site),
`SHARED_PRIVATE_ROOT`, `SHARED_UPLOADS_ROOT`, `SHARED_UPLOADS_URL` (fichiers
du site) et `APP_KEY`, identique à celle de `REJCC-Frontend`.
