// Motion design de l'accueil : orchestre l'intro plein écran puis l'entrée
// du hero (gros plan vidéo sur l'écran de la salle, puis recul de la caméra).
// Les animations du texte sont en CSS (resources/css/home-motion.css) ; ce
// module pose les classes au bon moment, cale le rideau sur la croix du logo,
// gère « Passer » et pilote la vidéo et le recul.

// Repères de la timeline de l'intro (secondes) — à garder alignés avec le CSS.
const WIPE_AT = 5.1; // début du rideau bleu
const HERO_OFFSET = 5.8; // le hero démarre quand le rideau s'efface
const CROSS = { x: 428, y: 395 }; // centre de la croix, repère du logo

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function startHero(hero, offset) {
    if (!hero || hero.classList.contains('is-live')) return;
    if (hero._cine) {
        hero._cine.start(offset);
        return;
    }
    hero.style.setProperty('--o', `${offset}s`);
    hero.classList.add('is-live');
}

/*
 * Ouverture « Autour de la table » : gros plan sur l'écran de la salle de
 * réunion où deux mains se rejoignent (vidéo de 3,7 s), puis la caméra recule
 * pour révéler l'assemblée et le titre apparaît.
 *
 * La scène (illustration + écran) est dimensionnée en « cover » sur le hero ;
 * le gros plan est une simple transformation (translate + scale) qui amène
 * l'écran à couvrir la partie visible du hero. Le recul anime cette
 * transformation vers l'identité.
 */
const ECRAN = { x: 0, y: 0.1519, w: 0.3565, h: 0.4488 }; // position de l'écran dans l'illustration
const RATIO = 1728 / 1132;
const RECUL_A = 2.35; // seconde de la vidéo où la caméra commence à reculer
const RECUL_MS = 1700;

function setupCinema(hero) {
    const scene = hero.querySelector('[data-cine-scene]');
    const video = hero.querySelector('[data-cine-video]');
    if (!scene || !video) return null;

    const connexion = navigator.connection;
    const econome = !!connexion && (connexion.saveData || /(^|-)2g$/.test(connexion.effectiveType || ''));
    let dejaVu = false;
    try { dejaVu = sessionStorage.getItem('rejcc_hero_vu') === '1'; } catch (e) {}

    const posterFin = video.getAttribute('poster');
    let geo = null;
    const placer = () => {
        const W = hero.clientWidth;
        const H = hero.clientHeight;
        const sw = Math.max(W, H * RATIO) * 1.06;
        const sh = sw / RATIO;
        const fx = W < 768 ? 0.3 : 0.5;
        const left = (W - sw) * fx;
        const top = (H - sh) * 0.38;
        Object.assign(scene.style, { width: `${sw}px`, height: `${sh}px`, left: `${left}px`, top: `${top}px` });

        // Gros plan : l'écran couvre la partie visible du hero (au plus la hauteur de la fenêtre).
        const vh = Math.min(H, window.innerHeight || H);
        const ex = ECRAN.x * sw, ey = ECRAN.y * sh, ew = ECRAN.w * sw, eh = ECRAN.h * sh;
        const k = Math.max(W / ew, vh / eh) * 1.01;
        const tx = W / 2 - left - k * (ex + ew / 2);
        const ty = vh / 2 - top - k * (ey + eh / 2);
        geo = { zoom: `translate(${tx}px, ${ty}px) scale(${k})` };
    };

    let etat = 'attente'; // attente → lecture → recul → fini
    let anim = null;

    const montrerTexte = () => {
        hero.style.setProperty('--o', '0s');
        hero.classList.add('is-live');
    };

    const reculer = (rapide = false) => {
        if (etat === 'recul' || etat === 'fini') return;
        etat = 'recul';
        detacher();
        try { sessionStorage.setItem('rejcc_hero_vu', '1'); } catch (e) {}
        const duree = rapide ? 900 : RECUL_MS;
        hero.classList.remove('is-cine-zoom');
        scene.style.transform = '';
        anim = scene.animate(
            [
                { transform: geo.zoom, filter: 'blur(0px) saturate(1)' },
                { transform: 'none', filter: 'blur(2.5px) saturate(1.08)' },
            ],
            { duration: duree, easing: 'cubic-bezier(0.65, 0, 0.25, 1)' },
        );
        setTimeout(montrerTexte, duree * 0.45);
        anim.finished.then(() => {
            etat = 'fini';
            hero.classList.add('is-cine-done');
        }, () => {});
    };

    const surTemps = () => {
        if (video.currentTime >= RECUL_A) reculer();
    };
    const passer = () => {
        if (etat === 'lecture' || etat === 'attente') reculer(true);
    };
    const onKey = (e) => {
        if (['Escape', 'Enter', ' ', 'ArrowDown', 'PageDown'].includes(e.key)) passer();
    };
    const detacher = () => {
        video.removeEventListener('timeupdate', surTemps);
        video.removeEventListener('ended', surTemps);
        window.removeEventListener('wheel', passer);
        window.removeEventListener('touchmove', passer);
        window.removeEventListener('keydown', onKey);
        hero.removeEventListener('click', passer);
    };

    placer();
    hero.classList.add('is-cine-zoom');
    scene.style.transform = geo.zoom;
    requestAnimationFrame(() => hero.classList.add('is-cine-ready'));

    // Lecture complète une fois par session ; ensuite (ou connexion économe),
    // on part directement de la poignée de main serrée et la caméra recule.
    const lire = !dejaVu && !econome;
    if (lire) {
        video.poster = video.dataset.posterDebut || video.poster;
        video.preload = 'auto';
        video.load();
    }

    let redim = 0;
    new ResizeObserver(() => {
        cancelAnimationFrame(redim);
        redim = requestAnimationFrame(() => {
            placer();
            if (etat === 'attente' || etat === 'lecture') scene.style.transform = geo.zoom;
        });
    }).observe(hero);

    let minuteur = 0;
    const go = () => {
        clearTimeout(minuteur);
        if (etat !== 'attente') return;
        if (!lire) {
            setTimeout(reculer, 350);
            return;
        }
        etat = 'lecture';
        video.addEventListener('timeupdate', surTemps);
        video.addEventListener('ended', surTemps);
        window.addEventListener('wheel', passer, { passive: true });
        window.addEventListener('touchmove', passer, { passive: true });
        window.addEventListener('keydown', onKey);
        hero.addEventListener('click', passer);
        const p = video.play();
        if (p && p.catch) {
            p.catch(() => {
                // Lecture refusée (mode économie d'énergie…) : on montre la poignée de main serrée.
                video.poster = posterFin;
                reculer();
            });
        }
        // Garde-fou : vidéo trop lente à charger.
        setTimeout(() => { if (etat === 'lecture' && video.currentTime < 0.2) reculer(); }, 4500);
    };

    return {
        start(offset) {
            clearTimeout(minuteur);
            minuteur = setTimeout(go, Math.max(0, offset * 1000));
        },
        startNow: go,
    };
}

