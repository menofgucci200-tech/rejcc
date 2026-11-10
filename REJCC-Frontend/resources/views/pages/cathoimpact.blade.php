@php
    // Carrousels réalisés par le département Communication (3 s par image, puis le suivant).
    $carrousels = \App\Support\Content\AccueilVitrine::carrousels();
    // Portraits publiés : cartes gérées depuis le dashboard, qui ouvrent la publication sur le réseau social.
    $portraits = collect(\App\Support\Api::get('/cathoimpact/portraits')['portraits'] ?? [])
        ->filter(fn ($p) => preg_match('#^https?://#i', $p['lien'] ?? '') && preg_match('#^https?://#i', $p['image_url'] ?? ''));
    $icones = ['instagram', 'linkedin', 'facebook', 'tiktok', 'youtube'];
    $etapes = [
        ['Je réponds au questionnaire', 'Quelques questions simples sur votre parcours, en quelques minutes.', 'pencil'],
        ['La Communication me recontacte', "Si votre parcours est retenu, l'équipe vous contacte pour préparer le portrait.", 'message-circle'],
        ['Mon portrait est publié', 'Il est diffusé sur nos réseaux sociaux et sur le site du REJCC.', 'sparkles'],
    ];
@endphp

{{--
    Page CathoImpact : animations déclarées par les attributs data-p-*
    (resources/js/vitrine.js, chargé grâce à data-vitrine). Sans JavaScript ou
    si le visiteur réduit les animations, tout s'affiche directement.
--}}
<x-site-layout title="CathoImpact : des parcours qui inspirent" description="CathoImpact met en avant des entrepreneurs et des professionnels catholiques au parcours inspirant. Racontez votre histoire au REJCC en répondant au questionnaire."
    :image="asset('brand/cathoimpact-partage.jpg')" :image-large="true" image-alt="Carrousel CathoImpact : portraits d'entrepreneurs catholiques au parcours inspirant">
