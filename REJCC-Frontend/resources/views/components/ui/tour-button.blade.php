{{--
    Bouton flottant « Découvrez le REJCC en vidéo » : un disque bleu avec un
    texte circulaire qui tourne. Au clic, le film de présentation (site,
    adhésion, validation, espace membre) s'ouvre en plein écran par une
    « plongée » circulaire (resources/js/tour-player.js).
    Le film existe en 16:9 et en 9:16 ; le lecteur choisit selon l'écran.
--}}
<button type="button" class="rj-tour-fab" data-tour-open
    data-src-landscape="{{ asset('videos/presentation-rejcc-16x9.mp4') }}"
    data-src-portrait="{{ asset('videos/presentation-rejcc-9x16.mp4') }}"
    data-poster-landscape="{{ asset('videos/presentation-rejcc-16x9.jpg') }}"
    data-poster-portrait="{{ asset('videos/presentation-rejcc-9x16.jpg') }}"
    data-captions="{{ asset('videos/presentation-rejcc.fr.vtt') }}"
    aria-label="Voir le film de présentation de la plateforme (3 min 50)">
    <svg class="rj-tour-fab__ring" viewBox="0 0 120 120" aria-hidden="true">
        <defs><path id="rj-tour-circle" d="M60 60 m-47 0 a47 47 0 1 1 94 0 a47 47 0 1 1 -94 0"/></defs>
        <text><textPath href="#rj-tour-circle" startOffset="0" textLength="292" lengthAdjust="spacing">DÉCOUVREZ LA PLATEFORME REJCC • EN VIDÉO • </textPath></text>
    </svg>
    <span class="rj-tour-fab__core" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg>
    </span>
    <span class="rj-tour-fab__tip" aria-hidden="true">La plateforme REJCC en vidéo · 3 min 50</span>
</button>
