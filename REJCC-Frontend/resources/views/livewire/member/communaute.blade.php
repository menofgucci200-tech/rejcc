@php
    use App\Support\Texte;
    use Illuminate\Support\Carbon;

    $annonces = collect($salons)->where('type', 'annonces')->values();
    $discussions = collect($salons)->where('type', '!=', 'annonces')->values();
    $estAnnonces = ($salon['type'] ?? null) === 'annonces';
    $moderateur = (bool) ($moi['moderateur'] ?? false);
    $quand = function (?string $date) {
        if (! $date) {
            return '';
        }
        $d = Carbon::parse($date)->setTimezone(config('app.timezone'))->locale('fr');

        return $d->isToday() ? $d->format('H:i') : ($d->isYesterday() ? 'Hier' : ($d->gt(now()->subDays(6)->startOfDay()) ? $d->isoFormat('ddd') : $d->format('d/m/y')));
    };
    $taille = fn (?int $o) => $o ? ($o >= 1048576 ? number_format($o / 1048576, 1, ',', ' ').' Mo' : max(1, round($o / 1024)).' Ko') : '';
    $duree = fn (?int $s) => $s ? intdiv($s, 60).':'.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) : '';
@endphp

<div>
    <x-member-light.topbar title="Communauté" />

    <div class="mx-auto max-w-[1280px] lg:px-8 lg:py-8">
        <div class="mb-5 hidden flex-wrap items-end justify-between gap-3 lg:flex">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Communauté</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <p class="text-[12.5px] text-meta">Le lieu d'échange de tous les membres du REJCC.</p>
        </div>

        @if ($info)
            <p data-test="info-communaute" class="panel-enter mx-3 my-2 inline-flex lg:mx-0 lg:mb-3 lg:mt-0 items-center gap-1.5 rounded-full bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure"><x-ui.icon name="info" class="size-3.5" /> {{ $info }}</p>
        @endif

        {{-- Téléphone : plein écran entre la barre du haut et la barre de navigation du bas. --}}
        <div class="grid h-[calc(100dvh-136px-env(safe-area-inset-bottom,0px))] min-h-[420px] grid-cols-1 overflow-hidden border-y border-brand/10 bg-white lg:h-[calc(100dvh-200px)] lg:min-h-[460px] lg:grid-cols-[320px_1fr] lg:rounded-[18px] lg:border lg:shadow-[0_2px_8px_rgba(3,29,89,.05)]"
            wire:poll.4s.visible="rafraichir">

            {{-- Liste des salons --}}
            <aside class="flex min-h-0 flex-col border-r border-cloud-200 {{ $slug ? 'hidden lg:flex' : 'flex' }}" data-test="liste-salons">
                <div class="shrink-0 border-b border-cloud-200 bg-brand px-4 py-3.5 text-white" style="box-shadow: inset 0 -3px 0 #ac0100">
                    <p class="text-[14px] font-extrabold">Communauté REJCC</p>
                    <p class="text-[11.5px] text-[#8fa3d9]">Tous les membres, sans limite</p>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    @foreach ([['Annonces', $annonces], ['Salons', $discussions]] as [$titre, $liste])
                        @if ($liste->isNotEmpty())
                            @if ($titre !== 'Annonces')
                                <p class="px-4 pb-1 pt-4 text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-meta">{{ $titre }}</p>
                            @endif
                            <ul class="list-none">
                                @foreach ($liste as $s)
                                    @php $nonLus = (int) $s['non_lus']; @endphp
                                    <li wire:key="salon-{{ $s['id'] }}">
                                        <button type="button" wire:click="ouvrir('{{ $s['slug'] }}')" data-test="salon" data-slug="{{ $s['slug'] }}"
                                            class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors {{ $slug === $s['slug'] ? 'bg-cloud' : 'hover:bg-cloud/60' }}">
                                            <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[12px] text-[20px] {{ $s['type'] === 'annonces' ? 'bg-brand text-white' : 'bg-azure/10' }}">{{ $s['emoji'] ?: '💬' }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-baseline justify-between gap-2">
                                                    <span class="flex min-w-0 items-center gap-1.5">
                                                        <span class="truncate text-[13.5px] text-brand {{ $nonLus ? 'font-extrabold' : 'font-semibold' }}">{{ $s['nom'] }}</span>
                                                        @if ($s['notifications'] === 'rien')<span title="En sourdine" class="shrink-0 text-meta"><x-ui.icon name="bell" class="size-3 opacity-60" /></span>@endif
                                                        @if ($s['direct'] ?? null)<span data-test="pastille-direct" title="Discussion vocale en cours : {{ $s['direct'] }}" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-accent px-1.5 text-[9.5px] font-extrabold uppercase leading-4 tracking-[0.05em] text-white"><span class="size-1.5 animate-pulse rounded-full bg-white"></span>En direct</span>@endif
                                                    </span>
                                                    @if ($s['dernier'])
                                                        <span class="shrink-0 text-[11px] {{ $nonLus ? 'font-bold text-accent' : 'text-meta' }}">{{ $quand($s['dernier']['date']) }}</span>
                                                    @endif
                                                </span>
                                                <span class="mt-0.5 flex items-center justify-between gap-2">
                                                    <span class="truncate text-xs {{ $nonLus ? 'font-semibold text-ink' : 'text-meta' }}">
                                                        @if ($s['verrou'])
                                                            <x-ui.icon name="lock" class="inline size-3" /> {{ $s['verrou'] }}
                                                        @elseif ($s['archive'])
                                                            Salon archivé
                                                        @elseif ($s['dernier'])
                                                            @if ($s['type'] !== 'annonces' && $s['dernier']['auteur'])<span class="text-meta">{{ $s['dernier']['moi'] ? 'Vous' : $s['dernier']['auteur'] }} :</span>@endif {{ $s['dernier']['extrait'] }}
                                                        @else
                                                            {{ $s['description'] }}
                                                        @endif
                                                    </span>
                                                    @if ($nonLus)
                                                        <span data-test="non-lus" class="flex min-w-5 shrink-0 items-center justify-center rounded-full bg-accent px-1 text-[10.5px] font-bold text-white">{{ $nonLus > 99 ? '99+' : $nonLus }}</span>
                                                    @endif
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach

                    <p class="px-4 pb-1 pt-4 text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-meta">Aussi sur la plateforme</p>
                    <ul class="list-none pb-3">
                        <li>
                            <a href="{{ route('espace-membre.groupes') }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 text-left hover:bg-cloud/60">
                                <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[12px] bg-cloud text-brand"><x-ui.icon name="network" class="size-5" /></span>
                                <span class="min-w-0"><span class="block text-[13.5px] font-semibold text-brand">Groupes sectoriels</span><span class="block truncate text-xs text-meta">Discussions de vos groupes professionnels</span></span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('espace-membre.messaging') }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 text-left hover:bg-cloud/60">
                                <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[12px] bg-cloud text-brand"><x-ui.icon name="message-circle" class="size-5" /></span>
                                <span class="min-w-0"><span class="block text-[13.5px] font-semibold text-brand">Messages privés</span><span class="block truncate text-xs text-meta">Entre membres, sans numéro de téléphone</span></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            {{-- Salon ouvert --}}
            <section class="flex min-h-0 flex-col bg-[#F7F8FB] {{ $slug ? 'flex' : 'hidden lg:flex' }}">
                @if (! $slug)
                    <div class="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-brand text-[26px] text-white">🤝</span>
                        <p class="max-w-sm text-[15px] font-bold text-brand">Bienvenue dans la Communauté REJCC</p>
                        <p class="max-w-sm text-[13px] leading-relaxed text-meta">Les annonces du réseau et les salons de discussion de tous les membres, réunis au même endroit. Choisissez un salon pour commencer.</p>
                    </div>
                @elseif ($erreur || ! $salon)
                    <div data-test="salon-verrouille" class="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center">
                        <x-ui.icon name="lock" class="size-8 text-meta" />
                        <p class="max-w-sm text-sm text-meta">{{ $erreur ?? 'Ce salon est indisponible.' }}</p>
                        @if ($erreurCode === 'subscription_required')
                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">M'abonner</a>
                        @endif
                        <button type="button" wire:click="fermer" class="text-[12.5px] font-semibold text-azure hover:underline">Retour aux salons</button>
                    </div>
                @else
                    {{-- En-tête du salon --}}
                    <div class="relative flex shrink-0 items-center gap-3 border-b border-cloud-200 bg-white px-3 py-3 sm:px-[18px]">
                        <button type="button" wire:click="fermer" class="icon-btn rounded-lg p-1 text-meta lg:hidden" aria-label="Retour aux salons">
                            <x-ui.icon name="arrow-left" class="size-[18px]" />
                        </button>
                        <span class="flex size-[38px] shrink-0 items-center justify-center rounded-[11px] text-[18px] {{ $estAnnonces ? 'bg-brand text-white' : 'bg-azure/10' }}">{{ $salon['emoji'] ?: '💬' }}</span>
                        <span class="min-w-0 flex-1">
                            <span data-test="salon-nom" class="block truncate text-sm font-bold text-brand">{{ $salon['nom'] }}</span>
                            <span class="flex flex-wrap items-center gap-x-2 text-[11.5px] text-meta">
                                @if ($salon['lecture_seule'] && ! $estAnnonces)<span class="font-semibold text-[#8A5A00]">Lecture seule</span>@endif
                                @if ($salon['mode_lent_secondes'] > 0)<span class="font-semibold text-[#8A5A00]">Mode lent · {{ $salon['mode_lent_secondes'] >= 60 ? intdiv($salon['mode_lent_secondes'], 60).' min' : $salon['mode_lent_secondes'].' s' }}</span>@endif
                                <span class="truncate">{{ $salon['description'] }}</span>
                            </span>
                        </span>
                        <button type="button" wire:click="basculerRecherche" data-test="rechercher-salon" aria-label="Rechercher dans le salon" title="Rechercher dans le salon"
                            class="icon-btn flex size-9 items-center justify-center rounded-[10px] {{ $rechercheOuverte ? 'bg-cloud text-brand' : 'text-meta hover:bg-cloud hover:text-brand' }}"><x-ui.icon name="search" class="size-[17px]" /></button>
                        <div class="relative" x-data="{ ouvert: false }" x-on:click.outside="ouvert = false" x-on:keydown.escape.window="ouvert = false">
                            <button type="button" x-on:click="ouvert = ! ouvert" data-test="menu-salon" aria-label="Options du salon" class="icon-btn flex size-9 items-center justify-center rounded-[10px] text-meta hover:bg-cloud hover:text-brand"><x-ui.icon name="more-horizontal" class="size-[18px]" /></button>
                            <div x-show="ouvert" x-transition.origin.top.right style="display: none" class="absolute right-0 z-30 mt-1 w-[250px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1.5 text-[13px] shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
                                <p class="px-4 pb-1 pt-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-meta">Notifications</p>
                                @foreach (['tout' => 'Tous les messages', 'mentions' => 'Mentions et réponses', 'rien' => 'Sourdine'] as $niveau => $libelle)
                                    <button type="button" wire:click="reglerNotifications('{{ $niveau }}')" x-on:click="ouvert = false" data-test="notif-{{ $niveau }}"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left font-semibold text-brand hover:bg-cloud">
                                        {{ $libelle }} @if (($moi['notifications'] ?? '') === $niveau)<x-ui.icon name="check" class="size-4 text-vert" />@endif
                                    </button>
                                @endforeach
                                @if ($salon['regles'])
                                    <div class="my-1 border-t border-cloud-200"></div>
                                    <button type="button" x-on:click="ouvert = false; $dispatch('voir-regles')" class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold text-brand hover:bg-cloud"><x-ui.icon name="list-checks" class="size-4 text-meta" /> Règles du salon</button>
                                @endif
                                @if (($moi['peut_lancer_vocal'] ?? false) && ! $direct)
                                    <div class="my-1 border-t border-cloud-200"></div>
                                    <button type="button" wire:click="ouvrirDirect" x-on:click="ouvert = false" data-test="lancer-direct" class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold text-brand hover:bg-cloud"><x-ui.icon name="mic" class="size-4 text-meta" /> Lancer une discussion vocale</button>
                                @endif
                                @if ($moderateur && ! $estAnnonces)
                                    <div class="my-1 border-t border-cloud-200"></div>
                                    <p class="px-4 pb-1 pt-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-meta">Modération</p>
                                    <button type="button" wire:click="basculerLectureSeule" x-on:click="ouvert = false" data-test="lecture-seule" class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold text-brand hover:bg-cloud">
                                        <x-ui.icon name="lock" class="size-4 text-meta" /> {{ $salon['lecture_seule'] ? 'Rouvrir le salon' : 'Passer en lecture seule' }}
                                    </button>
                                    <p class="px-4 pb-0.5 pt-1.5 text-[11.5px] font-semibold text-meta">Mode lent</p>
                                    <div class="flex flex-wrap gap-1.5 px-4 pb-2">
                                        @foreach ([0 => 'Non', 30 => '30 s', 60 => '1 min', 300 => '5 min', 900 => '15 min'] as $sec => $lib)
                                            <button type="button" wire:click="modeLent({{ $sec }})" x-on:click="ouvert = false" data-test="mode-lent-{{ $sec }}"
                                                class="rounded-full border px-2.5 py-1 text-[11.5px] font-bold {{ (int) $salon['mode_lent_secondes'] === $sec ? 'border-brand bg-brand text-white' : 'border-brand/15 text-brand hover:bg-cloud' }}">{{ $lib }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Discussion vocale en cours --}}
                    @if ($direct)
                        <div class="flex shrink-0 items-center gap-3 border-b border-accent/20 bg-accent/5 px-3 py-2 sm:px-[18px]" data-test="bandeau-direct">
                            <span class="relative flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-white"><x-ui.icon name="mic" class="size-4" /><span class="absolute -right-0.5 -top-0.5 size-2.5 animate-pulse rounded-full border-2 border-white bg-accent"></span></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[10.5px] font-extrabold uppercase tracking-[0.1em] text-accent">En direct</span>
                                <span class="block truncate text-[13px] font-semibold text-brand">{{ $direct['titre'] }}</span>
                            </span>
                            @if ($direct['lien'] ?? null)
                                <a href="{{ $direct['lien'] }}" target="_blank" rel="noopener" data-test="rejoindre-direct" class="btn-tap shrink-0 rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600">Rejoindre</a>
                            @endif
                            @if ($direct['peut_terminer'] ?? false)
                                <button type="button" wire:click="terminerDirect({{ $direct['id'] }})" wire:confirm="Terminer la discussion vocale pour tout le monde ?" data-test="terminer-direct" class="shrink-0 rounded-full px-2.5 py-1.5 text-[12px] font-semibold text-meta hover:bg-white">Terminer</button>
                            @endif
                        </div>
                    @endif

                    {{-- Sujets du salon --}}
                    @if ($salon['sujets_actives'] ?? false)
                        @php $sujetCourant = ctype_digit($sujet) ? collect($sujets)->firstWhere('id', (int) $sujet) : null; @endphp
                        <div class="shrink-0 border-b border-cloud-200 bg-white" data-test="barre-sujets">
                            <div class="flex items-center gap-1.5 overflow-x-auto px-3 py-2 [scrollbar-width:none] sm:px-[18px]">
                                @foreach (array_merge([['cle' => '', 'libelle' => 'Tout', 'ferme' => false, 'n' => null], ['cle' => 'general', 'libelle' => '💬 Général', 'ferme' => false, 'n' => null]],
                                    array_map(fn ($t) => ['cle' => (string) $t['id'], 'libelle' => trim(($t['emoji'] ?? '').' '.$t['titre']), 'ferme' => $t['ferme'], 'n' => $t['messages']], $sujets)) as $chip)
                                    <button type="button" wire:click="choisirSujet('{{ $chip['cle'] }}')" wire:key="sujet-{{ $chip['cle'] ?: 'tout' }}" data-test="sujet" data-sujet="{{ $chip['cle'] }}"
                                        class="flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold transition-colors {{ $sujet === $chip['cle'] ? 'bg-brand text-white' : 'border border-brand/12 text-brand hover:bg-cloud' }} {{ $chip['ferme'] ? 'opacity-70' : '' }}">
                                        @if ($chip['ferme'])<x-ui.icon name="lock" class="size-3" />@endif
                                        {{ $chip['libelle'] }}
                                        @if ($chip['n'])<span class="text-[10.5px] font-semibold {{ $sujet === $chip['cle'] ? 'text-white/70' : 'text-meta' }}">{{ $chip['n'] }}</span>@endif
                                    </button>
                                @endforeach
                                @if ($moi['peut_creer_sujet'] ?? false)
                                    <button type="button" wire:click="ouvrirNouveauSujet" data-test="nouveau-sujet" class="shrink-0 whitespace-nowrap rounded-full border border-dashed border-azure/50 px-3 py-1.5 text-[12px] font-bold text-azure hover:bg-azure/5">+ Nouveau sujet</button>
                                @endif
                            </div>
                            @if ($sujetCourant && ($sujetCourant['ferme'] || $moderateur))
                                <div class="flex items-center justify-between gap-2 border-t border-cloud-200 px-3 py-1.5 text-[11.5px] sm:px-[18px]">
                                    <span class="text-meta">{{ $sujetCourant['ferme'] ? 'Sujet fermé : il reste consultable, mais n\'accepte plus de messages.' : 'Sujet ouvert à tous les membres du salon.' }}</span>
                                    @if ($moderateur)
                                        <button type="button" wire:click="basculerSujet({{ $sujetCourant['id'] }}, {{ $sujetCourant['ferme'] ? 'false' : 'true' }})" data-test="basculer-sujet" class="shrink-0 font-bold text-azure hover:underline">{{ $sujetCourant['ferme'] ? 'Rouvrir le sujet' : 'Fermer le sujet' }}</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Recherche dans le salon --}}
                    @if ($rechercheOuverte)
                        <div class="shrink-0 border-b border-cloud-200 bg-white px-3 py-2.5 sm:px-[18px]" data-test="panneau-recherche">
                            <div class="relative">
                                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-meta" />
                                <input type="search" wire:model.live.debounce.350ms="recherche" data-test="champ-recherche" placeholder="Rechercher dans « {{ $salon['nom'] }} »…" autofocus
                                    class="w-full rounded-[10px] border border-brand/10 bg-cloud py-2 pl-9 pr-3 text-[13px] text-ink outline-none focus:border-azure">
                            </div>
                            @if (mb_strlen(trim($recherche)) >= 2)
                                <ul class="mt-2 max-h-[260px] overflow-y-auto">
                                    @forelse ($resultatsRecherche as $res)
                                        <li wire:key="res-{{ $res['id'] }}">
                                            <button type="button" wire:click="allerA({{ $res['id'] }})" data-test="resultat-recherche" class="block w-full rounded-[10px] px-3 py-2 text-left hover:bg-cloud">
                                                <span class="flex items-baseline justify-between gap-2 text-[12px]"><span class="font-bold text-brand">{{ $res['auteur'] }}</span><span class="shrink-0 text-meta">{{ $quand($res['date']) }}</span></span>
                                                <span class="line-clamp-2 text-[12.5px] text-ink">{{ $res['extrait'] }}</span>
                                            </button>
                                        </li>
                                    @empty
                                        <li class="px-3 py-3 text-[12.5px] text-meta">Aucun message ne contient « {{ trim($recherche) }} ».</li>
                                    @endforelse
                                </ul>
                            @endif
                        </div>
                    @endif

                    {{-- Messages épinglés --}}
                    @if (! empty($epingles))
                        <div class="relative shrink-0 border-b border-cloud-200 bg-white" x-data="{ tous: false }" x-on:click.outside="tous = false" data-test="barre-epingles">
                            <div class="flex items-center gap-2 px-3 py-2 sm:px-[18px]">
                                <span class="text-[14px]">📌</span>
                                <button type="button" wire:click="allerA({{ $epingles[0]['id'] }})" class="min-w-0 flex-1 text-left">
                                    <span class="block text-[10.5px] font-extrabold uppercase tracking-[0.1em] text-azure">Épinglé{{ count($epingles) > 1 ? ' · '.count($epingles) : '' }}</span>
                                    <span class="block truncate text-[12.5px] text-ink">{{ $epingles[0]['extrait'] }}</span>
                                </button>
                                @if (count($epingles) > 1)
                                    <button type="button" x-on:click="tous = ! tous" class="shrink-0 rounded-full px-2 py-1 text-[11.5px] font-bold text-azure hover:bg-cloud">Tout voir</button>
                                @endif
                            </div>
                            <ul x-show="tous" x-transition style="display: none" class="absolute inset-x-0 top-full z-20 border-b border-cloud-200 bg-white py-1 shadow-lg">
                                @foreach ($epingles as $ep)
                                    <li><button type="button" wire:click="allerA({{ $ep['id'] }})" x-on:click="tous = false" class="block w-full px-[18px] py-2 text-left hover:bg-cloud">
                                        <span class="block text-[11.5px] font-bold text-brand">{{ $ep['auteur'] }}</span>
                                        <span class="block truncate text-[12.5px] text-ink">{{ $ep['extrait'] }}</span>
                                    </button></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Fil --}}
                    <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-6" data-test="fil"
                        x-data="{ basProche: true, bas() { this.$el.scrollTop = this.$el.scrollHeight } }"
                        x-init="$nextTick(() => bas())"
                        x-on:scroll.throttle.150ms="basProche = ($el.scrollHeight - $el.scrollTop - $el.clientHeight) < 120"
                        x-on:fil-bas.window="setTimeout(() => bas(), 40)"
                        x-on:fil-cible.window="setTimeout(() => { const c = $el.querySelector('[data-id=\'' + $event.detail.id + '\']'); c && c.scrollIntoView({ block: 'center', behavior: 'smooth' }) }, 80)"
                        x-on:fil-nouveaux.window="basProche && setTimeout(() => bas(), 40)">

                        @if ($plusAnciens)
                            <div class="mb-3 text-center">
                                <button type="button" wire:click="chargerPlusAnciens" class="rounded-full border border-brand/10 bg-white px-3.5 py-1.5 text-[12px] font-semibold text-azure shadow-sm hover:bg-cloud">Afficher les messages précédents</button>
                            </div>
                        @endif

                        @if (empty($messages))
                            <div class="flex h-full flex-col items-center justify-center gap-2 py-10 text-center">
                                <span class="text-[28px]">{{ $salon['emoji'] ?: '💬' }}</span>
                                <p class="text-[13.5px] font-semibold text-brand">{{ $estAnnonces ? 'Aucune annonce pour le moment.' : 'Soyez le premier à écrire dans ce salon.' }}</p>
                                <p class="max-w-sm text-[12.5px] text-meta">{{ $salon['description'] }}</p>
                            </div>
                        @endif

                        @php $jourPrecedent = null; $auteurPrecedent = null; $heurePrecedente = null; @endphp
                        @foreach ($messages as $m)
                            @php
                                $d = Carbon::parse($m['date'])->setTimezone(config('app.timezone'))->locale('fr');
                                $jour = $d->toDateString();
                                $nouveauJour = $jour !== $jourPrecedent;
                                $suite = ! $nouveauJour && $auteurPrecedent === ($m['auteur']['id'] ?? null) && $heurePrecedente && $d->diffInMinutes($heurePrecedente) < 5 && $m['type'] !== 'supprime';
                                $jourPrecedent = $jour; $auteurPrecedent = $m['auteur']['id'] ?? null; $heurePrecedente = $d;
                                $auteur = $m['auteur'] ?? [];
                                $nom = trim(($auteur['prenom'] ?? '').' '.($auteur['nom'] ?? '')) ?: 'Membre';
                                $badge = match (true) {
                                    ($auteur['role'] ?? null) === 'admin' => ['Équipe REJCC', 'bg-brand text-white'],
                                    (bool) ($auteur['referent'] ?? false) => ['Référent', 'bg-azure text-white'],
                                    ($auteur['role'] ?? null) === 'mentor' => ['Mentor', 'bg-accent text-white'],
                                    default => null,
                                };
                                $lienFichier = $m['fichier'] ? route('espace-membre.communaute.fichier', ['id' => $m['id']]) : null;
                            @endphp

                            @if ($nouveauJour)
                                <div class="my-3 flex justify-center" wire:key="jour-{{ $jour }}">
                                    <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold text-meta shadow-sm">{{ $d->isToday() ? "Aujourd'hui" : ($d->isYesterday() ? 'Hier' : ucfirst($d->isoFormat('dddd D MMMM'))) }}</span>
                                </div>
                            @endif

                            <div wire:key="m-{{ $m['id'] }}-{{ $m['type'] }}" data-test="message" data-id="{{ $m['id'] }}"
                                class="group flex gap-2.5 rounded-[18px] transition-colors {{ $m['moi'] && ! $estAnnonces ? 'flex-row-reverse' : '' }} {{ $suite ? 'mt-1' : 'mt-3' }} {{ $cible === $m['id'] ? 'bg-[#FBF1DC] p-1.5' : '' }}"
                                x-data="{ menu: false, reac: false }" x-on:click.outside="menu = false; reac = false">

                                @if (! ($m['moi'] && ! $estAnnonces))
                                    <div class="w-[34px] shrink-0">
                                        @if (! $suite)<x-messagerie.avatar :personne="$auteur" taille="size-[34px]" texte="text-[11px]" />@endif
                                    </div>
                                @endif

                                <div class="relative flex min-w-0 max-w-[min(560px,82%)] flex-col {{ $m['moi'] && ! $estAnnonces ? 'items-end' : 'items-start' }}">
                                    @if (! $suite && ! ($m['moi'] && ! $estAnnonces))
                                        <p class="mb-0.5 flex items-center gap-1.5 px-1 text-[12px] font-bold text-brand">
                                            {{ $nom }}
                                            @if ($badge)<span class="rounded-full px-1.5 text-[9px] font-bold uppercase leading-4 tracking-[0.05em] {{ $badge[1] }}">{{ $badge[0] }}</span>@endif
                                        </p>
                                    @endif

                                    @if (($m['sujet'] ?? null) && $sujet === '' && $m['type'] !== 'supprime')
                                        <button type="button" wire:click="choisirSujet('{{ $m['sujet']['id'] }}')" data-test="etiquette-sujet" class="mb-0.5 max-w-full truncate rounded-full bg-azure/10 px-2 py-px text-[10.5px] font-bold text-azure hover:bg-azure/20"># {{ trim(($m['sujet']['emoji'] ?? '').' '.$m['sujet']['titre']) }}</button>
                                    @endif
                                    @if ($m['type'] === 'supprime')
                                        <div class="rounded-[14px] border border-dashed border-brand/15 px-3.5 py-2 text-[12.5px] italic text-meta">
                                            {{ $m['supprime_par_equipe'] ? 'Message retiré par la modération' : 'Message supprimé' }}
                                        </div>
                                    @else
                                        @php
                                            $bulle = $estAnnonces
                                                ? 'border border-brand/10 bg-white shadow-sm border-l-[3px] border-l-accent'
                                                : ($m['moi'] ? 'bg-brand text-white' : 'bg-white text-ink shadow-[0_1px_2px_rgba(3,29,89,.08)]');
                                            $sombre = $m['moi'] && ! $estAnnonces;
                                        @endphp
                                        <div class="relative rounded-[16px] px-3.5 py-2.5 text-[13.5px] leading-relaxed {{ $bulle }}">
                                            @if ($m['reponse_a'])
                                                <div class="mb-1.5 rounded-[10px] border-l-[3px] px-2.5 py-1.5 text-[12px] {{ $sombre ? 'border-white/60 bg-white/10 text-white/85' : 'border-azure bg-azure/5 text-meta' }}">
                                                    <span class="block font-bold {{ $sombre ? 'text-white' : 'text-azure' }}">{{ $m['reponse_a']['auteur'] }}</span>
                                                    <span class="line-clamp-2">{{ $m['reponse_a']['extrait'] }}</span>
                                                </div>
                                            @endif

                                            @if ($m['type'] === 'image' && $lienFichier)
                                                <a href="{{ $lienFichier }}" target="_blank" rel="noopener" class="mb-1 block overflow-hidden rounded-[12px]">
                                                    <img src="{{ $lienFichier }}" alt="Photo partagée par {{ $nom }}" loading="lazy" class="max-h-[320px] w-auto max-w-full object-cover">
                                                </a>
                                            @elseif ($m['type'] === 'fichier' && $lienFichier)
                                                <a href="{{ $lienFichier }}" target="_blank" rel="noopener" data-test="piece-pdf" class="mb-1 flex items-center gap-3 rounded-[12px] px-3 py-2.5 {{ $sombre ? 'bg-white/10' : 'bg-cloud' }}">
                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent text-[10px] font-extrabold text-white">PDF</span>
                                                    <span class="min-w-0">
                                                        <span class="block truncate text-[13px] font-semibold">{{ $m['fichier']['nom'] }}</span>
                                                        <span class="block text-[11.5px] {{ $sombre ? 'text-white/70' : 'text-meta' }}">{{ $taille($m['fichier']['taille'] ?? null) }} · Ouvrir</span>
                                                    </span>
                                                </a>
                                            @elseif ($m['type'] === 'vocal' && $lienFichier)
                                                <div class="flex items-center gap-2" data-test="note-vocale">
                                                    <x-ui.icon name="mic" class="size-4 shrink-0 {{ $sombre ? 'text-white/80' : 'text-azure' }}" />
                                                    <audio controls preload="none" src="{{ $lienFichier }}" class="h-9 w-[230px] max-w-full"></audio>
                                                    <span class="text-[11px] {{ $sombre ? 'text-white/70' : 'text-meta' }}">{{ $duree($m['fichier']['duree'] ?? null) }}</span>
                                                </div>
                                            @elseif ($m['type'] === 'sondage' && $m['sondage'])
                                                @php $sd = $m['sondage']; @endphp
                                                <div class="min-w-[240px] max-w-full sm:min-w-[300px]" data-test="sondage">
                                                    <p class="mb-0.5 flex items-center gap-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.1em] {{ $sombre ? 'text-white/70' : 'text-azure' }}">📊 Sondage{{ $sd['choix_multiple'] ? ' · plusieurs choix' : '' }}{{ $sd['anonyme'] ? ' · anonyme' : '' }}</p>
                                                    <p class="mb-2 text-[14px] font-bold">{{ $sd['question'] }}</p>
                                                    <div class="grid gap-1.5">
                                                        @foreach ($sd['options'] as $opt)
                                                            <button type="button" @if (! $sd['clos']) wire:click="voter({{ $m['id'] }}, {{ $sd['id'] }}, {{ $opt['id'] }})" @else disabled @endif data-test="option-sondage"
                                                                class="relative overflow-hidden rounded-[10px] border px-3 py-2 text-left text-[13px] {{ $sombre ? 'border-white/25' : 'border-brand/10' }} {{ $opt['moi'] ? ($sombre ? 'ring-2 ring-white/70' : 'ring-2 ring-azure') : '' }} {{ $sd['clos'] ? 'cursor-default' : ($sombre ? 'hover:bg-white/10' : 'hover:bg-cloud') }}">
                                                                @if ($sd['a_vote'] || $sd['clos'])
                                                                    <span class="absolute inset-y-0 left-0 {{ $sombre ? 'bg-white/15' : 'bg-azure/12' }}" style="width: {{ $opt['pourcentage'] }}%"></span>
                                                                @endif
                                                                <span class="relative flex items-center justify-between gap-3">
                                                                    <span class="flex items-center gap-2 font-semibold">
                                                                        <span class="flex size-4 shrink-0 items-center justify-center {{ $sd['choix_multiple'] ? 'rounded-[4px]' : 'rounded-full' }} border {{ $opt['moi'] ? ($sombre ? 'border-white bg-white' : 'border-azure bg-azure') : ($sombre ? 'border-white/60' : 'border-brand/30') }}">@if ($opt['moi'])<x-ui.icon name="check" class="size-3 {{ $sombre ? 'text-brand' : 'text-white' }}" />@endif</span>
                                                                        {{ $opt['libelle'] }}
                                                                    </span>
                                                                    @if ($sd['a_vote'] || $sd['clos'])<span class="shrink-0 text-[12px] font-bold tabular-nums">{{ $opt['pourcentage'] }} %</span>@endif
                                                                </span>
                                                                @if (($sd['a_vote'] || $sd['clos']) && ! empty($opt['votants']))
                                                                    <span class="relative mt-0.5 block truncate pl-6 text-[11px] {{ $sombre ? 'text-white/70' : 'text-meta' }}">{{ implode(', ', $opt['votants']) }}{{ $opt['votes'] > count($opt['votants']) ? ' et '.($opt['votes'] - count($opt['votants'])).' autre(s)' : '' }}</span>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    <p class="mt-1.5 flex flex-wrap items-center gap-x-2 text-[11.5px] {{ $sombre ? 'text-white/70' : 'text-meta' }}">
                                                        <span>{{ $sd['votants'] }} votant{{ $sd['votants'] > 1 ? 's' : '' }}</span>
                                                        @if ($sd['clos'])<span class="font-bold">· Sondage clos</span>
                                                        @elseif ($sd['fin_at'])<span>· clôture {{ \Illuminate\Support\Carbon::parse($sd['fin_at'])->setTimezone(config('app.timezone'))->locale('fr')->isoFormat('ddd D MMM [à] HH[h]mm') }}</span>@endif
                                                        @if ($sd['peut_clore'])<button type="button" wire:click="clore({{ $m['id'] }}, {{ $sd['id'] }})" wire:confirm="Clore ce sondage ? Plus personne ne pourra voter." data-test="clore-sondage" class="font-bold underline">Clore</button>@endif
                                                    </p>
                                                </div>
                                            @elseif ($m['type'] === 'direct' && $m['direct'])
                                                @php $dv = $m['direct']; $tz = config('app.timezone'); @endphp
                                                <div class="w-[min(320px,100%)]" data-test="carte-direct">
                                                    <p class="flex items-center gap-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.1em] {{ $dv['en_cours'] ? ($sombre ? 'text-white' : 'text-accent') : ($sombre ? 'text-white/70' : 'text-meta') }}">
                                                        @if ($dv['en_cours'])<span class="size-2 animate-pulse rounded-full {{ $sombre ? 'bg-white' : 'bg-accent' }}"></span> Discussion vocale en direct @else 🎙️ Discussion vocale terminée @endif
                                                    </p>
                                                    <p class="mt-0.5 text-[14px] font-bold leading-snug">{{ $dv['titre'] }}</p>
                                                    <p class="mt-0.5 text-[11.5px] {{ $sombre ? 'text-white/70' : 'text-meta' }}">
                                                        {{ $dv['en_cours'] ? 'Commencée à '.Carbon::parse($dv['debut'])->setTimezone($tz)->format('H\hi') : 'De '.Carbon::parse($dv['debut'])->setTimezone($tz)->format('H\hi').' à '.Carbon::parse($dv['fin'])->setTimezone($tz)->format('H\hi') }}
                                                        @if ($dv['en_cours'] && $dv['fournisseur'] === 'meet') · Google Meet : l'équipe vous admet en quelques secondes @endif
                                                    </p>
                                                    @if ($dv['en_cours'])
                                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                                            <a href="{{ $dv['lien'] }}" target="_blank" rel="noopener" data-test="rejoindre-carte" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-[12px] font-bold {{ $sombre ? 'bg-white text-brand' : 'bg-accent text-white hover:bg-accent-600' }}"><x-ui.icon name="mic" class="size-3.5" /> Rejoindre</a>
                                                            @if ($dv['peut_terminer'])
                                                                <button type="button" wire:click="terminerDirect({{ $dv['id'] }})" wire:confirm="Terminer la discussion vocale pour tout le monde ?" class="text-[12px] font-semibold underline {{ $sombre ? 'text-white/80' : 'text-meta' }}">Terminer</button>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif ($m['type'] === 'carte' && $m['carte'])
                                                @php $ca = $m['carte']; @endphp
                                                @if ($ca['indisponible'] ?? false)
                                                    <p class="mb-1 rounded-[12px] px-3 py-2.5 text-[12.5px] italic {{ $sombre ? 'bg-white/10 text-white/75' : 'bg-cloud text-meta' }}">{{ $ca['type_libelle'] }} n'est plus disponible.</p>
                                                @else
                                                    <a href="{{ $ca['lien'] }}" wire:navigate data-test="carte-partage" class="mb-1 block w-[min(320px,100%)] overflow-hidden rounded-[14px] border text-left {{ $sombre ? 'border-white/20 bg-white/10' : 'border-brand/10 bg-white' }}">
                                                        @if ($ca['image'])<img src="{{ $ca['image'] }}" alt="" loading="lazy" class="h-[120px] w-full object-cover">@endif
                                                        <span class="block px-3.5 py-3">
                                                            <span class="flex items-center gap-1.5 text-[10.5px] font-extrabold uppercase tracking-[0.1em] {{ $sombre ? 'text-white/70' : 'text-accent' }}">{{ $ca['type_libelle'] }}@if ($ca['etat'])<span class="rounded-full bg-[#FBF1DC] px-1.5 text-[9.5px] text-[#8A5A00]">{{ $ca['etat'] }}</span>@endif</span>
                                                            <span class="mt-0.5 block text-[14px] font-bold leading-snug {{ $sombre ? 'text-white' : 'text-brand' }}">{{ $ca['titre'] }}</span>
                                                            @if ($ca['detail'])<span class="mt-0.5 block text-[12px] {{ $sombre ? 'text-white/75' : 'text-meta' }}">{{ $ca['detail'] }}</span>@endif
                                                            <span class="mt-2 inline-flex items-center gap-1 rounded-full px-3 py-1 text-[12px] font-bold {{ $sombre ? 'bg-white text-brand' : 'bg-brand text-white' }}">{{ $ca['action'] }} <x-ui.icon name="arrow-right" class="size-3.5" /></span>
                                                        </span>
                                                    </a>
                                                @endif
                                            @endif

                                            @if ($editionId === $m['id'])
                                                <div class="min-w-[240px]">
                                                    <textarea wire:model="editionTexte" rows="3" data-test="edition-texte" class="w-full rounded-[10px] border border-brand/15 bg-white p-2 text-[13px] text-ink outline-none focus:border-azure"></textarea>
                                                    <div class="mt-1.5 flex justify-end gap-2">
                                                        <button type="button" wire:click="annulerEdition" class="rounded-full px-3 py-1 text-[12px] font-semibold {{ $sombre ? 'text-white/80' : 'text-meta' }}">Annuler</button>
                                                        <button type="button" wire:click="enregistrerEdition" data-test="edition-enregistrer" class="rounded-full bg-accent px-3 py-1 text-[12px] font-bold text-white">Enregistrer</button>
                                                    </div>
                                                </div>
                                            @elseif ($m['body'] && $m['type'] !== 'direct')
                                                <div data-test="message-texte" class="break-words [overflow-wrap:anywhere]">{!! Texte::message($m['body'], $sombre ? 'underline underline-offset-2 text-white' : 'underline underline-offset-2 text-azure', $sombre ? 'font-bold text-white' : 'font-bold text-azure') !!}</div>
                                            @endif

                                            <span class="mt-0.5 block text-right text-[10.5px] {{ $sombre ? 'text-white/65' : 'text-meta' }}">
                                                @if ($m['epingle'] ?? false)📌 @endif @if ($m['edite'])modifié · @endif{{ $d->format('H:i') }}
                                            </span>

                                            {{-- Actions : réagir et menu --}}
                                            <div class="absolute -top-3 {{ $m['moi'] && ! $estAnnonces ? 'left-0 -translate-x-[calc(100%+4px)]' : 'right-0 translate-x-[calc(100%+4px)]' }} flex items-center gap-0.5 rounded-full border border-brand/10 bg-white p-0.5 opacity-0 shadow-sm transition-opacity group-hover:opacity-100 focus-within:opacity-100" :class="(menu || reac) && '!opacity-100'">
                                                <button type="button" x-on:click="reac = ! reac; menu = false" data-test="reagir" aria-label="Réagir" class="flex size-7 items-center justify-center rounded-full text-[14px] hover:bg-cloud">🙂</button>
                                                <button type="button" x-on:click="menu = ! menu; reac = false" data-test="menu-message" aria-label="Actions sur le message" class="flex size-7 items-center justify-center rounded-full text-meta hover:bg-cloud"><x-ui.icon name="more-horizontal" class="size-4" /></button>
                                            </div>

                                            <div x-show="reac" x-transition style="display: none" class="absolute -top-12 z-20 flex gap-0.5 rounded-full border border-brand/10 bg-white p-1 shadow-lg {{ $m['moi'] && ! $estAnnonces ? 'right-0' : 'left-0' }}">
                                                @foreach ($emojis as $e)
                                                    <button type="button" wire:click="reagir({{ $m['id'] }}, '{{ $e }}')" x-on:click="reac = false" data-test="emoji" class="flex size-8 items-center justify-center rounded-full text-[17px] transition-transform hover:scale-125">{{ $e }}</button>
                                                @endforeach
                                            </div>

                                            <div x-show="menu" x-transition style="display: none" class="absolute top-6 z-20 w-[220px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1.5 text-left text-[13px] text-brand shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)] {{ $m['moi'] && ! $estAnnonces ? 'right-full mr-2' : 'left-full ml-2' }}">
                                                @if (! $estAnnonces || $moderateur)
                                                    <button type="button" wire:click="repondre({{ $m['id'] }})" x-on:click="menu = false" data-test="repondre" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold hover:bg-cloud"><x-ui.icon name="send" class="size-4 text-meta" /> Répondre</button>
                                                @endif
                                                @if ($m['peut_epingler'] ?? false)
                                                    <button type="button" wire:click="epingler({{ $m['id'] }}, {{ ($m['epingle'] ?? false) ? 'false' : 'true' }})" x-on:click="menu = false" data-test="epingler" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold hover:bg-cloud"><span class="w-4 text-center">📌</span> {{ ($m['epingle'] ?? false) ? 'Désépingler' : 'Épingler en haut du salon' }}</button>
                                                @endif
                                                @if ($m['peut_modifier'])
                                                    <button type="button" wire:click="commencerEdition({{ $m['id'] }})" x-on:click="menu = false" data-test="modifier" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold hover:bg-cloud"><x-ui.icon name="pencil" class="size-4 text-meta" /> Modifier</button>
                                                @endif
                                                @if ($m['peut_signaler'])
                                                    <button type="button" wire:click="ouvrirSignalement({{ $m['id'] }})" x-on:click="menu = false" data-test="signaler" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold hover:bg-cloud"><x-ui.icon name="alert-circle" class="size-4 text-meta" /> Signaler</button>
                                                @endif
                                                @if ($m['peut_supprimer'])
                                                    <button type="button" wire:click="supprimer({{ $m['id'] }})" wire:confirm="{{ $m['moi'] ? 'Supprimer ce message pour tout le monde ?' : 'Retirer ce message de '.$nom.' du salon ?' }}" x-on:click="menu = false" data-test="supprimer" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold text-accent hover:bg-cloud"><x-ui.icon name="trash-2" class="size-4" /> Supprimer</button>
                                                @endif
                                                @if ($moderateur && ! $m['moi'] && ($auteur['role'] ?? null) !== 'admin' && ! ($auteur['referent'] ?? false) && ($auteur['id'] ?? null))
                                                    <div class="my-1 border-t border-cloud-200"></div>
                                                    <p class="px-4 pb-0.5 pt-1 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-meta">Modération · {{ $auteur['prenom'] ?? '' }}</p>
                                                    @foreach (['1h' => '1 heure', '24h' => '24 heures', '7j' => '7 jours'] as $code => $libelle)
                                                        <button type="button" wire:click="sanctionner({{ $auteur['id'] }}, 'silence', '{{ $code }}')" wire:confirm="Empêcher {{ $nom }} d'écrire pendant {{ $libelle }} ?" x-on:click="menu = false" data-test="silence-{{ $code }}" class="flex w-full items-center gap-2.5 px-4 py-1.5 font-semibold hover:bg-cloud"><x-ui.icon name="clock" class="size-4 text-meta" /> Muet {{ $libelle }}</button>
                                                    @endforeach
                                                    <button type="button" wire:click="sanctionner({{ $auteur['id'] }}, 'exclure')" wire:confirm="Retirer {{ $nom }} de ce salon ? Un administrateur pourra le réintégrer." x-on:click="menu = false" data-test="exclure" class="flex w-full items-center gap-2.5 px-4 py-1.5 font-semibold text-accent hover:bg-cloud"><x-ui.icon name="user-x" class="size-4" /> Exclure du salon</button>
                                                @endif
                                            </div>
                                        </div>

                                        @if (! empty($m['reactions']))
                                            <div class="-mt-1.5 flex flex-wrap gap-1 px-2" data-test="reactions">
                                                @foreach ($m['reactions'] as $r)
                                                    <button type="button" wire:click="reagir({{ $m['id'] }}, '{{ $r['emoji'] }}')"
                                                        class="flex items-center gap-1 rounded-full border px-1.5 py-px text-[12px] shadow-sm {{ $r['moi'] ? 'border-azure/40 bg-azure/10 font-bold text-azure' : 'border-brand/10 bg-white text-meta' }}">{{ $r['emoji'] }} <span class="text-[11px]">{{ $r['nombre'] }}</span></button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Saisie --}}
                    <div class="shrink-0 border-t border-cloud-200 bg-white px-3 py-3 sm:px-[18px]" x-data="{ regles: false }" x-on:voir-regles.window="regles = true">
                        @if ($salon['regles'])
                            <div x-show="regles" x-transition style="display: none" class="mb-3 rounded-[14px] border border-brand/10 bg-cloud/60 p-4 text-[13px]">
                                <div class="mb-1.5 flex items-center justify-between"><p class="font-bold text-brand">Règles du salon</p><button type="button" x-on:click="regles = false" class="text-meta" aria-label="Fermer"><x-ui.icon name="x" class="size-4" /></button></div>
                                <p class="whitespace-pre-line leading-relaxed text-ink">{{ $salon['regles'] }}</p>
                            </div>
                        @endif

                        @if (! ($moi['regles_acceptees'] ?? true) && $salon['regles'] && ! $estAnnonces)
                            <div data-test="regles-a-accepter" class="rounded-[14px] border border-azure/25 bg-azure/5 p-4">
                                <p class="mb-1 text-[13.5px] font-bold text-brand">Avant d'écrire dans « {{ $salon['nom'] }} »</p>
                                <p class="mb-3 whitespace-pre-line text-[12.5px] leading-relaxed text-ink">{{ $salon['regles'] }}</p>
                                <button type="button" wire:click="accepterRegles" data-test="accepter-regles" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">J'accepte les règles</button>
                            </div>
                        @elseif (($sujetCourant['ferme'] ?? false) && ! $moderateur)
                            <p data-test="sujet-ferme" class="flex items-center justify-center gap-2 rounded-[12px] bg-cloud px-4 py-3 text-center text-[12.5px] font-semibold text-meta">
                                <x-ui.icon name="lock" class="size-4 shrink-0" /> Ce sujet est fermé. Écrivez dans un autre sujet ou dans Général.
                            </p>
                        @elseif ($moi['refus_ecriture'] ?? null)
                            <p data-test="refus-ecriture" class="flex items-center justify-center gap-2 rounded-[12px] bg-cloud px-4 py-3 text-center text-[12.5px] font-semibold text-meta">
                                <x-ui.icon name="{{ $estAnnonces ? 'megaphone' : 'lock' }}" class="size-4 shrink-0" /> {{ $moi['refus_ecriture'] }}
                            </p>
                        @else
                            @if ($reponseA)
                                <div class="mb-2 flex items-start gap-2 rounded-[12px] border-l-[3px] border-azure bg-azure/5 px-3 py-2 text-[12.5px]" data-test="reponse-a">
                                    <div class="min-w-0 flex-1"><span class="block font-bold text-azure">Réponse à {{ $reponseA['auteur'] }}</span><span class="block truncate text-meta">{{ $reponseA['extrait'] }}</span></div>
                                    <button type="button" wire:click="annulerReponse" class="text-meta" aria-label="Annuler la réponse"><x-ui.icon name="x" class="size-4" /></button>
                                </div>
                            @endif

                            @if ($photo || $document)
                                <div class="mb-2 flex items-center gap-3 rounded-[12px] bg-cloud p-2.5" data-test="apercu-piece">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Aperçu de la photo" class="size-16 rounded-[10px] object-cover">
                                    @else
                                        <span class="flex size-16 items-center justify-center rounded-[10px] bg-accent text-xs font-extrabold text-white">PDF</span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[12.5px] font-semibold text-brand">{{ ($photo ?? $document)->getClientOriginalName() }}</p>
                                        <input type="text" wire:model="legende" data-test="legende" maxlength="1000" placeholder="Ajouter une légende (facultatif)" class="mt-1 w-full rounded-[8px] border border-brand/10 bg-white px-2.5 py-1.5 text-[12.5px] outline-none focus:border-azure">
                                        @error('photo')<p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $message }}</p>@enderror
                                        @error('document')<p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <button type="button" wire:click="envoyerPiece" wire:loading.attr="disabled" data-test="envoyer-piece" class="btn-tap rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white">Envoyer</button>
                                        <button type="button" wire:click="annulerPiece" class="text-[12px] font-semibold text-meta">Annuler</button>
                                    </div>
                                </div>
                            @endif

                            @if (($moi['attente'] ?? 0) > 0)
                                <p x-data="{ s: {{ (int) $moi['attente'] }} }" x-init="const t = setInterval(() => { s--; if (s <= 0) clearInterval(t) }, 1000)" x-show="s > 0" data-test="attente-mode-lent" class="mb-2 text-center text-[11.5px] font-semibold text-[#8A5A00]">
                                    Mode lent : vous pourrez écrire de nouveau dans <span x-text="s >= 60 ? Math.ceil(s / 60) + ' min' : s + ' s'"></span>.
                                </p>
                            @endif

                            <div class="relative flex items-end gap-2"
                                x-data="{
                                    sugg: [], idx: 0, debut: 0, peutTous: @js((bool) ($moi['peut_mention_tous'] ?? false)),
                                    enreg: false, sec: 0, rec: null, flux: null, morceaux: [], minuteur: null, aEnvoyer: true, envoiVocal: false, erreurVocal: '',
                                    async saisie(e) {
                                        const t = e.target, avant = t.value.slice(0, t.selectionStart);
                                        const m = avant.match(/(?:^|\s)@([\p{L}\-]{0,20})$/u);
                                        if (! m) { this.sugg = []; return }
                                        this.debut = t.selectionStart - m[1].length - 1;
                                        const q = m[1];
                                        let liste = q.length >= 1 ? await $wire.chercherMembres(q) : [];
                                        if (this.peutTous && 'tous'.startsWith(q.toLowerCase())) liste.unshift({ id: 0, nom: 'tous', initiales: '@' });
                                        this.sugg = liste; this.idx = 0;
                                    },
                                    choisir(s) {
                                        const t = this.$refs.saisie, apres = t.value.slice(t.selectionStart), insere = '@' + s.nom + ' ';
                                        t.value = t.value.slice(0, this.debut) + insere + apres;
                                        t.dispatchEvent(new Event('input'));
                                        const pos = this.debut + insere.length; t.focus(); t.setSelectionRange(pos, pos);
                                        if (s.id) $wire.ajouterMention(s.id, s.nom);
                                        this.sugg = [];
                                    },
                                    touche(e) {
                                        if (this.sugg.length) {
                                            if (e.key === 'ArrowDown') { e.preventDefault(); this.idx = (this.idx + 1) % this.sugg.length; return }
                                            if (e.key === 'ArrowUp') { e.preventDefault(); this.idx = (this.idx - 1 + this.sugg.length) % this.sugg.length; return }
                                            if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.choisir(this.sugg[this.idx]); return }
                                            if (e.key === 'Escape') { this.sugg = []; return }
                                        }
                                        if (e.key === 'Enter' && ! e.shiftKey && window.matchMedia('(pointer: fine)').matches) { e.preventDefault(); $wire.envoyer() }
                                    },
                                    async choisirFichier(e) {
                                        const f = e.target.files[0]; e.target.value = '';
                                        if (! f) return;
                                        if (f.type === 'application/pdf') { $wire.upload('document', f); return }
                                        if (! f.type.startsWith('image/')) { $wire.set('info', 'Envoyez une photo (JPG, PNG, WebP) ou un document PDF.'); return }
                                        if (f.type === 'image/gif' || f.size < 350 * 1024) { $wire.upload('photo', f); return }
                                        try {
                                            const img = await createImageBitmap(f), max = 1600, r = Math.min(1, max / Math.max(img.width, img.height));
                                            const c = document.createElement('canvas'); c.width = Math.round(img.width * r); c.height = Math.round(img.height * r);
                                            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                                            c.toBlob(b => $wire.upload('photo', new File([b], (f.name.replace(/\.[^.]+$/, '') || 'photo') + '.jpg', { type: 'image/jpeg' })), 'image/jpeg', 0.82);
                                        } catch (err) { $wire.upload('photo', f) }
                                    },
                                    async demarrer() {
                                        this.erreurVocal = '';
                                        if (! navigator.mediaDevices?.getUserMedia || ! window.MediaRecorder) { this.erreurVocal = 'Votre navigateur ne permet pas d\'enregistrer une note vocale.'; return }
                                        try { this.flux = await navigator.mediaDevices.getUserMedia({ audio: true }) } catch (err) { this.erreurVocal = 'Autorisez l\'accès au micro pour enregistrer une note vocale.'; return }
                                        const type = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/webm', 'audio/ogg;codecs=opus'].find(t => MediaRecorder.isTypeSupported(t)) || '';
                                        this.rec = new MediaRecorder(this.flux, type ? { mimeType: type } : {});
                                        this.morceaux = [];
                                        this.rec.ondataavailable = ev => ev.data.size && this.morceaux.push(ev.data);
                                        this.rec.onstop = () => this.fin();
                                        this.rec.start(); this.enreg = true; this.sec = 0; this.aEnvoyer = true;
                                        this.minuteur = setInterval(() => { this.sec++; if (this.sec >= 180) this.arreter(true) }, 1000);
                                    },
                                    arreter(envoyer) {
                                        this.aEnvoyer = envoyer; clearInterval(this.minuteur);
                                        if (this.rec && this.rec.state !== 'inactive') this.rec.stop();
                                        this.flux?.getTracks().forEach(t => t.stop());
                                        this.enreg = false;
                                    },
                                    fin() {
                                        if (! this.aEnvoyer || this.sec < 1) return;
                                        const type = (this.rec.mimeType || 'audio/webm').split(';')[0];
                                        const f = new File(this.morceaux, 'note-vocale.' + (type.includes('mp4') ? 'm4a' : 'webm'), { type });
                                        const duree = this.sec; this.envoiVocal = true;
                                        $wire.upload('vocal', f, () => $wire.envoyerVocal(duree).then(() => this.envoiVocal = false), () => { this.envoiVocal = false; this.erreurVocal = 'La note vocale n\'a pas pu être envoyée.' });
                                    },
                                }"
                                x-on:saisie-focus.window="$refs.saisie?.focus()">

                                <template x-if="sugg.length">
                                    <ul class="absolute bottom-full left-0 z-30 mb-2 w-[280px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1 shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]" data-test="suggestions-mention">
                                        <template x-for="(s, i) in sugg" :key="s.id + '-' + s.nom">
                                            <li><button type="button" x-on:mousedown.prevent="choisir(s)" :class="i === idx ? 'bg-cloud' : ''" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-[13px] font-semibold text-brand">
                                                <span class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-azure to-accent text-[10px] font-bold text-white" x-text="s.initiales"></span>
                                                <span x-text="s.id ? s.nom : '@tous · prévenir tout le monde'"></span>
                                            </button></li>
                                        </template>
                                    </ul>
                                </template>

                                {{-- Sondage et partage --}}
                                <div class="relative shrink-0" x-data="{ plus: false }" x-show="! enreg" x-on:click.outside="plus = false" x-on:keydown.escape.window="plus = false">
                                    <button type="button" x-on:click="plus = ! plus" data-test="plus" aria-label="Sondage ou partage" title="Sondage ou partage"
                                        class="icon-btn flex size-10 items-center justify-center rounded-full text-meta hover:bg-cloud hover:text-brand" :class="plus && 'bg-cloud text-brand'"><x-ui.icon name="plus" class="size-5" /></button>
                                    <div x-show="plus" x-transition.origin.bottom.left style="display: none" class="absolute bottom-full left-0 z-30 mb-2 w-[250px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1.5 text-[13px] text-brand shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
                                        @if ($peutFichiers || $moderateur)
                                            <button type="button" wire:click="ouvrirSondage" x-on:click="plus = false" data-test="nouveau-sondage" class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold hover:bg-cloud"><span class="w-5 text-center">📊</span> Sondage</button>
                                        @else
                                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold text-meta hover:bg-cloud"><span class="w-5 text-center">📊</span> Sondage <x-ui.icon name="lock" class="ml-auto size-3.5" /></a>
                                        @endif
                                        <p class="px-4 pb-0.5 pt-2 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-meta">Partager</p>
                                        @foreach (['evenement' => ['📅', 'Un événement'], 'offre' => ['💼', 'Une offre d\'emploi'], 'formation' => ['🎓', 'Une formation'], 'projet' => ['🚀', 'Un projet']] as $type => [$ico, $lib])
                                            <button type="button" wire:click="ouvrirPartage('{{ $type }}')" x-on:click="plus = false" data-test="partager-{{ $type }}" class="flex w-full items-center gap-2.5 px-4 py-2 text-left font-semibold hover:bg-cloud"><span class="w-5 text-center">{{ $ico }}</span> {{ $lib }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Pièce jointe --}}
                                @if ($peutFichiers)
                                    <label class="icon-btn flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-meta hover:bg-cloud hover:text-brand" title="Photo ou document PDF" data-test="joindre" x-show="! enreg">
                                        <x-ui.icon name="image" class="size-5" />
                                        <input type="file" accept="image/*,application/pdf" class="sr-only" x-on:change="choisirFichier($event)">
                                    </label>
                                @else
                                    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate x-show="! enreg" title="Photos et documents : réservé aux membres à jour de leur abonnement" data-test="joindre-verrouille" class="icon-btn relative flex size-10 shrink-0 items-center justify-center rounded-full text-meta/60 hover:bg-cloud">
                                        <x-ui.icon name="image" class="size-5" /><x-ui.icon name="lock" class="absolute bottom-1.5 right-1.5 size-3 text-meta" />
                                    </a>
                                @endif

                                {{-- Enregistrement en cours --}}
                                <div x-show="enreg" style="display: none" class="flex flex-1 items-center gap-3 rounded-[20px] bg-accent/5 px-4 py-2.5" data-test="enregistrement">
                                    <span class="size-2.5 animate-pulse rounded-full bg-accent"></span>
                                    <span class="text-[13px] font-bold text-accent" x-text="Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0')"></span>
                                    <span class="text-[12px] text-meta">Enregistrement… (3 min max.)</span>
                                    <button type="button" x-on:click="arreter(false)" class="ml-auto text-[12px] font-semibold text-meta hover:text-accent">Annuler</button>
                                </div>

                                <textarea x-ref="saisie" x-show="! enreg" wire:model="texte" rows="1" data-test="saisie" maxlength="4000"
                                    x-on:input="saisie($event); $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 140) + 'px'"
                                    x-on:keydown="touche($event)"
                                    placeholder="{{ $estAnnonces ? 'Publier une annonce…' : (($sujetCourant ?? null) ? 'Écrire dans « '.$sujetCourant['titre'].' »…' : 'Écrire un message…') }}"
                                    class="max-h-[140px] min-h-[42px] flex-1 resize-none rounded-[20px] border border-brand/10 bg-cloud px-4 py-2.5 text-[13.5px] text-ink outline-none focus:border-azure"></textarea>

                                <button type="button" x-show="! enreg && ! $wire.texte.trim().length" x-on:click="demarrer()" :disabled="envoiVocal" data-test="micro" title="Enregistrer une note vocale"
                                    class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-full bg-azure text-white hover:bg-brand disabled:opacity-50">
                                    <x-ui.icon name="mic" class="size-5" />
                                </button>
                                <button type="button" x-show="enreg" style="display: none" x-on:click="arreter(true)" data-test="arreter-vocal" title="Envoyer la note vocale"
                                    class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-full bg-accent text-white">
                                    <x-ui.icon name="send" class="size-5" />
                                </button>
                                <button type="button" x-show="! enreg && $wire.texte.trim().length" style="display: none" wire:click="envoyer" wire:loading.attr="disabled" wire:target="envoyer" data-test="envoyer" aria-label="Envoyer"
                                    class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-full bg-accent text-white hover:bg-accent-600 disabled:opacity-60">
                                    <x-ui.icon name="send" class="size-5" />
                                </button>
                                <p x-show="erreurVocal" x-text="erreurVocal" style="display: none" class="absolute -top-6 right-0 text-[11.5px] font-semibold text-accent"></p>
                            </div>
                            <div wire:loading wire:target="photo,document,vocal" class="mt-1.5 text-[11.5px] font-semibold text-azure">Envoi du fichier…</div>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- Nouveau sondage --}}
    @if ($sondageOuvert)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerSondage" x-on:keydown.escape.window="$wire.fermerSondage()">
            <div class="panel-enter max-h-[92vh] w-full max-w-md overflow-y-auto rounded-[18px] bg-white p-6 shadow-2xl" data-test="fenetre-sondage">
                <h2 class="mb-3 text-[15px] font-bold text-brand">📊 Nouveau sondage</h2>
                <label class="mb-3 grid gap-1 text-[13px]"><span class="font-semibold text-brand">Question</span>
                    <input type="text" wire:model="sondageQuestion" maxlength="300" data-test="question-sondage" placeholder="Ex. : Quelle date pour la sortie détente ?" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                </label>
                <p class="mb-1 text-[13px] font-semibold text-brand">Choix</p>
                <div class="grid gap-2">
                    @foreach ($sondageOptions as $i => $opt)
                        <div class="flex items-center gap-2" wire:key="opt-{{ $i }}">
                            <input type="text" wire:model="sondageOptions.{{ $i }}" maxlength="120" data-test="choix-sondage" placeholder="Choix {{ $i + 1 }}" class="min-w-0 flex-1 rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure">
                            @if (count($sondageOptions) > 2)<button type="button" wire:click="retirerOption({{ $i }})" aria-label="Retirer ce choix" class="text-meta hover:text-accent"><x-ui.icon name="x" class="size-4" /></button>@endif
                        </div>
                    @endforeach
                </div>
                @if (count($sondageOptions) < 10)
                    <button type="button" wire:click="ajouterOption" data-test="ajouter-choix" class="mt-2 text-[12.5px] font-bold text-azure hover:underline">+ Ajouter un choix</button>
                @endif
                <div class="mt-4 grid gap-2 text-[13px]">
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="sondageMultiple" class="size-4 accent-[#031D59]"> Plusieurs réponses possibles</label>
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="sondageAnonyme" class="size-4 accent-[#031D59]"> Votes anonymes (on ne voit pas qui a voté quoi)</label>
                    <label class="mt-1 grid gap-1"><span class="font-semibold text-brand">Clôture automatique</span>
                        <select wire:model="sondageDuree" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                            <option value="">Pas de date (je le clos moi-même)</option>
                            <option value="1j">Dans 24 heures</option>
                            <option value="3j">Dans 3 jours</option>
                            <option value="7j">Dans 7 jours</option>
                        </select>
                    </label>
                </div>
                @if ($info)<p class="mt-3 text-[12px] font-semibold text-accent">{{ $info }}</p>@endif
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="fermerSondage" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-meta hover:bg-cloud">Annuler</button>
                    <button type="button" wire:click="publierSondage" data-test="publier-sondage" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Publier le sondage</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Partager un élément de la plateforme --}}
    @if ($partageOuvert)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerPartage" x-on:keydown.escape.window="$wire.fermerPartage()">
            <div class="panel-enter flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-[18px] bg-white shadow-2xl" data-test="fenetre-partage">
                <div class="border-b border-cloud-200 p-5">
                    <h2 class="mb-3 text-[15px] font-bold text-brand">Partager dans « {{ $salon['nom'] ?? '' }} »</h2>
                    <div class="mb-3 flex flex-wrap gap-1.5">
                        @foreach (['evenement' => 'Événements', 'offre' => 'Offres', 'formation' => 'Formations', 'projet' => 'Projets'] as $type => $lib)
                            <button type="button" wire:click="$set('partageType', '{{ $type }}')" class="rounded-full px-3 py-1.5 text-[12px] font-bold {{ $partageType === $type ? 'bg-brand text-white' : 'border border-brand/15 text-brand hover:bg-cloud' }}">{{ $lib }}</button>
                        @endforeach
                    </div>
                    <input type="search" wire:model.live.debounce.300ms="partageRecherche" data-test="recherche-partage" placeholder="Rechercher par titre…" class="w-full rounded-[10px] border border-brand/10 bg-cloud px-3 py-2 text-[13px] outline-none focus:border-azure">
                    <input type="text" wire:model="partageCommentaire" maxlength="1000" data-test="commentaire-partage" placeholder="Ajouter un mot (facultatif)" class="mt-2 w-full rounded-[10px] border border-brand/10 px-3 py-2 text-[13px] outline-none focus:border-azure">
                </div>
                <ul class="min-h-0 flex-1 overflow-y-auto p-2">
                    @forelse ($partageResultats as $c)
                        <li wire:key="part-{{ $c['type'] }}-{{ $c['id'] }}" class="flex items-center gap-3 rounded-[12px] px-3 py-2.5 hover:bg-cloud">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-bold text-brand">{{ $c['titre'] }}</p>
                                <p class="truncate text-[12px] text-meta">{{ $c['detail'] }}</p>
                            </div>
                            <button type="button" wire:click="partager('{{ $c['type'] }}', {{ $c['id'] }})" data-test="choisir-partage" class="btn-tap shrink-0 rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600">Partager</button>
                        </li>
                    @empty
                        <li class="px-3 py-8 text-center text-[13px] text-meta">Rien à partager pour le moment dans cette catégorie.</li>
                    @endforelse
                </ul>
                <div class="flex justify-end border-t border-cloud-200 p-3"><button type="button" wire:click="fermerPartage" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-meta hover:bg-cloud">Fermer</button></div>
            </div>
        </div>
    @endif

    {{-- Nouveau sujet --}}
    @if ($sujetOuvert)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="$set('sujetOuvert', false)" x-on:keydown.escape.window="$wire.set('sujetOuvert', false)">
            <div class="panel-enter w-full max-w-md rounded-[18px] bg-white p-6 shadow-2xl" data-test="fenetre-sujet">
                <h2 class="mb-1 text-[15px] font-bold text-brand">Nouveau sujet dans « {{ $salon['nom'] ?? '' }} »</h2>
                <p class="mb-4 text-[12.5px] leading-relaxed text-meta">Un sujet regroupe les messages sur une même question, pour que chacun suive ce qui l'intéresse.</p>
                <div class="flex items-end gap-2">
                    <label class="grid w-[72px] gap-1 text-[13px]"><span class="font-semibold text-brand">Emoji</span>
                        <input type="text" wire:model="sujetEmoji" maxlength="4" data-test="emoji-sujet" placeholder="🧾" class="rounded-[10px] border border-brand/15 px-3 py-2 text-center outline-none focus:border-azure">
                    </label>
                    <label class="grid min-w-0 flex-1 gap-1 text-[13px]"><span class="font-semibold text-brand">Titre</span>
                        <input type="text" wire:model="sujetTitre" maxlength="80" data-test="titre-sujet" placeholder="Ex. : Fiscalité des PME" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure" x-init="$nextTick(() => $el.focus())" x-on:keydown.enter="$wire.creerSujet()">
                    </label>
                </div>
                @if ($info)<p class="mt-3 text-[12px] font-semibold text-accent">{{ $info }}</p>@endif
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('sujetOuvert', false)" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-meta hover:bg-cloud">Annuler</button>
                    <button type="button" wire:click="creerSujet" data-test="creer-sujet" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Ouvrir le sujet</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lancer une discussion vocale --}}
    @if ($directOuvert)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="$set('directOuvert', false)" x-on:keydown.escape.window="$wire.set('directOuvert', false)">
            <div class="panel-enter w-full max-w-md rounded-[18px] bg-white p-6 shadow-2xl" data-test="fenetre-direct">
                <h2 class="mb-1 text-[15px] font-bold text-brand">🎙️ Discussion vocale en direct</h2>
                <p class="mb-4 text-[12.5px] leading-relaxed text-meta">
                    Tous les membres du salon sont prévenus et rejoignent la discussion d'un clic. Elle se termine d'elle-même au bout de 3 heures.
                </p>
                <label class="mb-3 grid gap-1 text-[13px]"><span class="font-semibold text-brand">Titre</span>
                    <input type="text" wire:model="directTitre" maxlength="120" data-test="titre-direct" placeholder="Ex. : Café réseau du jeudi" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                </label>
                <label class="grid gap-1 text-[13px]"><span class="font-semibold text-brand">Lien de visio {{ ($moi['meet_disponible'] ?? false) ? '(facultatif)' : '' }}</span>
                    <input type="url" wire:model="directLien" maxlength="500" data-test="lien-direct" placeholder="https://meet.google.com/… ou https://zoom.us/…" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                    <span class="text-[11.5px] text-meta">{{ ($moi['meet_disponible'] ?? false) ? 'Laissez vide : une réunion Google Meet est créée avec le compte du REJCC, qui admet les participants.' : 'Collez le lien de la réunion (Google Meet, Zoom, Teams…).' }}</span>
                </label>
                @if ($info)<p class="mt-3 text-[12px] font-semibold text-accent">{{ $info }}</p>@endif
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('directOuvert', false)" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-meta hover:bg-cloud">Annuler</button>
                    <button type="button" wire:click="lancerDirect" wire:loading.attr="disabled" wire:target="lancerDirect" data-test="confirmer-direct" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Lancer et prévenir les membres</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Signalement d'un message --}}
    @if ($signalementId)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerSignalement" x-on:keydown.escape.window="$wire.fermerSignalement()">
            <div class="panel-enter w-full max-w-md rounded-[18px] bg-white p-6 shadow-2xl" data-test="fenetre-signalement">
                <h2 class="mb-1 text-[15px] font-bold text-brand">Signaler ce message</h2>
                <p class="mb-3 text-[12.5px] leading-relaxed text-meta">L'équipe de modération examinera le message. L'auteur ne saura pas qui l'a signalé.</p>
                <textarea wire:model="motif" rows="3" maxlength="500" data-test="motif" placeholder="Pourquoi ce message pose-t-il problème ? (facultatif)" class="w-full rounded-[12px] border border-brand/15 p-3 text-[13px] outline-none focus:border-azure"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="fermerSignalement" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-meta hover:bg-cloud">Annuler</button>
                    <button type="button" wire:click="envoyerSignalement" data-test="envoyer-signalement" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Signaler</button>
                </div>
            </div>
        </div>
    @endif
</div>
