# Mettre le site REJCC en ligne

> Une fois hébergé dans le cloud, le site est en ligne **24h/24, même votre ordinateur éteint**.
> À chaque mise à jour du code, le site se met à jour **automatiquement** → les membres suivent l'évolution.

Le dépôt git local est déjà prêt (premier commit effectué).

---

## ✅ Méthode recommandée — GitHub + Vercel (gratuit)

Avantage : déploiement **automatique** à chaque évolution.

### 1. Créer un compte (si besoin)
- GitHub : https://github.com/signup
- Vercel : https://vercel.com/signup → **« Continue with GitHub »** (relie les deux comptes)

### 2. Créer un dépôt GitHub vide
Sur https://github.com/new → nom `rejcc` → **Private** (recommandé) → *Create repository*.
⚠️ Ne pas cocher « Add README ».

### 3. Envoyer le code (à exécuter dans `C:\projet\REJCC`)
Remplacez `VOTRE-COMPTE` par votre identifiant GitHub :
```bash
git remote add origin https://github.com/VOTRE-COMPTE/rejcc.git
git push -u origin main
```
(GitHub demandera de vous authentifier dans le navigateur la première fois.)

### 4. Déployer sur Vercel
1. https://vercel.com/new → **Import** le dépôt `rejcc`.
2. Vercel détecte **Next.js** tout seul → aucune configuration.
3. Cliquez **Deploy**. Au bout d'~1 min, vous obtenez une URL publique : `https://rejcc.vercel.app`.

### 5. Les mises à jour ensuite
Chaque fois que le code évolue :
```bash
git add -A
git commit -m "Description de la mise à jour"
git push
```
→ Vercel reconstruit et met le site à jour **automatiquement**.

---

## ⚡ Méthode alternative — Vercel CLI (sans GitHub)

La plus rapide pour une première mise en ligne, mais redéploiement manuel.
```bash
npm i -g vercel
vercel login        # connexion dans le navigateur
vercel              # déploiement de prévisualisation
vercel --prod       # déploiement en production (URL publique)
```

---

## 🌐 Votre nom de domaine (plus tard)
Pour passer de `rejcc.vercel.app` à **`rejcc.ci`** : Vercel → projet → *Settings → Domains* → ajouter le domaine, puis pointer les DNS chez votre registrar. (Le `siteConfig.url` est déjà réglé sur `https://www.rejcc.ci`.)

## 🔒 Bon à savoir
- Le dépôt en **Privé** : votre code n'est pas public, mais **le site en ligne reste accessible à tous** via son lien.
- Les PDFs de la charte (`docs/brand-source/`) sont dans le dépôt → garder le dépôt **Privé** évite de les exposer.
- Pas de secret/mot de passe dans le code à ce stade (le backend viendra en Phase 2 avec des variables d'environnement sécurisées sur Vercel).
