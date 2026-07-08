@php
    $circ = 2 * M_PI * 48;
    $dash = ($completion / 100) * $circ;
@endphp

<div class="px-8 pb-[60px] pt-[30px]">
    <!-- Stats strip -->
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($networkStats as $s)
            <div class="rounded-[20px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-[22px] py-5">
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex size-[34px] items-center justify-center rounded-[10px]" style="background: color-mix(in srgb, {{ $s['color'] }} 18%, transparent)">
                        <x-ui.icon :name="$s['icon']" class="size-4" style="color: {{ $s['color'] }}" />
                    </span>
                    <p class="m-0 text-[11.5px] leading-tight text-[var(--ms-muted)]">{{ $s['label'] }}</p>
                </div>
                <p class="m-0 font-display text-[28px] leading-none tracking-tight text-[var(--ms-text)]">
                    <x-ui.counter :value="$s['value']" :suffix="$s['suffix']" />
                </p>
            </div>
        @endforeach
    </div>

    <!-- Hero -->
    <div class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-[1.08fr_1fr]">
        <!-- Progress card -->
        <div class="rounded-[20px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-[30px] py-7">
            <div class="flex items-start gap-6">
                <div class="relative shrink-0">
                    <svg width="120" height="120" viewBox="0 0 120 120" style="transform: rotate(-90deg)">
                        <circle cx="60" cy="60" r="48" fill="none" stroke="var(--ms-surf2)" stroke-width="9" />
                        <circle cx="60" cy="60" r="48" fill="none" stroke="url(#ringGrad)" stroke-width="9" stroke-linecap="round"
                            stroke-dasharray="{{ $dash }} {{ $circ - $dash }}" />
                        <defs>
                            <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#4F6FBF" />
                                <stop offset="100%" stop-color="#E84A43" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="m-0 font-display text-2xl leading-none text-[var(--ms-text)]">{{ $completion }}%</p>
                        <p class="m-0 mt-1 text-[9.5px] tracking-wide text-[var(--ms-dim)]">PROFIL</p>
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="m-0 mb-1.5 font-serif text-xl italic text-[var(--ms-text)]">Votre réseau REJCC</h3>
                    <p class="m-0 mb-[18px] text-[13px] leading-relaxed text-[var(--ms-muted)]">
                        Complétez votre profil pour maximiser vos opportunités de networking.
                    </p>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach ([
                            ['label' => 'Profil complété', 'val' => $completion.'%', 'color' => '#9DB2EE'],
                            ['label' => 'Messages', 'val' => $unreadMessages > 0 ? $unreadMessages.' non lus' : 'À jour', 'color' => $unreadMessages > 0 ? '#F2A33C' : '#34D399'],
                            ['label' => 'Documents', 'val' => (string) $docs->count(), 'color' => '#E84A43'],
                            ['label' => 'Membres réseau', 'val' => $networkStats[0]['value'].'+', 'color' => '#34D399'],
                        ] as $stat)
                            <div class="rounded-xl border border-[var(--ms-bc)] px-3.5 py-2.5" style="background: var(--ms-surf2)">
                                <p class="m-0 font-display text-base font-bold" style="color: {{ $stat['color'] }}">{{ $stat['val'] }}</p>
                                <p class="m-0 mt-0.5 text-[11px] text-[var(--ms-dim)]">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Next event card -->
        <div class="flex flex-col rounded-[20px] px-7 py-7" style="background: linear-gradient(145deg, rgba(79,111,191,0.25), rgba(172,1,0,0.12)), var(--ms-surf); border: 1px solid var(--ms-bc);">
            @if ($upcomingEvents->isEmpty())
                <p class="text-sm text-[var(--ms-muted)]">Aucun événement à venir pour le moment.</p>
            @else
                @php $next = $upcomingEvents->first(); @endphp
                <span class="mb-4 inline-flex w-fit items-center gap-1.5 rounded-[20px] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.04em]" style="background: rgba(172,1,0,0.18); border: 1px solid rgba(172,1,0,0.3); color: #E84A43;">
                    <span class="size-1.5 animate-aurora rounded-full" style="background: #E84A43;"></span>
                    Prochain événement
                </span>
                <h3 class="m-0 mb-2.5 font-serif text-[22px] italic leading-tight text-[var(--ms-text)]">{{ $next->title }}</h3>
                <p class="m-0 mb-1.5 text-[13px] text-[var(--ms-muted)]">
                    📍 {{ $next->location }} · {{ $next->starts_at->locale('fr')->translatedFormat('d F Y') }}
                </p>
                <p class="m-0 flex-1 text-[13px] leading-relaxed text-[var(--ms-muted)]">{{ $next->excerpt ?? $next->description }}</p>
                <div class="mt-5 flex gap-2.5">
                    <a href="{{ url('/evenements/'.$next->slug) }}" class="flex-1 rounded-[10px] bg-white px-[18px] py-2.5 text-center text-[13px] font-bold tracking-tight text-[#031D59] no-underline">
                        En savoir plus
                    </a>
                    <span class="flex items-center justify-center rounded-[10px] px-3.5 py-2.5" style="background: var(--ms-surf2); border: 1px solid var(--ms-bc); color: var(--ms-muted);">
                        <x-ui.icon name="external-link" class="size-3.5" />
                    </span>
                </div>
            @endif
        </div>
    </div>

    <!-- Membres + Événements -->
    <div class="mb-10 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <section>
            <div class="mb-[18px] flex items-end justify-between">
                <div>
                    <h2 class="m-0 text-[17px] font-bold tracking-tight text-[var(--ms-text)]">Membres du réseau</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[var(--ms-muted)]">Connectez-vous avec vos pairs</p>
                </div>
                <a href="{{ route('espace-membre.directory') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12.5px] font-semibold no-underline" style="background: rgba(79,111,191,0.12); border: 1px solid rgba(79,111,191,0.22); color: #9DB2EE;">
                    Voir l'annuaire <x-ui.icon name="arrow-right" class="size-3" />
                </a>
            </div>
            <div class="rounded-[20px] border border-[var(--ms-bc)] bg-[var(--ms-surf)]">
                @if ($members->isEmpty())
                    <div class="px-6 py-8 text-center">
                        <x-ui.icon name="users" class="mx-auto mb-3 size-8 text-[var(--ms-dim)]" />
                        <p class="text-[13px] text-[var(--ms-muted)]">Aucun membre trouvé</p>
                    </div>
                @else
                    <ul class="m-0 list-none p-0">
                        @foreach ($members as $i => $m)
                            <li class="flex items-center gap-3 px-[18px] py-3.5 {{ $i < $members->count() - 1 ? 'border-b border-[var(--ms-bc)]' : '' }}">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                    {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="m-0 truncate text-[13.5px] font-semibold text-[var(--ms-text)]">{{ $m->prenom }} {{ $m->nom }}</p>
                                    @if ($m->secteur || $m->ville)
                                        <p class="m-0 mt-0.5 truncate text-xs text-[var(--ms-dim)]">{{ collect([$m->secteur, $m->ville])->filter()->join(' · ') }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}" wire:navigate title="Envoyer un message à {{ $m->prenom }}" class="flex size-[30px] shrink-0 items-center justify-center rounded-lg" style="background: var(--ms-surf2); border: 1px solid var(--ms-bc); color: var(--ms-muted);">
                                    <x-ui.icon name="message-circle" class="size-[13px]" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section>
            <div class="mb-[18px] flex items-end justify-between">
                <div>
                    <h2 class="m-0 text-[17px] font-bold tracking-tight text-[var(--ms-text)]">Événements à venir</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[var(--ms-muted)]">Restez connecté à l'agenda du réseau</p>
                </div>
                <a href="{{ url('/evenements') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12.5px] font-semibold no-underline" style="background: rgba(79,111,191,0.12); border: 1px solid rgba(79,111,191,0.22); color: #9DB2EE;">
                    Tous les événements <x-ui.icon name="arrow-right" class="size-3" />
                </a>
            </div>
            <div class="rounded-[20px] border border-[var(--ms-bc)] bg-[var(--ms-surf)]">
                @if ($upcomingEvents->isEmpty())
                    <div class="px-6 py-8 text-center">
                        <p class="text-[13px] text-[var(--ms-muted)]">Aucun événement à venir.</p>
                    </div>
                @else
                    <ul class="m-0 list-none py-2">
                        @foreach ($upcomingEvents as $ev)
                            <li class="flex items-start gap-3.5 px-[18px] py-3">
                                <div class="flex w-10 shrink-0 flex-col items-center">
                                    <div class="mb-1.5 size-2 rounded-full" style="background: #4F6FBF; box-shadow: 0 0 10px #4F6FBF80;"></div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="rounded-[20px] px-2 py-0.5 text-[10.5px] font-semibold" style="background: color-mix(in srgb, #4F6FBF 15%, transparent); color: #4F6FBF;">{{ $ev->category }}</span>
                                        <span class="text-[11px] text-[var(--ms-dim)]">{{ $ev->starts_at->locale('fr')->translatedFormat('d M') }}</span>
                                    </div>
                                    <p class="m-0 mb-0.5 text-[13.5px] font-semibold text-[var(--ms-text)]">{{ $ev->title }}</p>
                                    <p class="m-0 text-xs text-[var(--ms-dim)]">📍 {{ $ev->location }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    <!-- Documents récents -->
    @if ($docs->isNotEmpty())
        <section class="mb-10">
            <div class="mb-[18px] flex items-end justify-between">
                <div>
                    <h2 class="m-0 text-[17px] font-bold tracking-tight text-[var(--ms-text)]">Documents récents</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[var(--ms-muted)]">Dernières ressources partagées</p>
                </div>
                <a href="{{ route('espace-membre.documents') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12.5px] font-semibold no-underline" style="background: rgba(79,111,191,0.12); border: 1px solid rgba(79,111,191,0.22); color: #9DB2EE;">
                    Tous les documents <x-ui.icon name="arrow-right" class="size-3" />
                </a>
            </div>
            <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))">
                @foreach ($docs as $doc)
                    <div class="rounded-[20px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-5 py-[18px]">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-[10px]" style="background: rgba(232,74,67,0.14);">
                            <x-ui.icon name="folder-open" class="size-4 text-[#E84A43]" />
                        </div>
                        <p class="m-0 mb-1 text-[13.5px] font-semibold leading-tight text-[var(--ms-text)]">{{ $doc->title }}</p>
                        <p class="m-0 mb-3.5 text-[11.5px] text-[var(--ms-dim)]">{{ $doc->category }}</p>
                        <a href="{{ $doc->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold no-underline" style="background: rgba(79,111,191,0.12); border: 1px solid rgba(79,111,191,0.2); color: #9DB2EE;">
                            <x-ui.icon name="download" class="size-3" /> Télécharger
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Profil à compléter -->
    @if ($completion < 80)
        <div class="flex flex-wrap items-center justify-between gap-5 rounded-[20px] px-8 py-7" style="background: linear-gradient(135deg, rgba(79,111,191,0.18), rgba(172,1,0,0.1)), var(--ms-surf); border: 1px solid var(--ms-bc);">
            <div>
                <h3 class="m-0 mb-2 font-serif text-xl italic text-[var(--ms-text)]">Complétez votre profil</h3>
                <p class="m-0 text-[13.5px] text-[var(--ms-muted)]">
                    Votre profil est complété à {{ $completion }}%. Ajoutez vos informations pour améliorer votre visibilité dans le réseau.
                </p>
            </div>
            <a href="{{ route('espace-membre.profile') }}" wire:navigate class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-[22px] py-2.5 text-[13.5px] font-bold tracking-tight text-[#031D59] no-underline">
                Compléter mon profil <x-ui.icon name="arrow-right" class="size-3.5" />
            </a>
        </div>
    @endif
</div>
