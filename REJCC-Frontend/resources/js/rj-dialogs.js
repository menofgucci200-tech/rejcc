/**
 * Boîtes de dialogue et notifications à la charte REJCC.
 *
 * Remplacent les fenêtres natives du navigateur (confirm, prompt, alert),
 * jugées trop brutales : une carte centrée pour confirmer ou saisir un
 * motif, des notifications discrètes en bas d'écran, et des messages
 * d'erreur sous les champs au lieu des bulles de validation du navigateur.
 *
 *   rjConfirm('Supprimer « X » ? Cette action est irréversible.')  → Promise<boolean>
 *   rjPrompt('Pourquoi signalez-vous cette annonce ?', { requis: true }) → Promise<string|null>
 *   rjToast('Enregistré.', { type: 'succes' })
 *
 * `wire:confirm="…"` utilise automatiquement rjConfirm. Attributs facultatifs
 * sur l'élément : data-confirm-ok (libellé du bouton), data-confirm-ton
 * (danger | attention | info).
 */

const ICONES = {
    danger: '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/>',
    attention: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
    saisie: '<path d="M12 20h9"/><path d="M16.38 3.62a1 1 0 0 1 3 3L7.37 18.64a2 2 0 0 1-.86.5l-2.87.84a.5.5 0 0 1-.62-.62l.84-2.87a2 2 0 0 1 .5-.86z"/>',
    succes: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    erreur: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
};

const TONS = {
    danger: { pastille: 'bg-accent/10 text-accent', bouton: 'bg-accent hover:bg-accent-600' },
    attention: { pastille: 'bg-[#F5A623]/15 text-[#B27007]', bouton: 'bg-brand hover:bg-brand/90' },
    info: { pastille: 'bg-brand/[.07] text-brand', bouton: 'bg-brand hover:bg-brand/90' },
    saisie: { pastille: 'bg-azure/10 text-azure', bouton: 'bg-brand hover:bg-brand/90' },
};

/** Verbe en tête du message → libellé du bouton et ton de la fenêtre. */
const VERBES = {
    supprimer: ['Supprimer', 'danger'],
    retirer: ['Retirer', 'danger'],
    bloquer: ['Bloquer', 'danger'],
    quitter: ['Quitter', 'danger'],
    'clôturer': ['Clôturer', 'danger'],
    'désactiver': ['Désactiver', 'attention'],
    annuler: ['Oui, annuler', 'danger'],
    'vous désinscrire': ['Me désinscrire', 'danger'],
    indiquer: ['Confirmer', 'attention'],
    repasser: ['Repasser en brouillon', 'attention'],
    activer: ['Activer', 'attention'],
    approuver: ['Approuver', 'info'],
    publier: ['Publier', 'info'],
    'rétablir': ['Rétablir', 'info'],
    valider: ['Valider', 'info'],
    faire: ['Confirmer', 'info'],
};

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const svg = (nom, cls = 'size-5') => `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONES[nom]}</svg>`;

/** « Question ? Explication. » → { titre, texte }. */
function decouper(message) {
    const m = String(message).replaceAll('\\n', '\n').trim();
    const i = m.indexOf('?');
    if (i > -1 && i < m.length - 1) {
        return { titre: m.slice(0, i + 1).trim(), texte: m.slice(i + 1).trim() };
    }
    return { titre: m, texte: '' };
}

function deduire(titre) {
    const t = titre.toLowerCase();
    for (const [verbe, val] of Object.entries(VERBES)) {
        if (t.startsWith(verbe)) return val;
    }
    return ['Confirmer', 'info'];
}

let ouverte = null;

