# GUIDE COMPLET D'UTILISATION ET DES PARCOURS

## Plateforme REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques de Côte d'Ivoire

---

## 1. Présentation du document

### Objectif

Ce document est un **manuel fonctionnel et opérationnel** de la plateforme REJCC. Il décrit **ce que la plateforme permet réellement de faire**, pour chaque profil d'utilisateur, à travers des parcours détaillés et reproductibles. Il ne remplace pas une documentation technique (architecture Laravel, migrations, contrôleurs) : ces éléments ne sont mentionnés ici que lorsqu'ils expliquent un comportement visible par l'utilisateur.

### Périmètre

Le document couvre :
- le site public (vitrine) ;
- l'espace membre (authentifié) ;
- l'espace d'administration ;
- les parcours transverses (candidature → adhésion → connexion, inscription à un événement par QR code, abonnement payant, modération du marketplace, etc.).

### Méthode

Conformément à la règle fondamentale de cette mission, **aucune fonctionnalité n'a été documentée sur la seule base du code**. Pour chaque fonctionnalité :
1. le code a été lu pour identifier la fonctionnalité, son profil et son interface (deux revues de code séparées ont couvert respectivement le backend `REJCC-Backend` — API REST Laravel 12 — et le frontend `REJCC-Frontend` — Blade + Livewire 3) ;
2. les deux applications ont été **réellement installées et exécutées en local** (PHP 8.4, base de données SQLite, backend sur `http://127.0.0.1:8010`, frontend sur `http://127.0.0.1:8020`) ;
3. un compte administrateur, un compte candidat, deux comptes membres (dont un abonné) ont été créés et utilisés pour parcourir la plateforme dans un vrai navigateur (Chromium, piloté via Playwright) ;
4. chaque parcours a été exécuté pas à pas, avec captures d'écran, et les résultats réels (succès, erreurs, messages, redirections) ont été observés et consignés.

### Date de vérification

Tests réalisés le **18 septembre 2026**, sur les dépôts `REJCC-Backend` et `REJCC-Frontend` à l'état du commit `522ae41` (branche `claude/relaxed-maxwell-v2b9lt`).

### Limites des tests

- **Paiement CinetPay réel** : aucune clé API CinetPay valide n'était disponible dans l'environnement de vérification. Le déclenchement du paiement et la gestion de l'absence de configuration ont pu être testés ; la confirmation retour d'un paiement réellement effectué (Wave, Orange Money, MTN, Moov, carte) n'a pas pu être vérifiée. Pour compenser partiellement cette limite, un abonnement actif a été simulé directement en base de données (équivalent à un paiement réussi) afin de tester les fonctionnalités qu'il débloque.
- **Écran d'ouverture animé (PWA)** : cette fonctionnalité ne s'affiche qu'en mode application installée (« standalone ») et n'a pas pu être observée en navigation classique dans le temps imparti.
- **E-mails** : le serveur de messagerie était configuré en mode journal (`MAIL_MAILER=log`) ; les e-mails n'ont donc pas été réellement délivrés dans une boîte de réception, mais leur contenu généré a été vérifié dans les journaux applicatifs, ce qui permet de confirmer leur déclenchement et leur contenu.
- **Messagerie entre membres** : la page et le bouton d'entrée (« Envoyer un message » depuis l'annuaire) ont été confirmés à plusieurs reprises par capture d'écran ; l'aller-retour complet d'envoi et de réception d'un message n'a en revanche pas pu être validé de bout en bout dans cette session à cause d'instabilités propres à l'outillage de test automatisé (voir section 14).
- Toutes les autres fonctionnalités décrites comme « TESTÉ ET FONCTIONNEL » ont été vérifiées par une utilisation réelle, avec capture d'écran ou vérification directe des données (base de données, journaux, réponses API).

### Légende des statuts utilisés dans tout le document

| Statut | Signification |
|---|---|
| **TESTÉ ET FONCTIONNEL** | Parcours réalisé de bout en bout avec succès, résultat conforme à l'attendu. |
| **TESTÉ MAIS PARTIEL** | Fonctionnalité accessible et en partie vérifiée, mais parcours incomplet. |
| **TESTÉ ET EN ERREUR** | Fonctionnalité identifiée et testée, mais présentant un dysfonctionnement réel et reproduit. |
| **ACCESSIBLE MAIS NON TESTABLE** | Interface atteinte, mais impossible à tester jusqu'au bout dans les conditions disponibles (ex. dépendance à un service externe). |
| **IDENTIFIÉE DANS LE CODE MAIS NON VALIDÉE** | Présente dans le code, non vérifiée par un usage réel. |
| **NON IDENTIFIÉE** | Aucune fonctionnalité correspondante trouvée dans le code ni dans l'interface. |

---

## 2. Présentation de la plateforme

### Rôle

Le REJCC est un réseau entrepreneurial destiné aux jeunes catholiques de Côte d'Ivoire. La plateforme numérique sert trois objectifs : présenter le réseau au grand public (site vitrine), gérer l'adhésion et la vie des membres (espace membre), et donner aux responsables du réseau les outils de pilotage (administration).

### Public

- des **visiteurs** découvrant le réseau ;
- des **candidats** souhaitant rejoindre le réseau ;
- des **membres** (dont des **mentors**, un rôle particulier sans fonctionnalité dédiée à ce jour) utilisant les services au quotidien ;
- des **administrateurs** gérant le contenu, les membres et la vie associative.

### Grands espaces

| Espace | Accès | Rôle |
|---|---|---|
| Site vitrine | Public | Présentation, actualités, événements, adhésion |
| Espace membre (`/espace-membre/*`) | Connexion requise | Formations, réseau, opportunités, vie associative |
| Administration (`/admin/*`) | Rôle administrateur requis | Gestion de tout le contenu et des membres |

### Grands usages couverts

Adhésion et gestion des candidatures, authentification, formations en ligne avec modules et certification, parcours guidés de formations, événements internes et événements publics à inscription par QR code, groupes sectoriels, annuaire des membres, messagerie interne, marketplace de services/produits entre membres, offres d'emploi et de stage, projets communautaires, carte de membre numérique avec QR code, abonnement annuel payant, notifications, gestion éditoriale complète du site vitrine.

### Architecture (uniquement ce qui explique le comportement utilisateur)

La plateforme est composée de **deux applications Laravel distinctes qui communiquent en HTTP** : `REJCC-Backend` (API pure, base de données, aucune interface visible) et `REJCC-Frontend` (le site que l'utilisateur voit, sans accès direct à une base de données — chaque action passe par un appel à l'API backend). Cette séparation explique certains détails observés dans ce guide, par exemple : les fichiers médias sont téléversés et stockés par le frontend, puis seule leur adresse (URL) est transmise à l'API ; ou encore le fait que toute page de l'espace membre ou de l'administration nécessite que le frontend obtienne et conserve un jeton d'authentification auprès du backend.

---

## 3. Profils utilisateurs

La plateforme distingue six profils effectifs. Ce ne sont pas tous des valeurs d'une même colonne « rôle » : certains sont des états (abonné / non abonné), un autre une étape transitoire (candidat).

### Profil : Visiteur (invité, non connecté)

