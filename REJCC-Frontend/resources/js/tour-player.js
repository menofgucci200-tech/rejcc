// Lecteur immersif du film de présentation de la plateforme REJCC.
// Ouvert par le bouton flottant [data-tour-open] : l'écran « plonge » dans la
// vidéo par un cercle qui s'ouvre depuis le bouton. Choisit la version 9:16
// sur un écran en hauteur (téléphone), 16:9 sinon. Échap ou ✕ pour fermer.

let overlay = null;
let lastFocus = null;

function buildOverlay(btn) {
    const portrait = window.innerHeight > window.innerWidth;
    const src = portrait ? btn.dataset.srcPortrait : btn.dataset.srcLandscape;
    const poster = portrait ? btn.dataset.posterPortrait : btn.dataset.posterLandscape;

    const el = document.createElement('div');
    el.className = 'rj-tour';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-label', 'Film de présentation de la plateforme REJCC');
    el.innerHTML = `
        <video playsinline preload="auto" poster="${poster}">
            <source src="${src}" type="video/mp4">
            <track kind="subtitles" srclang="fr" label="Français" src="${btn.dataset.captions}">
        </video>
        <div class="rj-tour__bar">
            <button type="button" class="rj-tour__btn" data-tour-cc aria-pressed="false" title="Sous-titres">CC</button>
            <button type="button" class="rj-tour__btn" data-tour-close aria-label="Fermer la vidéo">✕</button>
        </div>
        <div class="rj-tour__end">
            <h2>Prêt à nous<br>rejoindre ?</h2>
            <div class="row">
                <a class="rj-tour__cta" href="/adhesion">Commencer mon adhésion →</a>
                <button type="button" class="rj-tour__btn" data-tour-replay>Revoir le film</button>
            </div>
        </div>`;
    return el;
}

function close() {
    if (!overlay) return;
    const el = overlay;
    overlay = null;
    const video = el.querySelector('video');
    video.pause();
    el.classList.remove('is-open');
    document.documentElement.classList.remove('rj-tour-open');
    window.__lenis?.start();
    setTimeout(() => el.remove(), 900);
    document.removeEventListener('keydown', onKey);
    lastFocus?.focus?.();
}

function onKey(e) {
    if (e.key === 'Escape') close();
}

function open(btn) {
    if (overlay) return;
    lastFocus = btn;
    const r = btn.getBoundingClientRect();
    overlay = buildOverlay(btn);
    overlay.style.setProperty('--tx', `${r.left + r.width / 2}px`);
    overlay.style.setProperty('--ty', `${r.top + r.height / 2}px`);
    document.body.appendChild(overlay);
    document.documentElement.classList.add('rj-tour-open');
    window.__lenis?.stop();

    const video = overlay.querySelector('video');
    const track = video.textTracks[0];
    if (track) track.mode = 'hidden';

    overlay.querySelector('[data-tour-close]').addEventListener('click', close);
    overlay.querySelector('[data-tour-cc]').addEventListener('click', (e) => {
        const on = e.currentTarget.getAttribute('aria-pressed') !== 'true';
        e.currentTarget.setAttribute('aria-pressed', String(on));
        if (track) track.mode = on ? 'showing' : 'hidden';
    });
    overlay.querySelector('[data-tour-replay]').addEventListener('click', () => {
        overlay.classList.remove('is-ended');
        video.currentTime = 0;
        video.play();
    });
    video.addEventListener('ended', () => overlay?.classList.add('is-ended'));
    document.addEventListener('keydown', onKey);

    // Plongée : le cercle s'ouvre depuis le bouton, puis la lecture démarre (avec le son,
    // autorisé car déclenchée par le clic).
    requestAnimationFrame(() => requestAnimationFrame(() => overlay?.classList.add('is-open')));
    video.play().catch(() => { video.controls = true; });
    video.addEventListener('click', () => (video.paused ? video.play() : video.pause()));
    overlay.querySelector('[data-tour-close]').focus({ preventScroll: true });
}

export function initTourPlayer() {
    if (window.__rjTourBound) return;
    window.__rjTourBound = true;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-tour-open]');
        if (btn) {
            e.preventDefault();
            open(btn);
        }
    });
    // Une navigation interne ferme le lecteur s'il était ouvert.
    document.addEventListener('livewire:navigating', close);
}
