// Motion design de l'accueil de la vitrine (resources/views/pages/home.blade.php).
// Les animations sont déclarées dans le HTML par des attributs data-p-* ; ce
// module les branche avec GSAP + ScrollTrigger. L'intro du logo reste pilotée
// par home-motion.js : le hero ([data-p-hero]) démarre quand elle s'efface.

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);
if (import.meta.env.DEV) Object.assign(window, { __gsap: gsap, __st: ScrollTrigger }); // inspection en local

let ctx = null;
let nettoyages = [];
const calme = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const tactile = () => window.matchMedia('(hover: none)').matches;
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

/* ------------------------------------------------------------------ texte */

// Découpe un titre en mots (ou en lettres) sans casser les balises internes
// (italique Caslon, point rouge…).
function decouper(el, mode) {
    if (el.dataset.pDecoupe) return;
    el.dataset.pDecoupe = '1';
    const marcher = (noeud) => {
        [...noeud.childNodes].forEach((n) => {
            // Ponctuation collée à un mot mis en forme (« entreprend, ») : on les
            // garde ensemble pour qu'elle ne passe jamais seule à la ligne.
            const colle = n.nodeType === 3 && n.previousSibling?.nodeType === 1 && n.textContent.match(/^[,.;:!?»)]+/);
            if (colle) {
                const bloc = document.createElement('span');
                bloc.style.whiteSpace = 'nowrap';
                const prec = n.previousSibling;
                prec.replaceWith(bloc);
                const ponct = document.createElement('span');
                ponct.className = 'p-mot';
                ponct.innerHTML = `<span class="p-mot-i">${colle[0]}</span>`;
                bloc.append(prec, ponct);
                n.textContent = n.textContent.slice(colle[0].length);
            }
            if (n.nodeType === 3) {
                const frag = document.createDocumentFragment();
                n.textContent.split(/(\s+)/).forEach((morceau) => {
                    if (!morceau) return;
                    if (/^\s+$/.test(morceau)) return frag.append(' ');
                    const mot = document.createElement('span');
                    mot.className = 'p-mot';
                    const dedans = document.createElement('span');
                    dedans.className = 'p-mot-i';
                    if (mode === 'chars') {
                        [...morceau].forEach((c) => {
                            const l = document.createElement('span');
                            l.className = 'p-lettre';
                            l.textContent = c;
                            dedans.append(l);
                        });
                    } else {
                        dedans.textContent = morceau;
                    }
                    mot.append(dedans);
                    frag.append(mot);
                });
                n.replaceWith(frag);
            } else if (n.nodeType === 1 && !n.matches('br')) {
                marcher(n);
            }
        });
    };
    marcher(el);
}

function cibles(el) {
    return el.dataset.pSplit === 'chars' ? $$('.p-lettre, .p-mot-i:not(:has(.p-lettre))', el) : $$('.p-mot-i', el);
}

/* ------------------------------------------------------------------- hero */

