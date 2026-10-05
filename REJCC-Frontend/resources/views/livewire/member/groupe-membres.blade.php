<div>
    <x-member-light.topbar :title="$groupe['name'] ?? 'Membres du groupe'" />

    @if ($locked ?? false)
        <x-member-light.paywall description="La liste des membres d'un groupe sectoriel (fiche complète, spécialité, contact) est réservée aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.groupes') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Tous les groupes
            </a>
        </div>
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $groupe['name'] ?? 'Groupe' }}</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            @if ($groupe['description'] ?? null)
                <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $groupe['description'] }}</p>
            @endif
        </div>

        <div class="relative mb-6 max-w-[420px]">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Rechercher (nom, ville, spécialité)…"
                class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
            />
        </div>

        <p data-test="nb-membres-groupe" class="mb-3 text-[12.5px] font-semibold text-[#5B677A]">{{ $meta['total'] ?? $members->count() }} membre{{ ($meta['total'] ?? $members->count()) > 1 ? 's' : '' }}{{ trim($query) !== '' ? ' pour « '.trim($query).' »' : '' }}</p>

        @if ($members->isEmpty())
            <p class="py-10 text-center text-sm text-[#5B677A]">{{ trim($query) !== '' ? 'Aucun membre ne correspond à votre recherche.' : 'Aucun membre dans ce groupe pour le moment.' }}</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))" wire:key="roster-page-{{ $meta['current_page'] ?? 1 }}">
                @foreach ($members as $m)
                    @php
                        $estMentor = ($m['role'] ?? 'member') === 'mentor';
                        $degrade = $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100';
                    @endphp
                    <article
                        data-test="carte-groupe"
                        wire:key="gm-{{ $m['id'] }}"
                        wire:click="voirProfil({{ $m['id'] }})"
                        class="card-hover flex cursor-pointer flex-col rounded-[16px] border bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $estMentor ? 'border-accent/30' : 'border-brand/10' }}"
                    >
                        <div class="flex items-start gap-3">
                            <span x-data="{ erreur: false }" class="relative shrink-0">
                                @if ($m['photo'] ?? null)
                                    <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $m['photo'] }}" alt="" class="size-12 rounded-xl object-cover">
                                @endif
                                <span @if ($m['photo'] ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $degrade }})" @else style="background: linear-gradient(135deg, {{ $degrade }})" @endif class="flex size-12 items-center justify-center rounded-xl text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($m['prenom'], 0, 1).mb_substr($m['nom'], 0, 1)) }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-brand">{{ $m['prenom'] }} {{ $m['nom'] }}</p>
                                <div class="mt-0.5 flex flex-wrap gap-1">
                                    @if ($estMentor)<span class="rounded-full bg-accent px-2 py-px text-[9.5px] font-bold uppercase tracking-[0.06em] text-white">Mentor</span>@endif
                                    @if ($m['nouveau'] ?? false)<span class="rounded-full bg-[#22A85A]/12 px-2 py-px text-[9.5px] font-bold uppercase tracking-[0.06em] text-[#1C8F4C]">Nouveau</span>@endif
                                    @if ($m['nb_avis'] ?? 0)
                                        <span data-test="carte-note" title="{{ $m['nb_avis'] }} avis" class="inline-flex items-center gap-0.5 rounded-full bg-[#F5A623]/12 px-2 py-px text-[10.5px] font-bold text-[#B7790F]">★ {{ number_format($m['note_moyenne'], 1, ',', ' ') }} <span class="font-semibold text-[#B7790F]/70">({{ $m['nb_avis'] }})</span></span>
                                    @endif
                                </div>
                                @if ($m['titre'] ?? null)
                                    <p class="mt-1 line-clamp-1 text-xs text-[#5B677A]">{{ $m['titre'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($m['specialite'])
                            <div class="mt-3 rounded-[10px] bg-cloud/60 px-3 py-2">
                                <p class="line-clamp-3 text-[12px] italic leading-relaxed text-ink">« {{ $m['specialite'] }} »</p>
                            </div>
                        @endif

                        @if (! empty($m['services']))
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($m['services'] as $service)
                                    <span class="max-w-full truncate rounded-full bg-brand/[.05] px-2 py-0.5 text-[11px] font-semibold text-brand">{{ $service }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 flex flex-1 flex-col gap-1 text-xs text-[#9AA6B8]">
                            @if ($m['zone'] ?? null)
                                <p class="flex items-start gap-1.5"><x-ui.icon name="map-pin" class="mt-px size-3 shrink-0" /> <span class="line-clamp-1">Intervient : {{ $m['zone'] }}</span></p>
                            @elseif ($m['ville'] || $m['organisation'])
                                <p class="flex items-start gap-1.5"><x-ui.icon name="map-pin" class="mt-px size-3 shrink-0" /> <span class="truncate">{{ collect([$m['ville'], $m['organisation']])->filter()->join(' · ') }}</span></p>
                            @endif
                            @if ($m['disponibilites'] ?? null)
                                <p class="flex items-start gap-1.5"><x-ui.icon name="clock" class="mt-px size-3 shrink-0" /> <span class="line-clamp-1">{{ $m['disponibilites'] }}</span></p>
                            @endif
                        </div>

                        @if ($m['id'] === (\App\Support\Api::user()->id ?? 0))
                            <span class="mt-3.5 flex items-center justify-center rounded-[9px] bg-cloud py-2 text-[12px] font-semibold text-[#9AA6B8]">C'est votre fiche</span>
                        @else
                            <a href="{{ route('espace-membre.messaging', ['to' => $m['id']]) }}" wire:navigate onclick="event.stopPropagation()"
                                class="btn-tap mt-3.5 flex items-center justify-center gap-1.5 rounded-[9px] border border-azure/25 bg-azure/10 py-2 text-[12.5px] font-semibold text-azure hover:bg-azure/20">
                                <x-ui.icon name="message-circle" class="size-[13px]" /> Envoyer un message
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>

            <x-ui.pager :meta="$meta" />
        @endif
    </div>

    <x-groupes.fiche-pro :fiche="$detail" :erreur="$avisErreur" />
    @endif
</div>
