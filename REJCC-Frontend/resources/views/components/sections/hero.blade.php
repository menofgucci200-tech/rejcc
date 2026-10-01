@php
    use App\Support\Content\SiteRemote;

    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
    $heroEyebrow = SiteRemote::field('home', 'hero', 'eyebrow', 'Réseau Entrepreneurial des Jeunes Chrétiens Catholiques');
    $heroSubtitle = SiteRemote::field('home', 'hero', 'subtitle', "Accéder à un réseau d'excellence pour entreprendre, grandir et réussir ensemble. Nous connectons les jeunes entrepreneurs catholiques pour co-créer des solutions durables, au service de l'Église et de la société.");
    $heroNote = SiteRemote::field('home', 'hero', 'note', '350+ membres déjà engagés dans 33 domaines.');

    // Constellation « union des talents » : des membres (nœuds) répartis sur
    // trois orbites, reliés au monogramme. Repère SVG 0 0 640 640, centre 320.
    $c = 320;
    $r0 = 196; // rayon de départ des liens (zone de protection autour du logo)
    $nodes = [
        [214, -152], [214, -38], [214, 58], [214, 168],
        [262, -112], [262, -8], [262, 96], [262, 142], [262, 212],
        [306, -74], [306, 22], [306, 122], [306, 188], [306, 248],
    ];
    $pt = fn ($r, $deg) => [round($c + $r * cos(deg2rad($deg)), 1), round($c + $r * sin(deg2rad($deg)), 1)];
    $links = [[0, 4], [1, 5], [5, 10], [2, 6], [6, 11], [3, 8], [8, 12], [7, 11], [4, 9], [9, 13]];
    $pulses = [1, 4, 6, 8, 10, 12]; // nœuds émettant un signal vers le centre

    $values = ['Foi', 'Excellence', 'Solidarité', 'Impact', 'Création de richesse'];

    $floatingCards = [
        ['icon' => 'network', 'label' => 'Networking', 'sub' => 'Connexions ciblées', 'x' => '-6%', 'y' => '14%'],
        ['icon' => 'graduation-cap', 'label' => 'Mentorat', 'sub' => 'Experts confirmés', 'x' => '72%', 'y' => '4%'],
        ['icon' => 'rocket', 'label' => 'Accélération', 'sub' => 'Projets à impact', 'x' => '70%', 'y' => '78%'],
    ];

    // Assemblage du monogramme : chaque pièce converge vers sa place exacte.
    $heroPieces = [
        ['stem-r', 'w', -56, -44], ['stem-uc', 'w', -44, 60], ['arc', 'w', 52, -48],
        ['inner-c', 'w', 72, 46], ['bar-1', 'w', -96, 0], ['bar-2', 'w', -96, 0], ['cross-j', 'w', 0, 96],
    ];
@endphp

<x-brand.logo-defs />

