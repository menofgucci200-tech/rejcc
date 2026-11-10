@props([
    'carrousels' => [],
    // Valeur data-test de chaque image (tests de l'accueil et de la page).
    'test' => 'cathoimpact-slide',
    // Légende en blanc sur fond sombre.
    'sombre' => false,
    // Pastille « Votre portrait ici ? » vers le questionnaire.
    'pastille' => true,
])

{{--
    Diaporama des carrousels CathoImpact (resources/js/vitrine.js, diaporama()) :
    chaque image reste 3 s, puis carrousel suivant. Survol ou focus : pause,
    flèches et pile de cartes qui s'ouvre. Clavier, glissé au doigt.
--}}
@if ($carrousels)
<div {{ $attributes->merge(['class' => 'p-ci group/ci relative mx-auto w-full max-w-[min(100%,480px)]']) }} data-p-ci data-p-ci-duree="3000"
     role="region" aria-roledescription="carrousel" aria-label="Carrousels CathoImpact publiés" tabindex="0">
    <span class="p-ci-pile absolute inset-0 rounded-[2rem] {{ $sombre ? 'bg-azure' : 'bg-azure/25' }}" style="--r:6deg; --x:7%" aria-hidden="true"></span>
    <span class="p-ci-pile absolute inset-0 rounded-[2rem] {{ $sombre ? 'bg-accent' : 'bg-accent/20' }}" style="--r:-5deg; --x:-6%" aria-hidden="true"></span>

    <div class="p-ci-scene relative aspect-[4/5] overflow-hidden rounded-[2rem] bg-brand-900 shadow-[0_50px_90px_-40px_rgba(3,29,89,.7)]">
        @foreach ($carrousels as $c => $car)
            @foreach ($car['slides'] as $k => $src)
                <img src="{{ asset($src) }}" width="864" height="1080" decoding="async" @if ($c + $k > 0) loading="lazy" @endif
                     alt="{{ $car['alts'][$k] ?? $car['titre'].' : image '.($k + 1).' sur '.count($car['slides']) }}"
                     class="p-ci-slide {{ $c + $k === 0 ? 'is-actif' : '' }}" data-c="{{ $c }}" data-k="{{ $k }}"
                     @if ($c + $k > 0) aria-hidden="true" @endif data-test="{{ $test }}">
            @endforeach
        @endforeach

        {{-- Progression du carrousel en cours, façon « stories » --}}
        <span class="pointer-events-none absolute inset-x-0 top-0 z-10 h-20 bg-gradient-to-b from-brand-900/45 to-transparent" aria-hidden="true"></span>
        <div class="absolute inset-x-4 top-4 z-20" aria-hidden="true">
            @foreach ($carrousels as $c => $car)
                <div class="p-ci-barres gap-1 {{ $c === 0 ? 'flex' : 'hidden' }}" data-c="{{ $c }}">
                    @foreach ($car['slides'] as $k => $src)<span class="h-[3px] flex-1 overflow-hidden rounded-full bg-white/35"><span class="block h-full w-0 rounded-full bg-white" data-k="{{ $k }}"></span></span>@endforeach
                </div>
            @endforeach
        </div>

        <button type="button" class="p-ci-fleche left-3" data-p-ci-prec aria-label="Image précédente"><x-ui.icon name="arrow-right" class="size-5 rotate-180" /></button>
        <button type="button" class="p-ci-fleche right-3" data-p-ci-suiv aria-label="Image suivante"><x-ui.icon name="arrow-right" class="size-5" /></button>
        <span class="p-ci-pause absolute bottom-4 right-4 z-20 rounded-full bg-brand-900 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white" aria-hidden="true">En pause</span>
    </div>

    <div class="relative mt-6 flex items-end gap-6 {{ $pastille ? 'sm:pr-36' : '' }}">
        <div class="min-w-0 overflow-hidden" aria-live="polite">
            @foreach ($carrousels as $c => $car)
                <p class="p-ci-legende {{ $c === 0 ? 'is-actif' : '' }}" data-c="{{ $c }}">
                    <span class="block font-display text-2xl uppercase leading-none {{ $sombre ? 'text-white' : 'text-brand' }}">{{ $car['titre'] }}</span>
                    <span class="mt-1 block text-sm {{ $sombre ? 'text-white/65' : 'text-meta' }}">{{ $car['sous_titre'] }}</span>
                </p>
            @endforeach
        </div>
        <p class="shrink-0 font-display text-lg tabular-nums {{ $sombre ? 'text-white' : 'text-brand' }}" aria-hidden="true"><span data-p-ci-num>01</span><span class="{{ $sombre ? 'text-white/35' : 'text-brand/30' }}"> / <span data-p-ci-total>{{ str_pad(count($carrousels[0]['slides']), 2, '0', STR_PAD_LEFT) }}</span></span></p>
    </div>
    @if (count($carrousels) > 1)
        <div class="mt-4 flex gap-2">
            @foreach ($carrousels as $c => $car)
                <button type="button" class="p-ci-choix {{ $c === 0 ? 'is-actif' : '' }} {{ $sombre ? '[&:not(.is-actif)]:bg-white/30' : '' }}" data-p-ci-choix="{{ $c }}" aria-label="Carrousel : {{ $car['titre'] }}"></button>
            @endforeach
        </div>
    @endif
    @if ($pastille)
        <a href="{{ route('cathoimpact.questionnaire') }}" wire:navigate class="absolute -bottom-6 -right-4 z-30 hidden rotate-6 rounded-2xl bg-accent px-4 py-3 font-display text-lg uppercase leading-none text-white shadow-xl transition-transform duration-500 hover:rotate-0 hover:scale-105 sm:block lg:-right-12" data-p-mouse="-22">Votre portrait<br>ici&nbsp;?</a>
    @endif
</div>
@endif