function hero() {
    const h = document.querySelector('[data-p-hero]');
    if (!h) return;

    const tl = gsap.timeline({ paused: true, defaults: { ease: 'expo.out' } });
    $$('[data-p-in]', h)
        .sort((a, b) => (+a.dataset.pAt || 0) - (+b.dataset.pAt || 0))
        .forEach((el) => {
            const at = +el.dataset.pAt || 0;
            const type = el.dataset.pIn;
            if (el.dataset.pSplit) {
                decouper(el, el.dataset.pSplit);
                gsap.set(el, { autoAlpha: 1 });
                tl.fromTo(cibles(el), { yPercent: 115 }, { yPercent: 0, duration: 1.1, stagger: el.dataset.pSplit === 'chars' ? 0.035 : 0.08 }, at);
                return;
            }
            const de = {
                up: { y: 40, autoAlpha: 0 },
                droite: { x: 60, autoAlpha: 0 },
                zoom: { scale: 1.25, autoAlpha: 0 },
                trait: { scaleX: 0, transformOrigin: 'left center' },
                fondu: { autoAlpha: 0 },
            }[type] ?? { y: 30, autoAlpha: 0 };
            const enfants = el.hasAttribute('data-p-each') ? [...el.children] : [el];
            if (el.hasAttribute('data-p-each')) gsap.set(el, { autoAlpha: 1 });
            tl.fromTo(enfants, de, { x: 0, y: 0, scale: 1, scaleX: 1, autoAlpha: 1, duration: 1, stagger: 0.1 }, at);
        });
    tl.progress(0); // pose l'état de départ (éléments masqués)
    h.classList.add('p-arme');

    let parti = false;
    const go = (delai = 0) => {
        if (parti) return;
        parti = true;
        gsap.delayedCall(delai, () => {
            tl.play();
            h.classList.add('p-parti');
        });
    };

    // L'intro pose is-live et --o (délai avant l'effacement du rideau) ; si on
    // la passe, elle disparaît plus tôt : on démarre alors tout de suite.
    const intro = document.getElementById('rejcc-intro');
    const lire = () => parseFloat(getComputedStyle(h).getPropertyValue('--o')) || 0;
    if (!intro) go(0.1);
    else {
        if (h.classList.contains('is-live')) go(lire());
        const surClasse = new MutationObserver(() => h.classList.contains('is-live') && go(lire()));
        const surIntro = new MutationObserver(() => !intro.isConnected && go(0));
        surClasse.observe(h, { attributes: true, attributeFilter: ['class'] });
        surIntro.observe(document.body, { childList: true });
        nettoyages.push(() => { surClasse.disconnect(); surIntro.disconnect(); });
    }
}

/* -------------------------------------------------------- apparitions */

const ENTREES = {
    up: { y: 60, autoAlpha: 0 },
    droite: { x: 80, autoAlpha: 0 },
    pop: { scale: 0.4, rotate: () => gsap.utils.random(-10, 10), autoAlpha: 0 },
    rideau: { clipPath: 'inset(0 0 100% 0)' },
};

function apparitions() {
    $$('[data-p-reveal]').forEach((el) => {
        const type = el.dataset.pReveal || 'up';
        const enfants = el.hasAttribute('data-p-each') ? [...el.children] : [el];
        gsap.set(el, { visibility: 'visible' });
        gsap.fromTo(enfants, ENTREES[type] ?? ENTREES.up, {
            x: 0, y: 0, scale: 1, rotate: 0, autoAlpha: 1, clipPath: 'inset(0 0 0% 0)',
            duration: type === 'pop' ? 1.2 : 1.1,
            ease: type === 'pop' ? 'elastic.out(1,.55)' : 'expo.out',
            stagger: +el.dataset.pStagger || 0.1,
            scrollTrigger: { trigger: el, start: 'top 88%' },
        });
    });

    $$('[data-p-split]:not([data-p-in])').forEach((el) => {
        decouper(el, el.dataset.pSplit);
        gsap.fromTo(cibles(el), { yPercent: 115 }, { yPercent: 0, duration: 1, ease: 'expo.out', stagger: el.dataset.pSplit === 'chars' ? 0.025 : 0.06, scrollTrigger: { trigger: el, start: 'top 88%' } });
    });

    // Texte qui « s'allume » mot par mot au défilement.
    $$('[data-p-fill]').forEach((el) => {
        decouper(el, 'words');
        gsap.fromTo($$('.p-mot-i', el), { opacity: 0.14 }, { opacity: 1, stagger: 0.1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: el.dataset.pFill || 'bottom 45%', scrub: true } });
    });

    // Élément qui grandit au défilement (appel final).
    $$('[data-p-grandit]').forEach((el) => {
        gsap.fromTo(el, { scale: +el.dataset.pGrandit || 0.6 }, { scale: 1, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'center 55%', scrub: true } });
    });

    // Fond de section qui change de couleur au défilement.
    $$('[data-p-teinte]').forEach((el) => {
        const [a, b] = el.dataset.pTeinte.split('|');
        gsap.fromTo(el, { backgroundColor: a }, { backgroundColor: b, ease: 'none', scrollTrigger: { trigger: el, start: 'top 60%', end: 'top 0%', scrub: true } });
    });
}

/* -------------------------------------------------------- chiffres clés */

