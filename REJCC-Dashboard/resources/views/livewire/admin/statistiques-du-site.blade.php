@php
    use Illuminate\Support\Carbon;

    $nombre = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $date = fn ($d, $f = 'j M') => Carbon::parse($d)->locale('fr')->translatedFormat($f);
    $jours = $stats['jours'];
    $max = max(1, max(array_column($jours, 'pages_vues') ?: [0]));
    $n = max(1, count($jours));
    $largeur = 720;
    $hauteur = 180;
    $pas = $largeur / $n;
    $graduation = max(1, (int) ceil($n / 8));
    $appareils = $stats['appareils'];
    $totalAppareils = max(1, $appareils['telephone'] + $appareils['ordinateur']);
    $pctTel = (int) round($appareils['telephone'] / $totalAppareils * 100);
    $maxPage = max(1, $stats['pages'][0]['vues'] ?? 1);
    $maxSource = max(1, $stats['sources'][0]['visites'] ?? 1);
    $libellePeriode = $stats['mois'] ? ucfirst($date($stats['du'], 'F Y')) : '30 derniers jours';
@endphp

<div>
    <x-admin-light.topbar title="Statistiques du site" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <x-admin-light.onglets groupe="statistiques" />
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Audience de rejcc.site</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Mesure anonyme et sans cookie : aucune adresse IP ni aucun profil n'est conservé. Un visiteur est compté une fois par jour ; les robots, l'espace membre et les préchargements ne sont pas comptés.</p>
            </div>
            <label class="flex items-center gap-2 text-[12px] font-bold text-[#5B677A]">
                Période
                <select wire:model.live="periode" data-test="periode" class="rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-[12.5px] font-bold text-brand focus:border-brand focus:outline-none">
                    <option value="30j">30 derniers jours</option>
                    @foreach ($moisDisponibles as $cle => $label)
                        <option value="{{ $cle }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- Chiffres clés --}}
        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Pages vues', $stats['pages_vues'], $stats['evolution']['pages_vues'], $stats['precedent']['pages_vues'], 'eye'],
                ['Visiteurs', $stats['visiteurs'], $stats['evolution']['visiteurs'], $stats['precedent']['visiteurs'], 'users'],
            ] as [$titre, $valeur, $evolution, $avant, $icone])
                <div data-test="chiffre-{{ \Illuminate\Support\Str::slug($titre) }}" class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]"><x-ui.icon :name="$icone" class="size-3.5" /> {{ $titre }}</p>
                    <p class="mt-1 text-[26px] font-bold leading-tight text-brand">{{ $nombre($valeur) }}</p>
                    <p class="mt-0.5 text-[11px] text-[#5B677A]">
                        @if ($evolution === null)
                            Pas de comparaison possible
                        @else
                            <span class="font-bold {{ $evolution >= 0 ? 'text-vert' : 'text-accent' }}">{{ $evolution > 0 ? '+' : '' }}{{ $evolution }} %</span>
                        @endif
                        · {{ $nombre($avant) }} du {{ $date($stats['precedent']['du']) }} au {{ $date($stats['precedent']['au']) }}
                    </p>
                </div>
            @endforeach
            <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]"><x-ui.icon name="smartphone" class="size-3.5" /> Téléphone ou ordinateur</p>
                <p class="mt-1 text-[26px] font-bold leading-tight text-brand">{{ $pctTel }} % <span class="text-[12px] font-semibold text-[#5B677A]">sur téléphone</span></p>
                <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-cloud-200" aria-hidden="true">
                    <div class="bg-brand" style="width: {{ $pctTel }}%"></div>
                </div>
                <p class="mt-1 text-[11px] text-[#5B677A]">{{ $nombre($appareils['telephone']) }} téléphone{{ $appareils['telephone'] > 1 ? 's' : '' }} · {{ $nombre($appareils['ordinateur']) }} ordinateur{{ $appareils['ordinateur'] > 1 ? 's' : '' }}</p>
            </div>
            <div class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]"><x-ui.icon name="link" class="size-3.5" /> Clics des liens de suivi</p>
                <p class="mt-1 text-[26px] font-bold leading-tight text-brand" data-test="clics-liens">{{ $clics ? $nombre($clics['total']) : '—' }}</p>
                <a href="{{ route('admin.liens-suivi') }}" wire:navigate class="mt-0.5 inline-block text-[11px] font-bold text-azure hover:text-brand">Gérer les liens de suivi →</a>
            </div>
        </div>

        {{-- Graphique par jour --}}
        <div class="mb-5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[13px] font-bold text-brand">Par jour · {{ $libellePeriode }}</p>
                <p class="flex items-center gap-3 text-[11px] text-[#5B677A]">
                    <span class="inline-flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-azure/30"></span> Pages vues</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-brand"></span> Visiteurs</span>
                </p>
            </div>
            @if ($stats['jours_mesures'] === 0)
                <p class="py-10 text-center text-sm text-[#5B677A]">Aucune visite mesurée sur cette période. La mesure commence dès la mise en ligne du compteur.</p>
            @else
                <svg viewBox="0 0 {{ $largeur }} {{ $hauteur + 22 }}" class="h-auto w-full" role="img" aria-label="Pages vues et visiteurs par jour" data-test="graphique-jours">
                    @foreach ([0.25, 0.5, 0.75, 1] as $f)
                        <line x1="0" x2="{{ $largeur }}" y1="{{ $hauteur - $hauteur * $f }}" y2="{{ $hauteur - $hauteur * $f }}" stroke="#EDF0F5" stroke-width="1" />
                    @endforeach
                    <text x="2" y="10" font-size="10" fill="#5B677A">{{ $nombre($max) }}</text>
                    @foreach ($jours as $jour => $v)
                        @php
                            $x = $loop->index * $pas;
                            $hVues = $v['pages_vues'] / $max * ($hauteur - 14);
                            $hVisiteurs = $v['visiteurs'] / $max * ($hauteur - 14);
                        @endphp
                        <g>
                            <title>{{ ucfirst($date($jour, 'l j F')) }} : {{ $v['pages_vues'] }} page{{ $v['pages_vues'] > 1 ? 's' : '' }} vue{{ $v['pages_vues'] > 1 ? 's' : '' }}, {{ $v['visiteurs'] }} visiteur{{ $v['visiteurs'] > 1 ? 's' : '' }}</title>
                            <rect x="{{ $x }}" y="0" width="{{ $pas }}" height="{{ $hauteur }}" fill="transparent" />
                            <rect x="{{ $x + $pas * 0.12 }}" y="{{ $hauteur - $hVues }}" width="{{ $pas * 0.76 }}" height="{{ $hVues }}" rx="2" fill="#4F6FBF" fill-opacity="0.3" />
                            <rect x="{{ $x + $pas * 0.3 }}" y="{{ $hauteur - $hVisiteurs }}" width="{{ $pas * 0.4 }}" height="{{ $hVisiteurs }}" rx="2" fill="#031D59" />
                        </g>
                        @if ($loop->index % $graduation === 0)
                            <text x="{{ $x + $pas / 2 }}" y="{{ $hauteur + 15 }}" font-size="10" fill="#5B677A" text-anchor="middle">{{ $date($jour) }}</text>
                        @endif
                    @endforeach
                </svg>
            @endif
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            {{-- Pages les plus vues --}}
            <div class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-3 text-[13px] font-bold text-brand">Pages les plus vues</p>
                @forelse ($stats['pages'] as $p)
                    <div class="mb-2.5" data-test="page-vue">
                        <div class="mb-0.5 flex items-baseline justify-between gap-3 text-[12.5px]">
                            <span class="truncate text-ink" title="{{ $p['chemin'] }}">{{ $p['label'] }} @if ($p['label'] !== $p['chemin'])<span class="text-[10.5px] text-[#5B677A]">{{ $p['chemin'] }}</span>@endif</span>
                            <span class="shrink-0 font-bold text-brand">{{ $nombre($p['vues']) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-cloud"><div class="h-1.5 rounded-full bg-azure" style="width: {{ max(2, round($p['vues'] / $maxPage * 100)) }}%"></div></div>
                    </div>
                @empty
                    <p class="py-6 text-center text-[12.5px] text-[#5B677A]">Pas encore de données.</p>
                @endforelse
            </div>

            {{-- Provenances --}}
            <div class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-1 text-[13px] font-bold text-brand">D'où viennent les visiteurs</p>
                <p class="mb-3 text-[11px] text-[#5B677A]">Site d'origine ou canal d'un lien de suivi. « Accès direct » : adresse tapée, favori ou application qui ne transmet pas sa provenance (WhatsApp notamment, d'où l'intérêt des liens de suivi).</p>
                @forelse ($stats['sources'] as $s)
                    <div class="mb-2.5" data-test="source">
                        <div class="mb-0.5 flex items-baseline justify-between gap-3 text-[12.5px]">
                            <span class="truncate text-ink">{{ $s['label'] }}</span>
                            <span class="shrink-0 font-bold text-brand">{{ $nombre($s['visites']) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-cloud"><div class="h-1.5 rounded-full bg-brand" style="width: {{ max(2, round($s['visites'] / $maxSource * 100)) }}%"></div></div>
                    </div>
                @empty
                    <p class="py-6 text-center text-[12.5px] text-[#5B677A]">Pas encore de données.</p>
                @endforelse
            </div>
        </div>

        {{-- Liens de suivi --}}
        @if ($clics && $clics['total'] > 0)
            <div class="mt-5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]" data-test="clics-detail">
                <p class="mb-3 text-[13px] font-bold text-brand">Clics des liens de suivi · {{ $libellePeriode }}</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ($clics['par_canal'] as $c)
                        <span class="rounded-full bg-cloud px-3 py-1 text-[12px] font-bold text-brand">{{ $c['label'] }} · {{ $nombre($c['clics']) }}</span>
                    @endforeach
                </div>
                @foreach (array_slice($clics['par_lien'], 0, 10) as $l)
                    <div class="flex items-baseline justify-between gap-3 border-t border-[#EDF0F5] py-2 text-[12.5px] first:border-t-0">
                        <span class="truncate text-ink">{{ $l['libelle'] }} <span class="font-mono text-[11px] text-[#5B677A]">/l/{{ $l['code'] }}</span></span>
                        <span class="shrink-0 font-bold text-brand">{{ $nombre($l['clics']) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