function seekForward(root, ms) {
    root.getAnimations({ subtree: true }).forEach((a) => {
        if ((a.currentTime ?? 0) < ms) a.currentTime = ms;
    });
}

function playIntro(intro, hero) {
    const html = document.documentElement;
    window.__lenis?.stop();
    window.scrollTo(0, 0);

    // Le rideau bleu s'ouvre depuis la croix du logo.
    const svg = intro.querySelector('.rj-intro__logo');
    const ctm = svg?.getScreenCTM();
    if (ctm) {
        const p = new DOMPoint(CROSS.x, CROSS.y).matrixTransform(ctm);
        intro.style.setProperty('--wx', `${p.x}px`);
        intro.style.setProperty('--wy', `${p.y}px`);
    }

    intro.classList.add('is-playing');
    startHero(hero, HERO_OFFSET);

    let done = false;
    const finish = () => {
        if (done) return;
        done = true;
        detach();
        intro.remove();
        html.classList.remove('rj-intro-on');
        window.__lenis?.start();
    };

    const skip = () => {
        const ms = WIPE_AT * 1000;
        seekForward(intro, ms);
        if (hero) seekForward(hero, ms);
        if (hero?._cine) setTimeout(() => hero._cine.startNow(), (HERO_OFFSET - WIPE_AT) * 1000);
    };
    const onKey = (e) => {
        if (e.key === 'Escape' || e.key === 'Enter' || e.key === ' ') skip();
    };
    const button = intro.querySelector('[data-intro-skip]');
    const detach = () => {
        button?.removeEventListener('click', skip);
        window.removeEventListener('keydown', onKey);
        window.removeEventListener('wheel', skip);
        window.removeEventListener('touchmove', skip);
    };
    button?.addEventListener('click', skip);
    window.addEventListener('keydown', onKey);
    window.addEventListener('wheel', skip, { passive: true });
    window.addEventListener('touchmove', skip, { passive: true });

    const out = intro.getAnimations().find((a) => a.animationName === 'rj-intro-out');
    if (out) {
        out.finished.then(finish, finish);
    }
    setTimeout(finish, 9000); // garde-fou (onglet en arrière-plan, etc.)
}

export function initHomeMotion() {
    const hero = document.querySelector('[data-hero-motion]');
    const intro = document.getElementById('rejcc-intro');
    if (!hero && !intro) return;
    if ((hero ?? intro).dataset.motionReady) return; // boot() peut être rappelé
    (hero ?? intro).dataset.motionReady = '1';
    window.__rjMotionStarted = true;

    if (reduceMotion()) {
        intro?.remove();
        document.documentElement.classList.remove('rj-intro-on');
        hero?.classList.add('is-live');
        return;
    }

    if (hero) hero._cine = setupCinema(hero);

    // On attend les polices (Anton, Caslon) pour éviter tout saut de texte.
    const fonts = document.fonts?.ready ?? Promise.resolve();
    const timeout = new Promise((resolve) => setTimeout(resolve, 800));
    Promise.race([fonts, timeout]).then(() => {
        if (intro?.isConnected && getComputedStyle(intro).display !== 'none') {
            playIntro(intro, hero);
        } else {
            intro?.remove();
            document.documentElement.classList.remove('rj-intro-on');
            startHero(hero, 0);
        }
    });
}
