@props(['partners'])

@php
    $list = collect($partners)->values();

    // Une « copie » doit être au moins aussi large que l'écran, sinon la boucle
    // laisse un blanc. Avec peu de partenaires on répète donc la liste (un
    // élément ≈ 208 px, on vise ~1920 px de large).
    $repeat = $list->isEmpty() ? 1 : max(1, (int) ceil(1920 / max(1, $list->count() * 208)));
    $sequence = collect(array_fill(0, $repeat, $list->all()))->flatten(1);

    // Vitesse constante quel que soit le nombre de logos (~4,5 s par logo).
    $duration = max(18, round($sequence->count() * 4.5, 1));
@endphp

@if ($list->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'partners-marquee']) }} style="--marquee-duration: {{ $duration }}s">
        <div class="partners-track">
            {{-- La séquence est dupliquée : la 2ᵉ copie (masquée aux lecteurs
                 d'écran) permet une boucle sans couture. --}}
            @foreach ([false, true] as $clone)
                @foreach ($sequence as $p)
                    @php
                        $name = $p->name ?? '';
                        $logo = $p->logo ?? null;
                        $site = $p->site_url ?? null;
                    @endphp
                    {{-- Un <a> sans href n'est ni cliquable ni focusable :
                         les partenaires sans site restent de simples vignettes. --}}
                    <a
                        class="partners-item"
                        @if ($site) href="{{ $site }}" target="_blank" rel="noopener noreferrer" title="{{ $name }} — ouvrir le site" @endif
                        @if ($clone) aria-hidden="true" tabindex="-1" @endif
                    >
                        @if ($logo)
                            {{-- Pas de loading="lazy" : l'animation déplace les
                                 vignettes hors de la zone visible (overflow
                                 hidden), le chargement différé ne se
                                 déclencherait jamais → logos vides. --}}
                            <img src="{{ $logo }}" alt="{{ $clone ? '' : $name }}" class="partners-logo">
                        @else
                            <span class="partners-name">{{ $name }}</span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>

    @once
        <style>
            .partners-marquee {
                position: relative;
                overflow: hidden;
                -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
                mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
            }
            .partners-track {
                display: flex;
                align-items: center;
                width: max-content;
                animation: partnersScroll var(--marquee-duration, 30s) linear infinite;
            }
            /* Pause au survol / au focus clavier pour pouvoir cliquer un logo. */
            .partners-marquee:hover .partners-track,
            .partners-marquee:focus-within .partners-track {
                animation-play-state: paused;
            }
            @keyframes partnersScroll {
                from { transform: translateX(0); }
                to { transform: translateX(-50%); }
            }
            .partners-item {
                /* marge (et non gap) : la largeur par élément est uniforme,
                   donc translateX(-50%) tombe pile sur la 2ᵉ copie. */
                margin-right: 18px;
                flex: 0 0 auto;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 190px;
                height: 104px;
                padding: 18px;
                border-radius: 18px;
                border: 1px solid rgba(3, 29, 89, .10);
                background: #fff;
                box-shadow: 0 2px 10px rgba(3, 29, 89, .05);
                transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
            }
            a.partners-item[href]:hover {
                transform: translateY(-4px);
                border-color: rgba(3, 29, 89, .25);
                box-shadow: 0 16px 34px -14px rgba(3, 29, 89, .35);
            }
            .partners-logo {
                max-height: 100%;
                max-width: 100%;
                object-fit: contain;
                filter: grayscale(1);
                opacity: .72;
                transition: filter .3s ease, opacity .3s ease;
            }
            .partners-item:hover .partners-logo { filter: grayscale(0); opacity: 1; }
            .partners-name {
                text-align: center;
                font-weight: 700;
                font-size: 14px;
                line-height: 1.25;
                letter-spacing: .01em;
                color: #031D59;
            }
            @media (max-width: 640px) {
                .partners-item { width: 152px; height: 88px; padding: 14px; margin-right: 12px; }
            }
            /* Accessibilité : pas d'animation → défilement manuel. */
            @media (prefers-reduced-motion: reduce) {
                .partners-marquee {
                    overflow-x: auto;
                    -webkit-mask-image: none;
                    mask-image: none;
                }
                .partners-track { animation: none; }
            }
        </style>
    @endonce
@endif