function chiffres() {
    // Compteur à rouleaux : une colonne de 0 à 9 par chiffre.
    $$('[data-p-odo]').forEach((el) => {
        const texte = el.dataset.pOdo;
        el.textContent = '';
        el.classList.add('p-odo');
        el.setAttribute('aria-label', texte);
        const cols = [...texte].map((c) => {
            const col = document.createElement('span');
            col.className = 'p-odo-col';
            col.setAttribute('aria-hidden', 'true');
            if (/\d/.test(c)) {
                col.innerHTML = Array.from({ length: 10 }, (_, k) => `<span>${k}</span>`).join('');
                col.dataset.c = c;
            } else col.innerHTML = `<span>${c}</span>`;
            el.append(col);
            return col;
        });
        gsap.fromTo(cols.filter((c) => c.dataset.c), { yPercent: 0 }, { yPercent: (i, c) => -c.dataset.c * 10, duration: 2.4, ease: 'expo.out', stagger: 0.12, scrollTrigger: { trigger: el, start: 'top 88%' } });
    });

    // Membres : un champ de points qui s'allume en vague.
    $$('[data-p-points]').forEach((zone) => {
        const points = [...zone.children];
        gsap.fromTo(points, { scale: 0.3, opacity: 0.12 }, {
            scale: 1, opacity: 1, duration: 0.5, ease: 'back.out(3)',
            stagger: { grid: 'auto', from: 'start', amount: 1.6 },
            scrollTrigger: { trigger: zone, start: 'top 85%' },
        });
        // Puis quelques points « pulsent » de temps en temps : le réseau vit.
        const pulse = () => {
            const p = points[Math.floor(Math.random() * points.length)];
            gsap.fromTo(p, { scale: 1 }, { scale: 2.2, backgroundColor: '#ac0100', duration: 0.35, yoyo: true, repeat: 1, ease: 'power2.out', clearProps: 'backgroundColor' });
        };
        const minuteur = setInterval(pulse, 380);
        nettoyages.push(() => clearInterval(minuteur));
    });

    // Événements : le mois en cours de l'année s'allume tour à tour.
    $$('[data-p-mois]').forEach((zone) => {
        const mois = [...zone.children];
        const tl = gsap.timeline({ repeat: -1, paused: true });
        mois.forEach((m, i) => tl.to(m, { backgroundColor: '#ac0100', color: '#fff', scale: 1.12, duration: 0.25, ease: 'power2.out' }, i * 0.35).to(m, { backgroundColor: 'rgba(255,255,255,.06)', color: 'rgba(255,255,255,.6)', scale: 1, duration: 0.5 }, i * 0.35 + 0.45));
        ScrollTrigger.create({ trigger: zone, start: 'top 90%', end: 'bottom top', onToggle: (s) => (s.isActive ? tl.play() : tl.pause()) });
    });
}

/* ------------------------------------------------------------- bandeaux */

function bandeaux() {
    $$('[data-p-marquee]').forEach((el) => {
        const sens = +el.dataset.pMarquee || -1;
        const t = gsap.fromTo(el, { xPercent: sens < 0 ? 0 : -50 }, { xPercent: sens < 0 ? -50 : 0, duration: +el.dataset.pDuree || 30, ease: 'none', repeat: -1 });
        ScrollTrigger.create({
            trigger: el,
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: (s) => t.timeScale(1 + Math.min(Math.abs(s.getVelocity()) / 250, 5)),
        });
        ScrollTrigger.addEventListener('scrollEnd', () => gsap.to(t, { timeScale: 1, duration: 0.8 }));
    });

    // Mot qui change en boucle.
    $$('[data-p-rotator]').forEach((el) => {
        const mots = [...el.children];
        gsap.set(mots, { yPercent: 110, autoAlpha: 0 });
        gsap.set(mots[0], { yPercent: 0, autoAlpha: 1 });
        let i = 0;
        const tourner = () => {
            const a = mots[i];
            const b = mots[(i = (i + 1) % mots.length)];
            gsap.to(a, { yPercent: -110, autoAlpha: 0, duration: 0.6, ease: 'power3.in' });
            gsap.fromTo(b, { yPercent: 110, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.8, ease: 'expo.out', delay: 0.3 });
        };
        const minuteur = setInterval(tourner, +el.dataset.pRotator || 2400);
        nettoyages.push(() => clearInterval(minuteur));
    });
}

