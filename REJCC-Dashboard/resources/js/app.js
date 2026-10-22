import './bootstrap';
import './rj-dialogs';
import './rj-app';
import Lenis from 'lenis';
import QRCode from 'qrcode';
import { initHomeMotion } from './home-motion';
import { initTourPlayer } from './tour-player';

// Génération de QR codes côté client (cartes membres, billets d'événement).
window.QRCode = QRCode;

// Lecture de QR codes par la caméra (pointage des événements) : détecteur
// natif du navigateur s'il existe, sinon jsQR chargé à la demande.
window.lecteurQr = () => ({
    actif: false,
    erreur: null,
    flux: null,
    dernier: null,
    async demarrer(surCode) {
        this.erreur = null;
        try {
            this.flux = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        } catch (e) {
            this.erreur = "Caméra indisponible : autorisez l'accès à la caméra ou saisissez le code à la main.";
            return;
        }
        const video = this.$refs.video;
        video.srcObject = this.flux;
        await video.play();
        this.actif = true;
        const natif = 'BarcodeDetector' in window ? new window.BarcodeDetector({ formats: ['qr_code'] }) : null;
        const jsQR = natif ? null : (await import('jsqr')).default;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const boucle = async () => {
            if (!this.actif) return;
            let code = null;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                if (natif) {
                    const res = await natif.detect(video).catch(() => []);
                    code = res[0]?.rawValue ?? null;
                } else {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    code = jsQR(img.data, img.width, img.height)?.data ?? null;
                }
            }
            // Un même code n'est envoyé qu'une fois toutes les 3 secondes.
            if (code && (code !== this.dernier?.code || Date.now() - this.dernier.t > 3000)) {
                this.dernier = { code, t: Date.now() };
                surCode(code);
            }
            requestAnimationFrame(boucle);
        };
        requestAnimationFrame(boucle);
    },
    arreter() {
        this.actif = false;
        this.flux?.getTracks().forEach((t) => t.stop());
        this.flux = null;
    },
});

// « Télécharger en image » de la carte membre : recto + verso en un PNG
// (bibliothèque chargée à la demande, uniquement quand on clique).
// On exporte une copie hors écran : les attributs Livewire/Alpine (wire:…, x-…,
// @…) rendraient l'image SVG intermédiaire invalide, et les QR codes (canvas)
// sont remplacés par leur image.
window.carteEnImage = async (el, fichier) => {
    const { toPng } = await import('html-to-image');
    const copie = el.cloneNode(true);
    const originaux = el.querySelectorAll('canvas');
    copie.querySelectorAll('canvas').forEach((c, i) => {
        const img = document.createElement('img');
        img.src = originaux[i].toDataURL('image/png');
        img.className = c.className;
        c.replaceWith(img);
    });
    [copie, ...copie.querySelectorAll('*')].forEach((n) => {
        [...n.attributes].forEach((a) => {
            if (/[:@]/.test(a.name) || a.name.startsWith('x-') || a.name.startsWith('wire')) n.removeAttribute(a.name);
        });
    });
    const hote = document.createElement('div');
    hote.style.cssText = `position:fixed;left:-10000px;top:0;width:${el.offsetWidth}px`;
    hote.appendChild(copie);
    document.body.appendChild(hote);
    try {
        await Promise.all([...copie.querySelectorAll('img')].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
        // imagePlaceholder : une image indisponible (photo hors ligne…) ne bloque pas l'export.
        const transparent = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
        const url = await toPng(copie, { pixelRatio: 3, backgroundColor: '#ffffff', imagePlaceholder: transparent });
        const a = document.createElement('a');
        a.href = url;
        a.download = fichier;
        a.click();
    } finally {
        hote.remove();
    }
};

function initLenis() {
    const lenis = new Lenis({ duration: 1.1, smoothWheel: true });
    window.__lenis = lenis;
    document.documentElement.classList.add('lenis');

    function raf(time) {
        if (window.__lenis !== lenis) return; // instance détruite : on arrête la boucle
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
}

// Lenis n'a de sens que sur la vitrine (scroll de la fenêtre). Dans l'admin et
// l'espace membre, le défilement se fait dans des panneaux internes (sidebar,
// colonne de contenu) et Lenis avalerait la molette partout ailleurs : on
// l'active/détruit selon le marqueur data-smooth-scroll posé par le layout.
function syncLenis() {
    const wants = document.body.hasAttribute('data-smooth-scroll');

    if (wants && !window.__lenis) {
        initLenis();
    } else if (!wants && window.__lenis) {
        window.__lenis.destroy();
        window.__lenis = null;
        document.documentElement.classList.remove('lenis');
    }
}

function initScrollProgress() {
    const update = () => {
        const bar = document.getElementById('scroll-progress-bar');
        if (!bar) return;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const pct = docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0;
        bar.style.width = pct + '%';
    };

    // Un seul listener global : le body est remplacé à chaque wire:navigate,
    // on recherche donc la barre à chaque scroll plutôt que de ré-attacher.
    if (!window.__scrollProgressBound) {
        window.addEventListener('scroll', update, { passive: true });
        window.__scrollProgressBound = true;
    }
    update();
}

function initReveal() {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -80px 0px', threshold: 0.1 },
    );

    document.querySelectorAll('[data-reveal]:not(.is-visible)').forEach((el) => observer.observe(el));
}

// Compteurs déjà animés (une seule animation par élément, même après un
// rafraîchissement Livewire qui conserve les nœuds).
const animatedCounters = new WeakSet();

function initCounters() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const animate = (el) => {
        animatedCounters.add(el);
        const target = parseFloat(el.dataset.counterValue || '0');
        const suffix = el.dataset.counterSuffix || '';

        if (reduceMotion) {
            el.textContent = target.toLocaleString('fr-FR') + suffix;
            return;
        }

        const duration = parseFloat(el.dataset.counterDuration || '2') * 1000;
        const start = performance.now();

        const tick = (t) => {
            const p = Math.min((t - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(eased * target).toLocaleString('fr-FR') + suffix;
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -60px 0px', threshold: 0.1 },
    );

    document.querySelectorAll('[data-counter]').forEach((el) => {
        if (animatedCounters.has(el)) return;
        if (!reduceMotion) el.textContent = '0' + (el.dataset.counterSuffix || '');
        observer.observe(el);
    });
}

function dismissLoader() {
    // `app-loaded` sur <html> (qui survit aux wire:navigate) : le splash ne
    // s'affiche qu'au tout premier chargement, jamais entre les pages.
    document.documentElement.classList.add('app-loaded');

    const loader = document.getElementById('page-loader');
    if (!loader) return;
    loader.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => loader.remove(), 600);
}

function boot() {
    syncLenis();
    initScrollProgress();
    initReveal();
    initCounters();
    dismissLoader();
    initHomeMotion();
    initTourPlayer();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);

document.addEventListener('livewire:navigating', () => {
    document.documentElement.classList.add('is-navigating');
});
document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.remove('is-navigating');
    // Lenis mesure la hauteur du document : on la recalcule après le
    // remplacement du body pour que le défilement reste fonctionnel.
    if (window.__lenis?.resize) window.__lenis.resize();
});
