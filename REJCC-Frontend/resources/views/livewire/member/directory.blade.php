<div>
    <x-member-light.topbar title="Annuaire des membres" />

    @if ($locked ?? false)
        <x-member-light.paywall description="L'annuaire des membres est réservé aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Annuaire des membres</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('tous')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                @foreach ($profiles as $p)
                    <button wire:click="setFiltre('{{ $p['id'] }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $p['id'] ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $p['label'] }}</button>
                @endforeach
                <button wire:click="setFiltre('mentors')" data-test="filtre-mentors" class="btn-tap inline-flex items-center gap-1 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'mentors' ? 'border-accent bg-accent text-white' : 'border-accent/25 bg-white text-accent' }}"><x-ui.icon name="nav-mentor" class="size-3.5" /> Mentors</button>
            </div>
        </div>

        <div class="relative mb-6 max-w-[420px]">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Rechercher (nom, domaine, ville)…"
                class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
            />
        </div>

        <p data-test="nb-resultats" class="mb-3 text-[12.5px] font-semibold text-[#5B677A]">
            @php $total = $meta['total'] ?? $members->count(); @endphp
            {{ $total }} {{ $filtre === 'mentors' ? 'mentor'.($total > 1 ? 's' : '') : 'membre'.($total > 1 ? 's' : '') }}{{ trim($query) !== '' ? ' pour « '.trim($query).' »' : '' }}
        </p>

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