/* -------------------------------------------------------- interactions */

function interactions() {
    if (tactile()) return;

    $$('[data-p-magnet]').forEach((b) => {
        const f = +b.dataset.pMagnet || 0.35;
        const qx = gsap.quickTo(b, 'x', { duration: 0.5, ease: 'power3.out' });
        const qy = gsap.quickTo(b, 'y', { duration: 0.5, ease: 'power3.out' });
        b.addEventListener('mousemove', (e) => {
            const r = b.getBoundingClientRect();
            qx((e.clientX - r.left - r.width / 2) * f);
            qy((e.clientY - r.top - r.height / 2) * f);
        });
        b.addEventListener('mouseleave', () => gsap.to(b, { x: 0, y: 0, duration: 0.9, ease: 'elastic.out(1,.4)' }));
    });

    $$('[data-p-tilt]').forEach((t) => {
        const max = +t.dataset.pTilt || 10;
        gsap.set(t, { transformPerspective: 900 });
        t.addEventListener('mousemove', (e) => {
            const r = t.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width;
            const py = (e.clientY - r.top) / r.height;
            gsap.to(t, { rotateY: (px - 0.5) * max * 2, rotateX: (0.5 - py) * max * 2, duration: 0.5, ease: 'power2.out' });
            t.style.setProperty('--gx', `${px * 100}%`);
            t.style.setProperty('--gy', `${py * 100}%`);
        });
        t.addEventListener('mouseleave', () => gsap.to(t, { rotateX: 0, rotateY: 0, duration: 1, ease: 'elastic.out(1,.45)' }));
    });

    // Parallaxe à la souris.
    const souris = $$('[data-p-mouse]');
    if (souris.length) {
        const qs = souris.map((el) => ({ f: +el.dataset.pMouse || 20, x: gsap.quickTo(el, 'x', { duration: 1.2, ease: 'power3.out' }), y: gsap.quickTo(el, 'y', { duration: 1.2, ease: 'power3.out' }) }));
        const bouger = (e) => {
            const mx = e.clientX / innerWidth - 0.5;
            const my = e.clientY / innerHeight - 0.5;
            qs.forEach((q) => { q.x(mx * q.f * 2); q.y(my * q.f * 2); });
        };
        window.addEventListener('mousemove', bouger);
        nettoyages.push(() => window.removeEventListener('mousemove', bouger));
    }
}

/* ----------------------------------------------------- scènes épinglées */

