# Film « Le parcours d'adhésion » (2 min 30)

Motion design de présentation de la plateforme et du parcours complet d'adhésion, sur ordinateur et sur mobile.
Films : `REJCC-Frontend/public/videos/parcours-adhesion-16x9.mp4` et `parcours-adhesion-9x16.mp4` (1920×1080 et 1080×1920, 30 i/s),
sous-titres `parcours-adhesion.fr.vtt`. Sur le site, le bouton flottant circulant ouvre le film en plein écran.

## Voix off (piste guide)

Voix de synthèse masculine provisoire : Piper « fr_FR-upmc-medium », locuteur « pierre » (licence CC BY-SA 4.0),
lecture posée (vitesse 0,8) avec une prosodie naturelle. Chaque phrase est générée plusieurs fois et la prise
la plus intelligible (vérifiée par reconnaissance vocale Whisper) est conservée ; traitement léger, sans
accentuation des aigus, pour éviter le timbre « métallique ». REJCC se dit « Rèje ». Pour la version
définitive, faire enregistrer ce texte (idéalement par un jeune homme ivoirien) en respectant les repères :
le film se recale sur les durées réelles (voir `source/`).

| Début | Fin | Chapitre | Texte |
|---|---|---|---|
| 002.40 s | 005.25 s | Ouverture | Vous avez une idée, un projet, une ambition ? |
| 006.00 s | 009.29 s | Ouverture | Le Rèje vous ouvre ses portes, et tout commence en ligne. |
| 010.89 s | 018.89 s | La plateforme | Voici la plateforme du Rèje : un site pour découvrir le réseau, un espace membre pour y grandir, et des outils pour faire vivre la communauté. |
| 019.64 s | 024.58 s | La plateforme | Sur ordinateur comme sur mobile, tout est pensé pour être simple, rapide et accessible. |
| 026.78 s | 036.31 s | Le parcours en 6 étapes | Rejoindre le réseau se fait en six étapes : découvrir, candidater, suivre sa demande, être validé, activer son abonnement… et profiter de tout le réseau. |
| 038.31 s | 044.36 s | Étape 1 · Découvrir | Tout commence sur la page d'accueil. Un clic sur le bouton Adhérer, et votre parcours commence. |
| 045.96 s | 053.72 s | Étape 2 · Candidater | Le formulaire d'adhésion vous guide étape par étape, huit au total, avec une barre de progression pour savoir où vous en êtes. |
| 054.47 s | 061.96 s | Étape 2 · Candidater | D'abord, vos informations générales : votre nom, vos coordonnées, votre ville, et le mot de passe de votre futur espace. |
| 062.71 s | 067.62 s | Étape 2 · Candidater | Puis votre diocèse et votre paroisse, votre profil, et vos compétences. |
| 068.37 s | 074.63 s | Étape 2 · Candidater | Vous avez déjà une activité ? Dites-le : le formulaire s'adapte à votre situation et à votre projet. |
| 075.38 s | 081.32 s | Étape 2 · Candidater | Enfin, vos attentes envers le réseau. Un récapitulatif vous permet de tout vérifier avant d'envoyer. |
| 083.52 s | 087.30 s | Étape 2 · Candidater | Votre demande est enregistrée. C'est aussi simple que ça. |
| 089.30 s | 093.95 s | Étape 3 · Suivre | À tout moment, suivez l'état de votre candidature avec votre adresse e-mail. |
| 095.55 s | 103.28 s | Étape 4 · Validation | Le bureau du Rèje étudie chaque demande. Une fois approuvée, votre compte membre est créé, et un message de bienvenue vous est envoyé. |
| 104.88 s | 110.00 s | Étape 5 · Activer (connexion) | Connectez-vous : votre espace membre vous attend, sur ordinateur comme sur votre téléphone. |
| 111.60 s | 120.09 s | Étape 5 · Activer (abonnement) | Pour tout débloquer, activez l'abonnement annuel de dix mille francs CFA, payable en toute sécurité par Mobile Money ou carte bancaire. |
| 121.69 s | 126.36 s | Étape 6 · Profiter (carte) | Votre carte de membre numérique, avec son QR code, est prête. |
| 127.96 s | 136.12 s | Étape 6 · Profiter (services) | Annuaire, messagerie, formations, événements, groupes sectoriels, marketplace et offres d'emploi : tout le réseau est désormais à portée de main. |
| 138.92 s | 141.80 s | Signature | Le Rèje. Ensemble, pour l'excellence. |
| 142.55 s | 145.65 s | Signature | Rejoignez-nous dès aujourd'hui sur Rèje point site. |

## Musique

Composition originale générée par code (`source/music.py`, aucun échantillon externe) : afro-pop douce à 100 BPM,
kalimba, marimba, nappe, basse et percussions légères ; la musique s'efface automatiquement sous la voix.

## Sources

- `source/index.html` + `source/tour.js` (+ `vertical.html`) : le film, fonction du temps, calé sur `cues.json` ;
- `source/adhesion.js`, `journey*.js` : captures automatiques du vrai parcours (local) ;
- `source/voix-off-gen.py`, `source/music.py`, `source/mix.py` : génération de la voix guide, de la musique et mixage ;
- `source/cap.js` : rendu image par image (Playwright), assemblage ffmpeg.
