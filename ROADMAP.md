# Feuille de route — Plateforme REJCC

Objectif final : **plateforme complète** (vitrine + espace membre + back-office), livrée **par phases itératives**.
Backend retenu : **Laravel + MySQL** (API REST). Déploiement : à décider (architecture compatible Vercel _ou_ hébergement serveur).

---

## ✅ Phase 1 — Socle & site vitrine _(réalisée)_

- [x] Extraction des assets de marque depuis les PDFs (logo couleur/blanc, lockup + monogramme).
- [x] **Design system** complet (tokens couleurs, typographie Anton/Manrope/Libre Caslon, utilitaires, keyframes).
- [x] Composants réutilisables (Button, Container, Eyebrow, SectionHeading, Reveal, Counter, LogoMark…).
- [x] **Layout global** : Navbar réactive + menu mobile, Footer, Loader premium, smooth scroll, barre de progression.
- [x] **Accueil** complet : Hero immersif, Présentation, Statistiques animées, Pourquoi rejoindre, Valeurs, Domaines (9 pôles / 33 domaines), Comment adhérer, Témoignages, Agenda, Actualités, CTA.
- [x] **Pages** : À propos, Activités, Domaines, Événements, Actualités, Partenaires, Adhésion, Contact, Espace membre (en-têtes + contenus / placeholders soignés).
- [x] SEO de base (metadata, OG, robots, sitemap), accessibilité (reduced-motion, focus, sémantique).
- [x] Build de production validé (16 routes statiques).

## 🟡 Phase 2 — Contenu dynamique & formulaires _(en cours)_

**Fait (front + contrat d'API, déployable sur Vercel) :**
- [x] **Formulaire d'adhésion** multi-étapes (formule → infos → paiement → confirmation), React Hook Form + Zod.
- [x] **Formulaire de contact** et **newsletter** fonctionnels.
- [x] Schémas Zod partagés (`src/lib/validation/schemas.ts`) + **routes API** Next.js avec validation serveur (`/api/adhesion`, `/api/contact`, `/api/newsletter`).
- [x] Formules d'adhésion (`src/lib/content/membership.ts`) — _tarifs provisoires à confirmer_.

**Fait (suite — front data-driven) :**
- [x] **Formulaire de partenariat** connecté (`/partenaires` + `/api/partenariat`) + vitrine partenaires.
- [x] **Blog / Actualités** piloté par les données : explorateur avec **catégories + recherche** (`/actualites`) et **pages article** (`/actualites/[slug]`, SSG).
- [x] **Événements** : **calendrier mensuel** navigable + filtres par type (`/evenements`) et **pages détail** (`/evenements/[slug]`, SSG).
- [x] Sitemap enrichi (articles + événements).

**Reste à faire (backend & intégrations) :**
- [ ] API **Laravel + MySQL** : modèles `members`, `events`, `articles`, `partners`, `messages`, `newsletter` + persistance des soumissions (remplacer les `TODO` dans les routes API).
  - Réutiliser les patterns du backend voisin (`EPCCI_BACKEND`) : auth JWT (`config/helpers.php`).
- [ ] **Paiement Mobile Money réel** (MTN/Orange/Moov/Wave) + carte — cf. `EPCCI_BACKEND/paiements/mtn_ci_webhook.php`. Nécessite les identifiants marchands.
- [ ] **E-mails** transactionnels (confirmation d'adhésion, accusé de contact/partenariat).
- [ ] **Inscription en ligne** dédiée aux événements + galerie.
- [ ] Mini back-office de contenu (actualités, événements, partenaires).

## 🔜 Phase 3 — Espace membre

- [ ] Authentification (inscription / connexion / mot de passe oublié), rôles.
- [ ] Tableau de bord membre, gestion du profil.
- [ ] **Annuaire des membres** + moteur de recherche interne.
- [ ] **Messagerie** entre membres + notifications.
- [ ] Espace documents / médias, agenda personnel.

## 🔜 Phase 4 — Back-office d'administration

- [ ] Gestion : membres, événements, partenaires, articles, médias, galerie, formulaires, inscriptions, documents.
- [ ] **Statistiques** et tableaux de bord.
- [ ] **Rôles & permissions** (RBAC).
- [ ] Modération et workflow de validation.
  > Option : back-office généré avec **Filament (Laravel)** pour accélérer.

## 🎯 Performance (cible continue)

- [ ] Lighthouse > 95, LCP < 2 s, CLS ~ 0.
- [ ] Remplacement des placeholders par de vraies photos optimisées (`next/image`, AVIF/WebP).
- [ ] Audit accessibilité WCAG AA complet.

---

## Points à confirmer avec le REJCC

- Coordonnées officielles (adresse, e-mail, téléphone) — actuellement provisoires dans `src/lib/content/site.ts`.
- Liens des réseaux sociaux.
- Statistiques réelles (membres, événements, mentors…).
- Photos authentiques (networking, mentorat, ateliers, cérémonies).
- Bureau exécutif / organigramme pour la page « À propos ».
- Formules et tarifs d'adhésion.
