@php
    $user = \App\Support\Api::user();
    $estMentor = ($user->role ?? null) === 'mentor';
@endphp

{{-- Accueil en trois niveaux : « À faire maintenant » (3 actions au plus),
     mes formations et mes événements, puis le reste (allégé).
     Se met à jour seul toutes les 60 s quand l'onglet est visible. --}}
<div wire:poll.visible.60s>
    <x-member-light.topbar title="Tableau de bord" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        @if (! $abonnementActif)
            <section data-test="abonnement-inactif" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-[#F5A623]/40 bg-[#FFF8EC] px-5 py-4 sm:px-6">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]">
                    <x-ui.icon name="shield" class="size-5" />
                </span>
                <div class="min-w-[200px] flex-1">
                    <p class="text-[15px] font-bold text-brand">Votre abonnement annuel n'est pas actif</p>
                    <p class="mt-0.5 text-[13px] text-[#5B677A]">La carte membre, l'annuaire, la messagerie et les projets sont verrouillés. Activez votre abonnement ({{ \App\Support\Tarif::libelle() }} / an) pour profiter de tout le réseau.</p>
                </div>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent-600 hover:shadow-md active:scale-95 sm:w-fit">
                    Activer mon abonnement <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </a>
            </section>
        @elseif ($enGrace)
            <section data-test="abonnement-grace" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-[#F5A623]/40 bg-[#FFF8EC] px-5 py-4 sm:px-6">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]">
                    <x-ui.icon name="clock" class="size-5" />
                </span>
                <div class="min-w-[200px] flex-1">
                    <p class="text-[15px] font-bold text-brand">Votre abonnement est arrivé à échéance</p>
                    <p class="mt-0.5 text-[13px] text-[#5B677A]">Vous gardez l'accès à tout le réseau jusqu'au <strong>{{ $graceFin?->translatedFormat('j F Y') }}</strong>. Renouvelez-le d'ici là : la date anniversaire est conservée.</p>
                </div>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-accent-600 hover:shadow-md active:scale-95 sm:w-fit">
                    Renouveler maintenant <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </a>
            </section>
        @elseif ($expireBientot)
            <section data-test="abonnement-expire" class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-azure/25 bg-azure/[.06] px-5 py-4 sm:px-6">
                <x-ui.icon name="clock" class="size-5 shrink-0 text-azure" />
                <p class="min-w-[200px] flex-1 text-[13.5px] text-brand">Votre abonnement expire le <strong>{{ $expireLe->translatedFormat('j F Y') }}</strong>. Pensez à le renouveler pour garder l'accès à tout le réseau.</p>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="text-[13px] font-bold text-azure hover:underline">Renouveler</a>
            </section>
        @endif

        {{-- Bonjour + accès direct à la carte membre --}}
        <section class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[26px] font-extrabold tracking-tight text-brand sm:text-[28px]">Bonjour {{ $user->prenom }} 👋</h1>
                <p class="mt-1 text-[13.5px] text-[#5B677A]">{{ ucfirst(now()->locale('fr')->translatedFormat('l j F')) }} · voici l'essentiel pour aujourd'hui.</p>
            </div>
            <a href="{{ route('espace-membre.carte') }}" wire:navigate data-test="acces-carte" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">
                <x-ui.icon name="qr-code" class="size-4" /> {{ $estMentor ? 'Ma carte mentor' : 'Ma carte membre' }}
                @if (! $abonnementActif)<x-ui.icon name="lock" class="size-[13px] text-[#B27007]" />@endif
            </a>
        </section>

        {{-- Niveau 1 : À faire maintenant --}}
        <section data-test="a-faire" class="mb-8 overflow-hidden rounded-[20px] border border-brand/10 bg-white shadow-[0_12px_32px_-18px_rgba(3,29,89,.35)]">
            <div class="flex items-center gap-2.5 bg-brand px-5 py-3.5 text-white sm:px-6" style="box-shadow: inset 0 -3px 0 #ac0100">
                <x-ui.icon name="sparkles" class="size-4 text-[#8FB0FF]" />
                <h2 class="text-[15px] font-extrabold tracking-[0.02em]">À faire maintenant</h2>
            </div>
            @if (empty($aFaire))
                <div data-test="a-faire-vide" class="flex flex-wrap items-center gap-4 px-5 py-6 sm:px-6">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#22A85A]/10 text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-5" /></span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[15px] font-bold text-brand">Tout est à jour, bravo !</p>
                        <p class="mt-0.5 text-[13px] text-[#5B677A]">Rien ne vous attend pour l'instant. Profitez-en pour échanger avec la communauté.</p>
                    </div>
                    <a href="{{ route('espace-membre.communaute') }}" wire:navigate class="btn-tap inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent sm:w-fit">Aller à la Communauté <x-ui.icon name="arrow-right" class="size-3.5" /></a>
                </div>
            @else
                <ul class="list-none divide-y divide-cloud-200">
                    @foreach ($aFaire as $i => $a)
                        <li wire:key="afaire-{{ $a['cle'] }}-{{ $i }}" data-test="action-{{ $a['cle'] }}" class="flex flex-wrap items-center gap-x-4 gap-y-3 px-5 py-4 sm:px-6">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ ($a['urgent'] ?? false) ? 'bg-accent text-white' : 'bg-azure/10 text-azure' }}">
                                <x-ui.icon :name="$a['icon']" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1 basis-[200px]">
                                <p class="text-[14.5px] font-bold leading-snug text-brand">{{ $a['titre'] }}</p>
                                <p class="mt-0.5 text-[12.5px] text-[#5B677A]">{{ $a['detail'] }}</p>
                                @isset($a['pct'])
                                    <div class="mt-2 h-1.5 max-w-[260px] overflow-hidden rounded bg-[#EDF0F5]"><div class="h-full rounded" style="width: {{ $a['pct'] }}%; background: linear-gradient(90deg,#031D59,#4F6FBF)"></div></div>
                                @endisset
                            </div>
                            <div class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto">
                                @if ($a['cle'] === 'participer' && ($a['evenement']['visio_ouverte'] ?? false))
                                    <x-evenements.rejoindre :e="$a['evenement']" />
                                @elseif (isset($a['inscrire']))
                                    <a href="{{ $a['url'] }}" wire:navigate class="text-[12.5px] font-semibold text-azure hover:underline">Voir le détail</a>
                                    <button type="button" wire:click="inscrireEvenement({{ $a['inscrire'] }})" wire:loading.attr="disabled" wire:target="inscrireEvenement" data-test="a-faire-inscrire"
                                        class="btn-tap inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60 sm:flex-none">
                                        <x-ui.icon name="check" class="size-4" /> {{ $a['bouton'] }}
                                    </button>
                                @else
                                    <a href="{{ $a['url'] }}" wire:navigate data-test="a-faire-bouton"
                                        class="btn-tap inline-flex flex-1 items-center justify-center gap-2 rounded-xl {{ $i === 0 ? 'bg-accent hover:bg-accent-600' : 'bg-brand hover:bg-accent' }} px-5 py-2.5 text-[13px] font-bold text-white sm:flex-none">
                                        {{ $a['bouton'] }} <x-ui.icon name="arrow-right" class="size-3.5" />
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Niveau 2 : mes formations et mes événements --}}
        <div class="mb-8 grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
            <section data-test="mes-formations">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Mes formations</h2>
                        <div class="mt-1 h-[3px] w-9 rounded bg-accent"></div>
                    </div>
                    <a href="{{ route('espace-membre.formations') }}" wire:navigate class="text-[12.5px] font-semibold text-azure hover:underline">Toutes mes formations →</a>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($enCours as $f)
                        <a href="{{ $f['url'] }}" wire:navigate data-test="formation-accueil" class="group flex items-center gap-3.5 px-[18px] py-3.5 transition-colors hover:bg-cloud/50 {{ ! $loop->last ? 'border-b border-cloud-200' : '' }}">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[11px] bg-brand text-white"><x-ui.icon name="graduation-cap" class="size-[18px]" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13.5px] font-semibold text-brand group-hover:underline">{{ $f['titre'] }}</span>
                                <span class="mt-1.5 flex items-center gap-2">
                                    <span class="h-1.5 flex-1 overflow-hidden rounded bg-[#EDF0F5]"><span class="block h-full rounded" style="width: {{ $f['pct'] }}%; background: linear-gradient(90deg,#031D59,#4F6FBF)"></span></span>
                                    <span class="shrink-0 text-[11.5px] font-bold text-brand">{{ $f['pct'] }} %</span>
                                </span>
                            </span>
                            <span class="shrink-0 text-[12.5px] font-bold text-azure">Continuer</span>
                        </a>
                    @empty
                        <div class="flex flex-col items-center gap-2.5 px-6 py-8 text-center">
                            <span class="flex size-10 items-center justify-center rounded-full bg-azure/10 text-azure"><x-ui.icon name="graduation-cap" class="size-[18px]" /></span>
                            <p class="text-[13.5px] font-semibold text-brand">{{ $formationsTerminees > 0 ? 'Toutes vos formations sont terminées, bravo !' : 'Aucune formation en cours' }}</p>
                            <p class="max-w-xs text-[12.5px] text-[#5B677A]">Choisissez votre prochaine formation dans le catalogue du réseau.</p>
                            <a href="{{ route('espace-membre.catalogue') }}" wire:navigate class="btn-tap mt-1 rounded-xl bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent">Parcourir le catalogue</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <section data-test="mes-evenements">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-[17px] font-bold text-brand">Mes événements</h2>
                        <div class="mt-1 h-[3px] w-9 rounded bg-accent"></div>
                    </div>
                    <a href="{{ route('espace-membre.evenements') }}" wire:navigate data-test="tous-evenements" class="text-[12.5px] font-semibold text-azure hover:underline">Tout l'agenda →</a>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($evenements as $ev)
                        @php $debut = \Carbon\Carbon::parse($ev['starts_at'])->setTimezone(config('app.timezone'))->locale('fr'); @endphp
                        <div wire:key="ev-accueil-{{ $ev['id'] }}" class="flex items-center gap-3.5 px-[18px] py-3 {{ ! $loop->last ? 'border-b border-cloud-200' : '' }}">
                            <span class="flex size-10 shrink-0 flex-col items-center justify-center rounded-[11px] {{ $ev['registered'] ? 'bg-accent text-white' : 'bg-azure/10 text-brand' }}">
                                <span class="text-[14px] font-extrabold leading-none">{{ $debut->format('d') }}</span>
                                <span class="text-[9.5px] font-bold uppercase">{{ $debut->translatedFormat('M') }}</span>
                            </span>
                            <a href="{{ route('espace-membre.evenements', ['evenement' => $ev['id']]) }}" wire:navigate data-test="evenement-accueil" class="group min-w-0 flex-1">
                                <span class="block truncate text-[13.5px] font-semibold text-brand group-hover:underline">{{ $ev['title'] }}</span>
                                <span class="block truncate text-[11.5px] text-[#5B677A]">{{ $debut->format('H\hi') }} · {{ ($ev['en_ligne'] ?? false) ? 'En ligne' : ($ev['location'] ?: $ev['category']) }}</span>
                            </a>
                            @if ($ev['registered'])
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[11px] font-bold text-[#1C8F4C]"><x-ui.icon name="check" class="size-3" /> Inscrit</span>
                            @elseif (empty($ev['refus']) && ! ($ev['en_cours'] ?? false))
                                <button type="button" wire:click="inscrireEvenement({{ $ev['id'] }})" wire:loading.attr="disabled" wire:target="inscrireEvenement" data-test="inscrire-accueil" class="btn-tap shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3 py-1.5 text-[11.5px] font-bold text-azure hover:bg-azure/20 disabled:opacity-60">M'inscrire</button>
                            @endif
                        </div>
                    @empty
                        <div class="flex flex-col items-center gap-2.5 px-6 py-8 text-center">
                            <span class="flex size-10 items-center justify-center rounded-full bg-azure/10 text-azure"><x-ui.icon name="calendar-days" class="size-[18px]" /></span>
                            <p class="text-[13.5px] font-semibold text-brand">Aucun événement à venir pour l'instant</p>
                            <p class="max-w-xs text-[12.5px] text-[#5B677A]">Les prochaines rencontres du réseau apparaîtront ici dès leur publication.</p>
                            <a href="{{ route('espace-membre.evenements', ['onglet' => 'passes']) }}" wire:navigate class="mt-1 text-[12.5px] font-bold text-azure hover:underline">Revoir les événements passés →</a>
                        </div>
                    @endforelse
                </div>
                @if ($nbInscriptions > 0)
                    <p class="mt-2 px-1 text-[12px] text-[#5B677A]">{{ $nbInscriptions > 1 ? "Vous êtes inscrit à {$nbInscriptions} événements à venir." : 'Vous êtes inscrit à 1 événement à venir.' }} <a href="{{ route('espace-membre.evenements', ['onglet' => 'mes']) }}" wire:navigate class="font-semibold text-azure hover:underline">Mes inscriptions</a></p>
                @endif
            </section>
        </div>

        {{-- Niveau 3 : le reste, allégé --}}
        <div class="mb-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <section data-test="ma-progression">
                <h2 class="text-[15px] font-bold text-brand">Ma progression</h2>
                <div class="mb-3 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-[34px] font-extrabold leading-none text-brand"><x-ui.counter :value="$progression" /></span>
                        <span class="text-lg font-bold text-[#8FA3D9]">%</span>
                        <span class="ml-1 text-[12px] text-[#5B677A]">de progression moyenne</span>
                    </div>
                    <div data-test="compteurs" class="mt-3 grid grid-cols-3 gap-2 text-center">
                        @foreach ([[$formationsTerminees, 'Formations terminées'], [$certificatsObtenus, 'Certificats'], [$modulesValides, 'Modules validés']] as [$valeur, $libelle])
                            <div class="rounded-[12px] bg-cloud px-1.5 py-2.5">
                                <p class="text-[18px] font-extrabold leading-none text-brand">{{ $valeur }}</p>
                                <p class="mt-1 text-[10.5px] leading-tight text-[#5B677A]">{{ $libelle }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-t border-dashed border-[#C9D3E6] pt-3">
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <p class="text-[12.5px] font-bold text-brand">Badge « Bâtisseur »</p>
                            <p class="text-[11.5px] font-bold text-azure">{{ $defisFaits }} / {{ count($defis) }} défis · {{ collect($defis)->where('fait', true)->sum('xp') }} XP</p>
                        </div>
                        @foreach ($defis as $d)
                            <div class="flex items-center gap-2.5 py-1">
                                <span class="flex size-[18px] shrink-0 items-center justify-center rounded-[5px] border-2 {{ $d['fait'] ? 'border-[#22A85A] bg-[#22A85A]' : 'border-[#C9D3E6] bg-white' }}">
                                    @if ($d['fait'])<x-ui.icon name="check" class="size-2.5 text-white" />@endif
                                </span>
                                <span class="flex-1 text-[12.5px] {{ $d['fait'] ? 'text-[#5B677A] line-through' : 'text-ink' }}">{{ $d['label'] }}</span>
                                <span class="text-[11px] font-bold text-azure">+{{ $d['xp'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($badgesParcours->isNotEmpty())
                        <div data-test="badges-parcours" class="mt-3 border-t border-dashed border-[#C9D3E6] pt-3">
                            <p class="mb-2 text-[12.5px] font-bold text-brand">Mes badges de parcours</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($badgesParcours as $b)
                                    <a href="{{ route('espace-membre.parcours.detail', $b['id']) }}" wire:navigate title="{{ $b['title'] }} · obtenu le {{ \Carbon\Carbon::parse($b['badge_obtenu_le'])->translatedFormat('j F Y') }}"
                                        class="flex size-10 items-center justify-center rounded-full transition-transform hover:scale-110" style="background: {{ $b['badge_couleur'] ?: '#4F6FBF' }}1A; color: {{ $b['badge_couleur'] ?: '#4F6FBF' }}">
                                        <x-ui.icon :name="$b['badge_icon'] ?: 'rocket'" class="size-[18px]" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-3">
                    <h2 class="text-[15px] font-bold text-brand">Membres du réseau</h2>
                    @if ($abonnementActif)
                        <a href="{{ route('espace-membre.directory') }}" wire:navigate class="text-[12.5px] font-semibold text-azure hover:underline">Voir l'annuaire →</a>
                    @endif
                </div>
                <div class="mb-3 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @if (! $abonnementActif)
                        <div data-test="annuaire-verrouille" class="flex flex-col items-center gap-2.5 px-6 py-7 text-center">
                            <span class="flex size-10 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B97400]"><x-ui.icon name="shield" class="size-[18px]" /></span>
                            <p class="text-[13.5px] font-semibold text-brand">L'annuaire est réservé aux membres abonnés</p>
                            <p class="max-w-xs text-[12.5px] text-[#5B677A]">Activez votre abonnement pour découvrir les membres du réseau et leur écrire.</p>
                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="mt-1 text-[12.5px] font-bold text-azure hover:underline">Activer mon abonnement →</a>
                        </div>
                    @elseif ($members->isEmpty())
                        <div class="px-6 py-7 text-center"><p class="text-[13px] text-[#5B677A]">Aucun membre à afficher pour l'instant.</p></div>
                    @else
                        <ul class="list-none p-0">
                            @foreach ($members as $i => $m)
                                <li class="flex items-center gap-3 px-[18px] py-3 {{ $i < $members->count() - 1 ? 'border-b border-cloud-200' : '' }}">
                                    @if ($m->photo ?? null)
                                        <img src="{{ $m->photo }}" alt="Photo de {{ $m->prenom }} {{ $m->nom }}" loading="lazy" data-test="photo-membre" class="size-9 shrink-0 rounded-full object-cover ring-1 ring-brand/10">
                                    @else
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full text-[12px] font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">{{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}</div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-semibold text-brand">{{ $m->prenom }} {{ $m->nom }}</p>
                                        @if ($m->secteur || $m->ville)
                                            <p class="truncate text-[11.5px] text-[#5B677A]">{{ collect([$m->secteur, $m->ville])->filter()->join(' · ') }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}" wire:navigate title="Écrire à {{ $m->prenom }}" aria-label="Écrire à {{ $m->prenom }}" data-test="ecrire-membre" class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-brand/10 bg-cloud text-[#5B677A] transition-all duration-200 hover:border-azure/30 hover:text-azure active:scale-95">
                                        <x-ui.icon name="message-circle" class="size-[14px]" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <div class="flex min-w-0 flex-col gap-6">
                <section>
                    <h2 class="text-[15px] font-bold text-brand">Parole &amp; prière du jour</h2>
                    <div class="mb-3 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                    <div class="flex gap-3 rounded-[14px] border border-l-[3px] border-brand/10 border-l-accent bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <x-ui.icon name="book-open" class="mt-0.5 size-5 shrink-0 text-accent" />
                        <div>
                            <p data-test="verset" class="font-serif text-[13.5px] italic leading-relaxed text-ink">« {{ $parole['verset'] }} » — {{ $parole['reference'] }}</p>
                            @if (filled($parole['intention']))
                                <p data-test="intention" class="mt-2 text-xs leading-relaxed text-[#5B677A]">Intention du jour : {{ \Illuminate\Support\Str::lcfirst($parole['intention']) }}</p>
                            @endif
                        </div>
                    </div>
                </section>

                @if (! empty($activites))
                    <section>
                        <h2 class="text-[15px] font-bold text-brand">Activité récente</h2>
                        <div class="mb-3 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                        <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            @foreach ($activites as $i => $a)
                                <div class="flex gap-3 {{ $i < count($activites) - 1 ? 'pb-3' : '' }}">
                                    <div class="flex flex-col items-center">
                                        <span class="mt-1 size-2 shrink-0 rounded-full" style="background: {{ $a['dot'] }}"></span>
                                        @if ($i < count($activites) - 1)
                                            <span class="mt-1 w-0.5 flex-1 bg-[#EDF0F5]"></span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-[12.5px] leading-snug text-ink">{{ $a['texte'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-[#5B677A]">{{ $a['quand'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>

        {{-- CathoImpact : invitation discrète, masquable (choix mémorisé dans ce navigateur). --}}
        <div wire:ignore>
            <aside data-test="carte-cathoimpact" style="display: none"
                x-data="{ masque: (() => { try { return localStorage.getItem('rejcc-cathoimpact-masque') === '1' } catch (e) { return false } })() }"
                x-show="! masque"
                class="flex flex-wrap items-center gap-3 rounded-[16px] border border-brand/10 bg-white/70 px-4 py-3.5 sm:px-5">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent"><x-ui.icon name="sparkles" class="size-4" /></span>
                <p class="min-w-[180px] flex-1 text-[13px] text-brand"><span class="font-serif italic">Votre parcours peut inspirer :</span> partagez-le avec CathoImpact.</p>
                <a href="{{ url('/cathoimpact/questionnaire') }}" data-test="cathoimpact-partager" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Partager mon parcours</a>
                <button type="button" data-test="cathoimpact-masquer" aria-label="Masquer cette invitation" title="Masquer"
                    x-on:click="masque = true; try { localStorage.setItem('rejcc-cathoimpact-masque', '1') } catch (e) {}"
                    class="flex size-8 shrink-0 items-center justify-center rounded-full text-[#5B677A] hover:bg-cloud hover:text-brand">
                    <x-ui.icon name="x" class="size-4" />
                </button>
            </aside>
        </div>
    </div>
</div>
