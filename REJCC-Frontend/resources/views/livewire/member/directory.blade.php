<div>
    <x-member-light.topbar title="Annuaire des membres" />

    @if ($locked ?? false)
        {{-- Aperçu pour les non-abonnés : chiffres réels, cartes décoratives floutées --}}
        <div class="mx-auto max-w-[1280px] px-8 py-8" data-test="apercu-annuaire">
            <div class="mb-5">
                <h1 class="mb-1 text-[17px] font-bold text-brand">Annuaire des membres</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>

            @if ($apercu)
                <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                    @foreach ([['Membres', $apercu['membres'], 'users'], ['Mentors', $apercu['mentors'], 'hand-heart'], ['Secteurs', count($apercu['secteurs'] ?? []), 'network'], ['Villes', $apercu['villes'], 'map-pin']] as [$label, $val, $icon])
                        <div class="flex items-center gap-3 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-brand/[.06] text-brand"><x-ui.icon :name="$icon" class="size-5" /></span>
                            <div><p class="text-[20px] font-extrabold leading-none text-brand">{{ $val }}</p><p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $label }}</p></div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="relative overflow-hidden rounded-[20px]">
                <div aria-hidden="true" class="pointer-events-none grid select-none gap-4 blur-[6px]" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))">
                    @foreach (array_slice(array_values(array_unique(array_merge(array_keys($apercu['secteurs'] ?? []), ['Commerce', 'Agriculture', 'Services', 'Éducation', 'Santé', 'Numérique']))), 0, 8) as $i => $secteurDeco)
                        <div class="rounded-[16px] border border-brand/10 bg-white p-[18px]">
                            <div class="flex items-center gap-3">
                                <span class="size-12 rounded-xl" style="background: linear-gradient(135deg, {{ $i % 3 === 0 ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})"></span>
                                <div class="flex-1 space-y-1.5">
                                    <span class="block h-3 w-3/4 rounded bg-brand/20"></span>
                                    <span class="block text-xs text-[#5B677A]">{{ $secteurDeco }}</span>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-1.5"><span class="h-5 w-16 rounded-full bg-brand/10"></span><span class="h-5 w-20 rounded-full bg-brand/10"></span></div>
                            <span class="mt-4 block h-8 rounded-[9px] bg-azure/10"></span>
                        </div>
                    @endforeach
                </div>
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-b from-white/30 via-white/70 to-white/90 p-6">
                    <div class="max-w-[440px] rounded-[20px] border border-brand/10 bg-white p-7 text-center shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-[#F5A623]/10 text-[#B27007]"><x-ui.icon name="lock" class="size-6" /></span>
                        <h2 class="mt-4 text-[16px] font-extrabold text-brand">
                            {{ ($apercu['membres'] ?? 0) > 1 ? $apercu['membres'].' membres vous attendent' : 'Rejoignez le réseau' }}
                        </h2>
                        <p class="mt-2 text-[13px] leading-relaxed text-[#5B677A]">
                            Trouvez des partenaires, des clients et des mentors parmi les entrepreneurs du REJCC
                            @if (! empty($apercu['secteurs']))
                                ({{ implode(', ', array_slice(array_keys($apercu['secteurs']), 0, 3)) }}…)
                            @endif
                            et écrivez-leur directement. L'annuaire est réservé aux membres à jour de leur abonnement annuel (10&nbsp;000&nbsp;F).
                        </p>
                        <a href="{{ route('espace-membre.abonnement') }}" wire:navigate data-test="debloquer-annuaire" class="btn-tap mt-5 inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">
                            <x-ui.icon name="shield-check" class="size-4" /> Débloquer l'annuaire
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Annuaire des membres</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        {{-- Votre fiche telle que les autres membres la voient --}}
        @php $initialesMoi = mb_strtoupper(mb_substr($moi->prenom ?? '', 0, 1).mb_substr($moi->nom ?? '', 0, 1)); @endphp
        <section data-test="ma-fiche-annuaire" x-data="{ ouvert: $persist(true).as('rejcc-annuaire-ma-fiche') }" class="mb-5 rounded-[16px] border {{ $visibleAnnuaire ? 'border-azure/20 bg-azure/[.04]' : 'border-[#F5A623]/40 bg-[#FFF8EC]' }} p-4">
            <div class="flex flex-wrap items-center gap-3">
                <span x-data="{ erreur: false }" class="relative shrink-0">
                    @if ($moi->photo ?? null)
                        <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $moi->photo }}" alt="" class="size-10 rounded-xl object-cover">
                    @endif
                    <span @if ($moi->photo ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, #4F6FBF, #AC0100)" @else style="background: linear-gradient(135deg, #4F6FBF, #AC0100)" @endif class="flex size-10 items-center justify-center rounded-xl text-xs font-bold text-white">{{ $initialesMoi }}</span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold text-brand">Votre fiche dans l'annuaire</p>
                    <p data-test="statut-visibilite" class="text-[12px] text-[#5B677A]">
                        @if ($visibleAnnuaire)
                            Visible par les membres · coordonnées {{ $coordonneesVisibles ? 'affichées' : 'masquées' }} · profil complété à <strong class="text-brand">{{ $completion }} %</strong>
                        @else
                            <strong class="text-[#8A5A00]">Vous n'apparaissez pas dans l'annuaire.</strong> Les autres membres ne peuvent pas vous trouver.
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    @if ($completion < 100)
                        <a href="{{ route('espace-membre.profile') }}" wire:navigate class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90">Compléter mon profil</a>
                    @endif
                    <a href="{{ route('espace-membre.profile') }}#preferences" wire:navigate class="btn-tap rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Gérer ma visibilité</a>
                    <button type="button" @click="ouvert = ! ouvert" :aria-expanded="ouvert" :aria-label="ouvert ? 'Masquer l\'aperçu' : 'Voir l\'aperçu'" class="flex size-8 items-center justify-center rounded-full text-[#5B677A] hover:bg-white">
                        <x-ui.icon name="chevron-down" class="size-4 transition-transform" x-bind:class="ouvert ? 'rotate-180' : ''" />
                    </button>
                </div>
            </div>
            <div x-show="ouvert" x-collapse class="mt-3">
                @if ($visibleAnnuaire && $manquants)
                    <p class="text-[12px] text-[#5B677A]">Pour être mieux trouvé·e, ajoutez : <span class="font-semibold text-brand">{{ implode(', ', $manquants) }}</span>. Votre métier et vos compétences apparaissent aussi sur votre carte.</p>
                @elseif ($visibleAnnuaire)
                    <p class="text-[12px] text-[#5B677A]">Votre profil est complet : merci, il aide les membres à vous trouver.</p>
                @else
                    <p class="text-[12px] text-[#8A5A00]">Réactivez « Apparaître dans l'annuaire » dans vos préférences pour que les membres puissent vous trouver et vous écrire.</p>
                @endif
            </div>
        </section>

        {{-- Profils : ligne défilante sur mobile --}}
        <div class="-mx-8 mb-4 flex gap-2 overflow-x-auto px-8 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0" data-test="filtres-profil">
            <button wire:click="setFiltre('tous')" class="btn-tap shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
            @foreach ($profiles as $p)
                <button wire:click="setFiltre('{{ $p['id'] }}')" class="btn-tap shrink-0 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $p['id'] ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $p['label'] }}</button>
            @endforeach
            <button wire:click="setFiltre('mentors')" data-test="filtre-mentors" class="btn-tap inline-flex shrink-0 items-center gap-1 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'mentors' ? 'border-accent bg-accent text-white' : 'border-accent/25 bg-white text-accent' }}"><x-ui.icon name="hand-heart" class="size-3.5" /> Mentors</button>
        </div>

        @php $nbFiltres = collect([$secteur, $ville, $groupe])->filter()->count() + ($tri === 'recents' ? 1 : 0); @endphp
        <div x-data="{ filtres: false }" class="mb-5 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,1fr))]">
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
            <div class="relative min-w-0 flex-1">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
                <input
                    wire:model.live.debounce.300ms="query"
                    type="text"
                    data-test="recherche-annuaire"
                    placeholder="Rechercher (nom, compétence, métier, ville)…"
                    aria-label="Rechercher dans l'annuaire"
                    class="h-10 w-full rounded-xl border border-brand/10 bg-white pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
                />
            </div>
            <button type="button" @click="filtres = ! filtres" :aria-expanded="filtres" data-test="bouton-filtres" class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-xl border px-3 text-[13px] font-semibold sm:hidden {{ $nbFiltres ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-brand' }}">
                Filtres{{ $nbFiltres ? ' ('.$nbFiltres.')' : '' }}
                <x-ui.icon name="chevron-down" class="size-3.5 transition-transform" x-bind:class="filtres ? 'rotate-180' : ''" />
            </button>
            </div>
            @foreach ([
                ['secteur', 'Tous les secteurs', collect($filtres['secteurs'])->map(fn ($v) => [$v, $v])],
                ['ville', 'Toutes les villes', collect($filtres['villes'])->map(fn ($v) => [$v, $v])],
                ['groupe', 'Tous les groupes', collect($filtres['groupes'])->map(fn ($g) => [(string) $g['id'], $g['nom']])],
            ] as [$champ, $tous, $options])
                <select x-show="filtres || window.innerWidth >= 640" x-cloak wire:model.live="{{ $champ }}" data-test="filtre-{{ $champ }}" aria-label="{{ $tous }}" class="h-10 w-full truncate rounded-xl border bg-white pl-3 pr-9 text-[13px] outline-none focus:border-azure {{ $this->{$champ} !== '' ? 'border-brand font-semibold text-brand' : 'border-brand/10 text-[#5B677A]' }}">
                    <option value="">{{ $tous }}</option>
                    @foreach ($options as [$valeur, $libelle])
                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            @endforeach
            <select x-show="filtres || window.innerWidth >= 640" x-cloak wire:model.live="tri" data-test="tri" aria-label="Trier" class="h-10 w-full rounded-xl border border-brand/10 bg-white pl-3 pr-9 text-[13px] text-[#5B677A] outline-none focus:border-azure">
                <option value="nom">Ordre alphabétique</option>
                <option value="recents">Plus récents</option>
            </select>
        </div>

        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p data-test="nb-resultats" class="text-[12.5px] font-semibold text-[#5B677A]">
            @php $total = $meta['total'] ?? $members->count(); @endphp
            {{ $total }} {{ $filtre === 'mentors' ? 'mentor'.($total > 1 ? 's' : '') : 'membre'.($total > 1 ? 's' : '') }}{{ trim($query) !== '' ? ' pour « '.trim($query).' »' : '' }}
        </p>
        @if ($filtresActifs)
            <button wire:click="reinitialiser" data-test="reinitialiser" class="inline-flex items-center gap-1 text-[12px] font-semibold text-azure hover:underline"><x-ui.icon name="x" class="size-3.5" /> Réinitialiser les filtres</button>
        @endif
        </div>

        @if ($members->isEmpty())
            <p class="py-10 text-center text-sm text-[#5B677A]">{{ $filtre === 'mentors' ? 'Aucun mentor pour le moment.' : 'Aucun membre trouvé.' }}</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))" wire:key="dir-page-{{ $meta['current_page'] ?? 1 }}">
                @foreach ($members as $m)
                    @php
                        $estMentor = ($m->role ?? 'member') === 'mentor';
                        $initiales = mb_strtoupper(mb_substr($m->prenom, 0, 1).mb_substr($m->nom, 0, 1));
                        $sousTitre = $m->titre ?: ($m->secteur ?: null);
                        $tags = $estMentor ? array_slice($m->mentor_expertises ?? [], 0, 3) : ($m->competences ?? []);
                    @endphp
                    <article data-test="carte-annuaire" wire:key="membre-{{ $m->id }}" wire:click="voirProfil({{ $m->id }})" class="card-hover flex cursor-pointer flex-col rounded-[16px] border bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $estMentor ? 'border-accent/30' : 'border-brand/10' }}">
                        <div class="flex items-start gap-3">
                            <span x-data="{ erreur: false }" class="relative shrink-0">
                                @if ($m->photo ?? null)
                                    <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $m->photo }}" alt="" class="size-12 rounded-xl object-cover">
                                @endif
                                <span @if ($m->photo ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})" @else style="background: linear-gradient(135deg, {{ $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})" @endif class="flex size-12 items-center justify-center rounded-xl text-sm font-bold text-white">{{ $initiales }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-brand">{{ $m->prenom }} {{ $m->nom }}</p>
                                <div class="mt-0.5 flex flex-wrap gap-1">
                                    @if ($estMentor)
                                        <span class="rounded-full bg-accent px-2 py-px text-[9.5px] font-bold uppercase tracking-[0.06em] text-white">Mentor</span>
                                    @endif
                                    @if ($m->nouveau ?? false)
                                        <span data-test="badge-nouveau" class="rounded-full bg-[#22A85A]/12 px-2 py-px text-[9.5px] font-bold uppercase tracking-[0.06em] text-[#1C8F4C]">Nouveau</span>
                                    @endif
                                </div>
                                @if ($sousTitre)
                                    <p class="mt-1 line-clamp-2 text-xs leading-snug text-[#5B677A]">{{ $sousTitre }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($tags)
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($tags as $tag)
                                    <span class="max-w-full truncate rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estMentor ? 'bg-accent/[.06] text-accent' : 'bg-brand/[.05] text-brand' }}">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-3 flex flex-1 items-start gap-1.5 text-xs text-[#9AA6B8]">
                            @if ($m->ville || $m->organisation)
                                <x-ui.icon name="map-pin" class="mt-px size-3 shrink-0" />
                                <span class="truncate">{{ collect([$m->ville, $m->organisation])->filter()->join(' · ') }}</span>
                            @else
                                <span class="italic">Profil à compléter</span>
                            @endif
                        </p>

                        <a
                            href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}"
                            wire:navigate
                            onclick="event.stopPropagation()"
                            class="btn-tap mt-3.5 flex items-center justify-center gap-1.5 rounded-[9px] border border-azure/25 bg-azure/10 py-2 text-[12.5px] font-semibold text-azure hover:bg-azure/20"
                        >
                            <x-ui.icon name="message-circle" class="size-[13px]" /> Envoyer un message
                        </a>
                    </article>
                @endforeach
            </div>

            <x-ui.pager :meta="$meta" />
        @endif
    </div>

    <x-member-light.profile-modal :member="$detail" />
    @endif
</div>
