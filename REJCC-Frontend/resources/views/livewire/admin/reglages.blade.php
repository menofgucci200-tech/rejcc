<div>
    <x-admin-light.topbar title="Réglages du site" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Réglages du site vitrine</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 text-xs text-[#9AA6B8]">Identité, coordonnées, réseaux sociaux et bandeau d'annonce — publiés immédiatement sur le site public.</p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-2">
            {{-- Identité --}}
            <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">Identité du réseau</p>
                    @if ($savedCard === 'identite')
                        <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Publié</span>
                    @endif
                </div>
                <div class="flex flex-col gap-3">
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Slogan
                        <input wire:model="slogan" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                        @error('slogan') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">À propos (présentation courte)
                        <textarea wire:model="about" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                        @error('about') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Positionnement (citation de la page d'accueil)
                        <textarea wire:model="positioning" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                        @error('positioning') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Notre mission
                        <textarea wire:model="mission" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                        @error('mission') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Notre vision
                        <textarea wire:model="vision" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                        @error('vision') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <button wire:click="saveIdentite" wire:loading.attr="disabled" class="btn-tap w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </div>
            </section>

            <div class="flex flex-col gap-6">
                {{-- Coordonnées --}}
                <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-bold text-brand">Coordonnées</p>
                        @if ($savedCard === 'coordonnees')
                            <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Publié</span>
                        @endif
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Adresse e-mail
                            <input wire:model="email" type="email" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            @error('email') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Téléphone
                            <input wire:model="phone" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            @error('phone') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Adresse
                            <input wire:model="address" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            @error('address') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Ville
                            <input wire:model="city" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            @error('city') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <button wire:click="saveCoordonnees" wire:loading.attr="disabled" class="btn-tap mt-4 w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </section>

                {{-- Réseaux sociaux --}}
                <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-1 flex items-center justify-between">
                        <p class="text-sm font-bold text-brand">Réseaux sociaux</p>
                        @if ($savedCard === 'reseaux')
                            <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Publié</span>
                        @endif
                    </div>
                    <p class="mb-4 text-[11.5px] text-[#9AA6B8]">Collez l'adresse complète de chaque page. Seuls les réseaux renseignés apparaissent dans le pied de page du site.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp (lien wa.me)',
                        ] as $field => $label)
                            <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">{{ $label }}
                                <input wire:model="{{ $field }}" type="url" placeholder="https://…" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                                @error($field) <span class="font-medium text-accent">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                    </div>
                    <button wire:click="saveReseaux" wire:loading.attr="disabled" class="btn-tap mt-4 w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </section>

                {{-- Bandeau d'annonce --}}
                <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-1 flex items-center justify-between">
                        <p class="text-sm font-bold text-brand">Bandeau d'annonce</p>
                        @if ($savedCard === 'annonce')
                            <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Publié</span>
                        @endif
                    </div>
                    <p class="mb-4 text-[11.5px] text-[#9AA6B8]">Un message affiché tout en haut du site public — idéal pour annoncer un événement ou une échéance.</p>
                    <label class="mb-3 inline-flex cursor-pointer items-center gap-2.5 text-[13px] font-semibold text-ink">
                        <button
                            type="button"
                            wire:click="$toggle('bannerEnabled')"
                            class="relative h-6 w-[42px] shrink-0 rounded-full transition-colors duration-200 active:scale-95"
                            style="background: {{ $bannerEnabled ? '#22A85A' : '#E6EAF0' }}"
                        >
                            <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200 ease-out" style="left: {{ $bannerEnabled ? '21px' : '3px' }}"></span>
                        </button>
                        {{ $bannerEnabled ? 'Bandeau affiché sur le site' : 'Bandeau désactivé' }}
                    </label>
                    <div class="flex flex-col gap-3">
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Message
                            <input wire:model="bannerText" type="text" placeholder="Ex : Grande rencontre du réseau le 20 juillet — inscrivez-vous !" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            @error('bannerText') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Lien (optionnel)
                                <input wire:model="bannerLink" type="text" placeholder="/evenements ou https://…" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Texte du lien
                                <input wire:model="bannerLabel" type="text" placeholder="En savoir plus" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                            </label>
                        </div>
                    </div>
                    <button wire:click="saveBannereAnnonce" wire:loading.attr="disabled" class="btn-tap mt-4 w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </section>
            </div>
        </div>
    </div>
</div>
