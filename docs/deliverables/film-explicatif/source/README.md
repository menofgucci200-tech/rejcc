# Film explicatif « Qu'est-ce que le REJCC ? » (60 s, 1920×1080)

Animation entièrement codée : `explainer.js` décrit chaque image comme une fonction du temps
(`render(t)`). Ouvrir `index.html` dans un navigateur joue le film en boucle (aperçu).

- `logo.js` : pièces vectorielles du logo officiel (tracé fidèle du fichier de la charte) ;
- `data.js` : contour de la Côte d'Ivoire (Natural Earth 1:10m, domaine public), villes,
  pictogrammes linéaires du site (Lucide, licence ISC) ;
- polices : copier dans `fonts/` Anton, Manrope (500/700/800) et Libre Caslon Display depuis
  `REJCC-Frontend/node_modules/@fontsource`.

Rendu : `node cap.js video frames 30 0 60`, puis
`ffmpeg -framerate 30 -i frames/f%05d.jpg -c:v libx264 -crf 17 -pix_fmt yuv420p film.mp4`.

Contenus repris de la charte (vision, mission, positionnement, valeurs, publics) et du site
(activités, avantages, 33 domaines en 9 pôles).
