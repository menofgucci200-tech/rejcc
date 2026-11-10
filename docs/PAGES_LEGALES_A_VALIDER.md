# Pages légales : ce que le Bureau doit compléter et valider

Les six pages (mentions légales, CGU, confidentialité, cookies, charte du membre, conditions d'abonnement) sont rédigées et chargées en **brouillons non publiés**. Rien n'apparaît sur le site tant qu'une page n'est pas publiée.

Pour les relire, les compléter et les publier : **Administration → Pages légales**, ouvrir la page, corriger, puis « Publier ». La version 1.0 est datée du jour de publication.

Les textes sources se trouvent dans `REJCC-Backend/database/data/pages-legales/`.

## 1. Informations à compléter (marquées « [À compléter : …] »)

| Page | Information |
|---|---|
| Mentions légales | Forme juridique (association loi 60-315 ou autre) |
| Mentions légales | Numéro et date du récépissé de déclaration |
| Mentions légales, Confidentialité | Adresse du siège |
| Mentions légales | Téléphone de contact |
| Mentions légales | Directeur de la publication (nom et fonction) |
| Confidentialité | Référence de la déclaration ou de l'autorisation ARTCI, si elle existe (sinon, supprimer la phrase) |

## 2. Choix proposés, à valider par le Bureau

**Remboursement de l'abonnement** (Conditions d'abonnement, §8)

- Un abonnement activé n'est pas remboursé.
- Exceptions remboursées : un paiement fait en double, un paiement débité sans activation, et les mois restants si le REJCC cesse les services ou ferme un compte sans faute du membre.

**Durées de conservation des données** (Confidentialité, §6)

| Données | Durée proposée |
|---|---|
| Compte d'un membre | 3 ans après la dernière activité ou la fermeture du compte |
| Demande d'adhésion refusée | 12 mois |
| Paiements et reçus | 10 ans (obligations comptables) |
| Messages du formulaire de contact | 12 mois |
| Inscriptions aux événements publics | 12 mois |
| Journaux de connexion | 12 mois |

**Autres choix**

- **Mineurs** (Confidentialité, §9) : le formulaire propose la tranche « Moins de 18 ans ». Le texte exige l'autorisation du représentant légal.
- **Débats partisans** (Charte, §4) : les échanges politiques polémiques sont exclus des espaces du réseau.
- **Exclusion d'un membre** (Charte, §7) : prononcée par le Bureau, après invitation à s'expliquer.
- **Litiges** (CGU et Conditions d'abonnement) : tribunaux d'Abidjan, après une tentative de solution amiable.

## 3. Points techniques à connaître

- **Pas de suppression automatique.** La plateforme ne supprime pas encore automatiquement les données à l'échéance des durées ci-dessus. Une fois les durées validées, une tâche de purge peut être ajoutée.
- **Pas de suppression du compte en libre-service.** Le membre ne peut pas supprimer son compte lui-même : il en fait la demande à contact@rejcc.site, comme l'indiquent les textes.
- **Hébergeur.** L'adresse de Hostinger (Larnaca, Chypre) est celle de sa société éditrice. Elle est à vérifier sur hostinger.com avant publication.
- **Durées des cookies.** Les durées indiquées dans la politique cookies (2 heures) correspondent au réglage par défaut des sessions. Si la durée de session a été modifiée sur le serveur, il faut corriger le texte.
- **Déclaration à l'ARTCI.** La loi n° 2013-450 prévoit des formalités auprès de l'ARTCI, notamment pour les données sensibles (le diocèse et la paroisse révèlent une appartenance religieuse) et pour les transferts de données hors de Côte d'Ivoire (l'hébergeur). Ces formalités relèvent du Bureau.
- **Relecture juridique.** Ces textes sont une base sérieuse, fidèle au fonctionnement réel de la plateforme, mais ils ne remplacent pas la relecture d'un juriste.