function scenes() {
    const mm = gsap.matchMedia();
    nettoyages.push(() => mm.revert());

    mm.add('(min-width: 1024px)', () => {
        // Parcours d'adhésion : défilement horizontal.
        $$('[data-p-hscroll]').forEach((zone) => {
            const piste = zone.querySelector('[data-p-track]');
            const barre = zone.querySelector('[data-p-progress]');
            const dist = () => piste.scrollWidth - innerWidth;
            const anim = gsap.to(piste, {
                x: () => -dist(),
                ease: 'none',
                scrollTrigger: { trigger: zone, pin: true, scrub: 0.8, end: () => '+=' + dist(), invalidateOnRefresh: true, onUpdate: (s) => barre && gsap.set(barre, { scaleX: s.progress }) },
            });
            $$('[data-p-hin]', piste).forEach((el) => {
                gsap.fromTo(el, { y: 80, autoAlpha: 0, rotate: 4 }, { y: 0, autoAlpha: 1, rotate: 0, ease: 'power3.out', scrollTrigger: { trigger: el, containerAnimation: anim, start: 'left 85%', end: 'left 45%', scrub: true } });
            });
        });

        // CathoImpact : la section reste à l'écran, le carrousel défile dans le
        // téléphone et les étapes du parcours s'allument au fil du défilement.
        $$('[data-p-catho]').forEach((zone) => {
            const piste = zone.querySelector('[data-p-catho-piste]');
            const slides = piste ? [...piste.children] : [];
            if (slides.length < 2) return;
            const etapes = $$('[data-p-catho-etape]', zone);
            const ligne = zone.querySelector('[data-p-catho-ligne]');
            const points = $$('[data-p-catho-point]', zone);
            const marquer = (p) => {
                const e = Math.min(etapes.length - 1, Math.floor(p * etapes.length));
                etapes.forEach((el, i) => el.classList.toggle('is-actif', i <= e));
                const s = Math.round(p * (slides.length - 1));
                points.forEach((el, i) => el.classList.toggle('is-actif', i === s));
            };
            piste.style.overflow = 'visible';
            gsap.timeline({
                scrollTrigger: { trigger: zone, pin: true, scrub: 0.6, end: '+=' + slides.length * 60 + '%', onUpdate: (s) => marquer(s.progress) },
            })
                .to(piste, { xPercent: -100 * (slides.length - 1), ease: 'power1.inOut', duration: slides.length - 1 }, 0)
                .fromTo(ligne, { scaleY: 0 }, { scaleY: 1, ease: 'none', duration: slides.length - 1 }, 0);
            marquer(0);
            nettoyages.push(() => (piste.style.overflow = ''));
        });
    });

    // Valeurs : chaque carte recule quand la suivante arrive.
    $$('[data-p-stack]').forEach((zone) => {
        const cartes = $$('[data-p-stack-card]', zone);
        cartes.forEach((c, i) => {
            if (i === cartes.length - 1) return;
            gsap.to(c, { scale: 0.9 + i * 0.015, filter: 'brightness(.75)', ease: 'none', scrollTrigger: { trigger: cartes[i + 1], start: 'top bottom', end: 'top 20%', scrub: true } });
        });
    });
}

/* ---------------------------------------------- carrousel (téléphone) */

// Sans épinglage (téléphone, tablette), le carrousel CathoImpact se fait
// glisser au doigt : on suit la position pour les points et les étapes.
function carrouselTactile() {
    $$('[data-p-catho]').forEach((zone) => {
        const piste = zone.querySelector('[data-p-catho-piste]');
        if (!piste) return;
        const points = $$('[data-p-catho-point]', zone);
        const suivre = () => {
            if (window.matchMedia('(min-width: 1024px)').matches && !calme()) return;
            const i = Math.round(piste.scrollLeft / piste.clientWidth);
            points.forEach((el, k) => el.classList.toggle('is-actif', k === i));
        };
        piste.addEventListener('scroll', suivre, { passive: true });
        points.forEach((p, k) => p.addEventListener('click', () => piste.scrollTo({ left: k * piste.clientWidth, behavior: 'smooth' })));
    });
}

/* ------------------------------------------------------------------ boot */

function toutAfficher() {
    $$('[data-p-odo]').forEach((el) => (el.textContent = el.dataset.pOdo));
    $$('[data-p-catho-etape]').forEach((el) => el.classList.add('is-actif'));
    document.querySelector('[data-p-hero]')?.classList.add('p-arme');
}

function arreter() {
    ctx?.revert();
    ctx = null;
    nettoyages.forEach((f) => f());
    nettoyages = [];
}

export function initVitrine() {
    const page = document.querySelector('[data-vitrine]');
    if (!page || page.dataset.pPret) return;
    page.dataset.pPret = '1';
    arreter();
    carrouselTactile();

    if (calme()) {
        toutAfficher();
        return;
    }

    ctx = gsap.context(() => {
        // Les scènes épinglées d'abord : les déclencheurs créés ensuite
        // tiennent compte de la place qu'elles ajoutent à la page.
        scenes();
        hero();
        apparitions();
        chiffres();
        bandeaux();
        interactions();
    });

    // Lenis pilote le défilement : ScrollTrigger suit ses mises à jour.
    window.__lenis?.on?.('scroll', ScrollTrigger.update);
    // Recalcul de toutes les positions une fois la page installée.
    ScrollTrigger.refresh();
    // Les images et polices chargées après coup changent aussi les hauteurs.
    if (document.readyState !== 'complete') window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}

document.addEventListener('livewire:navigating', arreter);
