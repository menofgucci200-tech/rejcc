import './bootstrap';
import Lenis from 'lenis';
import QRCode from 'qrcode';

// Génération de QR codes côté client (cartes membres de l'admin).
window.QRCode = QRCode;

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

function initCounters() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const animate = (el) => {
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

    document.querySelectorAll('[data-counter]').forEach((el) => observer.observe(el));
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
