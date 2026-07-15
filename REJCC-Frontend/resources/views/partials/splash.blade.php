{{--
    Écran d'ouverture animé (~3 s). Affiché UNIQUEMENT dans l'application
    installée (PWA en mode standalone), une seule fois par lancement. Aucun
    effet dans un navigateur classique. Placé tout en haut de <body> et retiré
    de façon synchrone si non applicable, pour éviter tout clignotement.
    Astuce QA : ajoutez « ?splash=1 » à l'URL pour le prévisualiser au navigateur.
--}}
<div id="rejcc-splash" role="presentation" aria-hidden="true">
    <span class="splash-glow"></span>
    <div class="splash-stage">
        <img src="{{ asset('brand/rejcc-logo-color.png') }}" alt="REJCC" class="splash-logo" width="210" height="380">
        <p class="splash-tagline">Ensemble pour l'excellence.</p>
    </div>
    <div class="splash-bar"><span></span></div>
</div>

<style>
    #rejcc-splash {
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: radial-gradient(125% 120% at 50% 36%, #ffffff 0%, #eef2f9 100%);
        opacity: 1;
        transition: opacity .5s ease;
    }
    #rejcc-splash.is-out { opacity: 0; }
    #rejcc-splash .splash-glow {
        position: absolute;
        width: 62vmin;
        height: 62vmin;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(79, 111, 191, .16), transparent 68%);
        animation: splashGlow 2.6s ease-in-out infinite;
    }
    #rejcc-splash .splash-stage {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    #rejcc-splash .splash-logo {
        width: min(210px, 56vw);
        height: auto;
        opacity: 0;
        transform: translateY(14px) scale(.86);
        animation: splashLogo .9s cubic-bezier(.22, 1, .36, 1) .1s forwards;
    }
    #rejcc-splash .splash-tagline {
        margin-top: 20px;
        font-family: ui-serif, Georgia, 'Times New Roman', serif;
        font-style: italic;
        font-size: 14.5px;
        letter-spacing: .01em;
        color: #5B677A;
        opacity: 0;
        animation: splashFade .7s ease .85s forwards;
    }
    #rejcc-splash .splash-bar {
        position: absolute;
        bottom: 13%;
        width: min(172px, 44vw);
        height: 3px;
        border-radius: 999px;
        background: rgba(3, 29, 89, .12);
        overflow: hidden;
    }
    #rejcc-splash .splash-bar span {
        display: block;
        height: 100%;
        width: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #031d59, #AC0100);
        transform: scaleX(0);
        transform-origin: left;
        animation: splashBar 2.6s ease .15s forwards;
    }
    @keyframes splashLogo { to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes splashFade { to { opacity: 1; } }
    @keyframes splashBar { to { transform: scaleX(1); } }
    @keyframes splashGlow {
        0%, 100% { transform: scale(.9); opacity: .65; }
        50% { transform: scale(1.08); opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        #rejcc-splash .splash-glow { animation: none; }
        #rejcc-splash .splash-logo,
        #rejcc-splash .splash-tagline,
        #rejcc-splash .splash-bar span {
            animation: splashFade .3s ease forwards;
            opacity: 1;
            transform: none;
        }
    }
</style>

<script>
    (function () {
        var splash = document.getElementById('rejcc-splash');
        if (!splash) return;

        var hold = location.search.indexOf('splash=hold') !== -1; // QA : fige l'écran
        var force = hold || location.search.indexOf('splash=1') !== -1;
        var standalone = false;
        try {
            standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true
                || document.referrer.indexOf('android-app://') === 0;
        } catch (e) {}

        // Navigateur classique, ou déjà montré durant ce lancement : on retire
        // immédiatement (synchrone, avant peinture → aucun flash).
        if (!force && (!standalone || sessionStorage.getItem('rejcc_splash_shown'))) {
            splash.parentNode && splash.parentNode.removeChild(splash);
            return;
        }

        try { sessionStorage.setItem('rejcc_splash_shown', '1'); } catch (e) {}
        var htmlEl = document.documentElement;
        var prevOverflow = htmlEl.style.overflow;
        htmlEl.style.overflow = 'hidden';

        if (hold) return; // QA : on laisse le splash affiché indéfiniment

        setTimeout(function () {
            splash.classList.add('is-out');
            setTimeout(function () {
                splash.parentNode && splash.parentNode.removeChild(splash);
                htmlEl.style.overflow = prevOverflow;
            }, 520);
        }, 3000);
    })();
</script>
