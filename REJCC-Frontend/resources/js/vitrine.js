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
    // CathoImpact : la ligne rouge se trace et les étapes s'allument.
    $$('[data-p-catho-etapes]').forEach((ol) => {
        const etapes = $$('[data-p-catho-etape]', ol);
        gsap.fromTo(ol.querySelector('[data-p-catho-ligne]'), { scaleY: 0 }, {
            scaleY: 1, ease: 'none',
            scrollTrigger: {
                trigger: ol, start: 'top 75%', end: 'bottom 50%', scrub: 0.6,
                onUpdate: (st) => etapes.forEach((el, i) => el.classList.toggle('is-actif', st.progress > i / etapes.length)),
            },
        });
    });

    $$('[data-p-reveal]').forEach((el) => {
        const type = el.dataset.pReveal || 'up';
        const enfants = el.hasAttribute('data-p-each') ? [...el.children] : [el];
        gsap.set(el, { visibility: 'visible' });
        gsap.fromTo(enfants, ENTREES[type] ?? ENTREES.up, {
            x: 0, y: 0, scale: 1, rotate: 0, autoAlpha: 1,
            // Le découpage n'est posé que pour le rideau, puis retiré : sinon il
            // rognerait tout ce qui dépasse (ombres, pastilles, piles de cartes).
            ...(type === 'rideau' ? { clipPath: 'inset(0 0 0% 0)', clearProps: 'clipPath' } : {}),
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
        mois.forEach((m, i) => tl.to(m, { backgroundColor: '#ac0100', color: '#fff', scale: 1.12, duration: 0.25, ease: 'power2.out' }, i * 0.35).to(m, { backgroundColor: '#021541', color: 'rgba(255,255,255,.6)', scale: 1, duration: 0.5 }, i * 0.35 + 0.45));
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

/* ------------------------------------------- remplissage du hero */

// La lumière remplit « REJCC » de gauche à droite, lettre après lettre
// (R, E, J, C, C), puis le monogramme. Dans une lettre, elle avance
// lentement (4 s, 6 s pour le logo), comme une masse qu'on pousse ; entre
// deux lettres, un trait de lumière fuse vers la suivante (≈ 0,5 s), qui
// s'allume d'un coup avec un petit rebond. Un éclat marque chaque lettre
// pleine ; une fois le logo plein, tout s'éteint et le cycle recommence.
// Pause quand le hero n'est plus à l'écran.
function remplir() {
    const zone = document.querySelector('[data-p-remplir]');
    const lettres = zone ? $$('[data-p-lettre]', zone) : [];
    if (!lettres.length) return;
    const elan = zone.querySelector('[data-p-elan]');
    const poser = (el, p) => el.style.setProperty('--p', p.toFixed(4));
    lettres.forEach((el) => poser(el, 0));

    // Trajets du trait : de la fin d'une lettre au début de la suivante.
    const trajets = [];
    const bord = (el, fin) => {
        const r = el.getBoundingClientRect();
        const z = zone.getBoundingClientRect();
        const logo = el.classList.contains('p-lt-logo');
        const x = logo ? r.left + r.width * 0.06 : fin ? r.right - r.width * 0.08 : r.left + r.width * 0.08;
        return { x: x - z.left, y: r.top - z.top + r.height * (logo ? 0.55 : 0.5) };
    };
    const calculer = () => {
        lettres.forEach((el, i) => {
            if (!i) return;
            const a = bord(lettres[i - 1], true);
            const b = bord(el, false);
            trajets[i] = { a, b, angle: (Math.atan2(b.y - a.y, b.x - a.x) * 180) / Math.PI };
        });
    };
    calculer();
    if (elan) gsap.set(elan, { xPercent: -50, yPercent: -50, autoAlpha: 0 });

    const tl = gsap.timeline({ repeat: -1, repeatDelay: 2, delay: 2.5 });
    lettres.forEach((el, i) => {
        const logo = el.classList.contains('p-lt-logo');
        if (i && elan) {
            tl.fromTo(elan,
                { x: () => trajets[i].a.x, y: () => trajets[i].a.y, rotation: () => trajets[i].angle },
                { x: () => trajets[i].b.x, y: () => trajets[i].b.y, duration: logo ? 0.75 : 0.5, ease: 'power3.inOut', immediateRender: false })
                .to(elan, { keyframes: [
                    { autoAlpha: 1, scaleX: 1.6, duration: logo ? 0.35 : 0.24, ease: 'power2.out' },
                    { autoAlpha: 0, scaleX: 0.3, duration: logo ? 0.4 : 0.26, ease: 'power2.in' },
                ] }, '<');
        }
        const etat = { p: 0 };
        tl.to(etat, {
            p: 1,
            duration: logo ? 6 : 4,
            ease: 'sine.inOut',
            onStart: () => { el.classList.remove('is-plein'); el.classList.add('is-actif'); },
            onUpdate: () => poser(el, etat.p),
            onComplete: () => { el.classList.remove('is-actif'); el.classList.add('is-plein'); },
        }, i ? '-=0.12' : 0);
    });
    // Tout reste allumé un instant, puis s'éteint et se vide.
    tl.to(zone, { '--fin': 0, duration: 3, ease: 'power1.inOut' }, '+=2.5')
        .add(() => lettres.forEach((el) => { el.classList.remove('is-plein', 'is-actif'); poser(el, 0); }))
        .set(zone, { '--fin': 1 });

    ScrollTrigger.create({ trigger: zone.closest('section') ?? zone, start: 'top bottom', end: 'bottom top', onToggle: (s) => (s.isActive ? tl.play() : tl.pause()) });

    // Les trajets suivent la mise en page (redimensionnement, polices chargées).
    let minuteur = null;
    const recalculer = () => {
        clearTimeout(minuteur);
        minuteur = setTimeout(() => { calculer(); tl.invalidate(); }, 200);
    };
    window.addEventListener('resize', recalculer);
    document.fonts?.ready.then(recalculer);
    nettoyages.push(() => { window.removeEventListener('resize', recalculer); clearTimeout(minuteur); });
}

/* ------------------------------------------- diaporama CathoImpact */

// Les carrousels CathoImpact défilent seuls : chaque image reste
// data-p-ci-duree ms (la barre de progression CSS donne le tempo), puis on
// passe au carrousel suivant. Survol, focus, onglet caché ou section hors de
// l'écran : pause. Flèches, clavier et glissé au doigt pour naviguer.
// Animations réduites : pas de défilement automatique, navigation manuelle.
function diaporama() {
    $$('[data-p-ci]').forEach((zone) => {
        const slides = $$('.p-ci-slide', zone);
        if (!slides.length) return;
        const nb = Math.max(...slides.map((s) => +s.dataset.c)) + 1;
        const de = (c) => slides.filter((s) => +s.dataset.c === c);
        const pad = (n) => String(n).padStart(2, '0');
        const num = zone.querySelector('[data-p-ci-num]');
        const total = zone.querySelector('[data-p-ci-total]');
        const auto = !calme();
        zone.style.setProperty('--p-ci-d', (+zone.dataset.pCiDuree || 3000) + 'ms');
        let c = 0;
        let k = 0;

        const raisons = new Set();
        const pause = (r, oui) => {
            if (oui) raisons.add(r);
            else raisons.delete(r);
            zone.classList.toggle('is-pause', raisons.size > 0);
        };

        const barres = () => {
            $$('.p-ci-barres', zone).forEach((g) => {
                const actif = +g.dataset.c === c;
                g.classList.toggle('hidden', !actif);
                g.classList.toggle('flex', actif);
                $$('[data-k]', g).forEach((b) => {
                    const i = +b.dataset.k;
                    b.classList.remove('is-encours');
                    b.classList.toggle('is-fait', actif && (i < k || (i === k && !auto)));
                    if (actif && i === k && auto) {
                        void b.offsetWidth; // relance l'animation de la barre
                        b.classList.add('is-encours');
                    }
                });
            });
        };

        const afficher = (nc, nk, effet) => {
            const ancien = slides.find((s) => s.classList.contains('is-actif'));
            const nouveau = de(nc)[nk];
            if (!nouveau) return;
            const changeCarrousel = nc !== c;
            if (nouveau !== ancien) {
                slides.forEach((s) => s.classList.remove('is-sortant', 'p-ci-avant', 'p-ci-arriere', 'p-ci-nouveau'));
                if (ancien) {
                    ancien.classList.remove('is-actif');
                    ancien.classList.add('is-sortant');
                    ancien.setAttribute('aria-hidden', 'true');
                }
                void nouveau.offsetWidth;
                nouveau.classList.add('is-actif', changeCarrousel || effet === 'nouveau' ? 'p-ci-nouveau' : effet < 0 ? 'p-ci-arriere' : 'p-ci-avant');
                nouveau.removeAttribute('aria-hidden');
            }
            if (changeCarrousel) {
                $$('.p-ci-legende', zone).forEach((l) => l.classList.toggle('is-actif', +l.dataset.c === nc));
                $$('[data-p-ci-choix]', zone).forEach((b) => b.classList.toggle('is-actif', +b.dataset.pCiChoix === nc));
            }
            c = nc;
            k = nk;
            if (num) num.textContent = pad(k + 1);
            if (total) total.textContent = pad(de(c).length);
            barres();
        };

        const suivant = () => (k + 1 < de(c).length ? afficher(c, k + 1, 1) : afficher((c + 1) % nb, 0, 'nouveau'));
        const precedent = () => {
            if (k > 0) return afficher(c, k - 1, -1);
            const pc = (c - 1 + nb) % nb;
            return afficher(pc, de(pc).length - 1, -1);
        };

        // Fin de la barre en cours = image suivante (la pause CSS gèle le tempo).
        zone.addEventListener('animationend', (e) => e.animationName === 'p-ci-barre' && suivant());
        zone.querySelector('[data-p-ci-suiv]')?.addEventListener('click', suivant);
        zone.querySelector('[data-p-ci-prec]')?.addEventListener('click', precedent);
        $$('[data-p-ci-choix]', zone).forEach((b) => b.addEventListener('click', () => afficher(+b.dataset.pCiChoix, 0, 'nouveau')));
        zone.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { e.preventDefault(); suivant(); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); precedent(); }
        });

        // Glissé au doigt
        let x0 = null;
        zone.addEventListener('pointerdown', (e) => { if (e.pointerType !== 'mouse') x0 = e.clientX; });
        zone.addEventListener('pointerup', (e) => {
            if (x0 === null) return;
            const dx = e.clientX - x0;
            x0 = null;
            if (Math.abs(dx) > 40) (dx < 0 ? suivant : precedent)();
        });

        zone.addEventListener('mouseenter', () => { pause('survol', true); zone.classList.add('is-survol'); });
        zone.addEventListener('mouseleave', () => { pause('survol', false); zone.classList.remove('is-survol'); });
        zone.addEventListener('focusin', () => pause('focus', true));
        zone.addEventListener('focusout', () => pause('focus', false));
        const onglet = () => pause('onglet', document.hidden);
        document.addEventListener('visibilitychange', onglet);
        const vue = new IntersectionObserver(([e]) => pause('ecran', !e.isIntersecting), { threshold: 0.25 });
        vue.observe(zone);
        nettoyages.push(() => { vue.disconnect(); document.removeEventListener('visibilitychange', onglet); });

        barres();
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
    diaporama();

    if (calme()) {
        toutAfficher();
        return;
    }

    ctx = gsap.context(() => {
        // Les scènes épinglées d'abord : les déclencheurs créés ensuite
        // tiennent compte de la place qu'elles ajoutent à la page.
        scenes();
        hero();
        remplir();
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
