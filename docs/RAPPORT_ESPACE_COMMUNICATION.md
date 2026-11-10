# Rapport : Espace Communication et CathoImpact (nuit du 10 au 11 novembre 2026)

**État :** tout est développé, testé localement et relu. **Rien n'est commité, poussé ni déployé** : j'attends ton feu vert.

- **Tests automatiques :** backend 402 réussis (311 au départ, +91), frontend 6 réussis (nouveau dossier de tests).
- **Vérifications :** chaque écran a été ouvert dans le navigateur, sur ordinateur et sur téléphone (375 px), avec des comptes de test Communication, Formation et administrateur général.
- **Revue finale :** une relecture indépendante du code a trouvé et corrigé 6 défauts (détail en section 9).

---

## 1. Sécurité : corrigé en premier

| Problème | Gravité | Correction |
|---|---|---|
| Toute personne ayant la rubrique « Contenu » (Communication, Juridique, Informatique, comptes de test) pouvait **lire et remplacer les clés de paiement CinetPay** dans Réglages. Remplacer ces clés détournait l'encaissement des abonnements | Critique | Les clés sont réservées à l'administrateur général : carte masquée, et refus 403 côté API |
| La route publique des réglages publiait le **jeton Google Meet chiffré**, l'adresse du compte, les mots interdits de la Communauté et les réglages des certificats | Élevée | Liste blanche : seuls les réglages utiles à la vitrine sont publics |
| La Médiathèque montrait à la Communication les **CV des candidats** et les **pièces jointes des inscriptions aux événements**, et permettait de les supprimer | Élevée | Ces dossiers sont exclus |
| Les comptes de test pouvaient envoyer messages, annulations ou reports **à de vrais inscrits** d'événements, et « annoncer à tous les membres » | Moyenne | Bloqué dès qu'un vrai inscrit existe |
| Les SVG téléversés publiquement permettaient d'injecter du script | Moyenne | SVG retiré des téléversements publics ; aucun usage n'en dépendait |

## 2. CathoImpact (priorité n°1, attendu aujourd'hui)

**Page publique `/cathoimpact`** :
- description et carrousel qui défile, sans changement ;
- nouvelle section **« Ils ont inspiré »** : cartes cliquables (image, nom, accroche, réseau) qui ouvrent la publication sur Instagram, LinkedIn, Facebook, TikTok ou YouTube. Pas de page portrait complète, comme demandé. La section reste invisible tant qu'aucune carte n'est publiée.

**Questionnaire simplifié** (« ratisser large »), en 2 étapes :
- le profil : entrepreneur ou professionnel ;
- un seul formulaire :
  - nom, e-mail, WhatsApp ;
  - entreprise et activité, ou poste et entreprise ; secteur, ville ;
  - « Votre parcours en quelques lignes » ;
  - « Qu'est-ce qui rend votre parcours inspirant ? » ;
  - lien LinkedIn ou page, facultatif ;
  - **une photo facultative**, compressée dans le téléphone avant l'envoi ;
  - **case de consentement obligatoire** (publication sur les réseaux et le site).

Supprimés : la date de naissance, la question « à ne pas divulguer » et les 5 à 10 photos. L'écran de remerciement annonce le questionnaire détaillé (Google Forms) si le parcours est retenu.

**Suivi dans le dashboard** (page CathoImpact) :
- liste avec filtres, recherche et fiche lisible : photo, consentement daté, historique ;
- 9 statuts : Reçu → Retenu → Questionnaire détaillé envoyé → Rempli → Attribué à un community manager → En création → Publié, plus Non retenu et Non publié ;
- pour chaque réponse : community manager attribué, lien du Google Form, lien de la publication, note interne ;
- pastille **« À traiter »** dans le menu ;
- **récapitulatif par e-mail chaque lundi à 8 h** à l'équipe Com, rien s'il n'y a rien de neuf ;
- onglet **« Galerie du site »** : ajouter, ordonner ou masquer les cartes « Ils ont inspiré ».

## 3. Espace Communication : le cockpit

