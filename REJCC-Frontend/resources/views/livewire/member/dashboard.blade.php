@php
    $user = \App\Support\Api::user();
@endphp

<div>
    <x-member-light.topbar title="Tableau de bord" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-2 text-[28px] font-extrabold tracking-tight text-brand">Bonjour {{ $user->prenom }} 👋</h1>
                <p class="max-w-lg font-serif text-[15px] italic text-[#5B677A]">« Tout ce que vous faites, faites-le de bon cœur, comme pour le Seigneur. » — Colossiens 3:23</p>
            </div>
        </section>

        <section class="mb-6 grid grid-cols-2 gap-3.5 sm:grid-cols-4">
            @foreach ($networkStats as $s)
                <div class="rounded-[18px] border border-brand/10 bg-white px-5 py-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="mb-2.5 flex size-[34px] items-center justify-center rounded-[10px]" style="background: color-mix(in srgb, {{ $s['color'] }} 16%, transparent)">
                        <x-ui.icon :name="$s['icon']" class="size-4" style="color: {{ $s['color'] }}" />
                    </span>
                    <p class="text-[26px] font-extrabold leading-none text-brand"><x-ui.counter :value="$s['value']" :suffix="$s['suffix']" /></p>
                    <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $s['label'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-[1.1fr_1fr]">
            <div class="rounded-[20px] border border-brand/10 bg-white px-7 py-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="flex items-start gap-6">
                    @php $circ = 2 * M_PI * 48; $dash = ($completion / 100) * $circ; @endphp
                    <div class="relative shrink-0">
                        <svg width="110" height="110" viewBox="0 0 120 120" style="transform: rotate(-90deg)">
                            <circle cx="60" cy="60" r="48" fill="none" stroke="#E6EAF0" stroke-width="9" />
                            <circle cx="60" cy="60" r="48" fill="none" stroke="url(#ringGrad)" stroke-width="9" stroke-linecap="round" stroke-dasharray="{{ $dash }} {{ $circ - $dash }}" />
                            <defs><linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#4F6FBF" /><stop offset="100%" stop-color="#AC0100" /></linearGradient></defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <p class="text-xl font-extrabold leading-none text-brand">{{ $completion }}%</p>
                            <p class="mt-1 text-[9px] tracking-wide text-[#9AA6B8]">PROFIL</p>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="mb-1.5 font-serif text-lg italic text-brand">Votre réseau REJCC</h3>
                        <p class="mb-4 text-[13px] leading-relaxed text-[#5B677A]">Complétez votre profil pour maximiser vos opportunités de networking.</p>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ([
                                ['label' => 'Profil complété', 'val' => $completion.'%', 'color' => '#4F6FBF'],
                                ['label' => 'Messages', 'val' => $unreadMessages > 0 ? $unreadMessages.' non lus' : 'À jour', 'color' => $unreadMessages > 0 ? '#F5A623' : '#22A85A'],
                                ['label' => 'Documents', 'val' => (string) $docs->count(), 'color' => '#AC0100'],
                                ['label' => 'Membres réseau', 'val' => $networkStats[0]['value'].'+', 'color' => '#22A85A'],
                            ] as $stat)
                                <div class="rounded-xl border border-brand/10 bg-cloud px-3.5 py-2.5">
                                    <p class="text-base font-bold" style="color: {{ $stat['color'] }}">{{ $stat['val'] }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#9AA6B8]">{{ $stat['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col rounded-[20px] px-7 py-6 text-white shadow-[0_12px_32px_rgba(3,29,89,.22)]" style="background: linear-gradient(120deg,#031D59 0%,#0B2E7A 60%,#1A3D8F 100%)">
                @if ($upcomingEvents->isEmpty())
                    <p class="text-sm text-[#C4D0EC]">Aucun événement à venir pour le moment.</p>
                @else
                    @php $next = $upcomingEvents->first(); @endphp
                    <span class="mb-3.5 inline-flex w-fit items-center gap-1.5 rounded-full border border-[#AC0100]/40 bg-[#AC0100]/25 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-white">
                        <span class="size-1.5 rounded-full bg-white"></span> Prochain événement
                    </span>
                    <h3 class="mb-2 font-serif text-xl italic leading-tight">{{ $next->title }}</h3>
                    <p class="mb-1.5 text-[13px] text-[#C4D0EC]">📍 {{ $next->location }} · {{ $next->starts_at->locale('fr')->translatedFormat('d F Y') }}</p>
                    <p class="flex-1 text-[13px] leading-relaxed text-[#C4D0EC]">{{ $next->excerpt ?? $next->description }}</p>
                    <div class="mt-4 flex gap-2.5">
                        <a href="{{ url('/evenements/'.$next->slug) }}" class="flex-1 rounded-[10px] bg-white px-4 py-2.5 text-center text-[13px] font-bold text-brand">En savoir plus</a>
                        <span class="flex items-center justify-center rounded-[10px] border border-white/20 bg-white/10 px-3.5 py-2.5">
                            <x-ui.icon name="external-link" class="size-3.5" />
                        </span>
                    </div>
                @endif
            </div>
        </section>

        <section class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div>
                <div class="mb-3.5 flex items-end justify-between">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Membres du réseau</h2>
                        <p class="mt-0.5 text-[13px] text-[#5B677A]">Connectez-vous avec vos pairs</p>
                    </div>
                    <a href="{{ route('espace-membre.directory') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure">
                        Voir l'annuaire <x-ui.icon name="arrow-right" class="size-3" />
                    </a>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @if ($members->isEmpty())
                        <div class="px-6 py-8 text-center"><p class="text-[13px] text-[#5B677A]">Aucun membre trouvé</p></div>
                    @else
                        <ul class="list-none p-0">
                            @foreach ($members as $i => $m)
                                <li class="flex items-center gap-3 px-[18px] py-3.5 {{ $i < $members->count() - 1 ? 'border-b border-cloud-200' : '' }}">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                        {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13.5px] font-semibold text-brand">{{ $m->prenom }} {{ $m->nom }}</p>
                                        @if ($m->secteur || $m->ville)
                                            <p class="mt-0.5 truncate text-xs text-[#9AA6B8]">{{ collect([$m->secteur, $m->ville])->filter()->join(' · ') }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}" wire:navigate title="Envoyer un message" class="flex size-[30px] shrink-0 items-center justify-center rounded-lg border border-brand/10 bg-cloud text-[#5B677A]">
                                        <x-ui.icon name="message-circle" class="size-[13px]" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div>
                <div class="mb-3.5 flex items-end justify-between">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Événements à venir</h2>
                        <p class="mt-0.5 text-[13px] text-[#5B677A]">Restez connecté à l'agenda du réseau</p>
                    </div>
                    <a href="{{ url('/evenements') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure">
                        Tous les événements <x-ui.icon name="arrow-right" class="size-3" />
                    </a>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @if ($upcomingEvents->isEmpty())
                        <div class="px-6 py-8 text-center"><p class="text-[13px] text-[#5B677A]">Aucun événement à venir.</p></div>
                    @else
                        <ul class="list-none py-2">
                            @foreach ($upcomingEvents as $ev)
                                <li class="flex items-start gap-3.5 px-[18px] py-3">
                                    <div class="mt-1.5 size-2 shrink-0 rounded-full bg-azure"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="mb-1 flex items-center gap-2">
                                            <span class="rounded-full bg-azure/15 px-2 py-0.5 text-[10.5px] font-semibold text-azure">{{ $ev->category }}</span>
                                            <span class="text-[11px] text-[#9AA6B8]">{{ $ev->starts_at->locale('fr')->translatedFormat('d M') }}</span>
                                        </div>
                                        <p class="mb-0.5 text-[13.5px] font-semibold text-brand">{{ $ev->title }}</p>
                                        <p class="text-xs text-[#9AA6B8]">📍 {{ $ev->location }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>

        @if ($docs->isNotEmpty())
            <section class="mb-8">
                <div class="mb-3.5 flex items-end justify-between">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Documents récents</h2>
                        <p class="mt-0.5 text-[13px] text-[#5B677A]">Dernières ressources partagées</p>
                    </div>
                    <a href="{{ route('espace-membre.documents') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure">
                        Tous les documents <x-ui.icon name="arrow-right" class="size-3" />
                    </a>
                </div>
                <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))">
                    @foreach ($docs as $doc)
                        <div class="rounded-[18px] border border-brand/10 bg-white px-5 py-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="mb-3 flex size-9 items-center justify-center rounded-[10px] bg-accent/10">
                                <x-ui.icon name="folder-open" class="size-4 text-accent" />
                            </div>
                            <p class="mb-1 text-[13.5px] font-semibold leading-tight text-brand">{{ $doc->title }}</p>
                            <p class="mb-3.5 text-[11.5px] text-[#9AA6B8]">{{ $doc->category }}</p>
                            <a href="{{ $doc->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-xs font-semibold text-azure">
                                <x-ui.icon name="download" class="size-3" /> Télécharger
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($completion < 80)
            <div class="flex flex-wrap items-center justify-between gap-5 rounded-[20px] border border-brand/10 px-8 py-6" style="background: linear-gradient(135deg, rgba(79,111,191,.10), rgba(172,1,0,.06)), #fff">
                <div>
                    <h3 class="mb-1.5 font-serif text-lg italic text-brand">Complétez votre profil</h3>
                    <p class="text-[13.5px] text-[#5B677A]">Votre profil est complété à {{ $completion }}%. Ajoutez vos informations pour améliorer votre visibilité dans le réseau.</p>
                </div>
                <a href="{{ route('espace-membre.profile') }}" wire:navigate class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-[13.5px] font-bold text-white">
                    Compléter mon profil <x-ui.icon name="arrow-right" class="size-3.5" />
                </a>
            </div>
        @endif
    </div>
</div>
