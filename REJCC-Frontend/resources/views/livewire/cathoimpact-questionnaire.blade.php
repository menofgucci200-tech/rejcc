<div>
@if ($status === 'success')
    <div class="flex min-h-screen flex-col items-center justify-center px-5 py-10 text-center" data-test="cathoimpact-merci">
        <span class="mb-5 flex size-[76px] items-center justify-center rounded-full text-white shadow-[0_10px_26px_rgba(34,168,90,.3)]" style="background: linear-gradient(135deg, #22A85A, #1C8A49)">
            <x-ui.icon name="check" class="size-9" />
        </span>
        <h1 class="mb-2.5 text-2xl font-extrabold text-brand">Merci d'avoir partagé votre parcours !</h1>
        <p class="mb-3 max-w-[420px] text-sm leading-relaxed text-[#5B677A]">Votre réponse est bien arrivée au département Communication du REJCC.</p>
        <p class="mb-7 max-w-[420px] text-sm leading-relaxed text-[#5B677A]">Si votre parcours est retenu, la Communication vous contactera par e-mail ou sur WhatsApp pour un questionnaire détaillé, avant la réalisation de votre portrait CathoImpact.</p>
        <a href="{{ route('cathoimpact') }}" wire:navigate class="rounded-[10px] bg-brand px-[22px] py-3 text-[13.5px] font-bold text-white hover:bg-brand/90">Retour à CathoImpact</a>
    </div>
