<div>
    <x-admin-light.topbar title="Générateur de visuels" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Générateur de visuels</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Des gabarits typographiques dans la charte du REJCC, prêts pour les réseaux sociaux. Choisissez un gabarit et un format, remplissez les textes, ajoutez une photo si vous le souhaitez, puis téléchargez le PNG. Tout se fait dans votre navigateur : la photo n'est envoyée nulle part.</p>
        </div>

        <div wire:ignore data-generateur data-titre="{{ mb_substr((string) request()->query('titre', ''), 0, 200) }}"
             data-logo-blanc="{{ asset('brand/rejcc-monogram-white.png') }}"
             data-logo-couleur="{{ asset('brand/rejcc-monogram-color.png') }}"
             class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[400px_1fr]">
            {{-- ── Réglages ─────────────────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="mb-2.5 text-[13px] font-bold text-brand">Gabarit</p>
                    <div data-liste-gabarits class="grid grid-cols-2 gap-2"></div>
                </div>

                <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="mb-2.5 text-[13px] font-bold text-brand">Format</p>
                    <div data-liste-formats class="grid grid-cols-3 gap-2"></div>
                    <p class="mb-2 mt-4 text-[13px] font-bold text-brand">Fond</p>
                    <div data-liste-fonds class="grid grid-cols-3 gap-2"></div>
                </div>

                <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="mb-2.5 text-[13px] font-bold text-brand">Textes</p>
                    <div data-champs class="space-y-3"></div>
                </div>

                <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="mb-1 text-[13px] font-bold text-brand">Photo de fond <span class="font-semibold text-[#5B677A]">(facultatif)</span></p>
                    <p data-aide-photo class="mb-2.5 text-[11.5px] text-[#5B677A]">Recouverte d'un voile bleu de la charte pour garder les textes lisibles. Préférez une photo naturelle et authentique.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="btn-tap inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-xs font-bold text-brand hover:bg-cloud">
                            <x-ui.icon name="image" class="size-3.5" /> <span data-libelle-photo>Choisir une photo</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp" data-photo data-test="photo-visuel" class="hidden">
                        </label>
                        <button type="button" data-retirer-photo class="hidden text-xs font-semibold text-[#5B677A] hover:text-accent">Retirer</button>
                    </div>
                    <div data-reglages-photo class="mt-3 hidden space-y-2.5">
                        <label class="block text-[11.5px] font-semibold text-[#5B677A]">Intensité du voile
                            <input type="range" min="35" max="92" value="78" data-voile class="mt-1 w-full accent-[#031D59]">
                        </label>
                        <label class="block text-[11.5px] font-semibold text-[#5B677A]">Cadrage vertical
                            <input type="range" min="0" max="100" value="40" data-cadrage class="mt-1 w-full accent-[#031D59]">
                        </label>
                    </div>
                </div>
            </div>

            {{-- ── Aperçu ───────────────────────────────────────────────── --}}
            <div class="order-first lg:order-none lg:sticky lg:top-24">
                <div class="rounded-[18px] border border-brand/10 bg-[#E9EDF3] p-4 sm:p-6">
                    <div data-cadre-apercu class="mx-auto" style="max-width: 460px">
                        <canvas data-canvas data-test="canvas-visuel" width="1080" height="1350" class="block h-auto w-full rounded-[6px] bg-brand shadow-[0_12px_40px_rgba(3,29,89,.25)]"></canvas>
                    </div>
                    <p data-chargement class="mt-3 text-center text-[11.5px] font-semibold text-[#5B677A]">Chargement des polices…</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p data-dimensions class="text-[12px] text-[#5B677A]">1080 × 1350 px</p>
                    <button type="button" data-telecharger data-test="telecharger-visuel" class="btn-tap inline-flex items-center gap-2 rounded-[10px] bg-accent px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-accent-600 disabled:opacity-60">
                        <x-ui.icon name="download" class="size-4" /> Télécharger en PNG
                    </button>
                </div>
                <p class="mt-3 text-[11.5px] text-[#5B677A]">Conseils : un titre court se lit mieux ; gardez les informations essentielles (quoi, quand, où). Pour les stories, les zones du haut et du bas restent dégagées pour l'interface d'Instagram et de WhatsApp.</p>
            </div>
        </div>
    </div>

    <script>
    (() => {
        /*
         * Générateur de visuels du REJCC : rendu <canvas> 100 % navigateur.
         * Polices du site (Anton ≈ Impact, Manrope ≈ Avenir Next, Libre Caslon
         * ≈ Big Caslon), monogramme officiel en PNG local (pas de canvas
         * « tainted »), photo lue en local (FileReader).
         */
        const BLEU = '#031D59', ROUGE = '#AC0100', BLANC = '#FFFFFF';
        const FORMATS = {
            post: { nom: 'Post', detail: '1080 × 1350', h: 1350 },
            carre: { nom: 'Carré', detail: '1080 × 1080', h: 1080 },
            story: { nom: 'Story', detail: '1080 × 1920', h: 1920 },
        };
        const FONDS = {
            bleu: { nom: 'Bleu', fond: BLEU, texte: BLANC, doux: 'rgba(255,255,255,.74)', trait: 'rgba(255,255,255,.22)', barre: ROUGE, logo: 'blanc' },
            blanc: { nom: 'Blanc', fond: '#FFFFFF', texte: BLEU, doux: 'rgba(51,51,51,.82)', trait: 'rgba(3,29,89,.16)', barre: ROUGE, logo: 'couleur', accent: ROUGE },
            rouge: { nom: 'Rouge', fond: ROUGE, texte: BLANC, doux: 'rgba(255,255,255,.80)', trait: 'rgba(255,255,255,.28)', barre: BLANC, logo: 'blanc' },
        };
        // [clé, libellé, valeur par défaut, type]
        const GABARITS = {
            evenement: { nom: "Annonce d'événement", champs: [
                ['surtitre', 'Surtitre', 'Événement'],
                ['titre', 'Titre', 'Rencontre des jeunes entrepreneurs', 'zone'],
                ['date', 'Date', 'Samedi 22 novembre 2026'],
                ['heure', 'Heure', '15 h 00'],
                ['lieu', 'Lieu', 'Abidjan, Plateau'],
                ['mention', 'Mention (inscription…)', 'Inscription sur rejcc.site'],
            ] },
            rappel: { nom: "Rappel « C'est demain ! »", champs: [
                ['accroche', 'Accroche', "C'est demain !"],
                ['titre', "Titre de l'événement", 'Rencontre des jeunes entrepreneurs', 'zone'],
                ['date', 'Date', 'Samedi 22 novembre 2026'],
                ['heure', 'Heure', '15 h 00'],
                ['lieu', 'Lieu', 'Abidjan, Plateau'],
            ] },
            citation: { nom: 'Citation ou verset', champs: [
                ['surtitre', 'Surtitre', 'Inspiration'],
                ['texte', 'Citation ou verset', "Accéder à un réseau d'excellence pour entreprendre, grandir et réussir ensemble.", 'zone'],
                ['auteur', 'Auteur ou référence', 'La promesse du REJCC'],
            ] },
            bienvenue: { nom: 'Bienvenue aux nouveaux membres', champs: [
                ['surtitre', 'Surtitre', 'Bienvenue'],
                ['titre', 'Titre', 'Bienvenue à nos nouveaux membres', 'zone'],
                ['noms', 'Noms (un par ligne)', 'Prénom Nom\nPrénom Nom\nPrénom Nom', 'zone'],
                ['message', 'Message', 'Heureux de grandir avec vous.'],
            ] },
            portrait: { nom: 'Portrait CathoImpact', champs: [
                ['surtitre', 'Surtitre', 'CathoImpact · Portrait'],
                ['nom', 'Nom', 'Prénom Nom'],
                ['fonction', 'Fonction et entreprise', "Fondatrice de [nom de l'entreprise]"],
                ['accroche', 'Accroche (citation courte)', 'Ma foi guide chacune de mes décisions d’entrepreneure.', 'zone'],
            ] },
            generique: { nom: 'Annonce générique', champs: [
                ['surtitre', 'Surtitre', 'Annonce'],
                ['titre', 'Titre', 'Les adhésions sont ouvertes', 'zone'],
                ['texte', 'Texte', 'Rejoignez un réseau d’excellence pour entreprendre, grandir et réussir ensemble.', 'zone'],
                ['action', 'Appel à l’action', 'rejcc.site/adhesion'],
            ] },
        };

        function init() {
            const racine = document.querySelector('[data-generateur]');
            if (! racine || racine.dataset.pret) return;
            racine.dataset.pret = '1';

            const $ = (s) => racine.querySelector(s);
            const canvas = $('[data-canvas]');
            const ctx = canvas.getContext('2d');
            const etat = { gabarit: 'evenement', format: 'post', fond: 'bleu', valeurs: {}, photo: null, voile: 78, cadrage: 40 };
            for (const [cle, g] of Object.entries(GABARITS)) {
                etat.valeurs[cle] = Object.fromEntries(g.champs.map(([k, , v]) => [k, v]));
            }
            // Titre prérempli (?titre=, depuis « Créer le visuel » après une planification).
            const titreDemande = (racine.dataset.titre || '').trim();
            if (titreDemande) {
                for (const v of Object.values(etat.valeurs)) if ('titre' in v) v.titre = titreDemande;
                etat.gabarit = 'generique';
            }
            const logos = { blanc: new Image(), couleur: new Image() };
            logos.blanc.src = racine.dataset.logoBlanc;
            logos.couleur.src = racine.dataset.logoCouleur;

            // ── Interface ────────────────────────────────────────────────
            const pastille = (actif) => 'btn-tap rounded-[10px] border px-2.5 py-2 text-left text-[12px] font-bold transition '
                + (actif ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:bg-cloud');

            function boutons(conteneur, liste, cle, sousTitre) {
                conteneur.innerHTML = '';
                for (const [k, v] of Object.entries(liste)) {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = pastille(etat[cle] === k);
                    b.dataset.test = cle + '-' + k;
                    b.innerHTML = '<span class="block"></span>' + (sousTitre && v[sousTitre] ? '<span class="block text-[10.5px] font-semibold opacity-70"></span>' : '');
                    b.firstChild.textContent = v.nom;
                    if (b.children[1]) b.children[1].textContent = v[sousTitre];
                    b.addEventListener('click', () => { etat[cle] = k; majInterface(); dessiner(); });
                    conteneur.appendChild(b);
                }
            }

            function champs() {
                const zone = $('[data-champs]');
                zone.innerHTML = '';
                for (const [k, libelle, , type] of GABARITS[etat.gabarit].champs) {
                    const l = document.createElement('label');
                    l.className = 'block';
                    l.innerHTML = '<span class="mb-1 block text-xs font-semibold text-[#5B677A]"></span>';
                    l.firstChild.textContent = libelle;
                    const el = document.createElement(type === 'zone' ? 'textarea' : 'input');
                    if (type === 'zone') el.rows = k === 'noms' ? 4 : 2; else el.type = 'text';
                    el.className = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
                    el.dataset.test = 'champ-' + k;
                    el.maxLength = 400;
                    el.value = etat.valeurs[etat.gabarit][k];
                    el.addEventListener('input', () => { etat.valeurs[etat.gabarit][k] = el.value; dessiner(); });
                    l.appendChild(el);
                    zone.appendChild(l);
                }
            }

            function majInterface() {
                boutons($('[data-liste-gabarits]'), GABARITS, 'gabarit');
                boutons($('[data-liste-formats]'), FORMATS, 'format', 'detail');
                boutons($('[data-liste-fonds]'), FONDS, 'fond');
                champs();
                const f = FORMATS[etat.format];
                canvas.height = f.h;
                $('[data-cadre-apercu]').style.maxWidth = { post: '460px', carre: '520px', story: '380px' }[etat.format];
                $('[data-dimensions]').textContent = '1080 × ' + f.h + ' px · ' + GABARITS[etat.gabarit].nom;
                const avecPhoto = !! etat.photo;
                $('[data-reglages-photo]').classList.toggle('hidden', ! avecPhoto);
                $('[data-retirer-photo]').classList.toggle('hidden', ! avecPhoto);
                $('[data-libelle-photo]').textContent = avecPhoto ? 'Changer de photo' : 'Choisir une photo';
                // Avec une photo, le fond est le voile bleu de la charte.
                $('[data-liste-fonds]').classList.toggle('opacity-40', avecPhoto && etat.gabarit !== 'portrait');
            }

            // ── Outils de texte ──────────────────────────────────────────
            function police(poids, taille, famille, italique = false) {
                return (italique ? 'italic ' : '') + poids + ' ' + taille + 'px ' + famille;
            }
            const ANTON = '"Anton", sans-serif', MANROPE = '"Manrope", sans-serif', CASLON = '"Libre Caslon Display", serif';

            function espacement(px) {
                if ('letterSpacing' in ctx) ctx.letterSpacing = px + 'px';
            }

            /** Coupe un texte en lignes (retours à la ligne manuels conservés). */
            function couper(texte, largeur) {
                const sortie = [];
                for (const para of String(texte).split('\n')) {
                    const mots = para.trim().split(/\s+/).filter(Boolean);
                    if (! mots.length) { sortie.push(''); continue; }
                    let ligne = '';
                    for (const mot of mots) {
                        const essai = ligne ? ligne + ' ' + mot : mot;
                        if (! ligne || ctx.measureText(essai).width <= largeur) ligne = essai;
                        else { sortie.push(ligne); ligne = mot; }
                    }
                    sortie.push(ligne);
                }
                while (sortie.length && sortie[sortie.length - 1] === '') sortie.pop();
                return sortie;
            }

            /**
             * Plus grande taille (de max à min) pour laquelle le texte tient
             * dans la largeur et la hauteur données.
             */
            function ajuster(texte, { poids = 400, famille, italique = false, majuscules = false, largeur, hauteur, max, min, interligne = 1.1, lettres = 0 }) {
                const t0 = majuscules ? String(texte).toLocaleUpperCase('fr') : String(texte);
                let res = null;
                for (let t = max; t >= min; t -= 2) {
                    ctx.font = police(poids, t, famille, italique);
                    espacement(lettres * t);
                    const lignes = couper(t0, largeur);
                    const h = lignes.length * t * interligne;
                    res = { texte: t0, taille: t, lignes, hauteur: h, poids, famille, italique, interligne, lettres };
                    if (h <= hauteur && lignes.every((l) => ctx.measureText(l).width <= largeur)) return res;
                }
                // Trop long même à la taille minimale : on tronque proprement.
                const maxLignes = Math.max(1, Math.floor(hauteur / (min * interligne)));
                if (res.lignes.length > maxLignes) {
                    res.lignes = res.lignes.slice(0, maxLignes);
                    res.lignes[maxLignes - 1] = res.lignes[maxLignes - 1].replace(/\s*\S*$/, '') + '…';
                    res.hauteur = maxLignes * min * interligne;
                }
                return res;
            }

            /** Écrit un bloc ajusté à partir de y (haut du bloc). Renvoie le bas du bloc. */
            function ecrire(bloc, x, y, couleur, aligne = 'left') {
                ctx.font = police(bloc.poids, bloc.taille, bloc.famille, bloc.italique);
                espacement(bloc.lettres * bloc.taille);
                ctx.fillStyle = couleur;
                ctx.textAlign = aligne;
                ctx.textBaseline = 'alphabetic';
                const lh = bloc.taille * bloc.interligne;
                // Ligne de base : centrée optiquement dans l'interligne.
                const decalage = (lh - bloc.taille) / 2 + bloc.taille * 0.86;
                bloc.lignes.forEach((l, i) => ctx.fillText(l, x, y + i * lh + decalage));
                espacement(0);
                ctx.textAlign = 'left';
                return y + bloc.hauteur;
            }

            function ligneSimple(texte, x, y, { poids, taille, famille, italique = false, couleur, lettres = 0, aligne = 'left', majuscules = false, largeurMax = 9999 }) {
                ctx.font = police(poids, taille, famille, italique);
                espacement(lettres);
                ctx.fillStyle = couleur;
                ctx.textAlign = aligne;
                ctx.textBaseline = 'alphabetic';
                let t = majuscules ? String(texte).toLocaleUpperCase('fr') : String(texte);
                while (t.length > 1 && ctx.measureText(t).width > largeurMax) t = t.slice(0, -2) + '…';
                ctx.fillText(t, x, y);
                const w = ctx.measureText(t).width;
                espacement(0);
                ctx.textAlign = 'left';
                return w;
            }

            // ── Fond, logo, pied de page ─────────────────────────────────
            function couvrir(img, x, y, w, h) {
                const r = Math.max(w / img.width, h / img.height);
                const sw = w / r, sh = h / r;
                const sx = (img.width - sw) / 2;
                const sy = (img.height - sh) * (etat.cadrage / 100);
                ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
            }

            function fondPhoto(W, H) {
                couvrir(etat.photo, 0, 0, W, H);
                const a = etat.voile / 100;
                ctx.fillStyle = `rgba(3,29,89,${a})`;
                ctx.fillRect(0, 0, W, H);
                // Renfort en bas, là où se trouvent les textes.
                const g = ctx.createLinearGradient(0, H * 0.35, 0, H);
                g.addColorStop(0, 'rgba(3,29,89,0)');
                g.addColorStop(1, `rgba(3,29,89,${Math.min(0.55, 1 - a + 0.25)})`);
                ctx.fillStyle = g;
                ctx.fillRect(0, 0, W, H);
            }

            function logo(x, y, hauteur, version) {
                const img = logos[version];
                if (! img.complete || ! img.naturalWidth) return 0;
                const w = hauteur * img.naturalWidth / img.naturalHeight;
                ctx.drawImage(img, x, y, w, hauteur);
                return w;
            }

            // ── Rendu ────────────────────────────────────────────────────
            function dessiner() {
                const W = 1080, H = FORMATS[etat.format].h;
                const story = etat.format === 'story', carre = etat.format === 'carre';
                const v = etat.valeurs[etat.gabarit];
                const photo = !! etat.photo;
                const portrait = etat.gabarit === 'portrait';
                // Avec une photo (hors portrait), le thème est le bleu de la charte.
                const th = FONDS[photo && ! portrait ? 'bleu' : etat.fond];
                const M = 92;
                const haut = story ? 190 : 84;
                const bas = story ? 230 : 84;
                const largeur = W - 2 * M;

                ctx.clearRect(0, 0, W, H);
                ctx.fillStyle = th.fond;
                ctx.fillRect(0, 0, W, H);

                let zoneHaut, zoneBas;
                const hLogo = carre ? 152 : 156; // monogramme : 100 px de large minimum (charte), soit 152 px de haut

                if (portrait) {
                    // Photo en haut, bandeau de la couleur choisie en bas.
                    const hPhoto = Math.round(H * (story ? 0.54 : carre ? 0.46 : 0.5));
                    if (photo) {
                        couvrir(etat.photo, 0, 0, W, hPhoto);
                        const g = ctx.createLinearGradient(0, hPhoto * 0.55, 0, hPhoto);
                        g.addColorStop(0, 'rgba(0,0,0,0)');
                        g.addColorStop(1, th.fond);
                        ctx.fillStyle = g;
                        ctx.fillRect(0, 0, W, hPhoto + 1);
                        const g2 = ctx.createLinearGradient(0, 0, 0, haut + hLogo + 60);
                        g2.addColorStop(0, 'rgba(3,29,89,.55)');
                        g2.addColorStop(1, 'rgba(3,29,89,0)');
                        ctx.fillStyle = g2;
                        ctx.fillRect(0, 0, W, haut + hLogo + 60);
                    } else {
                        // Sans photo : emplacement discret, à remplacer.
                        ctx.fillStyle = th.fond === '#FFFFFF' ? '#E9EDF3' : 'rgba(255,255,255,.07)';
                        ctx.fillRect(0, 0, W, hPhoto);
                        ligneSimple('Ajoutez la photo du portrait', W / 2, hPhoto / 2 + 14, { poids: 600, taille: 34, famille: MANROPE, couleur: th.doux, aligne: 'center' });
                    }
                    logo(M, haut, hLogo, photo ? 'blanc' : th.logo);
                    zoneHaut = hPhoto + (carre ? 36 : 48);
                } else {
                    if (photo) fondPhoto(W, H);
                    logo(M, haut, hLogo, th.logo);
                    zoneHaut = haut + hLogo + (carre ? 56 : 90);
                }

                // Surtitre à droite du monogramme.
                const surtitre = v.surtitre ?? (etat.gabarit === 'rappel' ? 'Rappel' : '');
                if (surtitre && ! portrait) {
                    ligneSimple(surtitre, W - M, haut + 44, { poids: 700, taille: 25, famille: MANROPE, couleur: th.doux, lettres: 6, aligne: 'right', majuscules: true, largeurMax: largeur - 160 });
                    ctx.fillStyle = th.barre;
                    ctx.fillRect(W - M - 64, haut + 66, 64, 6);
                }

                // Pied de page : site et signature institutionnelle.
                const yPied = H - bas;
                ctx.fillStyle = th.trait;
                ctx.fillRect(M, yPied - 62, largeur, 2);
                ligneSimple('rejcc.site', M, yPied, { poids: 700, taille: 26, famille: MANROPE, couleur: th.texte, lettres: 1 });
                ligneSimple("Ensemble pour l'excellence.", W - M, yPied, { poids: 400, taille: 31, famille: CASLON, italique: true, couleur: th.texte, aligne: 'right' });
                zoneBas = yPied - 62 - (carre ? 50 : 70);

                const dispo = zoneBas - zoneHaut;
                const rendu = { evenement, rappel, citation, bienvenue, portrait: rendrePortrait, generique }[etat.gabarit];
                rendu({ W, H, M, largeur, zoneHaut, zoneBas, dispo, th, v, story, carre });
            }

            /** Barre d'accent au-dessus d'un titre. */
            function barre(th, x, y) {
                ctx.fillStyle = th.barre;
                ctx.fillRect(x, y, 84, 8);
            }

            /**
             * Informations (libellé + valeur) empilées, séparées par un filet.
             * Avec mesure = true, renvoie seulement la hauteur, sans dessiner.
             */
            function infos(lignes, x, y, largeur, th, carre, mesure = false) {
                const tl = carre ? 21 : 23, tv = carre ? 36 : 42, pad = carre ? 18 : 24, col = 210;
                let yy = y;
                lignes.filter(([, val]) => String(val || '').trim()).forEach(([lib, val], i) => {
                    if (i && ! mesure) { ctx.fillStyle = th.trait; ctx.fillRect(x, yy, largeur, 2); }
                    yy += pad;
                    const bloc = ajuster(val, { poids: 600, famille: MANROPE, largeur: largeur - col, hauteur: tv * 1.2 * 2, max: tv, min: 26, interligne: 1.2 });
                    if (! mesure) {
                        // Libellé sur la ligne de base de la première ligne de la valeur.
                        const lh = bloc.taille * 1.2;
                        const base = yy + (lh - bloc.taille) / 2 + bloc.taille * 0.86;
                        ligneSimple(lib, x, base, { poids: 700, taille: tl, famille: MANROPE, couleur: th.doux, lettres: 4, majuscules: true });
                        ecrire(bloc, x + col, yy, th.texte);
                    }
                    yy += bloc.hauteur + pad;
                });
                return yy;
            }

            function hauteurInfos(lignes, carre, largeur) {
                return infos(lignes, 0, 0, largeur, null, carre, true);
            }

            function evenement({ M, largeur, zoneHaut, zoneBas, dispo, th, v, carre }) {
                const lignesInfos = [['Date', v.date], ['Heure', v.heure], ['Lieu', v.lieu]];
                const hInfos = hauteurInfos(lignesInfos, carre, largeur);
                const hMention = v.mention.trim() ? 70 : 0;
                const titre = ajuster(v.titre, { famille: ANTON, majuscules: true, largeur, hauteur: dispo - hInfos - hMention - 70, max: carre ? 118 : 138, min: 52, interligne: 1.04, lettres: 0.005 });
                // Bloc ancré en bas de la zone de contenu.
                let y = zoneBas - (titre.hauteur + 54 + hInfos + hMention);
                y = Math.max(zoneHaut, y);
                barre(th, M, y - 34);
                y = ecrire(titre, M, y, th.texte) + 40;
                y = infos(lignesInfos, M, y, largeur, th, carre);
                if (hMention) {
                    ligneSimple(v.mention, M, y + 46, { poids: 400, taille: carre ? 32 : 36, famille: CASLON, italique: true, couleur: th.accent || th.texte, largeurMax: largeur });
                }
            }

            function rappel({ M, largeur, zoneHaut, zoneBas, dispo, th, v, carre, story }) {
                const lignesInfos = [['Date', v.date], ['Heure', v.heure], ['Lieu', v.lieu]];
                const hInfos = hauteurInfos(lignesInfos, carre, largeur);
                const accroche = ajuster(v.accroche, { famille: CASLON, italique: true, largeur, hauteur: carre ? 150 : 260, max: carre ? 118 : story ? 150 : 140, min: 56, interligne: 1.05 });
                const titre = ajuster(v.titre, { famille: ANTON, majuscules: true, largeur, hauteur: dispo - hInfos - accroche.hauteur - 110, max: carre ? 76 : 92, min: 44, interligne: 1.06 });
                const total = accroche.hauteur + 36 + titre.hauteur + 44 + hInfos;
                let y = Math.max(zoneHaut, zoneHaut + (dispo - total) / 2);
                y = ecrire(accroche, M, y, th.accent || th.texte) + 24;
                barre(th, M, y);
                y = ecrire(titre, M, y + 34, th.texte) + 40;
                infos(lignesInfos, M, y, largeur, th, carre);
            }

            function citation({ M, largeur, zoneHaut, dispo, th, v, carre }) {
                const hAuteur = v.auteur.trim() ? 90 : 0;
                const guillemet = carre ? 170 : 220;
                const texte = ajuster(v.texte, { famille: CASLON, italique: true, largeur, hauteur: dispo - guillemet * 0.62 - hAuteur, max: carre ? 76 : 88, min: 38, interligne: 1.28 });
                const total = guillemet * 0.62 + texte.hauteur + hAuteur;
                let y = Math.max(zoneHaut, zoneHaut + (dispo - total) / 2);
                // Le guillemet (« ») occupe à peu près la moitié haute du corps : ligne de base à 0,5 em.
                ligneSimple('«', M - 6, y + guillemet * 0.5, { poids: 400, taille: guillemet, famille: CASLON, couleur: th.barre === BLANC ? 'rgba(255,255,255,.55)' : ROUGE });
                y += guillemet * 0.62;
                y = ecrire(texte, M, y, th.texte) + 36;
                if (hAuteur) {
                    barre(th, M, y);
                    ligneSimple(v.auteur, M, y + 62, { poids: 700, taille: carre ? 26 : 28, famille: MANROPE, couleur: th.doux, lettres: 4, majuscules: true, largeurMax: largeur });
                }
            }

            function bienvenue({ M, largeur, zoneHaut, dispo, th, v, carre }) {
                const noms = v.noms.split('\n').map((n) => n.trim()).filter(Boolean);
                const hMessage = v.message.trim() ? 90 : 0;
                const titre = ajuster(v.titre, { famille: ANTON, majuscules: true, largeur, hauteur: dispo * 0.42, max: carre ? 104 : 122, min: 50, interligne: 1.04 });
                // Les noms : sur deux colonnes s'ils sont nombreux.
                const colonnes = noms.length > 6 ? 2 : 1;
                const parColonne = Math.ceil(noms.length / colonnes) || 1;
                const hNomsMax = dispo - titre.hauteur - hMessage - 90;
                let tNom = Math.max(24, Math.min(carre ? 40 : 46, Math.floor(hNomsMax / (parColonne * 1.5))));
                // Les noms longs réduisent la taille plutôt que d'être tronqués.
                const largeurNom = (largeur - 40) / colonnes - 34;
                const plusLarge = (t) => { ctx.font = police(600, t, MANROPE); return Math.max(0, ...noms.map((n) => ctx.measureText(n).width)); };
                while (tNom > 24 && plusLarge(tNom) > largeurNom) tNom -= 2;
                const hNoms = noms.length ? parColonne * tNom * 1.5 : 0;
                const total = titre.hauteur + 70 + hNoms + hMessage;
                let y = Math.max(zoneHaut, zoneHaut + (dispo - total) / 2);
                barre(th, M, y - 34);
                y = ecrire(titre, M, y, th.texte) + 52;
                const lc = (largeur - 40) / colonnes;
                noms.forEach((nom, i) => {
                    const c = Math.floor(i / parColonne), r = i % parColonne;
                    const x = M + c * (lc + 40);
                    const yy = y + r * tNom * 1.5 + tNom;
                    ctx.fillStyle = th.barre;
                    ctx.fillRect(x, yy - tNom * 0.36, 14, 4);
                    ligneSimple(nom, x + 30, yy, { poids: 600, taille: tNom, famille: MANROPE, couleur: th.texte, largeurMax: lc - 34 });
                });
                y += hNoms;
                if (hMessage) {
                    ligneSimple(v.message, M, y + 62, { poids: 400, taille: carre ? 36 : 40, famille: CASLON, italique: true, couleur: th.accent || th.texte, largeurMax: largeur });
                }
            }

            function rendrePortrait({ M, largeur, zoneHaut, zoneBas, th, v, carre }) {
                const dispo = zoneBas - zoneHaut;
                const hSurtitre = 64;
                const nom = ajuster(v.nom, { famille: ANTON, majuscules: true, largeur, hauteur: carre ? 120 : 150, max: carre ? 96 : 112, min: 48, interligne: 1.02 });
                const fonction = ajuster(v.fonction, { poids: 600, famille: MANROPE, largeur, hauteur: 90, max: carre ? 32 : 36, min: 24, interligne: 1.25 });
                const accroche = ajuster(v.accroche ? '« ' + v.accroche.replace(/^[«"\s]+|[»"\s]+$/g, '') + ' »' : '', { famille: CASLON, italique: true, largeur, hauteur: Math.max(60, dispo - hSurtitre - nom.hauteur - fonction.hauteur - 70), max: carre ? 40 : 48, min: 28, interligne: 1.3 });
                const total = hSurtitre + nom.hauteur + 16 + fonction.hauteur + (accroche.lignes.length ? 36 + accroche.hauteur : 0);
                let y = zoneHaut + Math.max(0, (dispo - total) / 2);
                ctx.fillStyle = th.barre;
                ctx.fillRect(M, y + 4, 64, 6);
                ligneSimple(v.surtitre, M + 84, y + 16, { poids: 700, taille: 24, famille: MANROPE, couleur: th.doux, lettres: 6, majuscules: true, largeurMax: largeur - 84 });
                y += hSurtitre;
                y = ecrire(nom, M, y, th.texte) + 16;
                y = ecrire(fonction, M, y, th.doux) + 36;
                if (accroche.lignes.length) ecrire(accroche, M, y, th.texte);
            }

            function generique({ M, largeur, zoneHaut, zoneBas, dispo, th, v, carre }) {
                const hAction = v.action.trim() ? 96 : 0;
                const titre = ajuster(v.titre, { famille: ANTON, majuscules: true, largeur, hauteur: dispo * 0.55, max: carre ? 120 : 140, min: 52, interligne: 1.04 });
                const texte = ajuster(v.texte, { poids: 500, famille: MANROPE, largeur, hauteur: Math.max(50, dispo - titre.hauteur - hAction - 90), max: carre ? 38 : 42, min: 26, interligne: 1.42 });
                const total = titre.hauteur + 44 + (texte.lignes.length ? texte.hauteur : 0) + hAction;
                let y = Math.max(zoneHaut + 34, zoneBas - total);
                barre(th, M, y - 34);
                y = ecrire(titre, M, y, th.texte) + 44;
                if (texte.lignes.length) y = ecrire(texte, M, y, th.doux);
                if (hAction) {
                    const w = ligneSimple(v.action, M, y + 70, { poids: 700, taille: carre ? 34 : 38, famille: MANROPE, couleur: th.texte, largeurMax: largeur - 60 });
                    ctx.fillStyle = th.barre;
                    ctx.fillRect(M, y + 86, w, 4);
                    ligneSimple('→', M + w + 18, y + 70, { poids: 700, taille: carre ? 34 : 38, famille: MANROPE, couleur: th.texte });
                }
            }

            // ── Photo ────────────────────────────────────────────────────
            $('[data-photo]').addEventListener('change', (e) => {
                const f = e.target.files[0];
                if (! f) return;
                if (! /^image\/(jpeg|png|webp)$/.test(f.type)) { alert('Choisissez une photo JPG, PNG ou WebP.'); return; }
                const lecteur = new FileReader();
                lecteur.onload = () => {
                    const img = new Image();
                    img.onload = () => { etat.photo = img; majInterface(); dessiner(); };
                    img.src = lecteur.result;
                };
                lecteur.readAsDataURL(f);
                e.target.value = '';
            });
            $('[data-retirer-photo]').addEventListener('click', () => { etat.photo = null; majInterface(); dessiner(); });
            $('[data-voile]').addEventListener('input', (e) => { etat.voile = +e.target.value; dessiner(); });
            $('[data-cadrage]').addEventListener('input', (e) => { etat.cadrage = +e.target.value; dessiner(); });

            // ── Téléchargement ───────────────────────────────────────────
            $('[data-telecharger]').addEventListener('click', () => {
                dessiner();
                canvas.toBlob((blob) => {
                    const a = document.createElement('a');
                    const date = new Date().toISOString().slice(0, 10);
                    a.href = URL.createObjectURL(blob);
                    a.download = `rejcc-${etat.gabarit}-${etat.format}-${date}.png`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(() => URL.revokeObjectURL(a.href), 4000);
                }, 'image/png');
            });

            majInterface();
            // Polices du site chargées avant le premier dessin (sinon le canvas utilise une police de repli).
            const polices = ['400 100px "Anton"', '500 100px "Manrope"', '600 100px "Manrope"', '700 100px "Manrope"', '400 100px "Libre Caslon Display"', 'italic 400 100px "Libre Caslon Display"'];
            const logosPrets = Object.values(logos).map((img) => img.decode ? img.decode().catch(() => {}) : Promise.resolve());
            Promise.all([...polices.map((p) => document.fonts.load(p, 'AÉé«»')), ...logosPrets])
                .then(() => document.fonts.ready)
                .catch(() => {})
                .finally(() => { $('[data-chargement]').remove(); dessiner(); });
            dessiner();
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
        document.addEventListener('livewire:navigated', init);
    })();
    </script>
</div>