<div data-vitrine>

    {{-- 1. En-tête : le texte à gauche, les carrousels qui défilent à droite --}}
    <header class="relative overflow-hidden bg-brand pb-24 pt-32 text-white sm:pt-40 lg:pb-32">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <p class="p-contour absolute -bottom-[3vw] -left-4 font-display text-[22vw] uppercase leading-none [--p-trait:rgba(143,163,217,.16)]" data-p-mouse="-20">Impact</p>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_25%,rgba(79,111,191,.4),transparent_55%)]"></div>
        </div>

        <x-ui.container class="relative grid items-center gap-16 lg:grid-cols-[1fr_.95fr] lg:gap-20">
            <div>
                <nav class="flex items-center gap-1.5 text-sm text-white/55" aria-label="Fil d'Ariane" data-p-reveal="up">
                    <a href="{{ url('/') }}" wire:navigate class="transition-colors hover:text-white">Accueil</a>
                    <x-ui.icon name="chevron-right" class="size-3.5" />
                    <span class="text-white/80">CathoImpact</span>
                </nav>
                <x-ui.eyebrow tone="dark" class="mt-7" data-p-reveal="up">Portraits inspirants</x-ui.eyebrow>
                <h1 class="mt-5 font-display uppercase leading-[0.88] text-[clamp(3.4rem,8vw,7rem)]">
                    <span class="block" data-p-split="chars">Catho</span>
                    <span class="block font-serif normal-case text-azure" data-p-split="chars">Impact<span class="text-accent">.</span></span>
                </h1>
                <p class="mt-6 font-display text-[clamp(1.4rem,2.4vw,2rem)] uppercase leading-tight text-white/90" data-p-reveal="up">Des parcours qui inspirent.</p>
                <div class="mt-5 max-w-xl space-y-4 text-pretty text-[1.05rem] leading-relaxed text-white/75" data-p-reveal="up" data-p-each>
                    <p>CathoImpact est une série de carrousels qui met en lumière des entrepreneurs, des dirigeants, des cadres et des professionnels catholiques&nbsp;: de leurs débuts modestes aux défis surmontés, jusqu'à la réussite.</p>
                    <p>Ces parcours montrent que la foi, l'excellence professionnelle et la réussite concrète peuvent aller de pair. Le vôtre peut en inspirer d'autres.</p>
                </div>
                {{-- Bouton visible dès l'arrivée, avant le carrousel (téléphone compris). --}}
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap" data-p-reveal="up" data-p-each>
                    <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate size="lg" :withArrow="true" data-p-magnet data-test="cathoimpact-repondre">Répondre au questionnaire</x-ui.button>
                    @if ($portraits->isNotEmpty())
                        <x-ui.button href="#portraits" size="lg" variant="white" data-p-magnet>Voir les portraits</x-ui.button>
                    @endif
                </div>
                <ul class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm text-white/70" data-p-reveal="up" data-p-each data-p-stagger=".08">
                    <li class="flex items-center gap-2"><x-ui.icon name="clock" class="size-4 text-accent" />5 minutes pour répondre</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="users" class="size-4 text-accent" />Portrait préparé avec la Com</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="send" class="size-4 text-accent" />Publié sur nos réseaux</li>
                </ul>
            </div>

            <x-cathoimpact.diaporama :carrousels="$carrousels" test="cathoimpact-slide" :sombre="true" :pastille="false" data-p-reveal="up" />
        </x-ui.container>
    </header>

    {{-- 2. Bandeau des valeurs portées par les portraits --}}
    <div class="overflow-hidden bg-accent py-5 text-white" aria-hidden="true">
        <div class="flex w-max" data-p-marquee="-1" data-p-duree="28">
            @foreach ([0, 1] as $copie)
                @foreach (['Foi', 'Excellence', 'Réussite', 'Entreprendre', 'Servir', 'Inspirer'] as $mot)
                    <span class="flex items-center gap-8 whitespace-nowrap pr-8 font-display text-[clamp(1.6rem,3vw,2.4rem)] uppercase leading-none">{{ $mot }}<span class="size-2.5 rounded-full bg-white/60"></span></span>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- 3. Le déroulé, en trois cartes --}}
    <section class="bg-white py-24 sm:py-32" data-test="cathoimpact-etapes">
        <x-ui.container>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div>
                    <x-ui.eyebrow>Votre portrait</x-ui.eyebrow>
                    <h2 class="mt-4 font-display text-[clamp(2.4rem,5vw,4.2rem)] uppercase leading-[0.92] text-brand" data-p-split="words">Comment ça marche ?</h2>
                </div>
                <p class="max-w-sm text-ink/70" data-p-reveal="up">Trois étapes, de votre réponse à la publication. Vous gardez la main à chaque étape.</p>
            </div>
            <ol class="mt-14 grid gap-5 md:grid-cols-3" data-p-reveal="up" data-p-each data-p-stagger=".14">
                @foreach ($etapes as $n => [$titre, $texte, $icone])
                    @php $teinte = ['bg-brand text-white', 'bg-cloud text-brand', 'bg-accent text-white'][$n]; @endphp
                    <li class="p-glare relative flex min-h-[320px] flex-col justify-between overflow-hidden rounded-[2rem] p-8 {{ $teinte }}" data-p-tilt="6">
                        <span class="absolute -right-3 -top-8 font-display text-[10rem] leading-none opacity-[.08]">{{ $n + 1 }}</span>
                        <span class="grid size-14 place-items-center rounded-2xl {{ $n === 1 ? 'bg-brand text-white' : 'bg-white text-brand' }}"><x-ui.icon :name="$icone" class="size-7" /></span>
                        <div>
                            <p class="font-display text-sm uppercase tracking-[0.2em] opacity-60">Étape {{ $n + 1 }}</p>
                            <h3 class="mt-2 font-display text-[1.9rem] uppercase leading-[0.95]">{{ $titre }}</h3>
                            <p class="mt-3 text-pretty leading-relaxed opacity-80">{{ $texte }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-ui.container>
    </section>

    {{-- 4. Portraits publiés --}}
    @if ($portraits->isNotEmpty())
        <section id="portraits" class="scroll-mt-24 bg-cloud py-24 sm:py-32" data-test="cathoimpact-galerie">
            <x-ui.container>
                <x-ui.eyebrow>Portraits publiés</x-ui.eyebrow>
                <h2 class="mt-4 font-display text-[clamp(2.4rem,5vw,4.2rem)] uppercase leading-[0.92] text-brand" data-p-split="words">Ils ont <span class="font-serif normal-case text-azure">inspiré.</span></h2>
                <p class="mt-4 max-w-xl text-ink/70" data-p-reveal="up">Découvrez les parcours déjà mis en lumière par CathoImpact, sur nos réseaux sociaux.</p>
                <div class="mt-12 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4" data-p-reveal="up" data-p-each data-p-stagger=".08">
                    @foreach ($portraits as $p)
                        <a href="{{ $p['lien'] }}" target="_blank" rel="noopener noreferrer" data-test="cathoimpact-portrait" data-p-tilt="5"
                            aria-label="{{ $p['nom'] }} : voir la publication sur {{ $p['reseau_label'] }} (nouvel onglet)"
                            class="p-glare group relative block aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-brand focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent">
                            <img src="{{ $p['image_url'] }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <span class="absolute inset-0 bg-gradient-to-t from-brand-900/95 via-brand-900/25 to-transparent"></span>
                            <span class="absolute inset-x-0 bottom-0 flex flex-col gap-1.5 p-4 text-white sm:p-5">
                                <span class="font-display text-[1.2rem] uppercase leading-none sm:text-[1.45rem]">{{ $p['nom'] }}</span>
                                @if ($p['accroche'] ?? null)
                                    <span class="line-clamp-3 max-h-0 text-[13px] leading-snug text-white/80 opacity-0 transition-all duration-500 group-hover:max-h-16 group-hover:opacity-100 group-focus-visible:max-h-16 group-focus-visible:opacity-100">{{ $p['accroche'] }}</span>
                                @endif
                                <span class="mt-1 flex items-center gap-1.5 text-[12px] font-bold text-white/85">
                                    @if (in_array($p['reseau'] ?? '', $icones, true))
                                        <x-ui.social-icon :type="$p['reseau']" class="size-3.5" />
                                    @else
                                        <x-ui.icon name="external-link" class="size-3.5" />
                                    @endif
                                    {{ ($p['reseau'] ?? 'autre') === 'autre' ? 'Voir la publication' : $p['reseau_label'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-ui.container>
        </section>
    @endif

    {{-- 5. Appel final --}}
    <section class="relative overflow-hidden bg-brand py-28 text-center text-white sm:py-36">
        <x-ui.container class="relative">
            <h2 class="font-display text-[clamp(2.8rem,8vw,7rem)] uppercase leading-[0.9]" data-p-grandit=".6">Votre parcours <span class="font-serif normal-case text-azure">peut inspirer.</span></h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-white/75" data-p-reveal="up">Racontez-nous votre histoire : la Communication vous recontacte si elle est retenue.</p>
            <div class="mt-10" data-p-reveal="up">
                <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate size="lg" :withArrow="true" data-p-magnet=".5">Répondre au questionnaire</x-ui.button>
            </div>
        </x-ui.container>
    </section>
</div>
</x-site-layout>