@else
    @php
        $libelles = $profils[$profil] ?? null;
        $champ = fn (string $nom) => 'w-full rounded-xl border bg-white px-4 py-3 text-[15px] text-brand outline-none transition focus:ring-2 '.($errors->has($nom) ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20');
    @endphp
    <div class="min-h-screen bg-cloud">
        <div class="mx-auto w-full max-w-[640px] px-4 py-8 sm:py-10">
            <div class="mb-4 flex items-start gap-3.5">
                <a href="{{ route('cathoimpact') }}" wire:navigate aria-label="Retour à CathoImpact" class="flex size-11 shrink-0 items-center justify-center rounded-[12px] border border-brand/15 bg-white text-brand shadow-[0_2px_6px_rgba(3,29,89,.06)] hover:bg-cloud">
                    <x-ui.icon name="arrow-left" class="size-[18px]" />
                </a>
                <div class="min-w-0 pt-0.5">
                    <h1 class="text-[19px] font-extrabold leading-tight text-brand">Proposer mon parcours à CathoImpact</h1>
                    <p class="mt-1.5 text-[13px] font-bold text-brand">Étape {{ $step + 1 }} sur 2 · {{ $step === 0 ? 'Votre profil' : 'Votre parcours' }}</p>
                </div>
            </div>

            <div class="mb-6 h-1.5 overflow-hidden rounded-full bg-brand/10">
                <div class="h-full rounded-full transition-all duration-300" style="width: {{ $step === 0 ? 50 : 100 }}%; background: linear-gradient(90deg, #AC0100, #D95B5A)"></div>
            </div>

            <div class="rounded-[20px] border border-brand/10 bg-white p-6 shadow-[0_18px_50px_-24px_rgba(3,29,89,.28)] sm:p-8">
                {{-- Invisible pour les personnes : piège à robots. --}}
                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label>Site web <input type="text" wire:model="site_web" tabindex="-1" autocomplete="off"></label>
                </div>

                @if ($step === 0 || ! $libelles)
                    <p class="mb-1.5 text-sm leading-relaxed text-ink/75">Quelques minutes suffisent. Si votre parcours est retenu, la Communication vous recontactera pour aller plus loin.</p>
                    <p class="mb-4 mt-4 text-[15px] font-extrabold text-brand">Vous êtes :</p>
                    <div class="grid gap-3">
                        <button type="button" wire:click="choisirProfil('entrepreneur')" data-test="profil-entrepreneur" class="rounded-2xl border border-brand/15 p-5 text-left transition hover:border-brand hover:bg-cloud">
                            <span class="block text-base font-extrabold text-brand">Je suis entrepreneur</span>
                            <span class="mt-1 block text-sm text-ink/70">Fondateur, gérant d'entreprise, commerçant, porteur de projet indépendant.</span>
                        </button>
                        <button type="button" wire:click="choisirProfil('professionnel')" data-test="profil-professionnel" class="rounded-2xl border border-brand/15 p-5 text-left transition hover:border-brand hover:bg-cloud">
                            <span class="block text-base font-extrabold text-brand">Je ne suis pas entrepreneur</span>
                            <span class="mt-1 block text-sm text-ink/70">Salarié, cadre, expert, ou toute autre responsabilité dans le monde professionnel.</span>
                        </button>
                    </div>
                @else
                    <form wire:submit="envoyer" class="flex flex-col gap-5" novalidate>
                        <p class="-mb-1 text-xs font-bold uppercase tracking-[0.12em] text-azure">Vous</p>
                        <div class="flex flex-col gap-1.5">
                            <label for="ci-nom" class="text-sm font-semibold text-brand">Nom complet</label>
                            <input wire:model="nom_complet" id="ci-nom" type="text" autocomplete="name" class="{{ $champ('nom_complet') }}">
                            @error('nom_complet') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <label for="ci-email" class="text-sm font-semibold text-brand">E-mail</label>
                                <input wire:model="email" id="ci-email" type="email" autocomplete="email" class="{{ $champ('email') }}">
                                @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="ci-whatsapp" class="text-sm font-semibold text-brand">Numéro WhatsApp</label>
                                <input wire:model="whatsapp" id="ci-whatsapp" type="tel" autocomplete="tel" inputmode="tel" class="{{ $champ('whatsapp') }}">
                                @error('whatsapp') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <p class="-mb-1 mt-2 text-xs font-bold uppercase tracking-[0.12em] text-azure">Votre activité</p>
                        @php
                            // Entrepreneur : entreprise puis activité ; professionnel : poste puis entreprise.
                            $ordre = $profil === 'professionnel' ? ['fonction', 'entreprise'] : ['entreprise', 'fonction'];
                        @endphp
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($ordre as $nom)
                                <div class="flex flex-col gap-1.5" wire:key="champ-{{ $profil }}-{{ $nom }}">
                                    <label for="ci-{{ $nom }}" class="text-sm font-semibold text-brand">{{ $libelles[$nom] }}</label>
                                    <input wire:model="{{ $nom }}" id="ci-{{ $nom }}" type="text" maxlength="150" @if ($nom === 'entreprise') autocomplete="organization" @endif class="{{ $champ($nom) }}">
                                    @error($nom) <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <label for="ci-secteur" class="text-sm font-semibold text-brand">Secteur d'activité</label>
                                <input wire:model="secteur" id="ci-secteur" type="text" maxlength="100" placeholder="Ex. : agroalimentaire, finance, santé" class="{{ $champ('secteur') }}">
                                @error('secteur') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="ci-ville" class="text-sm font-semibold text-brand">Ville</label>
                                <input wire:model="ville" id="ci-ville" type="text" maxlength="100" autocomplete="address-level2" class="{{ $champ('ville') }}">
                                @error('ville') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <p class="-mb-1 mt-2 text-xs font-bold uppercase tracking-[0.12em] text-azure">Votre parcours</p>
                        <div class="flex flex-col gap-1.5" x-data="{ n: {{ mb_strlen($parcours) }} }">
                            <label for="ci-parcours" class="text-sm font-semibold text-brand">Votre parcours en quelques lignes</label>
                            <textarea wire:model="parcours" id="ci-parcours" rows="6" maxlength="{{ $parcoursMax }}" @input="n = $event.target.value.length" placeholder="Vos débuts, les grandes étapes, où vous en êtes aujourd'hui." class="{{ $champ('parcours') }}"></textarea>
                            <span class="flex justify-between gap-3 text-xs">
                                <span class="font-medium text-accent">@error('parcours') {{ $message }} @enderror</span>
                                <span class="shrink-0 text-[#5B677A]" :class="n > {{ $parcoursMax - 100 }} && 'font-bold text-accent'"><span x-text="n">{{ mb_strlen($parcours) }}</span> / {{ number_format($parcoursMax, 0, ',', ' ') }}</span>
                            </span>
                        </div>
                        <div class="flex flex-col gap-1.5" x-data="{ n: {{ mb_strlen($inspiration) }} }">
                            <label for="ci-inspiration" class="text-sm font-semibold text-brand">Qu'est-ce qui rend votre parcours inspirant ?</label>
                            <textarea wire:model="inspiration" id="ci-inspiration" rows="4" maxlength="{{ $inspirationMax }}" @input="n = $event.target.value.length" placeholder="Un défi surmonté, un impact autour de vous, une réussite dont vous êtes fier." class="{{ $champ('inspiration') }}"></textarea>
                            <span class="flex justify-between gap-3 text-xs">
                                <span class="font-medium text-accent">@error('inspiration') {{ $message }} @enderror</span>
                                <span class="shrink-0 text-[#5B677A]"><span x-text="n">{{ mb_strlen($inspiration) }}</span> / {{ number_format($inspirationMax, 0, ',', ' ') }}</span>
                            </span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="ci-lien" class="text-sm font-semibold text-brand">Lien vers votre profil LinkedIn ou une page <span class="font-normal text-[#5B677A]">(facultatif)</span></label>
                            <input wire:model="lien" id="ci-lien" type="url" inputmode="url" placeholder="https://" class="{{ $champ('lien') }}">
                            @error('lien') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <div x-data="{
                            envoi: false, erreur: '',
                            async reduire(f) {
                                if (f.size < 350 * 1024) return f;
                                try {
                                    const img = await createImageBitmap(f), max = 2000, r = Math.min(1, max / Math.max(img.width, img.height));
                                    const c = document.createElement('canvas'); c.width = Math.round(img.width * r); c.height = Math.round(img.height * r);
                                    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                                    const b = await new Promise(ok => c.toBlob(ok, 'image/jpeg', 0.85));
                                    return new File([b], (f.name.replace(/\.[^.]+$/, '') || 'photo') + '.jpg', { type: 'image/jpeg' });
                                } catch (err) { return f }
                            },
                            async choisir(e) {
                                const f = e.target.files[0]; e.target.value = '';
                                if (! f) return;
                                this.envoi = true; this.erreur = '';
                                try {
                                    const fichier = await this.reduire(f);
                                    await new Promise((ok, echec) => $wire.upload('photo', fichier, ok, echec));
                                } catch (err) { this.erreur = 'L\'envoi de la photo a échoué, réessayez.' }
                                this.envoi = false;
                            },
                        }">
                            <p class="text-sm font-semibold text-brand">Une photo de vous <span class="font-normal text-[#5B677A]">(facultatif)</span></p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">Un portrait net, de face si possible. JPG, PNG ou WebP.</p>
                            @if ($photo && ! $errors->has('photo'))
                                <div class="mt-3 flex items-center gap-4 rounded-2xl bg-cloud p-3" data-test="photo-apercu">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Votre photo" class="size-20 shrink-0 rounded-xl object-cover">
                                    <button type="button" wire:click="retirerPhoto" class="rounded-[10px] border border-[#C9D3E6] bg-white px-4 py-2 text-[13px] font-bold text-brand hover:bg-cloud">Retirer la photo</button>
                                </div>
                            @else
                                <label class="mt-3 flex cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-brand/20 p-5 text-sm font-bold text-brand transition hover:border-brand hover:bg-cloud focus-within:ring-2 focus-within:ring-accent/25" :class="envoi && 'pointer-events-none opacity-60'">
                                    <input type="file" accept="image/*" class="sr-only" @change="choisir($event)" data-test="photo-champ">
                                    <span x-text="envoi ? 'Envoi de la photo…' : 'Ajouter une photo'">Ajouter une photo</span>
                                </label>
                            @endif
                            <p x-show="erreur" x-text="erreur" x-cloak class="mt-2 text-xs font-medium text-accent"></p>
                            @error('photo') <p class="mt-2 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-cloud p-4">
                                <input type="checkbox" wire:model="consentement" data-test="consentement" class="mt-0.5 size-5 shrink-0 rounded border-brand/30 text-accent focus:ring-accent/25">
                                <span class="text-sm font-semibold leading-snug text-brand">{{ $consentementTexte }}</span>
                            </label>
                            @error('consentement') <p class="mt-2 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                            <p class="mt-3 text-xs leading-relaxed text-[#5B677A]">Vos réponses sont réservées au département Communication du REJCC. Répondre ne garantit pas la publication d'un portrait.</p>
                        </div>

                        @error('envoi') <p class="rounded-xl bg-accent/10 px-4 py-3 text-sm font-semibold text-accent">{{ $message }}</p> @enderror
                        @if ($errors->any() && ! $errors->has('envoi'))
                            <p class="text-sm font-semibold text-accent" data-test="erreurs-formulaire">Vérifiez les champs signalés en rouge.</p>
                        @endif

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <button type="button" wire:click="precedent" class="rounded-[10px] border border-[#C9D3E6] bg-white px-5 py-3 text-[13.5px] font-bold text-brand hover:bg-cloud">Retour</button>
                            <button type="submit" data-test="cathoimpact-envoyer" wire:loading.attr="disabled" wire:target="envoyer,photo" class="rounded-[10px] bg-accent px-6 py-3 text-[13.5px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">
                                <span wire:loading.remove wire:target="envoyer">Envoyer mon parcours</span>
                                <span wire:loading wire:target="envoyer">Envoi…</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif
</div>
