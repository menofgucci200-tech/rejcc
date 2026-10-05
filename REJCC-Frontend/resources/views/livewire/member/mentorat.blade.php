<div>
    <x-member-light.topbar title="Mentorat" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $estMentor ? 'Mon espace mentor' : 'Mentorat' }}</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">
                @if ($estMentor)
                    Votre fiche de mentor est visible par les membres dans l'annuaire, sur votre page biographique et dans la recherche de mentors. Tenez-la à jour pour recevoir des demandes adaptées.
                @else
                    Un accompagnement personnalisé par des entrepreneurs et experts confirmés du réseau.
                @endif
            </p>
        </div>

        @if ($estMentor)
            <div class="grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">
                <section data-test="fiche-mentor-form" class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <h2 class="text-[15px] font-bold text-brand">Ma fiche de mentor</h2>
                    <div class="mt-4 flex flex-col gap-4">
                        <div>
                            <label for="mentor-expertises" class="mb-1 block text-xs font-semibold text-[#5B677A]">Domaines d'expertise <span class="font-normal text-[#9AA6B8]">(séparés par des virgules, 8 au plus)</span></label>
                            <input id="mentor-expertises" wire:model="expertises" type="text" placeholder="Ex : Finance, Levée de fonds, Agro-business" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                        <div>
                            <label for="mentor-bio" class="mb-1 block text-xs font-semibold text-[#5B677A]">Présentation de mentor</label>
                            <textarea id="mentor-bio" wire:model="bio" rows="4" placeholder="Votre expérience, ce que vous pouvez apporter à un jeune entrepreneur…" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="mentor-dispo" class="mb-1 block text-xs font-semibold text-[#5B677A]">Disponibilités</label>
                                <input id="mentor-dispo" wire:model="disponibilites" type="text" placeholder="Ex : mardi et jeudi soir" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                            </div>
                            <div>
                                <label for="mentor-format" class="mb-1 block text-xs font-semibold text-[#5B677A]">Format des séances</label>
                                <select id="mentor-format" wire:model="format" class="w-full rounded-[10px] border border-brand/15 py-2.5 pl-3 pr-9 text-sm outline-none focus:border-azure">
                                    <option value="">Non précisé</option>
                                    <option value="visio">En visio</option>
                                    <option value="presentiel">En présentiel</option>
                                    <option value="les_deux">Visio ou présentiel</option>
                                </select>
                            </div>
                            <div>
                                <label for="mentor-capacite" class="mb-1 block text-xs font-semibold text-[#5B677A]">Mentorés suivis en même temps (au plus)</label>
                                <input id="mentor-capacite" wire:model="capacite" type="number" min="1" max="20" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                            </div>
                            <label class="flex items-center gap-2 self-end pb-2.5 text-[13px] font-semibold text-ink">
                                <input wire:model="accepte" type="checkbox" class="size-4 rounded border-brand/20 text-accent" /> J'accepte de nouveaux mentorés
                            </label>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button wire:click="enregistrerProfil" wire:loading.attr="disabled" data-test="enregistrer-fiche" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Enregistrer ma fiche</button>
                            @if ($messageProfil)
                                <span data-test="message-fiche" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> {{ $messageProfil }}</span>
                            @endif
                            @if ($erreurProfil)
                                <span data-test="erreur-fiche" role="alert" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="size-4" /> {{ $erreurProfil }}</span>
                            @endif
                        </div>
                    </div>
                </section>

                <aside class="flex flex-col gap-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Aperçu vu par les membres</p>
                    <x-mentorat.profil :mentor="$user->mentor ?? null" class="bg-white" />
                    <a href="{{ route('espace-membre.carte') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-accent hover:underline">
                        <x-ui.icon name="qr-code" class="size-3.5" /> Voir ma carte de mentor
                    </a>
                </aside>
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-[18px] border border-brand/10 bg-white px-8 py-16 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-accent/10 text-accent">
                    <x-ui.icon name="nav-mentor" class="size-7" />
                </span>
                <h2 class="mb-2 text-[17px] font-bold text-brand">Rencontrez les mentors du réseau</h2>
                <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">Découvrez les mentors, leurs domaines d'expertise et leurs disponibilités dans l'annuaire.</p>
                <a href="{{ route('espace-membre.directory', ['filtre' => 'mentors']) }}" wire:navigate class="btn-tap mt-5 rounded-full bg-accent px-4 py-2 text-xs font-semibold text-white hover:bg-accent-600">Voir les mentors</a>
            </div>
        @endif
    </div>
</div>
