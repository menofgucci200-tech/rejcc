{{--
    Intro animée de l'accueil (~6 s) — « La construction du réseau ».
    1. Positionnement : FOI. INNOVATION. ENTREPRENEURIAT.
    2. Le monogramme se construit pièce par pièce (tracé → remplissage), le E
       sort du R, la croix et le J s'élèvent : l'union des talents.
    3. Mot REJCC + signature typographique officielle, puis le slogan.
    4. Un fondu au bleu REJCC part de la croix et ouvre sur l'accueil.

    Jouée une fois par session de navigation, jamais si l'utilisateur a demandé
    à réduire les animations. « Passer », Échap ou la molette l'écourtent.
    QA : ?intro=1 force la lecture.
    Pilotée par resources/js/home-motion.js ; styles dans resources/css/home-motion.css.
--}}
<div id="rejcc-intro" class="rj-intro" role="presentation" aria-hidden="true">
    <span class="rj-intro__corner rj-intro__corner--tl"></span>
    <span class="rj-intro__corner rj-intro__corner--tr"></span>
    <span class="rj-intro__corner rj-intro__corner--bl"></span>
    <span class="rj-intro__corner rj-intro__corner--br"></span>

    <p class="rj-intro__meta rj-intro__meta--tl">Foi · Innovation · Entrepreneuriat</p>
    <p class="rj-intro__meta rj-intro__meta--bl">Côte d'Ivoire</p>

    {{-- Acte 1 : positionnement --}}
    <div class="rj-intro__words">
        @foreach (['Foi', 'Innovation', 'Entrepreneuriat'] as $i => $word)
            <span class="rj-intro__word" style="--i: {{ $i }}"><span>{{ $word }}<b>.</b></span></span>
        @endforeach
    </div>

    {{-- Actes 2-3 : construction du logo principal --}}
    <div class="rj-intro__stage">
        <svg class="rj-intro__logo" viewBox="236 28 538 950" role="img" aria-label="REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques">
            <defs>
                <clipPath id="rj-clip-bars"><rect x="338" y="100" width="260" height="160"/></clipPath>
                <clipPath id="rj-clip-cross"><rect x="320" y="200" width="200" height="436"/></clipPath>
                <clipPath id="rj-clip-word"><rect x="240" y="700" width="530" height="116"/></clipPath>
            </defs>

            <g class="rj-guides" fill="none">
                <line pathLength="1" x1="506" y1="30" x2="506" y2="976"/>
                <line pathLength="1" x1="238" y1="458" x2="772" y2="458"/>
                <line pathLength="1" x1="238" y1="812" x2="772" y2="812"/>
                <rect pathLength="1" x="258" y="52" width="497" height="913"/>
                <circle pathLength="1" cx="428" cy="395" r="66"/>
            </g>

            <g class="rj-ink">
                <use href="#rj-stem-r" class="rj-draw" style="--d: .05s"/>
                <use href="#rj-stem-uc" class="rj-draw" style="--d: .2s"/>
                <use href="#rj-arc" class="rj-draw" style="--d: .35s"/>
                <use href="#rj-inner-c" class="rj-draw" style="--d: .5s"/>
            </g>

            <g class="rj-red" clip-path="url(#rj-clip-bars)">
                <use href="#rj-bar-1" class="rj-slide-x" style="--d: 0s"/>
                <use href="#rj-bar-2" class="rj-slide-x" style="--d: .1s"/>
            </g>
            <g class="rj-red" clip-path="url(#rj-clip-cross)">
                <use href="#rj-cross-j" class="rj-rise" id="rj-intro-cross"/>
            </g>

            <g class="rj-navy" clip-path="url(#rj-clip-word)">
                @foreach (['w-r', 'w-e', 'w-j', 'w-c1', 'w-c2'] as $i => $piece)
                    <use href="#rj-{{ $piece }}" class="rj-letter" style="--i: {{ $i }}"/>
                @endforeach
            </g>

            <use href="#rj-line-1" class="rj-navy rj-line" style="--i: 0"/>
            <use href="#rj-line-2" class="rj-navy rj-line" style="--i: 1"/>
            <use href="#rj-line-3" class="rj-red rj-line" style="--i: 2"/>
        </svg>

        <p class="rj-intro__slogan">
            @foreach (['Ensemble', 'pour', "l'excellence"] as $i => $w)
                <span style="--i: {{ $i }}"><span>{{ $w }}@if ($loop->last)<b>.</b>@endif</span></span>
            @endforeach
        </p>
    </div>

    <button type="button" class="rj-intro__skip" data-intro-skip tabindex="-1">
        Passer l'intro
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>

    <span class="rj-intro__progress"></span>
    <span class="rj-intro__wipe"></span>
</div>

<script>
    // Décision synchrone (avant peinture) : on retire l'intro si elle ne doit pas jouer.
    (function () {
        var intro = document.getElementById('rejcc-intro');
        if (!intro) return;
        var html = document.documentElement;
        var force = /[?&]intro=1/.test(location.search);
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var seen = false;
        try { seen = sessionStorage.getItem('rejcc_intro_seen') === '1'; } catch (e) {}
        var splash = document.getElementById('rejcc-splash'); // app installée : le splash suffit

        // window.Livewire n'existe qu'après le premier chargement : une arrivée par
        // navigation interne (wire:navigate) ne rejoue jamais l'intro.
        var navigated = !!window.Livewire || html.classList.contains('app-loaded');

        if (!force && (reduce || seen || splash || navigated)) {
            intro.parentNode.removeChild(intro);
            return;
        }
        try { sessionStorage.setItem('rejcc_intro_seen', '1'); } catch (e) {}

        html.classList.add('rj-intro-on');
        var loader = document.getElementById('page-loader');
        if (loader) loader.parentNode.removeChild(loader);

        // Filet de sécurité : si le script d'animation ne démarre pas, on libère la page.
        setTimeout(function () {
            if (window.__rjMotionStarted || !intro.parentNode) return;
            intro.parentNode.removeChild(intro);
            html.classList.remove('rj-intro-on');
        }, 3000);
    })();
</script>