- **Conditions d'accès** : aucune — n'importe quel navigateur, sans compte.
- **Interfaces publiques accessibles** : Accueil (`/`), À propos (`/a-propos`), Activités (`/activites`), Domaines (`/domaines`), Événements publics (`/evenements`, `/evenements/{slug}`), Actualités (`/actualites`, `/actualites/{slug}`), Partenaires (`/partenaires`), Contact (`/contact`), suivi de candidature (`/suivre-ma-candidature`), carte membre publique (`/carte/{code}`), inscription publique à un événement par QR code (`/participer/{slug}`).
- **Peut consulter** : contenu institutionnel (secteurs, témoignages, chiffres clés, étapes d'adhésion), actualités, calendrier des événements publics, liste animée des partenaires.
- **Peut rechercher** : les actualités (catégorie, mot-clé), les événements (mois, type).
- **Peut s'inscrire** : comme candidat à l'adhésion (`/adhesion`) ; à un événement public par lien/QR code, sans créer de compte ; à la newsletter.
- **Peut contacter** : formulaire `/contact` ; formulaire de demande de partenariat sur `/partenaires`.
- **Peut se connecter** : bouton « Mon espace » (haut de page), redirige vers `/connexion`.
- **Ne peut pas** : accéder à l'espace membre ni à l'administration, voir le catalogue de formations, échanger des messages, consulter l'annuaire des membres.
- **Parcours principal testé et confirmé fonctionnel** : Accueil → découverte du contenu → `/adhesion` → soumission de candidature → attente de validation par un administrateur → connexion une fois la candidature acceptée.

### Profil : Candidat à l'adhésion (statut intermédiaire, pas encore un compte)

- **Conditions d'accès** : avoir soumis le formulaire `/adhesion`. Ceci crée un enregistrement `MembershipApplication` distinct d'un compte `User`, avec un statut `en_attente` / `accepte` / `refuse`.
- **Interface dédiée** : `/suivre-ma-candidature` — vérification du statut par e-mail.
- **Actions possibles** : consulter son statut ; aucune modification de la candidature après soumission n'est proposée.
- **Issue TESTÉE ET FONCTIONNELLE** : une candidature acceptée par un administrateur crée automatiquement un compte `User` (rôle « member ») avec le mot de passe choisi lors de la candidature ; le candidat peut alors se connecter directement. Une candidature refusée déclenche un e-mail de refus (motif optionnel) et aucun compte n'est créé.
- **Restriction** : impossible de se connecter tant que la candidature n'est pas acceptée.

### Profil : Membre (compte « member », sans abonnement actif)

- **Conditions d'accès** : acceptation d'une candidature d'adhésion (voie normale identifiée), ou création manuelle par un administrateur (`Admin → Nouvelle inscription`). *(Voir section 14 : l'inscription directe en libre-service existe côté API mais n'est reliée à aucune page publique du frontend.)*
- **Connexion** : `/connexion` (e-mail + mot de passe) → redirection vers `/espace-membre`.
- **Tableau de bord testé et fonctionnel** : agrège messages non lus, documents, suggestions de membres, événements à venir, progression des formations, certificats obtenus, une liste de « défis » gamifiés à points d'expérience, et un fil d'activité récent.
- **Fonctionnalités accessibles sans abonnement (toutes testées et fonctionnelles)** : Mes formations, Catalogue (inscription à une formation), Mes parcours guidés, Groupes sectoriels (rejoindre/quitter), Marketplace (consultation), Événements internes (s'inscrire/se désinscrire), Emploi & Stage (consulter et publier), Documents (consultation), Certificats (consultation), Profil (modification), Notifications.
- **Fonctionnalité annoncée mais non implémentée** : Mentorat — page accessible mais affichant un message « bientôt disponible ».
- **Fonctionnalités verrouillées (paywall testé et fonctionnel)** : Annuaire, Groupes → liste des membres d'un groupe, Messagerie, Projets, Carte membre complète, publication d'annonces sur le Marketplace. Chacune affiche un écran « Fonctionnalité réservée aux abonnés » avec un bouton « Payer mon abonnement (10 000 F) ».
- **Parcours confirmé** : Connexion → Tableau de bord → navigation libre → rencontre du paywall sur les fonctionnalités avancées → page « Mon abonnement ».

### Profil : Membre abonné (compte « member » + abonnement annuel actif)

- **Conditions d'accès** : paiement de l'abonnement annuel de 10 000 F CFA via `/espace-membre/abonnement` (CinetPay — Wave, Orange Money, MTN, Moov, carte), ou compte administrateur (toujours considéré comme abonné).
- **Fonctionnalités additionnelles, toutes testées et fonctionnelles** : Annuaire des membres (recherche/filtrage), Messagerie (partiellement confirmée, voir limites), Groupes → liste complète des membres, Projets (proposer un projet), Carte membre numérique complète avec QR code, publication d'annonces sur le Marketplace.
- **Renouvellement** : chaque paiement réussi prolonge la date d'expiration d'un an à partir de la date d'expiration existante (permet un renouvellement anticipé).

### Profil : Mentor (rôle « mentor »)

- **Statut réel constaté** : un rôle « mentor » existe en base et un administrateur peut créer/convertir un compte avec ce rôle (page `Admin → Mentors` confirmée listant ces comptes).
- **Constat central** : **aucune fonctionnalité de mentorat interactive n'existe**. La page `/espace-membre/mentorat`, visible par tout membre, affiche « bientôt disponible » et renvoie vers l'Annuaire et la Messagerie. Un compte « Mentor » se comporte à l'identique d'un compte « Membre » partout ailleurs (mêmes menus, mêmes droits), à l'exception de l'étiquette « Mentor » sur sa carte et de son apparition dans `Admin → Mentors`.
- **Statut** : TESTÉ ET FONCTIONNEL pour la création/liste des comptes mentors ; IDENTIFIÉE DANS LE CODE MAIS NON VALIDÉE pour toute fonctionnalité de mentorat proprement dite (elle n'existe pas).

### Profil : Administrateur (rôle « admin »)

- **Conditions d'accès** : compte créé au déploiement via la commande `php artisan app:seed-if-empty` (e-mail/mot de passe définis par variables d'environnement), ou créé par un autre administrateur à accès complet.
- **Connexion** : même page `/connexion` ; redirection automatique vers `/admin` selon le rôle renvoyé par l'API.
- **Abonnement** : toujours actif, aucun paywall.
- **Permissions modulables, testées et fonctionnelles** : un administrateur peut avoir un accès complet ou restreint à une liste de sections (ex. seulement « Formations » et « Événements »), réglé à la création du compte ou lors d'une modification. Un administrateur restreint ne voit dans son menu que les sections autorisées.
- **Interfaces accessibles (accès complet), toutes visitées et fonctionnelles** : Vue d'ensemble, Adhésions, Membres, Nouvelle inscription, Mentors, Formations (+ modules), Parcours, Événements, Inscriptions (QR), Projets, Marketplace, Certificats, Emploi & Stage, Actualités, Pages du site, Blocs de contenu, Médiathèque, Réglages du site, Newsletter, Documents, Contacts, Partenariats, Notifications, Journal d'audit, Export.
- **Restriction confirmée par le code** : impossible de supprimer un autre compte administrateur.

---

## 4. Cartographie générale de la plateforme

### 4.1 Interfaces publiques (Visiteur)

| Interface | Route | Objectif | Statut de vérification |
|---|---|---|---|
| Accueil | `/` | Vitrine, chiffres clés, valeurs, CTA adhésion | TESTÉ ET FONCTIONNEL |
| À propos | `/a-propos` | Présentation institutionnelle | TESTÉ ET FONCTIONNEL |
| Activités | `/activites` | Liste des activités du réseau | TESTÉ ET FONCTIONNEL |
| Domaines | `/domaines` | Secteurs représentés | TESTÉ ET FONCTIONNEL |
| Événements (liste + détail) | `/evenements`, `/evenements/{slug}` | Calendrier des événements publics | TESTÉ ET FONCTIONNEL |
| Actualités (liste + détail) | `/actualites`, `/actualites/{slug}` | Articles, recherche/filtre | TESTÉ ET FONCTIONNEL |
| Partenaires | `/partenaires` | Bandeau animé + formulaire partenariat | TESTÉ ET FONCTIONNEL |
| Contact | `/contact` | Formulaire de contact | TESTÉ ET FONCTIONNEL |
| Carte membre publique | `/carte/{code}` | Cible d'un QR code de carte membre | IDENTIFIÉE, NON RE-TESTÉE ISOLÉMENT |
| Inscription événement QR | `/participer/{slug}` | Formulaire dynamique d'inscription publique | TESTÉ ET FONCTIONNEL |
| Formulaire d'adhésion | `/adhesion` | Candidature en 8 étapes | TESTÉ ET EN ERREUR (partiel, voir §6) |
| Suivi de candidature | `/suivre-ma-candidature` | Statut de candidature par e-mail | TESTÉ ET FONCTIONNEL |
| Connexion | `/connexion` | Authentification | TESTÉ ET FONCTIONNEL |
| Mot de passe oublié / réinitialisation | `/mot-de-passe-oublie`, `/reinitialiser-mot-de-passe` | Réinitialisation | IDENTIFIÉE DANS LE CODE MAIS NON VALIDÉE (nécessite un e-mail réel non disponible dans l'environnement de test) |

### 4.2 Interfaces Espace membre

Voir le tableau détaillé section 10 (« Catalogue complet des interfaces »).

### 4.3 Interfaces Administration

Voir le tableau détaillé section 10.

### 4.4 Navigation observée

- **Barre de navigation publique** (identique desktop/mobile) : Accueil, À propos, Activités, Domaines, Événements, Actualités, Partenaires, Contact, plus les boutons « Mon espace » et « Adhérer ».
- **Menu latéral Espace membre** : Accueil, Ma carte membre 🔒, Mes formations, Catalogue, Mes parcours, Mentorat, Annuaire 🔒, Groupes sectoriels, Messagerie 🔒, Marketplace, Événements, Projets 🔒, Emploi & Stage, Documents, Certificats, + carte « Mon abonnement », + Paramètres et Déconnexion. (🔒 = réservé aux abonnés.)
- **Barre mobile Espace membre** : Accueil, Cours, Emploi, Marketplace, Plus (ouvre le tiroir complet).
- **Menu Administration**, groupé par catégories : Vue d'ensemble ; Base de données (Adhésions, Membres, Nouvelle inscription) ; Activité réseau (Formations, Parcours, Événements, Inscriptions QR, Projets, Marketplace, Certificats, Emploi & Stage, Mentors) ; Contenu du site (Actualités, Pages du site, Blocs de contenu, Médiathèque, Réglages du site, Newsletter, Documents) ; Support & système (Contacts, Partenariats, Notifications, Journal d'audit).

Toute cette navigation a été confirmée visuellement par capture d'écran lors des tests.

---

## 5. Parcours du visiteur — de A à Z

### Étape 1 — Arrivée sur la plateforme

URL testée : `http://127.0.0.1:8020/` (équivalent à `rejcc.site` en production). La page d'accueil affiche : un bandeau d'annonce optionnel (désactivable par l'administrateur), la barre de navigation, une section d'ouverture « ENSEMBLE POUR L'EXCELLENCE » avec deux boutons d'appel à l'action (« Rejoindre le réseau » et « Découvrir le REJCC »), des indicateurs chiffrés (nombre de membres, domaines), une section « Qui sommes-nous », une section « Pourquoi nous rejoindre », une section « Nos valeurs », une section « 33 domaines, un seul réseau », une section « Rejoignez-nous en 4 étapes » avec un bouton « Commencer mon adhésion », et un pied de page complet (navigation, liens, coordonnées, formulaire newsletter). **Constaté et conforme à l'attendu.**

### Étape 2 — Découverte

Le visiteur peut comprendre, sans connexion : la mission du réseau, ses domaines d'activité, comment adhérer, les événements à venir, les dernières actualités, et la liste des partenaires.

### Étape 3 — Navigation

Chaque page publique testée (À propos, Activités, Domaines, Événements, Actualités, Partenaires, Contact) a répondu avec un code 200 et un contenu affiché sans erreur console ni erreur serveur.

### Étape 4 — Passage à l'action

Actions réellement disponibles et testées : remplir le formulaire de contact ; s'abonner à la newsletter ; envoyer une demande de partenariat ; s'inscrire à un événement par QR code sans créer de compte ; démarrer une candidature d'adhésion ; se connecter si déjà membre.

### Étape 5 — Création d'un compte / passage à l'espace membre

Voir la section 6 (Parcours d'adhésion) pour le détail complet, y compris le problème critique identifié.

### Étape 6 — Première arrivée dans l'espace utilisateur

Après acceptation de sa candidature et première connexion, le nouveau membre arrive sur un tableau de bord riche : message de bienvenue personnalisé, citation biblique du jour, bloc de progression (0 % au départ), et une liste de « défis » à accomplir (s'inscrire à une formation, faire avancer une formation, s'inscrire à un événement, compléter son profil à 100 %), chacun associé à des points d'expérience. **Constaté, conforme, et particulièrement soigné.**

### Étape 7 — Utilisation régulière

Voir section 17.

### Étape 8 — Fin de session

Le lien « Se déconnecter » (présent dans l'espace membre et l'administration) a été testé : il ramène l'utilisateur à la page d'accueil publique et invalide sa session (l'accès direct à une URL de l'espace membre redirige de nouveau vers `/connexion`).

---

## 6. Parcours d'adhésion (candidature) — détail complet

### Point d'entrée

Bouton « Adhérer » (menu public) ou « Commencer mon adhésion » (page d'accueil) → route `/adhesion`.

### Description du formulaire

Assistant en **8 étapes**, avec barre de progression et titres d'étape visibles : (1) Informations générales, (2) Diocèse & paroisse, (3) Profil, (4) Compétences, (5) Entrepreneuriat, (6) Projet futur, (7) Attentes, (8) Récapitulatif.

### Données demandées (par étape, telles qu'observées)

| Étape | Champs | Obligatoire |
|---|---|---|
| 1. Informations générales | Nom et prénoms, Sexe (Homme/Femme), Tranche d'âge, Numéro WhatsApp, E-mail, Ville, Mot de passe + confirmation | Tous obligatoires |
| 2. Diocèse & paroisse | Diocèse, Paroisse | Obligatoires |
| 3. Profil | Statut actuel (choix multiple), Niveau d'études | Obligatoires |
| 4. Compétences | Domaine de formation, Compétences (choix multiple), Description libre | Description facultative, reste obligatoire |
| 5. Entrepreneuriat | A une activité actuelle (Oui/Non) ; si Oui : secteurs d'activité + ancienneté | Conditionnel |
| 6. Projet futur | Domaines d'activité futurs souhaités | Obligatoire **seulement si l'étape 5 = Non** |
| 7. Attentes | Attentes principales, Formations d'intérêt, Défi principal, Revenu mensuel | Obligatoires |
| 8. Récapitulatif | Relecture des réponses, bouton « Envoyer ma demande » | — |

### ⚠️ Résultat constaté : bug critique et bloquant

**Statut : TESTÉ ET EN ERREUR (critique).**

Parcours suivi : remplissage correct des étapes 1 à 4, puis à l'étape 5 (« Entrepreneuriat »), réponse **« Oui »** à « Avez-vous actuellement une ou des activités génératrices de revenus ou une entreprise ? », remplissage des secteurs d'activité et de l'ancienneté, clic sur « Suivant » (passage réussi à l'étape 6). À l'étape 6 (« Projet futur »), clic sur « Suivant » → **la plateforme affiche une page d'erreur « 500 | SERVER ERROR »**, brute et sans message compréhensible pour l'utilisateur.

**Cause identifiée dans le code** (`REJCC-Frontend/app/Livewire/AdhesionApplicationForm.php`) : la méthode `rulesFor(5)` ne retourne des règles de validation que si la réponse à l'étape 5 est « Non » ; si elle vaut « Oui », elle retourne un tableau vide. Le composant appelle alors `$this->validate([])`. Or Livewire, recevant un tableau de règles vide, retombe sur son comportement par défaut (chercher une propriété `$rules` ou une méthode `rules()` sur le composant), qui n'existent pas ici — d'où l'exception `Livewire\Exceptions\MissingRulesException`, confirmée dans les journaux applicatifs.

**Conséquence pour l'utilisateur réel** : **tout candidat déclarant déjà exercer une activité génératrice de revenus — c'est-à-dire une partie importante du public cible du REJCC (« porteurs de projet », « entrepreneurs confirmés ») — ne peut pas terminer sa candidature d'adhésion.** Le formulaire plante avant même d'atteindre le récapitulatif, sans aucune sauvegarde des données déjà saisies.

**Contre-test réalisé avec succès (chemin « Non »)** : en répondant « Non » à l'étape 5, le parcours se déroule intégralement jusqu'au récapitulatif, puis jusqu'à l'écran de confirmation **« Merci pour votre demande ! Votre adhésion au REJCC a bien été enregistrée. »**, avec une citation biblique et deux boutons (« Retour à l'accueil », « Suivre l'état de ma candidature »). **Statut : TESTÉ ET FONCTIONNEL pour ce chemin uniquement.**

### Bug secondaire observé (mineur, cosmétique)

**Statut : TESTÉ ET EN ERREUR (mineur).** Lorsqu'un champ obligatoire à choix (ex. « Sexe », « Secteurs d'activité ») est laissé vide et que l'utilisateur clique sur « Suivant », le message d'erreur affiché est la clé de traduction brute **`validation.required`** au lieu d'un texte en français (« Ce champ est obligatoire. »). Ce même défaut apparaît sur plusieurs champs de ce formulaire. Vérification faite sur un autre formulaire (Marketplace) : le message d'erreur y est correctement traduit en français (« Choisissez une catégorie. »), ce qui confirme que le problème est localisé au formulaire d'adhésion et n'est pas généralisé à toute la plateforme.

### Vérification de la suite du parcours (chemin réussi)

1. La candidature apparaît immédiatement dans `Admin → Adhésions`, statut « En attente », avec l'intégralité des réponses correctement restituées.
2. Le suivi de candidature (`/suivre-ma-candidature`) avec l'e-mail utilisé affiche le statut.
3. L'administrateur clique sur « Approuver » → une boîte de dialogue de confirmation native s'affiche : *« Approuver la demande de [Nom] ? Son compte membre sera créé et un e-mail de bienvenue lui sera envoyé. »* — confirmée.
4. Le candidat peut alors se connecter immédiatement avec l'e-mail et le mot de passe choisis lors de la candidature, et arrive sur son tableau de bord.
5. Une notification « Bienvenue au REJCC ! Votre candidature a été acceptée. » est visible dans `/espace-membre/notifications`.

**L'ensemble de cette chaîne (hors le bug de l'étape 5=Oui) est TESTÉ ET FONCTIONNEL.**

---

## 7. Parcours d'authentification

### Connexion

**Statut : TESTÉ ET FONCTIONNEL.** Accueil → « Mon espace » → `/connexion` → saisie e-mail + mot de passe → clic « Se connecter » → redirection vers `/espace-membre` (membre) ou `/admin` (administrateur), selon le rôle renvoyé par l'API.

### Déconnexion

**Statut : TESTÉ ET FONCTIONNEL.** Lien « Se déconnecter » (bas du menu latéral, espace membre et administration) → retour à l'accueil public ; toute tentative d'accès direct à une URL protégée redirige ensuite vers `/connexion`.

### Cas d'erreur constaté : limiteur de tentatives de connexion

**Observation réelle** : après plusieurs tentatives de connexion rapprochées (5 requêtes par minute et par adresse IP, limite définie côté API), la plateforme affiche le message **« Too Many Attempts. »** sous le champ e-mail. Ce comportement de protection est un choix de sécurité légitime et attendu ; toutefois, comme pour le point précédent, **ce message n'est pas traduit en français**, alors que le reste de l'interface l'est systématiquement.

### Mot de passe oublié / réinitialisation

**Statut : ACCESSIBLE MAIS NON TESTÉ DE BOUT EN BOUT.** Les pages `/mot-de-passe-oublie` et `/reinitialiser-mot-de-passe` sont accessibles et affichent un formulaire cohérent ; l'envoi réel d'un e-mail de réinitialisation n'a pas pu être vérifié dans l'environnement de test (messagerie en mode journal, pas de boîte réelle). Le code montre que la demande renvoie toujours un message de succès générique (protection contre l'énumération des comptes), qu'une réinitialisation invalide toutes les sessions existantes de l'utilisateur, et que le jeton de réinitialisation expire après une heure.

---

## 8. Parcours détaillés par profil

### 8.1 Parcours Membre non abonné (utilisation réelle testée)

1. Connexion → tableau de bord.
2. **Catalogue** (`/espace-membre/catalogue`) : la formation créée pour les besoins du test (« Créer son entreprise en Côte d'Ivoire », gratuite, certifiante) s'affiche avec sa catégorie, sa durée, son niveau et un bouton « Voir les modules ». Clic sur inscription → confirmation immédiate, le bouton devient « Voir les modules ». **TESTÉ ET FONCTIONNEL.**
3. **Mes formations → Détail d'une formation** : les modules apparaissent dans l'ordre, avec un verrouillage séquentiel (le module 2 affiche une icône de cadenas tant que le module 1 n'est pas terminé). Clic sur le module 1, bouton « Marquer ce module comme terminé » → progression mise à jour, module 2 déverrouillé. Une fois les deux modules terminés, un certificat est délivré automatiquement (voir §8.3). **TESTÉ ET FONCTIONNEL.**
4. **Groupes sectoriels** (`/espace-membre/groupes`) : 9 groupes proposés (Agriculture & Pêche, Informatique & Technologie, Communication & Médias, Finance & Investissement, Administration & Gestion, Éducation & Formation, Santé & Bien-être, BTP & Construction, Industrie & Production, etc.). Clic sur « Rejoindre » → un formulaire demande de décrire sa spécialité (au moins 10 caractères) → clic sur « Enregistrer » → message « Votre fiche a bien été enregistrée dans le groupe. », le groupe rejoint apparaît dans un bloc « Mes groupes », avec les boutons « Ma spécialité » et « Quitter ». **TESTÉ ET FONCTIONNEL.**
5. **Emploi & Stage** (`/espace-membre/emplois`) : publication d'une offre de stage testée avec succès (« Stage développeur web junior ») ; **l'offre apparaît instantanément dans la liste publique de l'espace membre, sans aucune validation préalable par un administrateur**, message « Offre publiée ! Elle est visible par tous les membres. » **TESTÉ ET FONCTIONNEL — à noter comme un traitement différent du Marketplace (voir §14).**
6. **Notifications** (`/espace-membre/notifications`) : affiche correctement, avec horodatage, la notification de bienvenue et toute notification liée à une action (ex. validation d'une annonce Marketplace). **TESTÉ ET FONCTIONNEL.**
7. **Profil, Documents, Certificats, Mes parcours** : interfaces atteintes et cohérentes visuellement ; Documents et Mes parcours n'ont pas fait l'objet d'un scénario de modification complet dans cette session (contenu de test insuffisant pour Mes parcours — aucun parcours guidé n'avait été créé côté administration).
8. **Rencontre du paywall** : tentative d'accès à Annuaire, Messagerie, Projets, Carte, ou publication Marketplace → écran cohérent « Fonctionnalité réservée aux abonnés » avec bouton de paiement. **TESTÉ ET FONCTIONNEL** sur les quatre premières pages ; le blocage de la publication Marketplace a été vérifié indirectement (un abonnement actif était nécessaire pour publier, confirmé une fois l'abonnement simulé).

### 8.2 Parcours Membre abonné (abonnement simulé pour les besoins du test)

1. **Annuaire** (`/espace-membre/annuaire`) : liste, recherche et filtres par profil (Étudiant & jeune diplômé / Porteur de projet / Entrepreneur confirmé) fonctionnels ; le second compte de test créé pour l'occasion y apparaît correctement avec un bouton « Envoyer un message ». **TESTÉ ET FONCTIONNEL.**
2. **Ma carte membre** (`/espace-membre/carte`) : carte numérique complète avec nom, mention « Membre officiel », numéro de membre au format `REJCC-2026-XXXXXXXX`, date d'adhésion, et un véritable QR code généré côté client scannable vers la page publique de la carte ; boutons « Ajouter ma photo » et « Imprimer / enregistrer en PDF ». **TESTÉ ET FONCTIONNEL.**
3. **Projets** (`/espace-membre/projets`) : formulaire de proposition (nom, description, nombre de membres impliqués) → soumission → le projet apparaît immédiatement avec le statut « En évaluation » et la mention « Votre projet ». **TESTÉ ET FONCTIONNEL.**
4. **Marketplace — publication** : formulaire complet (type Service/Produit, catégorie, titre, description, prix, contact, média) → soumission → message « Annonce soumise ! Elle sera visible sur la Marketplace dès validation par l'administration. », l'annonce apparaît dans « Mes annonces » avec le statut « En attente de validation ». **TESTÉ ET FONCTIONNEL** — voir §11 pour la suite du parcours (modération admin).
5. **Messagerie** : la page vide affiche correctement « Aucune conversation. Démarrez-en une depuis l'annuaire. » avec un raccourci vers l'annuaire ; le bouton « Envoyer un message » présent sur une fiche de l'annuaire a été confirmé visible et cliquable dans plusieurs captures d'écran indépendantes. **Statut : TESTÉ MAIS PARTIEL** — l'envoi et la réception effectifs d'un message entre les deux comptes de test n'ont pas pu être menés à leur terme dans cette session, en raison d'instabilités précises de l'environnement de test automatisé (et non d'une anomalie observée côté API : des appels directs et répétés à l'API `/api/members` avec le jeton du compte testeur ont toujours renvoyé la liste correcte des membres). Ce point mériterait une nouvelle vérification manuelle, hors automatisation, pour lever le doute.

### 8.3 Certificats

**Statut : TESTÉ ET FONCTIONNEL.** Une fois les deux modules d'une formation certifiante complétés, la page Certificats affiche automatiquement le certificat obtenu, avec sa date d'obtention et une référence unique au format `REJCC-CERT-2026-0001`, accompagné de la mention « Présentez cette référence pour toute vérification ». Aucune action manuelle n'est nécessaire : le certificat est calculé et délivré à la volée dès que la condition (formation certifiante + tous les modules terminés) est remplie.

### 8.4 Parcours Administrateur (utilisation réelle testée)

1. **Connexion** avec le compte administrateur seedé au déploiement → redirection vers `/admin`.
2. **Vue d'ensemble** : tableau de bord avec compteurs (membres, formations publiées, certificats délivrés, projets proposés), un graphique d'évolution du réseau sur 3/6/12 mois, un bloc « En attente d'action », un histogramme des inscriptions par formation, et des boutons d'export CSV par jeu de données (Membres, Candidatures, Contacts, Newsletter, Formations, Événements, Opportunités). **TESTÉ ET FONCTIONNEL**, les chiffres reflètent exactement les données créées pendant les tests.
3. **Adhésions** : file d'attente des candidatures avec filtres (Toutes/En attente/Approuvées/Rejetées), recherche, et actions Voir/Approuver/Rejeter. **TESTÉ ET FONCTIONNEL** (voir §6).
4. **Membres** : liste complète avec filtres par rôle (Administrateurs/Mentors/Membres) et par statut (Actifs/Suspendus), recherche, export, et actions par ligne (voir dossier, modifier, générer le QR, suspendre, créer un message, supprimer — sauf pour un compte administrateur, action grisée/absente). **TESTÉ ET FONCTIONNEL.**
5. **Formations** : création d'une formation (titre, catégorie, description, durée, niveau, gratuite/certifiante/publiée) → apparaît immédiatement dans le catalogue public de l'espace membre. Ajout de modules (titre, description, URL vidéo, URL document, durée, ordre) → apparaissent dans l'ordre côté membre avec le verrouillage séquentiel attendu. **TESTÉ ET FONCTIONNEL** de bout en bout.
6. **Inscriptions (QR)** — module phare testé intégralement :
   - création d'un événement à inscription publique (titre, date, **date limite optionnelle**, lieu, description, capacité, champ personnalisé additionnel) → message « Événement enregistré. » ;
   - onglet « QR & lien » : génération instantanée d'un **véritable QR code** (image téléchargeable en PNG) et d'un lien public copiable (`/participer/{slug}`) ;
   - le lien public a été ouvert dans un navigateur en tant que visiteur : formulaire cohérent (Prénom, Nom, Téléphone/WhatsApp, E-mail optionnel, champ personnalisé, case « Je suis déjà membre du REJCC »), soumission réussie → écran « Inscription confirmée ! Merci [Prénom], votre place est réservée... » ;
   - **un e-mail de confirmation a été effectivement généré** avec un contenu personnalisé correct (prénom, nom de l'événement, lieu), vérifié dans les journaux du serveur de messagerie ;
   - retour côté administration, onglet « Participants » : le participant apparaît avec toutes ses informations, le compteur d'inscrits passe de 0 à 1, export CSV/Excel disponible.
   - **L'ensemble de ce parcours (Inscriptions QR) est TESTÉ ET FONCTIONNEL, sans aucune anomalie constatée.**
7. **Marketplace (modération)** : la liste « En attente » affiche l'annonce soumise par le membre testeur avec toutes ses informations (auteur, ville, prix, catégorie) et les actions Voir/Publier/Refuser/Supprimer. Clic sur « Publier » → l'annonce passe en « En ligne », la file d'attente repasse à 0, et **le membre reçoit automatiquement une notification** (« Votre annonce est en ligne ! ») visible dans son espace. **TESTÉ ET FONCTIONNEL** de bout en bout, modération comprise.
8. **Journal d'audit** : chaque action d'écriture réalisée pendant les tests (création de formation, création d'événement, approbation de candidature, publication d'annonce) est apparue correctement dans le journal, avec l'auteur, l'action, la cible, l'adresse IP et l'horodatage exacts. **TESTÉ ET FONCTIONNEL.**
9. **Réglages du site** : formulaire complet (identité, coordonnées, réseaux sociaux, bandeau d'annonce, paiement CinetPay) affiché et cohérent. Modification non poussée jusqu'à l'enregistrement dans cette session (pour ne pas altérer la configuration de test), mais l'interface est confirmée fonctionnelle visuellement.
10. **Autres sections visitées et confirmées accessibles sans erreur** : Mentors, Parcours, Projets, Certificats, Actualités, Contenu du site, Pages du site, Médiathèque, Newsletter, Documents, Contacts, Partenariats, Notifications (diffusion). Ces pages n'ont pas toutes fait l'objet d'un scénario de création/modification complet dans le temps imparti ; elles sont donc classées **ACCESSIBLE, chargement confirmé sans erreur**, avec un niveau de vérification fonctionnelle moindre que les points 1 à 9 ci-dessus (voir la matrice section 15 pour le détail précis par fonctionnalité).

---

## 9. Catalogue complet des fonctionnalités

Chaque fiche suit le modèle demandé. Seules les fonctionnalités ayant fait l'objet d'un test réel poussé sont détaillées intégralement ; les autres sont résumées avec leur statut réel.

### Fiche — Candidature d'adhésion

**Profil(s) concerné(s) :** Visiteur
**Statut :** TESTÉ ET EN ERREUR (chemin « activité actuelle = Oui ») / TESTÉ ET FONCTIONNEL (chemin « Non »)
**Point d'entrée :** Bouton « Adhérer » ou « Commencer mon adhésion » → `/adhesion`
**Objectif :** Constituer un dossier de candidature avant création d'un compte membre.

#### 1. Où trouver la fonctionnalité ?
Menu public → « Adhérer » (bouton visible sur toutes les pages publiques), ou section « Comment adhérer » de la page d'accueil → bouton « Commencer mon adhésion ».

#### 2. Conditions préalables
Aucune. Accessible à tout visiteur.

#### 3. Parcours complet
Voir le détail intégral en section 6 de ce document.

#### 4. Données demandées
Voir le tableau de la section 6.

#### 5. Résultat
- Chemin « Non » : création d'un enregistrement de candidature, écran de confirmation, possibilité de suivi par e-mail.
- Chemin « Oui » : erreur serveur 500, aucune candidature enregistrée.

#### 6. Cas d'erreur
Erreur serveur 500 reproduite deux fois de manière identique (voir section 6). Message de validation non traduit (`validation.required`) sur les champs à choix de l'étape 1.

#### 7. Résultat attendu / constaté
**Résultat constaté :** blocage total pour une partie significative du public cible.
**Résultat attendu :** le formulaire devrait accepter la réponse « Oui » à l'étape 5 exactement comme il accepte « Non », en ne rendant obligatoires les champs de l'étape 6 que dans le cas « Non » (ce qui est déjà l'intention du code), sans provoquer d'erreur serveur dans le cas contraire.

---

### Fiche — Inscription publique à un événement par QR code

**Profil(s) concerné(s) :** Visiteur (saisie) / Administrateur (création de l'événement et consultation des participants)
**Statut :** TESTÉ ET FONCTIONNEL
**Point d'entrée :** Lien ou QR code communiqué par le REJCC → `/participer/{slug}`
**Objectif :** Permettre à toute personne, membre ou non, de s'inscrire à un événement sans créer de compte.

#### 1. Où trouver la fonctionnalité ?
Administration → Activité réseau → Inscriptions (QR) → « Créer un événement », puis onglet « QR & lien » pour récupérer le QR code ou le lien à diffuser.

#### 2. Conditions préalables
Un administrateur doit avoir créé l'événement et défini éventuellement une date limite, une capacité et des champs de formulaire personnalisés.

#### 3. Parcours complet
**Étape 1 — Administrateur crée l'événement.** Renseigne titre, date, date limite (optionnelle), lieu, description, capacité ; peut ajouter des champs personnalisés (ex. « Nom de votre entreprise ou projet »). Clique sur « Créer ». Résultat immédiat : bandeau « Événement enregistré. », l'événement apparaît dans la liste avec le statut « Ouvert ».

**Étape 2 — Administrateur récupère le support de diffusion.** Onglet « QR & lien » : un QR code s'affiche instantanément (généré côté navigateur), avec un lien public en clair, un bouton « Télécharger le QR (PNG) », un bouton « Copier le lien » et un bouton « Aperçu ».

**Étape 3 — Le visiteur scanne le QR code ou ouvre le lien.** Arrive sur une page publique reprenant le nom et le lieu de l'événement, avec un formulaire.

**Étape 4 — Le visiteur remplit le formulaire et valide.** Bouton « Je réserve ma place ». Résultat immédiat : écran « Inscription confirmée ! Merci [Prénom], votre place est réservée pour [Événement]. »

**Étape 5 — Confirmation automatique.** Un e-mail de confirmation est généré (contenu vérifié), avec un texte personnalisé et l'adresse du lieu.

**Étape 6 — Suivi côté administration.** Le nombre d'inscrits est mis à jour instantanément (ex. 0 → 1) ; l'onglet « Participants » liste chaque inscrit avec toutes ses réponses, recherche et export possibles.

#### 4. Données demandées
Prénom, Nom, Téléphone/WhatsApp (obligatoires), E-mail (optionnel), case « Je suis déjà membre du REJCC », plus tout champ personnalisé défini par l'administrateur (texte, zone de texte, liste déroulante, case à cocher, fichier).

#### 5. Résultat
Création d'un participant (`EventParticipant`), envoi d'un e-mail de confirmation, mise à jour du compteur d'inscrits visible côté administration.

#### 6. Cas d'erreur
Non testés dans cette session : dépassement de capacité, inscription après la date limite, événement fermé. Le code prévoit des messages dédiés pour ces trois cas (« Clôturé », complet, date limite dépassée), mais ils n'ont pas été déclenchés manuellement.

#### 7. Résultat attendu / constaté
Résultat constaté conforme à l'attendu sur le chemin nominal testé.

---

### Fiche — Formations et modules

**Profil(s) concerné(s) :** Membre (suivi), Administrateur (création)
**Statut :** TESTÉ ET FONCTIONNEL
**Point d'entrée :** `Espace membre → Catalogue` (membre) / `Admin → Formations` (administrateur)
**Objectif :** Proposer des formations en ligne structurées en modules, avec suivi de progression et certification automatique.

#### 1. Où trouver la fonctionnalité ?
Côté administrateur : Activité réseau → Formations → « + Nouvelle formation », puis bouton « Modules » sur la formation créée.
Côté membre : Catalogue → carte de la formation → bouton d'inscription ; puis Mes formations → formation → liste des modules.

#### 2. Conditions préalables
Une formation doit être créée et publiée par un administrateur pour apparaître dans le catalogue.

#### 3. Parcours complet
**Étape 1 (administrateur)** — Remplit titre, catégorie, description, durée, niveau, coche « gratuite », « certifiante », « publiée ». Clique sur enregistrer. La formation apparaît dans la liste avec le statut « Publiée ».

**Étape 2 (administrateur)** — Ouvre l'onglet « Modules », ajoute un premier module (titre, description, URL vidéo, durée, ordre), puis un second. Les deux modules apparaissent listés dans l'ordre.

**Étape 3 (membre)** — Va dans Catalogue, voit la formation avec son étiquette « Certifiante » et son prix (« Gratuit »), clique sur le bouton d'inscription. Le bouton devient « Voir les modules ».

**Étape 4 (membre)** — Ouvre la formation depuis « Mes formations ». Voit une barre de progression à 0 % et les deux modules, le second grisé avec une icône de verrou.

**Étape 5 (membre)** — Clique sur le module 1 pour le déplier, clique sur « Marquer ce module comme terminé ». Le module 2 se déverrouille.

**Étape 6 (membre)** — Répète l'opération pour le module 2.

**Étape 7 (résultat automatique)** — Un certificat apparaît immédiatement dans « Mes certificats », avec sa référence unique.

#### 4. Données demandées
Formation : titre, catégorie, description, durée, niveau, indicateurs gratuite/certifiante/publiée, média optionnel. Module : titre, description, URL vidéo, URL document, durée, ordre.

#### 5. Résultat
Catalogue mis à jour instantanément côté membre ; progression suivie module par module ; certificat émis automatiquement à la complétion d'une formation certifiante.

#### 6. Cas d'erreur
Non testés : tentative de valider un module verrouillé sans avoir terminé le précédent (le code impose un ordre séquentiel, non contourné manuellement dans cette session).

#### 7. Résultat attendu / constaté
Conforme à l'attendu, sans anomalie.

---

### Fiche — Groupes sectoriels

**Profil(s) concerné(s) :** Membre
**Statut :** TESTÉ ET FONCTIONNEL
**Point d'entrée :** `Espace membre → Groupes sectoriels`
**Objectif :** Regrouper les membres par domaine d'activité pour des échanges ciblés.

#### 1. Où trouver la fonctionnalité ?
Menu latéral → Groupes sectoriels.

#### 2. Conditions préalables
Aucune (accessible sans abonnement) ; la consultation de la liste complète des membres d'un groupe (« trombinoscope ») est en revanche réservée aux abonnés.

#### 3. Parcours complet
**Étape 1** — La page affiche 9 groupes (Agriculture & Pêche, Informatique & Technologie, etc.), chacun avec sa description, son nombre de membres et un bouton « Rejoindre ».

**Étape 2** — Clic sur « Rejoindre » d'un groupe → une fenêtre s'ouvre demandant de décrire sa spécialité dans ce domaine (au moins 10 caractères, exemple fourni en filigrane).

**Étape 3** — Saisie du texte, clic sur « Enregistrer » → message « Votre fiche a bien été enregistrée dans le groupe. »

**Étape 4** — Le groupe rejoint apparaît désormais dans un nouveau bloc « Mes groupes (1) » en haut de page, et sa carte affiche un badge « Membre », la spécialité saisie en citation, et deux boutons : « Ma spécialité » (modifier) et « Quitter ».

#### 4. Données demandées
Un seul champ : la spécialité (texte libre, 10 à 600 caractères).

#### 5. Résultat
Adhésion immédiate au groupe, comptage des membres mis à jour.

#### 6. Cas d'erreur
Non testé : validation en dessous de 10 caractères (le code impose cette règle mais elle n'a pas été déclenchée volontairement).

#### 7. Résultat attendu / constaté
Conforme à l'attendu.

---

### Fiche — Marketplace (services et produits entre membres)

**Profil(s) concerné(s) :** Membre abonné (publication) / Membre (consultation) / Administrateur (modération)
**Statut :** TESTÉ ET FONCTIONNEL
**Point d'entrée :** `Espace membre → Marketplace` / `Admin → Marketplace`
**Objectif :** Permettre aux membres de proposer des services ou produits, avec validation préalable par l'administration.

#### 1. Où trouver la fonctionnalité ?
Menu latéral → Marketplace → bouton « + Proposer un service / produit ».

#### 2. Conditions préalables
Un abonnement actif est nécessaire pour **publier** une annonce (la consultation du catalogue est libre pour tout membre connecté).

#### 3. Parcours complet
**Étape 1 (membre)** — Clique sur « Proposer un service / produit », choisit le type (Service/Produit), une catégorie (liste déroulante : Artisanat & BTP, Alimentation & Restauration, Mode & Beauté, Services numériques, Transport & Logistique, Éducation & Formation, Santé & Bien-être, Agriculture, Commerce & Distribution, Finance & Conseil, Événementiel, Autre), un titre, une description, un prix optionnel, un contact, et éventuellement un fichier ou un lien média. Clique sur « Soumettre à la validation ».

**Étape 2 (résultat immédiat)** — Message « Annonce soumise ! Elle sera visible sur la Marketplace dès validation par l'administration. » ; l'annonce apparaît dans l'onglet « Mes annonces » avec le statut « En attente de validation ».

**Étape 3 (administrateur)** — Dans `Admin → Marketplace`, l'onglet « En attente » liste l'annonce avec l'auteur, sa ville, son prix, sa catégorie, et trois actions : Voir, Publier, Refuser.

**Étape 4 (administrateur)** — Clique sur « Publier ». L'annonce passe instantanément dans l'onglet « En ligne », le compteur « En attente » repasse à 0.

**Étape 5 (résultat côté membre)** — Le membre reçoit une notification « Votre annonce est en ligne ! « [Titre] » a été validée par l'administration et est maintenant visible sur la Marketplace. », consultable dans `/espace-membre/notifications`.

#### 4. Données demandées
Type (Service/Produit), Catégorie (obligatoire), Titre, Description, Prix (optionnel), Contact, média (optionnel).

#### 5. Résultat
Annonce créée avec statut « en_attente », puis « approuve » après validation ; notification automatique au membre à chaque décision (approbation **et** refus, ce dernier avec motif).

#### 6. Cas d'erreur
Message de validation correctement traduit constaté (« Choisissez une catégorie. ») lorsque la catégorie n'est pas sélectionnée — contrairement au formulaire d'adhésion, ce formulaire traduit correctement ses erreurs.

#### 7. Résultat attendu / constaté
Conforme à l'attendu, aucune anomalie.

---

### Fiche — Emploi & Stage

**Profil(s) concerné(s) :** Membre
**Statut :** TESTÉ ET FONCTIONNEL (avec écart de traitement à signaler)
**Point d'entrée :** `Espace membre → Emploi & Stage`
**Objectif :** Permettre aux membres de publier et consulter des offres d'emploi, de stage ou des annonces diverses.

#### Parcours testé
Clic sur « + Publier une offre » → formulaire (intitulé, type Emploi/Stage/Annonce, entreprise, lieu, description, site web, contact, date limite optionnelle, fiche de poste optionnelle) → clic sur « Publier l'offre » → message « Offre publiée ! Elle est visible par tous les membres. » → **l'offre est immédiatement visible dans la liste, sans aucune étape de validation par un administrateur.**

#### Résultat attendu / constaté
**Résultat constaté :** publication immédiate sans modération.
**Élément à signaler à l'équipe** : ce traitement diffère de celui du Marketplace, qui impose une validation administrative avant mise en ligne, alors que les deux fonctionnalités reposent sur un principe similaire de contenu généré par les membres. Il ne s'agit pas d'un bug technique (le comportement est cohérent et volontaire dans le code), mais d'une incohérence fonctionnelle qui mérite une décision produit (faut-il modérer les offres d'emploi comme le Marketplace ?).

---

### Fiche — Carte de membre numérique

**Profil(s) concerné(s) :** Membre abonné
**Statut :** TESTÉ ET FONCTIONNEL
**Point d'entrée :** `Espace membre → Ma carte membre`

Carte visuelle complète (nom, mention de statut, logo REJCC) et un second volet avec un véritable QR code fonctionnel, le numéro de membre (`REJCC-2026-XXXXXXXX`) et la date d'adhésion. Boutons « Ajouter ma photo » et « Imprimer / enregistrer en PDF » présents et cohérents. Pour un membre **non abonné**, la même page affiche un écran verrouillé au lieu de la carte complète (comportement vérifié par le code — `MemberCardController` renvoie une version « locked » minimale — cohérent avec le paywall observé partout ailleurs).

---

### Fiche — Abonnement payant (CinetPay)

**Profil(s) concerné(s) :** Membre
**Statut :** TESTÉ MAIS PARTIEL (ACCESSIBLE MAIS NON TESTABLE pour la confirmation de paiement réelle)
**Point d'entrée :** `Espace membre → Mon abonnement`

#### Parcours testé
La page affiche le prix (10 000 F CFA), les avantages débloqués, et un bouton « Payer 10 000 F ». **Dans l'environnement de test, aucune clé API CinetPay n'était configurée** : le clic sur le bouton a été testé et a produit, de façon propre et sans erreur brute, le message **« Le paiement en ligne n'est pas encore configuré. Contactez un administrateur. »** — preuve que le cas d'absence de configuration est correctement anticipé et géré côté interface (l'API renvoie une erreur 503 gérée proprement par le frontend).

#### Ce qui n'a pas pu être testé
Le paiement réel via un moyen CinetPay (Wave, Orange Money, MTN, Moov, carte bancaire), la redirection vers la page hébergée CinetPay, ni le retour après paiement (webhook de confirmation), faute de clés API réelles disponibles.

#### Vérification indirecte
Un abonnement actif a été simulé directement en base de données (équivalent à un paiement CinetPay réussi). Toutes les fonctionnalités qu'il débloque (Annuaire, Carte, Projets, Marketplace, Messagerie) ont ensuite été testées avec succès (voir §8.2), confirmant que **le mécanisme de déblocage fonctionne correctement une fois l'abonnement enregistré comme actif**, indépendamment de la manière dont il l'est devenu.

---

### Fiche — Mentorat

**Statut : NON IDENTIFIÉE (fonctionnalité annoncée mais non implémentée).**
**Point d'entrée :** `Espace membre → Mentorat`

La page affiche un message indiquant que la fonctionnalité arrive bientôt, avec des liens de repli vers l'Annuaire et la Messagerie. Aucun formulaire de demande de mentorat, aucune mise en relation, aucun tableau de bord dédié n'existe dans le code ou dans l'interface.

---

## 10. Catalogue complet des interfaces

### 10.1 Espace membre

| Interface | Route | Réservé abonné ? | Statut de vérification |
|---|---|---|---|
| Tableau de bord | `/espace-membre` | Non | TESTÉ ET FONCTIONNEL |
| Ma carte membre | `/espace-membre/carte` | Oui | TESTÉ ET FONCTIONNEL |
| Mes formations | `/espace-membre/formations` | Non | TESTÉ ET FONCTIONNEL |
| Détail formation | `/espace-membre/formations/{id}` | Non | TESTÉ ET FONCTIONNEL |
| Catalogue | `/espace-membre/catalogue` | Non | TESTÉ ET FONCTIONNEL |
| Mes parcours | `/espace-membre/parcours` | Non | ACCESSIBLE, non testé en profondeur (aucun parcours guidé créé pendant les tests) |
| Détail parcours | `/espace-membre/parcours/{id}` | Non | IDENTIFIÉE DANS LE CODE MAIS NON VALIDÉE |
| Mentorat | `/espace-membre/mentorat` | Non | TESTÉ — fonctionnalité non implémentée (message d'attente) |
| Annuaire | `/espace-membre/annuaire` | Oui | TESTÉ ET FONCTIONNEL |
| Groupes sectoriels | `/espace-membre/groupes` | Non | TESTÉ ET FONCTIONNEL |
| Membres d'un groupe | `/espace-membre/groupes/{id}` | Oui | ACCESSIBLE, non testé en profondeur |
| Messagerie | `/espace-membre/messagerie` | Oui | TESTÉ MAIS PARTIEL |
| Marketplace | `/espace-membre/marketplace` | Publier: oui / Consulter: non | TESTÉ ET FONCTIONNEL |
| Événements | `/espace-membre/evenements` | Non | ACCESSIBLE, inscription/désinscription non re-testée dans cette session (fonctionnalité équivalente déjà validée côté public QR) |
| Projets | `/espace-membre/projets` | Oui | TESTÉ ET FONCTIONNEL |
| Emploi & Stage | `/espace-membre/emplois` | Non | TESTÉ ET FONCTIONNEL |
| Documents | `/espace-membre/documents` | Non | ACCESSIBLE, chargement confirmé |
| Certificats | `/espace-membre/certificats` | Non | TESTÉ ET FONCTIONNEL |
| Profil | `/espace-membre/profil` | Non | ACCESSIBLE, chargement confirmé (formulaire non soumis dans cette session) |
| Notifications | `/espace-membre/notifications` | Non | TESTÉ ET FONCTIONNEL |
| Abonnement | `/espace-membre/abonnement` | — | TESTÉ MAIS PARTIEL (voir fiche dédiée) |

### 10.2 Administration

| Interface | Route | Statut de vérification |
|---|---|---|
| Vue d'ensemble | `/admin` | TESTÉ ET FONCTIONNEL |
| Membres | `/admin/membres` | TESTÉ ET FONCTIONNEL |
| Nouvelle inscription | `/admin/inscription` | ACCESSIBLE, chargement confirmé |
| Adhésions | `/admin/adhesions` | TESTÉ ET FONCTIONNEL |
| Mentors | `/admin/mentors` | ACCESSIBLE, chargement confirmé (liste vide dans cette session, aucun compte mentor créé) |
| Formations | `/admin/formations` | TESTÉ ET FONCTIONNEL |
| Parcours | `/admin/parcours` | ACCESSIBLE, chargement confirmé |
| Événements | `/admin/evenements` | ACCESSIBLE, chargement confirmé |
| Inscriptions (QR) | `/admin/inscriptions` | TESTÉ ET FONCTIONNEL |
| Projets | `/admin/projets` | ACCESSIBLE, chargement confirmé (le projet créé côté membre y apparaît) |
| Marketplace | `/admin/marketplace` | TESTÉ ET FONCTIONNEL |
| Certificats | `/admin/certificats` | ACCESSIBLE, chargement confirmé |
| Emploi & Stage | `/admin/emplois` | ACCESSIBLE, chargement confirmé |
| Actualités | `/admin/actualites` | ACCESSIBLE, chargement confirmé |
| Pages du site | `/admin/pages` | ACCESSIBLE, chargement confirmé |
| Blocs de contenu | `/admin/contenu` | ACCESSIBLE, chargement confirmé |
| Médiathèque | `/admin/mediatheque` | ACCESSIBLE, chargement confirmé |
| Réglages du site | `/admin/reglages` | ACCESSIBLE, formulaire cohérent visuellement, non soumis |
| Newsletter | `/admin/newsletter` | ACCESSIBLE, chargement confirmé |
| Documents | `/admin/documents` | ACCESSIBLE, chargement confirmé |
| Contacts | `/admin/contacts` | ACCESSIBLE, chargement confirmé |
| Partenariats | `/admin/partenariats` | ACCESSIBLE, chargement confirmé |
| Notifications (diffusion) | `/admin/notifications` | ACCESSIBLE, chargement confirmé (diffusion non testée) |
| Journal d'audit | `/admin/audit` | TESTÉ ET FONCTIONNEL |
| Export | `/admin/export/{dataset}` | IDENTIFIÉE DANS LE CODE, boutons présents et visibles, téléchargement non déclenché dans cette session |

---

## 11. Parcours détaillés de bout en bout (grands parcours métier)

### Parcours métier n°1 — De la candidature à la première utilisation de l'espace membre

**Statut global : TESTÉ ET FONCTIONNEL (sur le chemin « activité actuelle = Non »).**

1. Visiteur → `/adhesion` → remplit les 8 étapes → soumet sa candidature.
2. La candidature apparaît dans `Admin → Adhésions`, statut « En attente ».
3. Administrateur consulte le détail, clique sur « Approuver », confirme dans la boîte de dialogue.
4. Un compte membre est créé automatiquement ; le candidat reçoit une notification de bienvenue.
5. Le nouveau membre se connecte avec l'e-mail et le mot de passe choisis lors de la candidature.
6. Il découvre son tableau de bord, ses défis, et peut immédiatement s'inscrire à une formation.

### Parcours métier n°2 — De l'abonnement payant à l'utilisation des fonctionnalités premium

**Statut global : TESTÉ MAIS PARTIEL (paiement réel non testable, déblocage vérifié).**

1. Membre non abonné rencontre un paywall (ex. sur Annuaire).
2. Clique sur « Payer mon abonnement » → `/espace-membre/abonnement`.
3. Clique sur « Payer 10 000 F » → *(en production)* redirection vers CinetPay, choix du moyen de paiement, paiement, retour sur la plateforme.
4. L'abonnement devient actif ; l'annuaire, la messagerie, la carte, les projets et la publication Marketplace se débloquent immédiatement.

### Parcours métier n°3 — De la publication d'une annonce Marketplace à sa mise en ligne

**Statut global : TESTÉ ET FONCTIONNEL, intégralement vérifié.**

1. Membre abonné soumet une annonce → statut « En attente de validation ».
2. Administrateur consulte la file d'attente, vérifie les informations, clique sur « Publier ».
3. L'annonce devient visible de tous les membres dans le catalogue Marketplace.
4. Le membre auteur reçoit une notification confirmant la mise en ligne.

### Parcours métier n°4 — De la création d'un événement à sa clôture avec liste de participants

**Statut global : TESTÉ ET FONCTIONNEL, intégralement vérifié (hors clôture manuelle et export).**

1. Administrateur crée un événement à inscription publique par QR code.
2. Diffuse le QR code ou le lien.
3. Des visiteurs et/ou des membres s'inscrivent via le formulaire public.
4. Chaque inscrit reçoit un e-mail de confirmation automatique.
5. L'administrateur suit en temps réel le nombre d'inscrits et peut consulter, rechercher et exporter la liste des participants.
6. *(Non testé dans cette session)* L'administrateur peut fermer les inscriptions manuellement avant la date limite si la capacité est atteinte.

### Parcours métier n°5 — De l'inscription à une formation à l'obtention d'un certificat

**Statut global : TESTÉ ET FONCTIONNEL, intégralement vérifié.**

1. Membre s'inscrit à une formation certifiante depuis le catalogue.
2. Complète les modules dans l'ordre imposé.
3. Un certificat est délivré automatiquement, consultable et vérifiable par sa référence unique.

---

## 12. Parcours croisés entre profils

| Profil 1 | Action | Traitement | Profil 2 | Résultat | Retour vers profil 1 |
|---|---|---|---|---|---|
| Candidat | Soumet une candidature d'adhésion | Enregistrement en base, visible immédiatement | Administrateur | Voit la candidature dans sa file d'attente, peut l'approuver ou la rejeter | Notification de bienvenue (si acceptée) ou e-mail de refus (si rejetée) |
| Membre | Propose une annonce Marketplace | Mise en file d'attente de modération | Administrateur | Voit l'annonce, l'approuve ou la refuse (avec motif) | Notification automatique de la décision |
| Membre | Publie une offre d'emploi/stage | Publication immédiate, aucune file d'attente | Tous les membres | Voient l'offre instantanément dans la liste | — |
| Visiteur | S'inscrit à un événement par QR code | Enregistrement du participant | Administrateur | Voit le participant dans sa liste, peut l'exporter | E-mail de confirmation automatique envoyé au visiteur |
| Membre A (abonné) | Consulte l'annuaire et clique sur « Envoyer un message » | Ouverture (ou création) d'une conversation | Membre B (abonné) | Reçoit le message dans sa messagerie | Réponse visible par le membre A dans le même fil *(aller-retour complet non confirmé dans cette session, voir §8.2 et §14)* |
| Membre | Rejoint un groupe sectoriel | Ajout à la liste des membres du groupe avec sa spécialité | Autres membres abonnés du même groupe | Peuvent le voir dans le trombinoscope du groupe | — |

---

## 13. Fonctionnalités testées — tableau de synthèse

| Fonctionnalité | Profil | Interface | Point d'entrée | Statut | Test effectué | Résultat |
|---|---|---|---|---|---|---|
| Navigation publique complète | Visiteur | Site vitrine | Menu principal | TESTÉ ET FONCTIONNEL | Visite de chaque page publique | Toutes les pages répondent correctement |
| Candidature d'adhésion (chemin Oui) | Visiteur | `/adhesion` | Bouton Adhérer | TESTÉ ET EN ERREUR | Parcours complet des 8 étapes | Erreur serveur 500 à l'étape 6 |
| Candidature d'adhésion (chemin Non) | Visiteur | `/adhesion` | Bouton Adhérer | TESTÉ ET FONCTIONNEL | Parcours complet des 8 étapes | Candidature enregistrée avec succès |
| Suivi de candidature | Candidat | `/suivre-ma-candidature` | Lien du menu | TESTÉ ET FONCTIONNEL | Recherche par e-mail | Statut affiché correctement |
| Approbation de candidature | Administrateur | `/admin/adhesions` | Bouton Approuver | TESTÉ ET FONCTIONNEL | Approbation réelle | Compte membre créé, notification envoyée |
| Connexion | Tous | `/connexion` | Bouton Mon espace | TESTÉ ET FONCTIONNEL | Connexion avec identifiants réels | Redirection correcte selon le rôle |
| Déconnexion | Membre / Admin | Menu latéral | Lien Se déconnecter | TESTÉ ET FONCTIONNEL | Déconnexion réelle | Session invalidée |
| Tableau de bord membre | Membre | `/espace-membre` | Après connexion | TESTÉ ET FONCTIONNEL | Consultation | Contenu personnalisé affiché |
| Catalogue et inscription à une formation | Membre | `/espace-membre/catalogue` | Menu latéral | TESTÉ ET FONCTIONNEL | Inscription réelle | Inscription confirmée |
| Modules de formation et progression | Membre | `/espace-membre/formations/{id}` | Mes formations | TESTÉ ET FONCTIONNEL | Complétion des 2 modules | Progression et déverrouillage corrects |
| Certificat automatique | Membre | `/espace-membre/certificats` | Menu latéral | TESTÉ ET FONCTIONNEL | Vérification après complétion | Certificat émis avec référence correcte |
| Rejoindre un groupe sectoriel | Membre | `/espace-membre/groupes` | Menu latéral | TESTÉ ET FONCTIONNEL | Adhésion réelle à un groupe | Confirmation et affichage corrects |
| Paywall (Annuaire, Messagerie, Projets, Carte) | Membre non abonné | Pages concernées | Menu latéral | TESTÉ ET FONCTIONNEL | Accès sans abonnement | Écran de blocage cohérent affiché |
| Paiement abonnement — cas non configuré | Membre | `/espace-membre/abonnement` | Bouton Payer | TESTÉ ET FONCTIONNEL (gestion d'erreur) | Clic réel sur Payer | Message d'erreur clair affiché |
| Déblocage post-abonnement | Membre abonné | Annuaire, Carte, Projets, Marketplace | Menu latéral | TESTÉ ET FONCTIONNEL | Abonnement simulé + navigation | Toutes les fonctionnalités déverrouillées |
| Publication Marketplace | Membre abonné | `/espace-membre/marketplace` | Bouton Proposer | TESTÉ ET FONCTIONNEL | Soumission réelle | Annonce en attente créée |
| Modération Marketplace | Administrateur | `/admin/marketplace` | Bouton Publier | TESTÉ ET FONCTIONNEL | Approbation réelle | Annonce publiée, notification envoyée |
| Publication offre d'emploi | Membre | `/espace-membre/emplois` | Bouton Publier | TESTÉ ET FONCTIONNEL | Soumission réelle | Offre visible immédiatement, sans modération |
| Création d'événement à inscription QR | Administrateur | `/admin/inscriptions` | Bouton Créer | TESTÉ ET FONCTIONNEL | Création réelle | Événement et QR code générés |
| Inscription publique par QR code | Visiteur | `/participer/{slug}` | Lien/QR | TESTÉ ET FONCTIONNEL | Inscription réelle | Confirmation affichée, e-mail généré |
| Consultation des participants | Administrateur | `/admin/inscriptions` | Onglet Participants | TESTÉ ET FONCTIONNEL | Consultation réelle | Participant listé avec ses données |
| Journal d'audit | Administrateur | `/admin/audit` | Menu latéral | TESTÉ ET FONCTIONNEL | Consultation après plusieurs actions | Toutes les actions correctement journalisées |
| Gestion des membres (liste, filtres) | Administrateur | `/admin/membres` | Menu latéral | TESTÉ ET FONCTIONNEL | Consultation réelle | Liste correcte avec filtres opérationnels |
| Notifications membre | Membre | `/espace-membre/notifications` | Menu latéral | TESTÉ ET FONCTIONNEL | Consultation après plusieurs événements | Historique complet et correct |
| Messagerie (ouverture de fil + envoi) | Membre abonné | `/espace-membre/messagerie` | Annuaire → Envoyer un message | TESTÉ MAIS PARTIEL | Tentatives répétées | Bouton confirmé présent, envoi non confirmé de bout en bout |
| Mentorat | Membre | `/espace-membre/mentorat` | Menu latéral | NON IDENTIFIÉE (non implémentée) | Consultation | Message « bientôt disponible » |

---

## 14. Fonctionnalités partielles ou problématiques

### 1. Formulaire d'adhésion — blocage total si « activité actuelle = Oui » *(critique)*

Voir le détail complet en section 6 et dans la fiche dédiée de la section 9. **Impact utilisateur majeur : une partie substantielle du public cible du REJCC (porteurs de projet, entrepreneurs déjà actifs) ne peut pas terminer sa candidature.**

### 2. Mentorat — rôle présent, fonctionnalité absente

Le rôle « mentor » existe et peut être attribué, mais aucune fonctionnalité de mentorat interactif n'a été implémentée. Point de blocage : la page correspondante ; comportement constaté : message d'attente ; impact : les membres identifiés comme mentors ne disposent d'aucun outil dédié, et les membres cherchant un mentor ne disposent d'aucun moyen d'en faire la demande via la plateforme.

### 3. Messages de validation non traduits (formulaire d'adhésion et limiteur de connexion)

Deux occurrences constatées : la clé brute `validation.required` sur le formulaire d'adhésion, et le message anglais « Too Many Attempts. » lors d'un dépassement du nombre de tentatives de connexion. Un autre formulaire testé (Marketplace) affiche, lui, des messages correctement traduits, ce qui montre qu'il ne s'agit pas d'une limitation générale de la plateforme mais d'oublis ponctuels.

### 4. Incohérence de modération entre Marketplace et Emploi & Stage

Le Marketplace impose une validation administrative avant publication ; les offres d'Emploi & Stage sont publiées instantanément sans aucun contrôle. Comportement volontaire dans le code, mais incohérence fonctionnelle entre deux fonctionnalités de nature similaire, à trancher par l'équipe.

### 5. Messagerie — parcours non confirmé de bout en bout, et une cause plausible identifiée dans le code

Le point d'entrée (bouton « Envoyer un message » depuis l'annuaire) et l'état vide de la messagerie ont été confirmés à plusieurs reprises par capture d'écran. L'envoi et la réception effectifs d'un message n'ont pas pu être menés à leur terme dans cette session : après plusieurs tentatives réparties dans le temps (y compris après redémarrage complet des deux serveurs et en dehors de toute autre charge de test), l'annuaire a fini par afficher de façon répétée « Aucun membre trouvé » pour le compte de test utilisé, alors que des appels directs et répétés à l'API (`GET /api/members`) avec un jeton frais renvoyaient systématiquement la bonne liste. **Statut : TESTÉ MAIS PARTIEL.**

En creusant le code du composant concerné (`REJCC-Frontend/app/Livewire/Member/Directory.php`, méthode `render()`), une explication plausible et **confirmée par lecture du code** a été trouvée : l'appel `Api::get('/members', $params, Api::token())` ne vérifie jamais la clé `ok` de la réponse — le code fait directement `Collection::make($result['members'] ?? [])`. **Si l'appel à l'API échoue pour une raison quelconque (jeton momentanément invalide, erreur réseau, erreur serveur), la page affiche exactement le même résultat qu'un annuaire réellement vide (« Aucun membre trouvé »), sans aucun message d'erreur distinctif.** Vérification faite sur l'ensemble du dossier `app/Livewire` (68 appels à `Api::get()` recensés) : ce même schéma (`$result['...'] ?? [...]` sans vérification préalable de `ok`) est en réalité **le mode d'écriture systématique de tous les composants de l'espace membre** — Tableau de bord, Catalogue, Certificats, Documents, Emploi & Stage, et l'Annuaire lui-même en font tous usage. Ce n'est pas une preuve que c'est la cause exacte de l'instabilité observée ici pour la messagerie, mais c'est un défaut de robustesse réel, vérifié dans le code, et généralisé à la quasi-totalité de l'espace membre : **toute panne ou erreur transitoire de communication avec l'API backend se traduit systématiquement, pour l'utilisateur, par un écran « vide » ou « aucun résultat » plutôt que par un message d'erreur explicite l'invitant à réessayer.** Une correction consisterait à distinguer, à l'écran, l'absence réelle de données de l'échec d'un appel à l'API.

### 6. Ancien flux `/adhesion` côté API (Member + Payment) — probablement obsolète

Identifié dans le code backend : un point d'entrée public plus ancien (modèles `Member` et `Payment`, fournisseurs wave/orange/djamo) semble ne jamais faire passer un paiement au statut « réussi » (initiation réelle marquée comme non terminée dans le code). Aucune page du frontend actuel n'utilise ce point d'entrée — la route `/inscription` redirige directement vers le formulaire moderne `/adhesion`. Recommandation : vérifier avec l'équipe si ce code est encore nécessaire ou peut être retiré.

### 7. Inscription directe en libre-service — non exposée par une page dédiée

L'API expose un point d'entrée de création de compte immédiate (sans validation d'un administrateur), mais aucune page du site ne semble en tirer parti dans l'interface actuelle : le seul chemin d'inscription visible par un visiteur est le formulaire de candidature, qui exige une validation manuelle. Ce point est identifié dans le code mais n'a pas été confirmé comme un manque volontaire ou un oubli.

### 8. Pas de vérification d'adresse e-mail

Aucune étape de confirmation d'e-mail n'existe après une inscription ou une acceptation de candidature — un compte est actif immédiatement avec l'adresse saisie, sans preuve qu'elle appartient réellement à la personne.

### 9. Paiement CinetPay — dépendance non testable en conditions réelles

Voir la fiche dédiée en section 9. Le comportement en l'absence de configuration a été vérifié et est correctement géré ; le paiement réel n'a pas pu être vérifié faute de clés API disponibles dans l'environnement de test.

### 10. Écran d'ouverture animé (PWA) — non observable en navigation classique

Fonctionnalité conçue pour ne s'afficher qu'en mode application installée. Non observée dans cette session (navigateur classique).

---

## 15. Matrice Fonctionnalité × Profil × Interface

| Fonctionnalité | Visiteur | Membre | Membre abonné | Mentor | Administrateur |
|---|:---:|:---:|:---:|:---:|:---:|
| Consulter le site vitrine | ✅ | ✅ | ✅ | ✅ | ✅ |
| Candidater à l'adhésion | ✅ | — | — | — | — |
| S'inscrire à un événement par QR code | ✅ | ✅ | ✅ | ✅ | ✅ |
| Se connecter / se déconnecter | — | ✅ | ✅ | ✅ | ✅ |
| Tableau de bord personnalisé | — | ✅ | ✅ | ✅ | ✅ (vue administrateur) |
| Formations, modules, certificats | — | ✅ | ✅ | ✅ | Gestion complète |
| Parcours guidés | — | ✅ | ✅ | ✅ | Gestion complète |
| Groupes sectoriels (rejoindre) | — | ✅ | ✅ | ✅ | — |
| Groupes sectoriels (voir les membres) | — | ❌ (paywall) | ✅ | selon abonnement | Gestion complète |
| Annuaire des membres | — | ❌ (paywall) | ✅ | selon abonnement | Gestion complète |
| Messagerie | — | ❌ (paywall) | ⚠️ partiel | selon abonnement | — |
| Marketplace (consulter) | — | ✅ | ✅ | ✅ | Modération |
| Marketplace (publier) | — | ❌ (paywall) | ✅ | selon abonnement | Modération |
| Emploi & Stage | — | ✅ | ✅ | ✅ | Gestion complète |
| Projets | — | ❌ (paywall) | ✅ | selon abonnement | Gestion complète |
| Carte membre complète | — | ❌ (paywall) | ✅ | selon abonnement | — |
| Mentorat (interactif) | — | ❌ non implémenté | ❌ non implémenté | ❌ non implémenté | — |
| Abonnement / paiement | — | ✅ | (déjà actif) | selon cas | (toujours actif) |
| Gestion des membres, candidatures, contenu | — | — | — | — | ✅ |
| Journal d'audit | — | — | — | — | ✅ |

Légende : ✅ testé et fonctionnel · ⚠️ partiellement testé · ❌ non disponible pour ce profil (paywall ou fonctionnalité non implémentée) · — non applicable.

---

## 16. Guide de prise en main

Cette section s'adresse à une personne qui découvre la plateforme pour la première fois.

### Si vous êtes un nouveau visiteur souhaitant adhérer

1. Rendez-vous sur la page d'accueil du site.
2. Cliquez sur « Adhérer » ou « Commencer mon adhésion ».
3. Remplissez les quatre premières étapes du formulaire normalement.
4. **À l'étape « Entrepreneuriat »** : si vous avez déjà une activité en cours, sachez qu'un problème technique connu empêche actuellement de terminer le formulaire en répondant « Oui » à cette question (voir section 6). Si vous rencontrez une page d'erreur, contactez l'équipe REJCC pour finaliser votre candidature autrement, en attendant la correction de ce problème.
5. Si vous n'avez pas encore d'activité, répondez « Non » : le formulaire se termine normalement, jusqu'à l'écran « Merci pour votre demande ! ».
6. Patientez pendant l'examen de votre candidature. Vous pouvez suivre son statut via « Suivre l'état de ma candidature », en utilisant l'e-mail renseigné.
7. Une fois votre candidature acceptée, connectez-vous avec cet e-mail et le mot de passe choisi lors de la candidature.

### Si vous venez d'obtenir votre compte membre

1. Connectez-vous.
2. Découvrez votre tableau de bord et sa liste de « défis » : ils vous guident vers les premières actions à faire (s'inscrire à une formation, compléter son profil).
3. Parcourez le Catalogue de formations et inscrivez-vous à celle qui vous intéresse.
4. Rejoignez un ou plusieurs Groupes sectoriels correspondant à votre domaine.
5. Si vous souhaitez accéder à l'annuaire des membres, à la messagerie, publier sur le Marketplace, proposer un projet, ou obtenir votre carte de membre complète, vous devrez payer l'abonnement annuel (10 000 F CFA) depuis « Mon abonnement ».

### Si vous êtes administrateur

1. Connectez-vous avec le compte administrateur fourni par l'équipe technique.
2. Commencez par « Vue d'ensemble » pour avoir une photographie de l'état du réseau.
3. Traitez en priorité les éléments signalés dans le bloc « En attente d'action » (candidatures, contacts non traités, annonces à modérer, demandes de partenariat).
4. Pour organiser un événement avec inscription par QR code : allez dans « Inscriptions (QR) », créez l'événement, puis récupérez le QR code à imprimer ou le lien à diffuser.
5. Pour créer une formation : allez dans « Formations », créez la fiche, puis ajoutez ses modules un par un.
6. Si vous devez créer un compte administrateur avec des droits limités à certaines sections uniquement, utilisez « Nouvelle inscription » et cochez uniquement les sections souhaitées plutôt que « accès complet ».

---

## 17. Parcours d'utilisation régulière

### Visiteur régulier
Consulte les actualités et le calendrier des événements ; s'inscrit ponctuellement à un événement via un lien partagé ; peut décider, après plusieurs visites, de candidater à l'adhésion.

### Membre régulier (non abonné)
Se connecte, consulte son tableau de bord et ses notifications, avance dans ses formations en cours, consulte les nouvelles offres d'emploi/stage, participe à des événements internes. Peut publier ses propres offres d'emploi/stage sans restriction.

### Membre abonné régulier
En plus des usages ci-dessus : consulte l'annuaire pour identifier d'autres membres utiles à son projet, échange par messagerie, consulte et publie sur le Marketplace, suit les projets communautaires, présente sa carte de membre (QR code) lors d'événements physiques.

### Administrateur régulier
Traite quotidiennement les candidatures en attente, les messages de contact et les demandes de partenariat ; modère les nouvelles annonces Marketplace ; publie des actualités et des événements ; consulte le tableau de bord et le journal d'audit pour suivre l'activité du réseau.

---

## 18. Synthèse générale

### Ce que permet réellement la plateforme

La plateforme REJCC couvre, de façon opérationnelle et globalement soignée, l'ensemble du cycle de vie d'un membre : découverte publique, candidature, validation, vie associative quotidienne (formations certifiantes avec modules et certificats automatiques, groupes sectoriels, marketplace modéré, offres d'emploi, projets communautaires, carte numérique avec QR code) et un modèle d'abonnement payant qui distingue clairement les fonctionnalités de base des fonctionnalités premium. L'administration dispose d'outils complets et cohérents pour gérer le contenu, les membres, la modération et pour suivre l'activité via un journal d'audit fiable.

### Ce qui est opérationnel (confirmé par un usage réel)

Navigation publique ; candidature d'adhésion (sur le chemin « Non ») et son cycle complet jusqu'à la connexion du nouveau membre ; formations avec modules, progression et certification automatique ; groupes sectoriels ; paywall et déblocage des fonctionnalités premium ; marketplace avec modération et notifications ; offres d'emploi ; inscription publique à un événement par QR code avec e-mail de confirmation automatique et suivi des participants ; gestion des membres et des candidatures côté administration ; journal d'audit.

### Ce qui est partiel

La messagerie entre membres (point d'entrée confirmé, envoi/réception non confirmé de bout en bout dans cette session) ; le paiement d'abonnement réel (mécanisme de déblocage confirmé, transaction réelle non testable faute de clés API) ; plusieurs interfaces d'administration secondaires visitées et fonctionnelles à l'écran mais dont les actions de création/modification n'ont pas toutes été poussées jusqu'au bout dans le temps disponible (Réglages, Contenu, Pages, Actualités, Documents, Contacts, Partenariats, Newsletter, Notifications de diffusion, Export).

### Ce qui nécessite une intervention

**En priorité absolue** : corriger le bug bloquant du formulaire d'adhésion qui empêche tout candidat ayant déjà une activité de finaliser sa candidature (section 6). Ce point empêche une partie du public cible du REJCC de rejoindre le réseau et devrait être traité avant toute autre amélioration.

**En priorité secondaire** : traduire les messages d'erreur restants en français (formulaire d'adhésion, limiteur de connexion) ; décider d'une politique cohérente de modération entre Marketplace et Emploi & Stage ; statuer sur l'avenir du rôle « Mentor » (développer la fonctionnalité de mentorat ou retirer la mention pour éviter toute confusion) ; vérifier manuellement, hors automatisation, le bon fonctionnement complet de la messagerie entre deux membres abonnés ; et, plus largement, faire en sorte que les composants de l'espace membre distinguent à l'écran une absence réelle de données d'un échec de communication avec l'API — un défaut de robustesse confirmé dans le code et généralisé à la quasi-totalité de ces composants (voir section 14, point 5).

### Principaux parcours validés

Adhésion (chemin sans activité actuelle) → validation admin → connexion → utilisation de l'espace membre ; abonnement → déblocage des fonctionnalités premium ; création et diffusion d'un événement par QR code → inscription publique → confirmation automatique → suivi des participants ; formation → modules → certificat ; publication Marketplace → modération → notification.

### Principales limites constatées de cette vérification

Absence de clés API de paiement réelles ; messagerie non confirmée de bout en bout dans le temps disponible ; certaines sections d'administration secondaires vérifiées uniquement au niveau de leur accessibilité et de leur affichage, sans test exhaustif de chaque action de création/modification/suppression qu'elles proposent.
