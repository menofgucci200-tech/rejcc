<div>
    <x-member-light.topbar title="Ma carte membre" />

    @if ($locked ?? false)
        <x-member-light.paywall description="Votre carte de membre officielle (avec QR code) n'est délivrée qu'aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    <div class="mx-auto max-w-[1120px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Ma carte de membre</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Votre carte officielle du REJCC. Ajoutez votre photo, puis présentez le QR code (recto scanné = accès à votre profil).</p>
            </div>
            @if ($message)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                    <x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}
                </span>
            @endif
        </div>

        {{-- Input fichier caché, relié à la zone photo du recto via l'id --}}
        <input type="file" id="card-photo-input" wire:model="photoUpload" accept="image/*" class="hidden">

        <div id="carte-print-zone" wire:loading.class="opacity-60" wire:target="photoUpload">
            <x-member-card
                :name="$name"
                :roleLabel="$roleLabel"
                :role="$role"
                :numero="$numero"
                :code="$code"
                :photo="$photo"
                :dateAdhesion="$dateAdhesion"
                :validite="$validite?->format('d/m/Y')"
                :editable="true"
                uploadId="card-photo-input"
            />
            <p class="carte-consigne mt-[8mm] hidden max-w-[180mm] text-center text-[9pt] text-[#5B677A]">Découpez le recto et le verso le long des pointillés, collez-les dos à dos puis faites plastifier la carte. Format carte bancaire : 85,6 × 54 mm.</p>
        </div>

        @if ($validite)
            <p data-test="validite" class="mt-5 text-center text-[13px] text-[#5B677A]">
                Carte valable jusqu'au <strong class="text-brand">{{ $validite->translatedFormat('j F Y') }}</strong> — abonnement annuel renouvelable à cette date anniversaire.
                @if ($validite->lte(now()->addDays(30)))
                    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="font-bold text-accent hover:underline">Renouveler maintenant</a>
                @endif
            </p>
        @endif

        @error('photoUpload') <p class="mt-4 text-center text-xs font-medium text-accent">{{ $message }}</p> @enderror

        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <button type="button" x-data @click="$dispatch('presenter-carte')" data-test="presenter" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">
                <x-ui.icon name="qr-code" class="size-3.5" /> Présenter ma carte
            </button>
            <label for="card-photo-input" class="btn-tap inline-flex cursor-pointer items-center gap-2 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                <x-ui.icon name="image" class="size-3.5" /> {{ $photo ? 'Changer ma photo' : 'Ajouter ma photo' }}
            </label>
            <button type="button" data-test="imprimer" onclick="{{ $photo ? 'window.print()' : "if (confirm('Votre carte n\\'a pas encore de photo. Imprimer quand même ?')) window.print()" }}" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                <x-ui.icon name="download" class="size-3.5" /> Imprimer / enregistrer en PDF
            </button>
            <button type="button" data-test="telecharger-image"
                x-data="{ busy: false }" :disabled="busy"
                @click="busy = true; window.carteEnImage(document.querySelector('#carte-print-zone > .grid'), @js('carte-rejcc-'.\Illuminate\Support\Str::slug($name).'.png')).catch(() => alert('Le téléchargement a échoué, réessayez.')).finally(() => busy = false)"
                class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud disabled:opacity-60">
                <x-ui.icon name="image" class="size-3.5" /> <span x-text="busy ? 'Préparation…' : 'Télécharger en image'">Télécharger en image</span>
            </button>
            <span wire:loading wire:target="photoUpload" class="text-xs font-semibold text-[#9AA6B8]">Envoi de la photo…</span>
        </div>

        {{-- Mode « Présenter » : plein écran, QR en grand pour être scanné
             facilement depuis l'écran du téléphone ; l'écran reste allumé. --}}
        <div
            wire:ignore
            x-data="{
                open: false, lock: null,
                async show() {
                    this.open = true;
                    document.documentElement.classList.add('overflow-hidden');
                    this.$nextTick(() => window.QRCode && window.QRCode.toCanvas(this.$refs.qr, @js(url('/carte/'.$code)), { width: 640, margin: 1, color: { dark: '#1D2556', light: '#ffffff' } }, () => { this.$refs.qr.style.width = ''; this.$refs.qr.style.height = ''; }));
                    try { this.lock = await navigator.wakeLock?.request('screen'); } catch (e) {}
                },
                hide() { this.open = false; document.documentElement.classList.remove('overflow-hidden'); this.lock?.release?.(); this.lock = null; },
            }"
            @presenter-carte.window="show()"
            @keydown.escape.window="open && hide()"
        >
            <div x-show="open" x-cloak x-transition.opacity data-test="mode-presenter" role="dialog" aria-modal="true" aria-label="Carte membre à présenter"
                class="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-5 overflow-y-auto {{ $role === 'mentor' ? 'bg-[#AC0100]' : 'bg-[#1D2556]' }} px-6 py-10 text-center text-white">
                <button type="button" @click="hide()" aria-label="Fermer" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">
                    <x-ui.icon name="x" class="size-5" />
                </button>
                <img src="{{ asset('brand/carte-logo.png') }}" alt="REJCC" class="w-28">
                <div class="flex items-center gap-3.5">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="size-14 rounded-xl object-cover ring-2 ring-white/20">
                    @endif
                    <div class="text-left">
                        <p class="font-serif text-xl font-bold uppercase tracking-[0.05em]">{{ $name }}</p>
                        <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-[#E07A7A]">{{ $roleLabel }}</p>
                    </div>
                </div>
                <div class="rounded-3xl bg-white p-4 shadow-2xl">
                    {{-- taille en classe (la lib QR réécrit les styles en ligne) --}}
                    <canvas x-ref="qr" class="!block !h-[min(78vw,46vh)] !w-[min(78vw,46vh)]"></canvas>
                </div>
                <p class="text-[12px] font-bold uppercase tracking-[0.2em] text-white/80">Scannez pour voir mon profil</p>
                <div class="text-[13px] leading-relaxed text-white/85">
                    <p>N° {{ $numero }}</p>
                    @if ($validite)
                        <p class="font-bold text-[#7FE0A6]">Valable jusqu'au {{ $validite->translatedFormat('j F Y') }}</p>
                    @endif
                </div>
                <p class="text-[11px] text-white/50">Astuce : augmentez la luminosité de l'écran pour faciliter le scan.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Impression / export PDF : n'imprimer que la carte, en conservant les
         couleurs de fond (par défaut les navigateurs les suppriment). --}}
    <style>
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            /* Le layout membre défile dans un conteneur interne (overflow-hidden) :
               on sort la carte du flux (fixed) pour qu'elle occupe seule la page. */
            body * { visibility: hidden; }
            #carte-print-zone, #carte-print-zone * { visibility: visible; }
            #carte-print-zone {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 0;
                opacity: 1 !important;
            }
            /* Format réel d'une carte bancaire (ISO 7810 ID-1 : 85,6 × 54 mm),
               recto et verso côte à côte, avec un trait de coupe en pointillés. */
            #carte-print-zone {
                flex-direction: column;
                justify-content: flex-start;
                padding-top: 15mm;
            }
            #carte-print-zone > .grid {
                grid-template-columns: 85.6mm 85.6mm !important;
                width: auto;
                max-width: none;
                gap: 8mm !important;
            }
            #carte-print-zone > .grid > div {
                box-shadow: none !important;
                outline: 0.25mm dashed #8a94a6;
                outline-offset: 1.5mm;
            }
            #carte-print-zone .carte-consigne { display: block !important; }
            #carte-print-zone .carte-sans-photo,
            #carte-print-zone .carte-sans-photo * { visibility: hidden !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</div>