function fenetre({ titre, texte = '', ok = 'Confirmer', annuler = 'Annuler', ton = 'info', saisie = null }) {
    if (ouverte) ouverte.fermer(null);
    const t = TONS[ton] ?? TONS.info;
    const precedent = document.activeElement;

    return new Promise((resolve) => {
        const racine = document.createElement('div');
        racine.className = 'rj-dialog fixed inset-0 z-[200] flex items-end justify-center bg-brand/40 p-3 backdrop-blur-[2px] sm:items-center sm:p-6';
        racine.setAttribute('role', 'presentation');
        const idTitre = 'rj-dialog-titre-' + Date.now();
        racine.innerHTML = `
            <div role="alertdialog" aria-modal="true" aria-labelledby="${idTitre}" data-test="rj-dialog"
                class="rj-dialog-carte w-full max-w-[440px] rounded-[20px] bg-white p-5 shadow-[0_24px_60px_rgba(3,29,89,.28)] sm:p-6">
                <div class="flex items-start gap-3.5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px] ${t.pastille}">${svg(ton === 'saisie' ? 'saisie' : ton)}</span>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <p id="${idTitre}" class="text-[15px] font-bold leading-snug text-brand">${esc(titre)}</p>
                        ${texte ? `<p class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-[#5B677A]">${esc(texte)}</p>` : ''}
                    </div>
                </div>
                ${saisie ? `
                    <textarea rows="3" data-test="rj-dialog-saisie" maxlength="${saisie.max ?? 500}" placeholder="${esc(saisie.placeholder ?? '')}"
                        class="mt-4 w-full rounded-[12px] border border-brand/15 bg-white px-3.5 py-2.5 text-[13px] text-ink placeholder:text-[#9AA6B8]"></textarea>
                    <p data-rj-erreur class="mt-1 hidden text-[12px] font-semibold text-accent"></p>` : ''}
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-rj-annuler class="btn-tap rounded-full border border-brand/15 bg-white px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">${esc(annuler)}</button>
                    <button type="button" data-rj-ok data-test="rj-dialog-ok" class="btn-tap rounded-full px-5 py-2.5 text-[13px] font-bold text-white ${t.bouton}">${esc(ok)}</button>
                </div>
            </div>`;
        document.body.appendChild(racine);
        document.documentElement.classList.add('rj-dialog-ouverte');

        const champ = racine.querySelector('textarea');
        const erreur = racine.querySelector('[data-rj-erreur]');
        const btnOk = racine.querySelector('[data-rj-ok]');
        const btnAnnuler = racine.querySelector('[data-rj-annuler]');

        const fermer = (valeur) => {
            document.removeEventListener('keydown', clavier, true);
            racine.classList.add('rj-dialog-sortie');
            setTimeout(() => racine.remove(), 160);
            document.documentElement.classList.remove('rj-dialog-ouverte');
            ouverte = null;
            if (precedent?.focus) precedent.focus({ preventScroll: true });
            resolve(valeur);
        };
        const valider = () => {
            if (!saisie) return fermer(true);
            const v = champ.value.trim();
            if (saisie.requis && v.length < (saisie.min ?? 3)) {
                erreur.textContent = saisie.messageRequis ?? 'Précisez en quelques mots.';
                erreur.classList.remove('hidden');
                champ.setAttribute('aria-invalid', 'true');
                champ.focus();
                return;
            }
            fermer(v);
        };
        const clavier = (e) => {
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); fermer(saisie ? null : false); }
            if (e.key === 'Enter' && !saisie && document.activeElement !== btnAnnuler) { e.preventDefault(); valider(); }
            if (e.key === 'Tab') {
                // Le focus reste dans la fenêtre.
                const f = [...racine.querySelectorAll('textarea, button')];
                const i = f.indexOf(document.activeElement);
                if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
                else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
            }
        };
        document.addEventListener('keydown', clavier, true);
        btnOk.addEventListener('click', valider);
        btnAnnuler.addEventListener('click', () => fermer(saisie ? null : false));
        racine.addEventListener('mousedown', (e) => { if (e.target === racine) fermer(saisie ? null : false); });
        champ?.addEventListener('input', () => { erreur.classList.add('hidden'); champ.removeAttribute('aria-invalid'); });

        ouverte = { fermer };
        requestAnimationFrame(() => (champ ?? (ton === 'danger' ? btnAnnuler : btnOk)).focus());
    });
}

export function rjConfirm(message, options = {}) {
    const { titre, texte } = decouper(message);
    const [ok, ton] = deduire(titre);
    return fenetre({
        titre: options.titre ?? titre,
        texte: options.texte ?? texte,
        ok: options.ok ?? ok,
        annuler: options.annuler ?? (ok === 'Oui, annuler' ? 'Non, revenir' : 'Annuler'),
        ton: options.ton ?? ton,
    }).then(Boolean);
}

export function rjPrompt(message, options = {}) {
    const { titre, texte } = decouper(message);
    return fenetre({
        titre: options.titre ?? titre,
        texte: options.texte ?? texte,
        ok: options.ok ?? 'Envoyer',
        annuler: options.annuler ?? 'Annuler',
        ton: options.ton ?? 'saisie',
        saisie: { placeholder: options.placeholder ?? '', requis: options.requis ?? false, min: options.min, max: options.max, messageRequis: options.messageRequis },
    });
}

/* ── Notifications ─────────────────────────────────────────────────── */

export function rjToast(message, { type = 'succes', duree = 4500 } = {}) {
    let pile = document.getElementById('rj-toasts');
    if (!pile) {
        pile = document.createElement('div');
        pile.id = 'rj-toasts';
        pile.className = 'pointer-events-none fixed inset-x-3 bottom-3 z-[210] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-5 sm:bottom-5 sm:items-end';
        pile.setAttribute('aria-live', 'polite');
        document.body.appendChild(pile);
    }
    const couleurs = { succes: 'text-[#1C8F4C]', erreur: 'text-accent', info: 'text-azure' };
    const t = document.createElement('div');
    t.setAttribute('role', type === 'erreur' ? 'alert' : 'status');
    t.dataset.test = 'rj-toast';
    t.className = 'rj-toast pointer-events-auto flex w-full max-w-[380px] items-start gap-2.5 rounded-[14px] border border-brand/10 bg-white px-4 py-3 shadow-[0_12px_32px_rgba(3,29,89,.16)]';
    t.innerHTML = `<span class="mt-px shrink-0 ${couleurs[type] ?? couleurs.info}">${svg(type === 'info' ? 'info' : type, 'size-[18px]')}</span>
        <p class="min-w-0 flex-1 text-[13px] font-semibold leading-snug text-ink">${esc(message)}</p>
        <button type="button" aria-label="Fermer" class="-mr-1 shrink-0 rounded-full p-0.5 text-[#9AA6B8] hover:text-brand"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>`;
    const retirer = () => { t.classList.add('rj-toast-sortie'); setTimeout(() => t.remove(), 200); };
    t.querySelector('button').addEventListener('click', retirer);
    pile.appendChild(t);
    if (duree) setTimeout(retirer, duree);
}

/* ── Messages de validation des champs (au lieu des bulles natives) ─── */

function messageChamp(el) {
    const v = el.validity;
    if (el.dataset.message) return el.dataset.message;
    if (v.valueMissing) {
        if (el.type === 'checkbox') return 'Cochez cette case pour continuer.';
        if (el.type === 'radio') return 'Choisissez une option.';
        if (el.type === 'file') return 'Joignez un fichier.';
        if (el.tagName === 'SELECT') return 'Choisissez une option dans la liste.';
        return 'Ce champ est obligatoire.';
    }
    if (v.typeMismatch) {
        if (el.type === 'email') return 'Saisissez une adresse e-mail valide (ex. : nom@exemple.com).';
        if (el.type === 'url') return 'Saisissez une adresse web valide, commençant par https://';
        return 'Format incorrect.';
    }
    if (v.tooShort) return `Saisissez au moins ${el.minLength} caractères.`;
    if (v.tooLong) return `Saisissez au plus ${el.maxLength} caractères.`;
    if (v.rangeUnderflow) return `La valeur minimale est ${el.min}.`;
    if (v.rangeOverflow) return `La valeur maximale est ${el.max}.`;
    if (v.stepMismatch) return 'Valeur non valide.';
    if (v.patternMismatch) return el.title || 'Format incorrect.';
    if (v.badInput) return 'Valeur non valide.';
    return el.validationMessage || 'Valeur non valide.';
}

function ancre(el) {
    // Message placé après le bloc visuel du champ (icône, libellé englobant…).
    const p = el.parentElement;
    if (el.type === 'checkbox' || el.type === 'radio') return el.closest('label') ?? p;
    if (p && (p.tagName === 'LABEL' || p.classList.contains('relative'))) return p;
    return el;
}

function effacer(el) {
    el.removeAttribute('aria-invalid');
    el.classList.remove('rj-invalide');
    const id = el.dataset.rjErreur;
    if (id) document.getElementById(id)?.remove();
    delete el.dataset.rjErreur;
}

let premierInvalide = null;
document.addEventListener('invalid', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLElement) || el.closest('[data-validation-native]')) return;
    e.preventDefault();
    effacer(el);
    const msg = document.createElement('p');
    msg.id = 'rj-err-' + Math.random().toString(36).slice(2, 9);
    msg.className = 'rj-erreur-champ mt-1 flex items-start gap-1 text-[12px] font-semibold text-accent';
    msg.dataset.test = 'erreur-champ';
    msg.innerHTML = `${svg('erreur', 'mt-px size-3.5 shrink-0')}<span>${esc(messageChamp(el))}</span>`;
    ancre(el).insertAdjacentElement('afterend', msg);
    el.setAttribute('aria-invalid', 'true');
    el.classList.add('rj-invalide');
    el.setAttribute('aria-describedby', msg.id);
    el.dataset.rjErreur = msg.id;
    if (!premierInvalide) {
        premierInvalide = el;
        el.focus({ preventScroll: true });
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        setTimeout(() => (premierInvalide = null), 0);
    }
}, true);

['input', 'change'].forEach((type) =>
    document.addEventListener(type, (e) => {
        const el = e.target;
        if (el?.dataset?.rjErreur && el.checkValidity?.()) effacer(el);
        // Groupe de boutons radio : une option choisie efface l'erreur des autres.
        if (el?.type === 'radio' && el.name) document.querySelectorAll(`input[type=radio][name="${CSS.escape(el.name)}"]`).forEach(effacer);
    }, true),
);

/* ── Branchement Livewire ──────────────────────────────────────────── */

document.addEventListener('livewire:init', () => {
    const L = window.Livewire;

    // wire:confirm → fenêtre REJCC (le directive natif appelle window.confirm).
    L.hook('directive.init', ({ el, directive }) => {
        if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) return;
        el.__livewire_confirm = (action, instead) => {
            instead(); // on stoppe l'événement d'origine ; l'action est rejouée si l'on confirme
            rjConfirm(directive.expression || 'Confirmer cette action ?', {
                ok: el.dataset.confirmOk,
                ton: el.dataset.confirmTon,
            }).then((ok) => ok && action());
        };
    });

    // Erreurs réseau des actions Livewire : message clair au lieu des dialogues natifs.
    L.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                rjConfirm('Votre session a expiré ? Rechargez la page pour continuer là où vous en étiez.', {
                    titre: 'Votre session a expiré', ok: 'Recharger la page', annuler: 'Plus tard', ton: 'attention',
                }).then((ok) => ok && window.location.reload());
                return;
            }
            if (document.documentElement.hasAttribute('data-debug') && status >= 500) return; // détail visible en développement
            preventDefault();
            const messages = {
                401: 'Votre session a pris fin. Reconnectez-vous pour continuer.',
                403: "Vous n'avez pas l'autorisation d'effectuer cette action.",
                404: "Cet élément n'existe plus ou a été déplacé.",
                413: 'Le fichier est trop volumineux.',
                429: 'Trop de tentatives en peu de temps. Patientez une minute puis réessayez.',
                503: 'La plateforme est en maintenance. Réessayez dans quelques minutes.',
            };
            rjToast(messages[status] ?? 'Une erreur est survenue. Réessayez dans un instant.', { type: 'erreur', duree: 7000 });
        });
    });
});

// Notifications émises par les composants : $this->dispatch('rj-toast', message: '…', type: 'succes').
window.addEventListener('rj-toast', (e) => {
    const d = Array.isArray(e.detail) ? e.detail[0] : e.detail;
    const types = { success: 'succes', error: 'erreur', warning: 'erreur' };
    if (d?.message) rjToast(d.message, { type: types[d.type] ?? d.type ?? 'succes' });
});

window.rjConfirm = rjConfirm;
window.rjPrompt = rjPrompt;
window.rjToast = rjToast;
