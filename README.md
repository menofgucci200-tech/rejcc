# REJCC — Site & plateforme officiels

Site web premium du **REJCC — Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire**.
_« Ensemble pour l'excellence. »_

Expérience immersive et haut de gamme (inspiration Apple / Stripe / Linear / Awwwards), fidèle à la charte graphique officielle, pensée pour donner envie d'adhérer, de participer et de revenir.

---

## 🧱 Stack technique

| Domaine | Choix |
|---|---|
| Framework | **Next.js 15** (App Router) + **React 19** |
| Langage | **TypeScript** |
| Styles | **Tailwind CSS v4** (design tokens dans `globals.css`) |
| Animations | **Framer Motion** + **Lenis** (smooth scroll) |
| Icônes | **Lucide** (outline) + glyphes sociaux SVG dédiés |
| Polices | `next/font` — **Anton** (Impact), **Manrope** (Avenir Next), **Libre Caslon Display** (Big Caslon) |
| Backend (à venir) | **Laravel + MySQL** (API REST) — voir `ROADMAP.md` |

## 🚀 Démarrage

```bash
npm install
npm run dev      # http://localhost:3000
npm run build    # build de production
npm start        # serveur de production
```

Prérequis : Node 18.18+ (testé sur Node 25).

## 📁 Structure

```
src/
  app/                 # Routes (App Router) + layout, SEO (robots/sitemap), favicon
    page.tsx           # Accueil (11 sections)
    a-propos/ activites/ domaines/ evenements/
    actualites/ partenaires/ adhesion/ contact/ connexion/
    globals.css        # Design system (couleurs, typo, utilitaires, keyframes)
  components/
    layout/            # Navbar, Footer, Loader, SmoothScroll, ScrollProgress, PageHeader
    sections/          # Hero, About, Stats, WhyJoin, Values, DomainsPreview,
                       # HowToJoin, Testimonials, Events, News, CtaBand, ComingSoon
    ui/                # Container, Button, Eyebrow, SectionHeading, Reveal,
                       # Counter, LogoMark, SocialIcons
  lib/
    content/           # Contenu institutionnel (site, home, domains, activities, events, news)
    utils.ts           # helper cn()
public/brand/          # Logos extraits (couleur + blanc, lockup + monogramme)
docs/
  brand-source/        # PDFs officiels (identité de marque + charte graphique)
  brand-extracted/     # Assets logo extraits des PDFs
```

## 🎨 Charte respectée

- **Couleurs** : bleu `#031D59` (dominante), rouge `#AC0100` (accent), gris clair `#F4F6F8`, bleu clair `#4F6FBF`, gris foncé `#333333`.
- **Logo** : utilisé sans déformation, rotation, recoloration ni effet (contraintes de la charte). Animations limitées à des révélations (fondu / translation).
- **Iconographie** : exclusivement outline / géométrique.
- **Photos** : non fournies → placeholders de marque soignés (dégradés / monogramme), **à remplacer** par de vraies photos (networking, mentorat, ateliers…). Aucune image générique ou d'aspect « IA ».

## ♿ Accessibilité & SEO

- HTML sémantique, `lang="fr"`, hiérarchie de titres, `aria-label` sur les contrôles.
- `prefers-reduced-motion` respecté (animations et smooth scroll désactivés).
- Focus visible, contrastes conformes WCAG AA sur les couleurs de marque.
- Metadata complètes (Open Graph, Twitter), `robots.txt` et `sitemap.xml` générés.

## ⚠️ Contenu provisoire

Statistiques, témoignages, événements et actualités sont des **données d'exemple** (dans `src/lib/content/`), à remplacer par les contenus réels ou à piloter depuis le futur back-office.
