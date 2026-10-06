@php
    $user = \App\Support\Api::user();
@endphp

{{-- Se met à jour seul toutes les 60 s quand l'onglet est visible (nouveaux messages, événements, progression…). --}}
<div wire:poll.visible.60s>
    <x-member-light.topbar title="Tableau de bord" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        @if (! $abonnementActif)
            <section data-test="abonnement-inactif" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-[#F5A623]/40 bg-[#FFF8EC] px-6 py-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]">
                    <x-ui.icon name="shield" class="size-5" />
                </span>
                <div class="min-w-[220px] flex-1">
                    <p class="text-[15px] font-bold text-brand">Votre abonnement annuel n'est pas actif</p>
                    <p class="mt-0.5 text-[13px] text-[#5B677A]">La carte membre, l'annuaire, la messagerie et les projets sont verrouillés. Activez votre abonnement ({{ \App\Support\Tarif::libelle() }} / an) pour profiter de tout le réseau.</p>
                </div>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent-600 hover:shadow-md active:scale-95 sm:w-fit">
                    Activer mon abonnement <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </a>
            </section>
        @elseif ($enGrace)
            <section data-test="abonnement-grace" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-[#F5A623]/40 bg-[#FFF8EC] px-6 py-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]">
                    <x-ui.icon name="clock" class="size-5" />
                </span>
                <div class="min-w-[220px] flex-1">
                    <p class="text-[15px] font-bold text-brand">Votre abonnement est arrivé à échéance</p>
                    <p class="mt-0.5 text-[13px] text-[#5B677A]">Vous gardez l'accès à tout le réseau jusqu'au <strong>{{ $graceFin?->translatedFormat('j F Y') }}</strong>. Renouvelez-le d'ici là : la date anniversaire est conservée.</p>
                </div>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent-600 hover:shadow-md active:scale-95 sm:w-fit">
                    Renouveler maintenant <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </a>
            </section>
        @elseif ($expireBientot)
            <section data-test="abonnement-expire" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-azure/25 bg-azure/[.06] px-6 py-4">
                <x-ui.icon name="clock" class="size-5 shrink-0 text-azure" />
                <p class="min-w-[220px] flex-1 text-[13.5px] text-brand">Votre abonnement expire le <strong>{{ $expireLe->translatedFormat('j F Y') }}</strong>. Pensez à le renouveler pour garder l'accès à tout le réseau.</p>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="text-[13px] font-bold text-azure hover:underline">Renouveler</a>
            </section>
        @endif

        <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-2 text-[28px] font-extrabold tracking-tight text-brand">Bonjour {{ $user->prenom }} 👋</h1>
                <p class="max-w-lg font-serif text-[15px] italic text-[#5B677A]">« Tout ce que vous faites, faites-le de bon cœur, comme pour le Seigneur. » — Colossiens 3:23</p>
            </div>
            {{-- Reprend la formation en cours ; sans formation en cours, invite au catalogue. --}}
            <a href="{{ $continuer['url'] ?? route('espace-membre.catalogue') }}" wire:navigate data-test="cta-principal" class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-[22px] py-3 text-sm font-bold text-white shadow-[0_6px_16px_rgba(172,1,0,.25)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent-600 hover:shadow-[0_10px_22px_rgba(172,1,0,.32)] active:scale-95 sm:w-fit">
                {{ $continuer ? 'Reprendre ma formation' : 'Découvrir les formations' }}
                <x-ui.icon name="arrow-right" class="nudge-x size-4" />
            </a>
        </section>

        <section class="relative mb-6 grid grid-cols-1 gap-6 overflow-hidden rounded-[20px] px-5 py-6 text-white sm:gap-8 sm:px-8 sm:py-7 shadow-[0_12px_32px_rgba(3,29,89,.22)] lg:grid-cols-[1.2fr_1fr]" style="background: linear-gradient(120deg,#031D59 0%,#0B2E7A 60%,#1A3D8F 100%)">
            <div class="relative">
                <p class="mb-2.5 text-xs font-semibold tracking-[0.12em] text-[#8FA3D9]">PROGRESSION GÉNÉRALE</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-[52px] font-extrabold leading-none tracking-tight"><x-ui.counter :value="$progression" /></span>
                    <span class="text-2xl font-bold text-[#8FA3D9]">%</span>
                </div>
                <div class="mt-4 h-2.5 max-w-[420px] overflow-hidden rounded-md bg-white/15">
                    <div class="h-full rounded-md" style="width: {{ $progression }}%; background: linear-gradient(90deg,#4F6FBF,#8FB0FF)"></div>
                </div>
                <p class="mt-3.5 text-[13px] text-[#C4D0EC]">
                    @if ($continuer)
                        Continuez <strong class="text-white">{{ $continuer['titre'] }}</strong> — {{ $continuer['module'] }}.
                    @elseif ($formationsTerminees > 0)
                        Bravo, toutes vos formations sont terminées ! Choisissez la suivante dans le catalogue.
                    @else
                        Inscrivez-vous à une formation du catalogue pour démarrer votre progression.
                    @endif
                </p>
            </div>
            <div data-test="compteurs" class="grid grid-cols-3 gap-2 sm:gap-3">
                @foreach ([
                    ['icon' => 'graduation-cap', 'value' => $formationsTerminees, 'label' => 'Formations terminées'],
                    ['icon' => 'award', 'value' => $certificatsObtenus, 'label' => 'Certificats obtenus'],
                    ['icon' => 'check-circle', 'value' => $modulesValides, 'label' => 'Modules validés'],
                ] as $stat)
                    <div class="rounded-[14px] border border-white/10 bg-white/[.08] px-2.5 py-3 sm:px-3.5 sm:py-4">
                        <x-ui.icon :name="$stat['icon']" class="size-4 text-[#8FB0FF] sm:size-5" />
                        <p class="mt-1.5 text-[22px] font-extrabold leading-none sm:mt-2 sm:text-[26px]"><x-ui.counter :value="$stat['value']" :suffix="$stat['suffix'] ?? ''" /></p>
                        <p class="mt-1 text-[11px] leading-tight text-[#C4D0EC] sm:text-xs">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mb-8 grid grid-cols-1 items-start gap-6 lg:grid-cols-[1.85fr_1fr]">
            <div class="flex min-w-0 flex-col gap-6">
                @if ($continuer)
                    <section>
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Continuer ma formation</h2>
                        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                        <div class="card-hover grid grid-cols-1 overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] lg:grid-cols-[260px_1fr]">
                            <div class="flex min-h-[150px] items-center justify-center" style="background: repeating-linear-gradient(45deg,#0B2E7A 0 22px,#123A8C 22px 44px)">
                                <x-ui.icon name="graduation-cap" class="size-10 text-white/70" />
                            </div>
                            <div class="flex flex-col gap-2.5 p-6">
                                <span class="w-fit rounded-full bg-[#E8EDF8] px-2.5 py-1 text-[11px] font-bold text-brand">{{ $continuer['categorie'] }}</span>
                                <h3 class="text-[19px] font-bold text-brand">{{ $continuer['titre'] }}</h3>
                                <p class="text-[13px] text-[#5B677A]">{{ $continuer['module'] }}</p>
                                <div class="mt-auto flex items-center gap-3">
                                    <div class="h-2 flex-1 overflow-hidden rounded-md bg-[#EDF0F5]">
                                        <div class="h-full rounded-md" style="width: {{ $continuer['pct'] }}%; background: linear-gradient(90deg,#031D59,#4F6FBF)"></div>
                                    </div>
                                    <span class="text-[13px] font-bold text-brand">{{ $continuer['pct'] }} %</span>
                                    <a href="{{ $continuer['url'] }}" wire:navigate data-test="continuer" class="rounded-[10px] bg-brand px-5 py-2.5 text-[13px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent hover:shadow-md active:scale-95">Continuer</a>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if (! empty($recommandations))
                    <section>
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Recommandé pour vous</h2>
                        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                        <div class="relative overflow-hidden rounded-[18px] bg-brand p-5 text-white">
                            <div class="mb-3 flex items-center gap-2 text-[11px] font-bold tracking-[0.1em] text-[#8FA3D9]">
                                <x-ui.icon name="sparkles" class="size-3.5 text-[#8FB0FF]" />
                                À DÉCOUVRIR DANS LE RÉSEAU
                            </div>
                            <div class="flex flex-col gap-2.5">
                                @foreach ($recommandations as $r)
                                    <a href="{{ $r['url'] }}" wire:navigate data-test="recommandation" class="block rounded-xl border border-white/10 bg-white/[.08] px-3.5 py-3 transition-all duration-200 ease-out hover:translate-x-1 hover:bg-white/[.16] active:scale-[0.98]">
                                        <p class="text-[13px] font-bold">{{ $r['type'] }} · {{ $r['titre'] }}</p>
                                        <p class="mt-0.5 text-[11.5px] text-[#C4D0EC]">{{ $r['detail'] }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                <section>
                    <div class="mb-3.5 flex items-end justify-between">
                        <div>
                            <h2 class="text-[17px] font-bold text-brand">Événements à venir</h2>
                            <p class="mt-0.5 text-[13px] text-[#5B677A]">Restez connecté à l'agenda du réseau</p>
                        </div>
                        <a href="{{ route('espace-membre.evenements') }}" wire:navigate data-test="tous-evenements" class="group inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure transition-all duration-200 ease-out hover:bg-azure/20 active:scale-95">
                            Tous les événements <x-ui.icon name="arrow-right" class="nudge-x size-3" />
                        </a>
                    </div>
                    <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @if ($upcomingEvents->isEmpty())
                            <div class="px-6 py-8 text-center"><p class="text-[13px] text-[#5B677A]">Aucun événement à venir.</p></div>
                        @else
                            <ul class="list-none py-2">
                                @foreach ($upcomingEvents as $ev)
                                    <li>
                                        <a href="{{ route('espace-membre.evenements', ['evenement' => $ev->id]) }}" wire:navigate data-test="evenement-accueil" class="group flex items-start gap-3.5 px-[18px] py-3 transition-colors duration-200 hover:bg-cloud/50">
                                            <div class="mt-1.5 size-2 shrink-0 rounded-full bg-azure"></div>
                                            <div class="min-w-0 flex-1">
                                                <div class="mb-1 flex items-center gap-2">
                                                    <span class="rounded-full bg-azure/15 px-2 py-0.5 text-[10.5px] font-semibold text-azure">{{ $ev->category }}</span>
                                                    <span class="text-[11px] text-[#9AA6B8]">{{ $ev->starts_at->locale('fr')->translatedFormat('d M') }}</span>
                                                    @if (! empty($ev->registered))
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#22A85A]/10 px-2 py-0.5 text-[10.5px] font-semibold text-[#22A85A]"><x-ui.icon name="check" class="size-3" /> Inscrit</span>
                                                    @endif
                                                </div>
                                                <p class="mb-0.5 text-[13.5px] font-semibold text-brand group-hover:underline">{{ $ev->title }}</p>
                                                @if ($ev->location)
                                                    <p class="text-xs text-[#9AA6B8]">📍 {{ $ev->location }}</p>
                                                @endif
                                            </div>
                                            <x-ui.icon name="chevron-right" class="mt-2 size-4 shrink-0 text-[#C9D3E6] transition-transform duration-200 group-hover:translate-x-0.5 group-hover:text-azure" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                <section>
                    <div class="mb-3.5 flex items-end justify-between">
                        <div>
                            <h2 class="text-[17px] font-bold text-brand">Membres du réseau</h2>
                            <p class="mt-0.5 text-[13px] text-[#5B677A]">Connectez-vous avec vos pairs</p>
                        </div>
                        <a href="{{ route('espace-membre.directory') }}" wire:navigate class="group inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure transition-all duration-200 ease-out hover:bg-azure/20 active:scale-95">
                            Voir l'annuaire <x-ui.icon name="arrow-right" class="nudge-x size-3" />
                        </a>
                    </div>
                    <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @if (! $abonnementActif)
                            <div data-test="annuaire-verrouille" class="flex flex-col items-center gap-2.5 px-6 py-8 text-center">
                                <span class="flex size-10 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]"><x-ui.icon name="shield" class="size-[18px]" /></span>
                                <p class="text-[13.5px] font-semibold text-brand">L'annuaire est réservé aux membres abonnés</p>
                                <p class="max-w-xs text-[12.5px] text-[#5B677A]">Activez votre abonnement pour découvrir les membres du réseau et leur écrire.</p>
                                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="mt-1 text-[12.5px] font-bold text-azure hover:underline">Activer mon abonnement →</a>
                            </div>
                        @elseif ($members->isEmpty())
                            <div class="px-6 py-8 text-center"><p class="text-[13px] text-[#5B677A]">Aucun membre trouvé</p></div>
                        @else
                            <ul class="list-none p-0">
                                @foreach ($members as $i => $m)
                                    <li class="flex items-center gap-3 px-[18px] py-3.5 transition-colors duration-200 hover:bg-cloud/50 {{ $i < $members->count() - 1 ? 'border-b border-cloud-200' : '' }}">
                                        @if ($m->photo ?? null)
                                            <img src="{{ $m->photo }}" alt="Photo de {{ $m->prenom }} {{ $m->nom }}" loading="lazy" data-test="photo-membre" class="size-10 shrink-0 rounded-full object-cover ring-1 ring-brand/10">
                                        @else
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                                {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13.5px] font-semibold text-brand">{{ $m->prenom }} {{ $m->nom }}</p>
                                            @if ($m->secteur || $m->ville)
                                                <p class="mt-0.5 truncate text-xs text-[#9AA6B8]">{{ collect([$m->secteur, $m->ville])->filter()->join(' · ') }}</p>
                                            @endif
                                        </div>
                                        <a href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}" wire:navigate title="Envoyer un message" class="flex size-[30px] shrink-0 items-center justify-center rounded-lg border border-brand/10 bg-cloud text-[#5B677A] transition-all duration-200 ease-out hover:scale-110 hover:border-azure/30 hover:text-azure active:scale-95">
                                            <x-ui.icon name="message-circle" class="size-[13px]" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            </div>

            <div class="flex min-w-0 flex-col gap-6">
                <section>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Parole &amp; prière du jour</h2>
                    <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                    <div class="flex gap-3.5 rounded-[14px] border border-l-[3px] border-brand/10 border-l-accent bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <x-ui.icon name="book-open" class="mt-0.5 size-[22px] shrink-0 text-accent" />
                        <div>
                            <p data-test="verset" class="font-serif text-[13.5px] italic leading-relaxed text-ink">« {{ $parole['verset'] }} » — {{ $parole['reference'] }}</p>
                            @if (filled($parole['intention']))
                                <p data-test="intention" class="mt-2.5 text-xs leading-relaxed text-[#5B677A]">Intention du jour : {{ \Illuminate\Support\Str::lcfirst($parole['intention']) }}</p>
                            @endif
                        </div>
                    </div>
                </section>

                @if ($prochaineSeance)
                    <section data-test="prochaine-seance">
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Prochaine séance de mentorat</h2>
                        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                        <a href="{{ route('espace-membre.mentorat.suivi', $prochaineSeance['mentorship_id']) }}" wire:navigate class="card-hover block rounded-[18px] border border-accent/20 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="flex items-start gap-3">
                                <span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl bg-accent text-white">
                                    <span class="text-[15px] font-extrabold leading-none">{{ \Carbon\Carbon::parse($prochaineSeance['debut'])->format('d') }}</span>
                                    <span class="text-[9.5px] font-bold uppercase">{{ \Carbon\Carbon::parse($prochaineSeance['debut'])->translatedFormat('M') }}</span>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-bold text-brand">{{ ucfirst(\Carbon\Carbon::parse($prochaineSeance['debut'])->translatedFormat('l \à H\hi')) }} · {{ $prochaineSeance['format'] === 'visio' ? 'visio' : 'présentiel' }}</p>
                                    <p class="text-[12.5px] text-[#5B677A]">{{ $prochaineSeance['je_suis'] === 'mentor' ? 'Avec votre mentoré·e' : 'Avec votre mentor' }} {{ $prochaineSeance['avec'] }}</p>
                                    @if ($prochaineSeance['statut'] === 'proposee')
                                        <p class="mt-1 text-[11.5px] font-bold text-[#8A5A00]">{{ $prochaineSeance['proposee_par_moi'] ? 'En attente de confirmation' : 'À confirmer de votre côté' }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </section>
                @endif

                <section>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Mes défis</h2>
                    <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                    <div class="rounded-[18px] border border-brand/10 p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]" style="background: linear-gradient(150deg,#fff,#F0F3FA)">
                        @foreach ($defis as $d)
                            <div class="flex items-center gap-3 py-2.5">
                                <span class="flex size-[22px] shrink-0 items-center justify-center rounded-[7px] border-2 {{ $d['fait'] ? 'border-[#22A85A] bg-[#22A85A]' : 'border-[#C9D3E6] bg-white' }}">
                                    @if ($d['fait'])
                                        <x-ui.icon name="check" class="size-3 text-white" />
                                    @endif
                                </span>
                                <span class="flex-1 text-[13.5px] {{ $d['fait'] ? 'text-[#9AA6B8] line-through' : 'text-ink' }}">{{ $d['label'] }}</span>
                                <span class="text-[11.5px] font-bold text-azure">+{{ $d['xp'] }} XP</span>
                            </div>
                        @endforeach
                        <div class="mt-3 flex items-center gap-3 border-t border-dashed border-[#C9D3E6] pt-3.5">
                            <span class="flex size-[38px] shrink-0 items-center justify-center rounded-full" style="background: linear-gradient(135deg,#AC0100,#D95B5A)">
                                <x-ui.icon name="award" class="size-[18px] text-white" />
                            </span>
                            <div>
                                <p class="text-[13px] font-bold text-brand">Badge « Bâtisseur »</p>
                                <p class="text-xs text-[#5B677A]">{{ $defisFaits }} / {{ count($defis) }} défis complétés — {{ collect($defis)->where('fait', true)->sum('xp') }} XP gagnés</p>
                            </div>
                        </div>
                    </div>
                </section>

                @if ($badgesParcours->isNotEmpty())
                    <section data-test="badges-parcours">
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Mes badges de parcours</h2>
                        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                        <div class="flex flex-col gap-2 rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            @foreach ($badgesParcours as $b)
                                <a href="{{ route('espace-membre.parcours.detail', $b['id']) }}" wire:navigate class="flex items-center gap-3 rounded-[12px] p-1.5 transition-colors hover:bg-cloud/70">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full" style="background: {{ $b['badge_couleur'] ?: '#4F6FBF' }}1A; color: {{ $b['badge_couleur'] ?: '#4F6FBF' }}">
                                        <x-ui.icon :name="$b['badge_icon'] ?: 'rocket'" class="size-[18px]" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold text-brand">{{ $b['title'] }}</span>
                                        <span class="block text-[11.5px] text-[#9AA6B8]">Obtenu le {{ \Carbon\Carbon::parse($b['badge_obtenu_le'])->translatedFormat('j F Y') }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Activité récente</h2>
                    <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                    <div class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @foreach ($activites as $i => $a)
                            <div class="flex gap-3 {{ $i < count($activites) - 1 ? 'pb-4' : '' }}">
                                <div class="flex flex-col items-center">
                                    <span class="mt-1 size-2.5 shrink-0 rounded-full" style="background: {{ $a['dot'] }}"></span>
                                    @if ($i < count($activites) - 1)
                                        <span class="mt-1 w-0.5 flex-1 bg-[#EDF0F5]"></span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[13px] leading-snug text-ink">{{ $a['texte'] }}</p>
                                    <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $a['quand'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        @if ($docs->isNotEmpty())
            <section class="mb-8">
                <div class="mb-3.5 flex items-end justify-between">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Documents récents</h2>
                        <p class="mt-0.5 text-[13px] text-[#5B677A]">Dernières ressources partagées</p>
                    </div>
                    <a href="{{ route('espace-membre.documents') }}" wire:navigate class="group inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-[12.5px] font-semibold text-azure transition-all duration-200 ease-out hover:bg-azure/20 active:scale-95">
                        Tous les documents <x-ui.icon name="arrow-right" class="nudge-x size-3" />
                    </a>
                </div>
                <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))">
                    @foreach ($docs as $doc)
                        <div class="card-hover rounded-[18px] border border-brand/10 bg-white px-5 py-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="mb-3 flex size-9 items-center justify-center rounded-[10px] bg-accent/10">
                                <x-ui.icon name="folder-open" class="size-4 text-accent" />
                            </div>
                            <p class="mb-1 text-[13.5px] font-semibold leading-tight text-brand">{{ $doc->title }}</p>
                            <p data-test="document" class="mb-3.5 text-[11.5px] text-[#9AA6B8]">{{ $doc->category }}@if ($doc->ajoute) · ajouté {{ $doc->ajoute->diffForHumans() }}@endif</p>
                            <a href="{{ route('espace-membre.documents', ['document' => $doc->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-xs font-semibold text-azure transition-all duration-200 ease-out hover:bg-azure/20 active:scale-95">
                                <x-ui.icon :name="($doc->verrouille ?? false) ? 'lock' : 'eye'" class="size-3" /> {{ ($doc->verrouille ?? false) ? 'Réservé' : 'Consulter' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($completion < 100)
            <div class="flex flex-wrap items-center justify-between gap-5 rounded-[20px] border border-brand/10 px-8 py-6" style="background: linear-gradient(135deg, rgba(79,111,191,.10), rgba(172,1,0,.06)), #fff">
                <div>
                    <h3 class="mb-1.5 font-serif text-lg italic text-brand">Complétez votre profil</h3>
                    <p data-test="completion-accueil" class="text-[13.5px] text-[#5B677A]">Votre profil est complété à {{ $completion }} %. Il manque : {{ implode(', ', $champsManquants) }}.</p>
                </div>
                <a href="{{ route('espace-membre.profile') }}" wire:navigate class="group inline-flex shrink-0 items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-[13.5px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent hover:shadow-md active:scale-95">
                    Compléter mon profil <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </a>
            </div>
        @endif
    </div>
</div>
