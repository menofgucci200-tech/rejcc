# Campagne de test sur le site réel

Les départements du REJCC (et quelques membres) testent **rejcc.site
lui-même** avec des comptes de test. Les bugs trouvés sont donc ceux que
rencontreraient les vrais membres, et on les corrige à la source. Leurs
suggestions aident à décider s'il faut un espace dédié par département.

## Ce que voit un compte de test

- Un liseré orangé en haut de chaque page et une pastille **« Mode test ·
  Département »** en bas de l'écran.
- Le bouton **« Signaler · Suggérer »** : bug ou erreur, amélioration, besoin
  du département, question. La page et le navigateur sont joints
  automatiquement.
- Sur une page d'erreur (page introuvable, erreur serveur) : **« Signaler
  cette erreur »**.

Les vrais membres et les administrateurs habituels ne voient rien de tout
cela.

## Règles à donner aux testeurs

1. **Tout ce que vous faites est réel** : un projet, une annonce, une
   inscription à un événement sont visibles des vrais membres.
2. **Commencez vos titres par `[TEST]`** (projets, annonces, offres,
   événements, actualités, formations, documents, albums). Ils seront
   supprimés à la fin de la campagne.
3. Ne contactez pas de vrais membres sans raison (messagerie, mentorat).
4. Au moindre doute ou comportement étrange : **« Signaler · Suggérer »**.

## Créer les comptes

1. Remplir un CSV sur le modèle de [`comptes-test-modele.csv`](comptes-test-modele.csv)
   (Excel : « Enregistrer sous → CSV (séparateur : point-virgule) ») :

   | Colonne | Contenu |
   |---|---|
   | `departement` | Nom du département (regroupe ses retours) |
   | `prenom`, `nom`, `email` | La personne qui teste |
   | `acces` | `membre` (espace membre) ou `admin` (administration) |
   | `sections` | Pour un `admin` : rubriques autorisées, séparées par `\|`. Vide = toutes celles ouvertes aux testeurs. |

   Conseil : **deux comptes par département**, un `membre` et un `admin`
   limité à ses rubriques. L'e-mail doit être différent pour chacun
   (ex. `prenom.nom+membre@…`).

   Rubriques ouvertes aux comptes de test : `membres`, `formations`,
   `evenements`, `projets`, `communaute` (Marketplace), `certificats`,
   `opportunites` (emploi), `mentors`, `groupes`, `actualites`, `contenu`
   (pages du site), `documents`, `contacts`, `messagerie`, `partenariats`,
   `audit`, `suggestions`.

   **Toujours refusées** aux comptes de test, même pour un admin :
   `newsletter`, `notifications` et `salons` (aucun envoi de masse aux vrais
   membres : la gestion des salons publie des annonces à toute la
   Communauté) et `adhesions` (aucune décision sur de vraies candidatures).

2. Sur le serveur :

   ```bash
   scp -P 65002 -i ~/.ssh/hostinger_deploy comptes.csv u777115762@147.79.103.230:~/comptes.csv
   ssh -p 65002 -i ~/.ssh/hostinger_deploy u777115762@147.79.103.230 \
     'cd ~/apps/rejcc/REJCC-Backend && php artisan test:comptes ~/comptes.csv; rm ~/comptes.csv'
   ```

   La commande contrôle tout le fichier avant de créer quoi que ce soit,
   puis affiche les mots de passe (du type `Rejcc-K7P4-2958`), aussi
   enregistrés dans `storage/app/private/identifiants-test-*.csv` sur le
   serveur. Relancée, elle met les comptes à jour **sans changer les mots de
   passe** (`--nouveaux-mots-de-passe` pour les régénérer). Elle refuse de
   toucher un vrai compte de la plateforme.

## Suivre les retours

**Administration → Support & système → Retours des testeurs** : filtre par
département et par statut (Nouveau, À corriger, Corrigé, À étudier, Retenu,
Écarté), réponse ou note interne, export **CSV**. Les nouveaux retours
apparaissent aussi dans la cloche « À traiter ».

## Fin de la campagne

```bash
ssh -p 65002 -i ~/.ssh/hostinger_deploy u777115762@147.79.103.230
cd ~/apps/rejcc/REJCC-Backend && php artisan test:nettoyer
```

La commande affiche d'abord ce qu'elle va supprimer et demande confirmation :

- les comptes de test, et avec eux tout ce qu'ils ont créé (projets,
  annonces, offres, documents proposés, inscriptions, messages privés et
  messages postés dans les salons de la Communauté, réactions, votes, avis,
  mentorats, certificats…) ;
- les contenus dont le titre commence par `[TEST]` (événements, actualités,
  formations, albums… créés depuis l'administration).

Les retours des testeurs (bugs, suggestions) sont **conservés**, rattachés à
leur département, ainsi que le journal d'audit. Faire l'export CSV des
retours avant, par sécurité.
