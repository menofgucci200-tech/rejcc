/*
 * Application installable et notifications sur l'appareil (Web Push).
 * - window.rjApp.installer() : propose l'installation (Android, ordinateur) ;
 * - window.rjPush : état, activation et désactivation des notifications.
 */

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

let invitation = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    invitation = e;
    window.dispatchEvent(new CustomEvent('rj-app-installable'));
});
window.addEventListener('appinstalled', () => {
    invitation = null;
    window.dispatchEvent(new CustomEvent('rj-app-installee'));
});

const estIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
const estInstallee = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

window.rjApp = {
    installable: () => Boolean(invitation),
    installee: estInstallee,
    ios: estIos,
    async installer() {
        if (!invitation) return false;
        invitation.prompt();
        const { outcome } = await invitation.userChoice;
        invitation = null;
        return outcome === 'accepted';
    },
};

function cleVersOctets(b64) {
    const pad = '='.repeat((4 - (b64.length % 4)) % 4);
    const brut = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...brut].map((c) => c.charCodeAt(0)));
}

window.rjPush = {
    /** 'non-supporte' | 'ios-installer' | 'refuse' | 'actif' | 'inactif' */
    async etat() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            return estIos() && !estInstallee() ? 'ios-installer' : 'non-supporte';
        }
        if (Notification.permission === 'denied') return 'refuse';
        const reg = await navigator.serviceWorker.getRegistration('/');
        const abo = reg ? await reg.pushManager.getSubscription() : null;
        return abo ? 'actif' : 'inactif';
    },
    /** Demande l'autorisation puis abonne l'appareil ; renvoie l'abonnement (JSON) à transmettre à l'API. */
    async activer(cle) {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') throw new Error(permission === 'denied' ? 'refuse' : 'ignore');
        const reg = (await navigator.serviceWorker.getRegistration('/')) || (await navigator.serviceWorker.register('/sw.js'));
        await navigator.serviceWorker.ready;
        const abo = (await reg.pushManager.getSubscription())
            || (await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: cleVersOctets(cle) }));
        return abo.toJSON();
    },
    async desactiver() {
        const reg = await navigator.serviceWorker.getRegistration('/');
        const abo = reg ? await reg.pushManager.getSubscription() : null;
        if (!abo) return null;
        const endpoint = abo.endpoint;
        await abo.unsubscribe();
        return endpoint;
    },
};

/* ── Paramètres : composants Alpine ────────────────────────────────── */

