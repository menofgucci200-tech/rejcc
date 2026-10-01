# Film de présentation REJCC (60 s)

Source du film `../REJCC-film-presentation-60s.mp4` (1920×1080, 30 i/s).
Tout le film est une fonction du temps (`film.js` → `render(t)`) ; ouvrir
`index.html` dans un navigateur le joue en boucle (aperçu).

Pour le régénérer, il faut d'abord recréer le dossier `assets/` (non versionné) :

- `assets/shots/` : captures du site, via `node shots.js` avec le frontend servi sur http://127.0.0.1:8020 ;
- `assets/seq_intro/`, `assets/seq_d/`, `assets/seq_m/` : images de l'animation d'accueil
  (plein écran 1920×1080, ordinateur 1440×900, mobile 390×844 @2x), numérotées `0000.jpg`… ;
- `fonts/` : Anton, Manrope (500/700/800) et Libre Caslon Display, copiés depuis `REJCC-Frontend/node_modules/@fontsource`.

Puis `node cap.js video frames 30 0 60` et assemblage avec ffmpeg :
`ffmpeg -framerate 30 -i frames/f%05d.jpg -c:v libx264 -crf 18 -pix_fmt yuv420p film.mp4`.