Le compte d'un membre de la Com arrive sur son espace, qui comporte :
- **7 chiffres du mois** : nouveaux membres et adhésions, activité de la Communauté, CathoImpact reçues et publiées, visites du site (avec l'évolution), demandes en attente, inscriptions aux événements, publications publiées sur prévues. Comme demandé, ni abonnés newsletter ni abonnés par réseau ;
- **« À faire aujourd'hui »** : tour en cours, publications du jour et du lendemain avec un bouton « Marquer publié », retards, demandes urgentes, réponses CathoImpact à examiner ;
- **accès rapides** aux 10 outils, puis aux rubriques existantes : Actualités, Événements, Communauté, Médiathèque, Réglages…

Une nouvelle rubrique de droits, **« Espace Communication »**, est donnée automatiquement aux comptes du département.

## 4. Calendrier éditorial (priorité n°2) : contre les oublis de publication

- **Chaque publication prévue** a une date, un sujet, un type, un format (vidéo courte, carrousel…), des canaux et un responsable.
  - Les canaux se cochent tout seuls selon leur plan de diffusion.
  - Le statut va de prévu à publié, non publié ou annulé, et le lien de la publication est conservé.
- **Affichage :** grille du mois (liste par jour sur téléphone), vue liste, filtres, **retards en rouge**.
- **Tours de rôle :** un assistant génère les blocs du mois (lundi→mercredi, jeudi→samedi…) en alternant les membres choisis. En haut s'affiche « Cette semaine : X ». Une publication sans responsable prend celui du tour.
- **Rappels automatiques par e-mail :**
  - la veille à 18 h, au responsable ;
  - le jour même à 8 h, au responsable ;
  - à 9 h, à toute l'équipe, ce qui n'a pas été publié la veille.
- **Import de leur fichier Excel** (.xlsx ou CSV) avec aperçu et erreurs ligne par ligne, modèle téléchargeable, export, reprise du mois précédent.
- **Bilan mensuel** imprimable ou en PDF : régularité par responsable (qui publie quand c'est son tour), publications par canal et par type, CathoImpact, chiffres du réseau, visites du site, demandes des départements.

## 5. Demandes des autres départements (fin des demandes par WhatsApp)

- **Bouton « Demander une communication »** dans l'espace de **chaque** département.
  - Le formulaire comprend un type, un message, la date souhaitée, les canaux (pré-cochés selon le plan) et jusqu'à 3 pièces jointes.
  - Si la date tombe dans moins de 7 jours, un avertissement rappelle le délai de 1 à 2 semaines, sans bloquer l'envoi.
  - Le demandeur suit l'avancement de sa demande et peut l'annuler avant acceptation.
- **Côté Com :**
  - liste triée par urgence ;
  - statuts : nouvelle → acceptée → planifiée → publiée, ou refusée avec un motif obligatoire ;
  - réponse au demandeur ;
  - **« Planifier » crée directement la publication dans le calendrier**. La marquer publiée clôt la demande, dans les deux sens.
- **E-mails :** à l'équipe Com à chaque nouvelle demande, et au demandeur à chaque étape.
- **Plan de diffusion** affiché en tableau, avec l'encadré : « le canal Annonces = celui de la Communauté sur la plateforme, pas WhatsApp ».

## 6. Mesure d'audience

- **Compteur de visites** du site vitrine : **anonyme, sans cookie, sans adresse IP conservée**, donc aucune bannière de consentement n'est nécessaire.
  - Il compte les pages vues, les visiteurs du jour, les pages les plus vues, les provenances (Facebook, Google…) et la part téléphone/ordinateur.
  - Coût : environ 1 ms par page.
- **Liens de suivi** `rejcc.site/l/code` : un lien par canal (WhatsApp, Facebook…) pour savoir lequel ramène du monde. Les clics sont comptés, et les robots d'aperçu ne le sont pas.
- **Page « Statistiques du site »** : graphique sur 30 jours ou par mois, comparaison avec la période précédente.

## 7. Boîte à outils

- **Boîte à outils de marque :**
  - repères de la charte (couleurs à copier, polices, slogan, tailles minimales du logo) ;
  - logos officiels à télécharger ;
  - espace privé pour leurs documents (PDF de marque, gabarits, liens Canva).
- **Générateur de visuels**, sans IA, avec des gabarits sobres dans la charte : événement, « C'est demain ! », citation ou verset, bienvenue, portrait CathoImpact, annonce. Trois formats (post, carré, story), photo de fond possible, **export PNG**.
- **Textes prêts à copier** : 24 textes par défaut (présentations, bios, hashtags, messages types, mentions de consentement), avec compteur de caractères.
- **Contacts médias :** fichier presse avec recherche et export CSV.

## 8. Décisions que j'ai prises (tu m'as dit de trancher)

1. **Nom du réseau :** « Jeunes Chrétiens Catholiques » partout, comme tu l'as confirmé. Leur PDF d'identité de marque est à corriger.
2. **Photos CathoImpact :** une seule photo facultative sur le site. Les 5 à 10 photos seront demandées dans le Google Form, **qui devra reprendre la case de consentement**.
3. **Pages du site :** la Com garde l'accès (même droit que Réglages et Médiathèque). Ce n'est pas dangereux, donc je n'ai pas séparé les droits.
4. **Documents de marque :** ils sont rangés à part, et non dans la rubrique « Documents », qui est visible des membres.
5. **Comptes de test :** ils ne voient ni les vraies réponses CathoImpact, ni les vrais contacts médias, ni les vraies demandes. Ils ne modifient que ce qu'ils ont créé (liens, publications, tours). Ils ne déclenchent aucun e-mail vers la vraie équipe.
6. **Bilan en PDF :** il passe par l'impression du navigateur, sans nouvelle librairie.
7. **Hors périmètre :** la newsletter et la galerie photos existent déjà. Je n'ai pas fait de circuit de validation, puisque seule la Com valide. Pas de suivi des abonnés par réseau non plus : ils l'ont jugé inutile.

## 9. Défauts trouvés et corrigés pendant les tests et la revue

- **Faille XSS** dans la boîte à outils de marque : un faux fichier image pouvait exécuter du script. Désormais, l'aperçu est réservé aux PDF et aux images.
- **Comptes de test** : ils pouvaient modifier les vrais liens de suivi déjà diffusés, ou effacer les tours et les publications réels.
- **E-mails déclenchés par des testeurs** vers la vraie équipe (rappels, nouvelles demandes).
- **Injection de formules** dans les exports CSV.
- **Bugs fonctionnels :**
  - méthode en double (erreur fatale) ;
  - conflit de nom qui provoquait une erreur 500 dans le calendrier ;
  - pastille du cockpit non mise à jour ;
  - débordements sur téléphone ;
  - textes enrichis des fichiers Excel mal lus ;
  - « Formation · Formation » affiché en double ;
  - sel de la mesure d'audience non ignoré par git.

## 10. Points restés ouverts (à décider)

1. **Les CV et pièces d'inscription aux événements** sont stockés dans un dossier public, accessible à qui connaît l'adresse (nom aléatoire). Il faudrait les déplacer vers le stockage privé. C'est risqué sans accès à la production, donc je ne l'ai pas fait.
2. **Les comptes de test peuvent modifier les vraies cartes « Ils ont inspiré »**, comme ils le font déjà pour les actualités.
3. **`test:nettoyer`** efface les données des testeurs mais pas leurs fichiers physiques (photos, pièces jointes).
4. **Page Confidentialité** : elle est en base, donc je n'y ai pas touché. Paragraphe à coller dans Pages légales :
   > « Mesure d'audience. Pour connaître la fréquentation du site, nous comptons les pages consultées de façon anonyme et sans cookie. Aucune adresse IP ni aucun identifiant n'est conservé : une empreinte technique éphémère, renouvelée chaque jour puis détruite, sert seulement à ne pas compter deux fois un même visiteur dans la journée. Les chiffres agrégés sont gardés au plus 400 jours. »
5. **À obtenir de la Com :**
   - les noms et e-mails de l'équipe (pour créer les comptes) ;
   - leur fichier Excel ;
   - la confirmation qu'ils utilisent Instagram.

## 11. Mise en production (sur ton « déploie »)

1. Commit, push, puis `./deploy.sh` (les assets du site et du dashboard sont déjà compilés).
2. `php artisan migrate --force` sur le backend, pour les 7 migrations du 2026_11_10 :
   - `cathoimpact_candidatures` ;
   - `cathoimpact_portraits` ;
   - la rubrique Communication aux comptes Com ;
   - les outils ;
   - les liens de suivi ;
   - les demandes ;
   - le calendrier.
3. Vérifier dans les `.env` : `MAIL_ADMIN_EMAIL` et `DASHBOARD_URL` (backend), et `SHARED_PRIVATE_ROOT` (dashboard, même dossier que le site).
4. Droits d'écriture sur `REJCC-Frontend/storage/app/private/statistiques` et `storage/framework/audience`.
5. Aucun cron à ajouter. Le cron hPanel existant déclenche :
   - les rappels du calendrier, toutes les 10 minutes ;
   - le récapitulatif CathoImpact, le lundi à 8 h.
   Le premier jour, vérifier dans les journaux que les rappels partent bien.
6. Après la mise en ligne :
   - créer les comptes de l'équipe Com dans l'espace du département ;
   - téléverser les 2 PDF de marque ;
   - coller le paragraphe Confidentialité ;
   - visiter le site et vérifier que les statistiques se remplissent.
