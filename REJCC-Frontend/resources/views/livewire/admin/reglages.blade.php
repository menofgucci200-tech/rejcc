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

                {{-- Paiement — Abonnement --}}
                <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-1 flex items-center justify-between">
                        <p class="text-sm font-bold text-brand">Paiement — Abonnement (CinetPay)</p>
                        @if ($savedCard === 'paiement')
                            <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Enregistré</span>
                        @endif
                    </div>
                    <p class="mb-4 text-[11.5px] text-[#9AA6B8]">
                        Identifiants de votre compte marchand <span class="font-semibold">CinetPay</span> (Wave, Orange Money, MTN, Moov, carte bancaire), nécessaires pour encaisser l'abonnement annuel des membres (10 000 F). Créez un compte sur cinetpay.com puis collez ci-dessous la clé API et l'ID de site indiqués dans votre tableau de bord CinetPay.
                    </p>
                    <div class="flex flex-col gap-3" x-data="{ show: false }">
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Clé API (apikey)
                            <div class="relative">
                                <input
                                    wire:model="cinetpayApiKey"
                                    :type="show ? 'text' : 'password'"
                                    autocomplete="off"
                                    placeholder="Clé API CinetPay"
                                    class="w-full rounded-[9px] border border-brand/15 px-3 py-2 pr-10 text-sm font-normal outline-none focus:border-azure"
                                />
                                <button type="button" @click="show = !show" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9AA6B8] hover:text-brand" :aria-label="show ? 'Masquer' : 'Afficher'">
                                    <x-ui.icon name="eye" class="size-4" />
                                </button>
                            </div>
                            @error('cinetpayApiKey') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">ID de site (site_id)
                            <div class="relative">
                                <input
                                    wire:model="cinetpaySiteId"
                                    :type="show ? 'text' : 'password'"
                                    autocomplete="off"
                                    placeholder="Site ID CinetPay"
                                    class="w-full rounded-[9px] border border-brand/15 px-3 py-2 pr-10 text-sm font-normal outline-none focus:border-azure"
                                />
                            </div>
                            @error('cinetpaySiteId') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <button wire:click="savePaiement" wire:loading.attr="disabled" class="btn-tap mt-4 w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Enregistrer</button>
                </section>
            </div>
        </div>

            {{-- Parole & prière du jour (espace membre) --}}
            <section data-test="carte-paroles" class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] mt-6">
                <div class="mb-1 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">Parole &amp; prière du jour (espace membre)</p>
                    @if ($savedCard === 'paroles')
                        <span class="panel-enter inline-flex items-center gap-1 text-[11.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> Publié</span>
                    @endif
                </div>
                <p class="mb-4 text-[11.5px] text-[#9AA6B8]">Affichée sur le tableau de bord des membres. Un verset différent chaque jour, dans l'ordre de la liste, puis on recommence. Le verset marqué « Aujourd'hui » est celui que voient les membres en ce moment.</p>
                @error('paroles') <p class="mb-3 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                <div class="flex flex-col gap-3">
                    @foreach ($paroles as $i => $p)
                        <div wire:key="parole-{{ $i }}" class="rounded-[12px] border p-3.5 {{ $i === $paroleDuJour ? 'border-azure/50 bg-azure/[.04]' : 'border-brand/10' }}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold text-[#9AA6B8]">N° {{ $i + 1 }}
                                    @if ($i === $paroleDuJour)
                                        <span class="ml-1.5 rounded-full bg-azure px-2 py-0.5 text-[10px] text-white">Aujourd'hui</span>
                                    @endif
                                </span>
                                @if (count($paroles) > 1)
                                    <button type="button" wire:click="retirerParole({{ $i }})" class="text-[11.5px] font-semibold text-accent hover:underline">Retirer</button>
                                @endif
                            </div>
                            <div class="grid gap-2.5 md:grid-cols-[1fr_180px]">
                                <textarea wire:model="paroles.{{ $i }}.verset" rows="2" placeholder="Texte du verset" class="resize-y rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                                <input wire:model="paroles.{{ $i }}.reference" type="text" placeholder="Proverbes 16:3" class="h-fit rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                            </div>
                            <input wire:model="paroles.{{ $i }}.intention" type="text" placeholder="Intention de prière (optionnel) : Prions pour…" class="mt-2.5 w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                            @error("paroles.{$i}.verset") <p class="mt-1 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                            @error("paroles.{$i}.reference") <p class="mt-1 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-wrap gap-2.5">
                    <button type="button" wire:click="ajouterParole" class="btn-tap inline-flex items-center gap-1.5 rounded-[9px] border border-brand/15 px-4 py-2.5 text-sm font-semibold text-brand hover:bg-cloud"><x-ui.icon name="plus" class="size-4" /> Ajouter un verset</button>
                    <button wire:click="saveParoles" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </div>
            </section>
    </div>
</div>