<section id="accueil" class="rj-hero relative flex min-h-[100svh] items-center overflow-hidden bg-brand pb-32 pt-28 lg:pt-24" data-hero-motion>
    <script>
        (function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.documentElement.classList.add('rj-motion');
            // Filet de sécurité : si le module d'animation ne démarre pas, on affiche le hero.
            var hero = document.currentScript.parentNode;
            setTimeout(function () { if (!window.__rjMotionStarted) hero.classList.add('is-live'); }, 3000);
        })();
    </script>

    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="rj-anim rj-fade absolute inset-0 bg-grid opacity-40 [mask-image:radial-gradient(ellipse_at_70%_50%,black,transparent_70%)]" style="--d: 0s"></div>
        <div class="absolute inset-x-0 top-0 h-40 bg-linear-to-b from-brand-900/60 to-transparent"></div>
    </div>

    <div class="relative mx-auto grid w-full max-w-7xl items-center gap-16 container-px lg:grid-cols-[1.08fr_0.92fr]">
        <div>
            <p class="rj-eyebrow flex items-center gap-3 text-[0.72rem] font-bold uppercase tracking-[0.22em] text-white/70">
                <span class="rj-anim rj-rule h-px w-10 bg-accent" style="--d: .05s"></span>
                <span class="rj-mask"><span class="rj-anim rj-up block" style="--d: .15s">{{ $heroEyebrow }}</span></span>
            </p>

            <h1 class="mt-7 font-display uppercase leading-[0.92] tracking-[-0.01em] text-white text-[clamp(2.9rem,6.6vw,5.4rem)]">
                <span class="rj-mask block"><span class="rj-anim rj-up block" style="--d: .25s">Ensemble</span></span>
                <span class="rj-mask block pb-[0.06em]">
                    <span class="rj-anim rj-up block" style="--d: .38s">pour <span class="font-serif normal-case tracking-normal text-azure">l'excellence</span><span class="text-accent">.</span></span>
                </span>
            </h1>

            <p class="rj-anim rj-soft mt-7 max-w-xl text-pretty text-[1.05rem] leading-relaxed text-white/75 sm:text-lg" style="--d: .7s">
                {{ $heroSubtitle }}
            </p>

            <div class="rj-anim rj-soft mt-9 flex flex-wrap items-center gap-3" style="--d: .85s">
                <x-ui.button href="{{ url($cta['href']) }}" size="lg" variant="primary" :with-arrow="true">Rejoindre le réseau</x-ui.button>
                <x-ui.button href="/a-propos" size="lg" variant="ghost" class="text-white hover:bg-white/10">Découvrir le REJCC</x-ui.button>
            </div>

            <div class="rj-anim rj-soft mt-8 flex items-center gap-5 text-sm text-white/60" style="--d: 1s">
                <div class="flex -space-x-2">
                    @foreach (['JK', 'AB', 'GA', 'MT'] as $initials)
                        <span class="inline-flex size-9 items-center justify-center rounded-full border-2 border-brand bg-azure/30 text-[0.7rem] font-bold text-white">{{ $initials }}</span>
                    @endforeach
                </div>
                <span class="font-medium text-white/80">{{ $heroNote }}</span>
            </div>
        </div>

        <div class="rj-orbit relative mx-auto hidden aspect-square w-full max-w-[38rem] lg:block" aria-hidden="true">
            <svg class="rj-net absolute inset-0 size-full overflow-visible" viewBox="0 0 640 640" fill="none" data-parallax="10">
                <g class="rj-orbits" stroke="currentColor">
                    @foreach ([214, 262, 306] as $i => $r)
                        <circle class="rj-anim rj-draw-line" pathLength="1" cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" style="--d: {{ .3 + $i * .12 }}s"/>
                    @endforeach
                </g>
                <g class="rj-links" stroke="currentColor">
                    @foreach ($nodes as $i => [$r, $deg])
                        @php([$x1, $y1] = $pt($r0, $deg))
                        @php([$x2, $y2] = $pt($r, $deg))
                        <line class="rj-anim rj-draw-line" pathLength="1" x1="{{ $x2 }}" y1="{{ $y2 }}" x2="{{ $x1 }}" y2="{{ $y1 }}" style="--d: {{ .7 + $i * .05 }}s"/>
                    @endforeach
                    @foreach ($links as $i => [$a, $b])
                        @php([$ax, $ay] = $pt(...$nodes[$a]))
                        @php([$bx, $by] = $pt(...$nodes[$b]))
                        <line class="rj-anim rj-draw-line rj-chord" pathLength="1" x1="{{ $ax }}" y1="{{ $ay }}" x2="{{ $bx }}" y2="{{ $by }}" style="--d: {{ 1.1 + $i * .06 }}s"/>
                    @endforeach
                </g>
                <g class="rj-pulses">
                    @foreach ($pulses as $i => $n)
                        @php([$r, $deg] = $nodes[$n])
                        @php([$px, $py] = $pt($r, $deg))
                        @php([$qx, $qy] = $pt($r0, $deg))
                        <circle class="rj-pulse" cx="{{ $px }}" cy="{{ $py }}" r="3.2" style="--tx: {{ round($qx - $px, 1) }}px; --ty: {{ round($qy - $py, 1) }}px; --d: {{ 2 + $i * .55 }}s"/>
                    @endforeach
                </g>
                <g class="rj-nodes">
                    @foreach ($nodes as $i => [$r, $deg])
                        @php([$x, $y] = $pt($r, $deg))
                        <g class="rj-anim rj-node" style="--d: {{ .9 + $i * .05 }}s; transform-origin: {{ $x }}px {{ $y }}px">
                            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $i % 3 === 0 ? 7 : 5 }}" class="rj-node__ring"/>
                            <circle cx="{{ $x }}" cy="{{ $y }}" r="2.6" class="rj-node__dot"/>
                        </g>
                    @endforeach
                </g>
            </svg>

            {{-- Logo compact officiel, version blanche (fond bleu autorisé par la charte) --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="rj-anim rj-fade absolute size-[61%] rounded-full bg-brand-700/45" style="--d: .2s"></span>
                <svg class="rj-mark relative h-[47%] w-auto overflow-visible" viewBox="250 44 510 776" role="img" aria-label="Logo REJCC">
                    <defs><clipPath id="rj-hero-word"><rect x="250" y="704" width="510" height="114"/></clipPath></defs>
                    @foreach ($heroPieces as $i => [$id, $tone, $dx, $dy])
                        <use href="#rj-{{ $id }}" class="rj-anim rj-converge" style="--d: {{ .35 + $i * .07 }}s; --dx: {{ $dx }}px; --dy: {{ $dy }}px"/>
                    @endforeach
                    <g clip-path="url(#rj-hero-word)">
                        @foreach (['w-r', 'w-e', 'w-j', 'w-c1', 'w-c2'] as $i => $piece)
                            <use href="#rj-{{ $piece }}" class="rj-anim rj-converge" style="--d: {{ .95 + $i * .05 }}s; --dx: 0px; --dy: 34px"/>
                        @endforeach
                    </g>
                </svg>
            </div>

            @foreach ($floatingCards as $i => $card)
                <div class="rj-anim rj-pop absolute" style="left: {{ $card['x'] }}; top: {{ $card['y'] }}; --d: {{ 1.5 + $i * .25 }}s" data-parallax="{{ 18 + $i * 6 }}">
                    <div class="glass-dark flex animate-float items-center gap-3 whitespace-nowrap rounded-2xl px-4 py-3 shadow-xl" style="animation-delay: -{{ $i * 2 }}s">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-white/10 text-white">
                            <x-ui.icon :name="$card['icon']" class="size-4.5" />
                        </span>
                        <div class="leading-tight">
                            <p class="text-sm font-semibold text-white">{{ $card['label'] }}</p>
                            <p class="text-xs text-white/60">{{ $card['sub'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Bandeau des valeurs (charte : Foi, Excellence, Solidarité, Impact, Création de richesse) --}}
    <div class="rj-anim rj-fade absolute inset-x-0 bottom-0 overflow-hidden border-t border-white/10 py-4" style="--d: 1.3s" aria-label="Nos valeurs">
        <div class="rj-marquee flex w-max items-center">
            @foreach ([0, 1] as $copy)
                <ul class="flex shrink-0 items-center" @if ($copy) aria-hidden="true" @endif>
                    @foreach (array_merge($values, $values) as $value)
                        <li class="flex items-center font-display text-[clamp(1.4rem,2.6vw,2.2rem)] uppercase leading-none tracking-[0.01em] text-white/[0.14]">
                            <span class="px-8">{{ $value }}</span>
                            <span class="size-2 bg-accent"></span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</section>
