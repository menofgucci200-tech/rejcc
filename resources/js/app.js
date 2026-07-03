import './bootstrap';
import Lenis from 'lenis';

function initLenis() {
    const lenis = new Lenis({ duration: 1.1, smoothWheel: true });
    document.documentElement.classList.add('lenis');

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
}

function initScrollProgress() {
    const bar = document.getElementById('scroll-progress-bar');
    if (!bar) return;

    const update = () => {
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const pct = docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0;
        bar.style.width = pct + '%';
    };

    window.addEventListener('scroll', update, { passive: true });
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
    const loader = document.getElementById('page-loader');
    if (!loader) return;
    loader.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => loader.remove(), 600);
}

let lenisStarted = false;

function boot() {
    if (!lenisStarted) {
        initLenis();
        lenisStarted = true;
    }
    initScrollProgress();
    initReveal();
    initCounters();
    dismissLoader();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