// Recadrage de la photo de profil : carré, glisser pour cadrer, curseur de
// zoom ; export JPEG 600 × 600 (léger) envoyé au composant Livewire.
window.recadragePhoto = () => ({
    ouvert: false,
    src: null,
    img: null,
    zoom: 1,
    min: 1,
    x: 0,
    y: 0,
    taille: 280,
    envoi: false,
    glisse: null,
    choisir(e) {
        const f = e.target.files[0];
        e.target.value = '';
        if (!f) return;
        if (!f.type.startsWith('image/')) { window.rjToast('Choisissez une image (JPG, PNG, WebP…).', { type: 'erreur' }); return; }
        if (f.size > 15 * 1024 * 1024) { window.rjToast('Cette image est trop lourde (15 Mo maximum).', { type: 'erreur' }); return; }
        const img = new Image();
        img.onload = () => {
            this.img = img;
            this.src = img.src;
            this.min = Math.max(this.taille / img.width, this.taille / img.height);
            this.zoom = this.min;
            this.x = (this.taille - img.width * this.zoom) / 2;
            this.y = (this.taille - img.height * this.zoom) / 2;
            this.ouvert = true;
        };
        img.onerror = () => window.rjToast("Image illisible : essayez un autre fichier.", { type: 'erreur' });
        img.src = URL.createObjectURL(f);
    },
    borner() {
        const w = this.img.width * this.zoom, h = this.img.height * this.zoom;
        this.x = Math.min(0, Math.max(this.taille - w, this.x));
        this.y = Math.min(0, Math.max(this.taille - h, this.y));
    },
    zoomer(v) {
        const ancien = this.zoom, c = this.taille / 2;
        this.zoom = Math.max(this.min, Math.min(this.min * 4, Number(v)));
        this.x = c - (c - this.x) * (this.zoom / ancien);
        this.y = c - (c - this.y) * (this.zoom / ancien);
        this.borner();
    },
    debut(e) { const p = e.touches ? e.touches[0] : e; this.glisse = { px: p.clientX, py: p.clientY, x: this.x, y: this.y }; },
    deplacer(e) {
        if (!this.glisse) return;
        const p = e.touches ? e.touches[0] : e;
        this.x = this.glisse.x + p.clientX - this.glisse.px;
        this.y = this.glisse.y + p.clientY - this.glisse.py;
        this.borner();
    },
    fin() { this.glisse = null; },
    style() { return `width:${this.img?.width * this.zoom}px;height:${this.img?.height * this.zoom}px;transform:translate(${this.x}px,${this.y}px)`; },
    valider() {
        const sortie = 600, k = sortie / this.taille;
        const c = document.createElement('canvas');
        c.width = c.height = sortie;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, sortie, sortie);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(this.img, this.x * k, this.y * k, this.img.width * this.zoom * k, this.img.height * this.zoom * k);
        this.envoi = true;
        c.toBlob((blob) => {
            const fichier = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
            this.$wire.upload('photoFile', fichier,
                () => { this.envoi = false; this.fermer(); },
                () => { this.envoi = false; window.rjToast("L'envoi de la photo a échoué, réessayez.", { type: 'erreur' }); });
        }, 'image/jpeg', 0.88);
    },
    fermer() { this.ouvert = false; if (this.src) URL.revokeObjectURL(this.src); this.src = null; this.img = null; },
});

// Notifications sur cet appareil (onglet Notifications).
window.notificationsAppareil = (cle) => ({
    etat: 'chargement',
    installable: false,
    installee: false,
    ios: false,
    occupe: false,
    async init() {
        this.ios = window.rjApp.ios();
        this.installee = window.rjApp.installee();
        this.installable = window.rjApp.installable();
        window.addEventListener('rj-app-installable', () => { this.installable = true; });
        window.addEventListener('rj-app-installee', () => { this.installable = false; this.installee = true; });
        this.etat = await window.rjPush.etat();
    },
    async activer() {
        if (!cle) return;
        this.occupe = true;
        try {
            const abo = await window.rjPush.activer(cle);
            await this.$wire.enregistrerAppareil(abo);
            this.etat = 'actif';
        } catch (e) {
            this.etat = await window.rjPush.etat();
            window.rjToast(this.etat === 'refuse'
                ? 'Les notifications sont bloquées pour ce site : autorisez-les dans les réglages du navigateur.'
                : "Activation annulée ou impossible sur cet appareil.", { type: 'erreur' });
        }
        this.occupe = false;
    },
    async desactiver() {
        this.occupe = true;
        const endpoint = await window.rjPush.desactiver().catch(() => null);
        await this.$wire.retirerAppareil(endpoint);
        this.etat = await window.rjPush.etat();
        this.occupe = false;
    },
    async installer() {
        if (await window.rjApp.installer()) this.installee = true;
        this.installable = window.rjApp.installable();
    },
});

// Formulaire suivi : bandeau « modifications non enregistrées » et alerte en
// quittant la page tant que ce n'est pas enregistré.
window.formulaireSuivi = () => ({
    sale: false,
    init() {
        this.garde = (e) => { if (this.sale) { e.preventDefault(); e.returnValue = ''; } };
        window.addEventListener('beforeunload', this.garde);
        window.addEventListener('rj-enregistre', () => { this.sale = false; });
    },
    destroy() { window.removeEventListener('beforeunload', this.garde); },
});

// Texte agrandi (confort de lecture) : appliqué sans recharger la page.
window.addEventListener('rj-texte-grand', (e) => {
    const d = Array.isArray(e.detail) ? e.detail[0] : e.detail;
    document.documentElement.classList.toggle('rj-texte-grand', Boolean(d?.actif));
});
