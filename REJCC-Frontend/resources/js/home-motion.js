// Motion design de l'accueil : orchestre l'intro plein écran puis l'entrée
// du hero. Les animations elles-mêmes sont en CSS (resources/css/home-motion.css) ;
// ce module se contente de poser les classes au bon moment, de caler le
// rideau sur la croix du logo, de gérer « Passer » et la parallaxe.

// Repères de la timeline de l'intro (secondes) — à garder alignés avec le CSS.
const WIPE_AT = 5.1; // début du rideau bleu
const HERO_OFFSET = 5.8; // le hero démarre quand le rideau s'efface
const CROSS = { x: 428, y: 395 }; // centre de la croix, repère du logo

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function startHero(hero, offset) {
    if (!hero || hero.classList.contains('is-live')) return;
    hero.style.setProperty('--o', `${offset}s`);
    hero.classList.add('is-live');
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

function initParallax(hero) {
    if (!hero || !window.matchMedia('(pointer: fine)').matches) return;
    const layers = [...hero.querySelectorAll('[data-parallax]')];
    if (!layers.length) return;

    let tx = 0, ty = 0, x = 0, y = 0, raf = 0;
    const tick = () => {
        x += (tx - x) * 0.08;
        y += (ty - y) * 0.08;
        layers.forEach((el) => {
            const k = parseFloat(el.dataset.parallax) || 0;
            el.style.translate = `${(x * k).toFixed(2)}px ${(y * k).toFixed(2)}px`;
        });
        raf = Math.abs(tx - x) + Math.abs(ty - y) > 0.001 ? requestAnimationFrame(tick) : 0;
    };
    hero.addEventListener('pointermove', (e) => {
        const r = hero.getBoundingClientRect();
        tx = (e.clientX - r.left) / r.width - 0.5;
        ty = (e.clientY - r.top) / r.height - 0.5;
        if (!raf) raf = requestAnimationFrame(tick);
    });
    hero.addEventListener('pointerleave', () => {
        tx = 0;
        ty = 0;
        if (!raf) raf = requestAnimationFrame(tick);
    });
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

    initParallax(hero);

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
