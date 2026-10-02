{{--
    Bouton flottant « Découvrez le parcours d'adhésion » : un disque bleu avec
    un texte circulaire qui tourne. Au clic, la vidéo du parcours s'ouvre en
    plein écran par une « plongée » circulaire (resources/js/tour-player.js).
    Le film existe en 16:9 et en 9:16 ; le lecteur choisit selon l'écran.
--}}
<button type="button" class="rj-tour-fab" data-tour-open
    data-src-landscape="{{ asset('videos/parcours-adhesion-16x9.mp4') }}"
    data-src-portrait="{{ asset('videos/parcours-adhesion-9x16.mp4') }}"
    data-poster-landscape="{{ asset('videos/parcours-adhesion-16x9.jpg') }}"
    data-poster-portrait="{{ asset('videos/parcours-adhesion-9x16.jpg') }}"
    data-captions="{{ asset('videos/parcours-adhesion.fr.vtt') }}"
    aria-label="Voir le film du parcours d'adhésion (2 min 30)">
    <svg class="rj-tour-fab__ring" viewBox="0 0 120 120" aria-hidden="true">
        <defs><path id="rj-tour-circle" d="M60 60 m-47 0 a47 47 0 1 1 94 0 a47 47 0 1 1 -94 0"/></defs>
        <text><textPath href="#rj-tour-circle" startOffset="0" textLength="292" lengthAdjust="spacing">DÉCOUVREZ LE PARCOURS D'ADHÉSION • EN VIDÉO • </textPath></text>
    </svg>
    <span class="rj-tour-fab__core" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg>
    </span>
    <span class="rj-tour-fab__tip" aria-hidden="true">Le parcours d'adhésion en vidéo · 2 min 30</span>
</button>
