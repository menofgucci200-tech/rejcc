@php
    use App\Support\Content\AccueilVitrine;
    use App\Support\Content\SiteRemote;

    extract(AccueilVitrine::donnees());

    $heroSurtitre = SiteRemote::field('home', 'hero', 'eyebrow', "Jeunes Chrétiens Catholiques · Côte d'Ivoire");
    $heroTexte = SiteRemote::field('home', 'hero', 'subtitle', 'Le réseau des jeunes entrepreneurs et professionnels catholiques : formations, mentorat, événements et une communauté qui vous pousse plus loin.');
    $cathoAccroche = SiteRemote::field('home', 'cathoimpact', 'subtitle', "Entrepreneurs, dirigeants, cadres : des catholiques qui réussissent en restant fidèles à leur foi. Votre parcours peut en inspirer d'autres.");
    $finTitre = SiteRemote::field('home', 'cta-band', 'title', 'Votre place est ici.');
    $finTexte = SiteRemote::field('home', 'cta-band', 'text', 'Rejoignez une communauté de jeunes entrepreneurs catholiques déterminés à grandir, collaborer et réussir, ensemble.');
    $icones = ['instagram', 'linkedin', 'facebook', 'tiktok', 'youtube'];
@endphp

{{--
    Accueil « Récit » : l'identité du réseau racontée au fil du défilement.
    Chaque section reste masquable depuis Pages du site → Accueil. Les
    animations (resources/js/vitrine.js) sont déclarées par les attributs
    data-p-* ; sans JavaScript ou si le visiteur réduit les animations, tout
    s'affiche directement.
--}}
<x-site-layout>
    <x-slot:intro><x-sections.intro /></x-slot:intro>

<div data-vitrine>

    {{-- 1. Hero : titre au mot qui change, trois portes d'entrée --}}
    <section id="accueil" class="relative flex min-h-[100svh] items-center overflow-hidden bg-brand pb-20 pt-32 text-white" data-hero-motion data-p-hero>
        @include('pages.accueil._script-hero')
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <p class="p-contour absolute -left-6 bottom-[-4vw] font-display text-[30vw] uppercase leading-none [--p-trait:rgba(143,163,217,.18)]" data-p-in="fondu" data-p-at=".2" data-p-mouse="-25">REJCC</p>
            <div class="absolute -right-20 top-10 h-[80%] opacity-[.06]" data-p-mouse="35"><img src="{{ asset('brand/rejcc-monogram-white.svg') }}" alt="" class="h-full" data-p-in="zoom" data-p-at="0"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(79,111,191,.35),transparent_55%)]"></div>
        </div>

        <div class="relative mx-auto grid w-full max-w-7xl items-center gap-12 container-px lg:grid-cols-[1.15fr_.85fr]">
            <div>
                <p class="flex items-center gap-3 text-[0.72rem] font-bold uppercase tracking-[0.22em] text-white/75">
                    <span class="h-px w-10 bg-accent" data-p-in="trait" data-p-at=".1"></span>
                    <span data-p-in="up" data-p-at=".15">{{ $heroSurtitre }}</span>
                </p>
                <h1 class="mt-6 font-display uppercase leading-[0.92] text-[clamp(3rem,7.4vw,6.6rem)]">
                    <span class="block" data-p-in="up" data-p-split="words" data-p-at=".25">Ensemble</span>
                    <span class="block" data-p-in="up" data-p-split="words" data-p-at=".35">pour</span>
                    <span class="relative block h-[1.22em] overflow-hidden font-serif normal-case tracking-normal text-azure" data-p-rotator="2300" data-p-in="up" data-p-at=".5">
                        @foreach ($mots as $m)<span class="absolute inset-x-0 top-0">{{ $m }}<span class="text-accent">.</span></span>@endforeach
                    </span>
                </h1>
                <p class="mt-7 max-w-xl text-pretty text-lg leading-relaxed text-white/80" data-p-in="up" data-p-at=".8">{{ $heroTexte }}</p>
            </div>

            <nav class="flex flex-col border-t border-white/15" aria-label="Accès rapides" data-p-in="droite" data-p-each data-p-at="1">
                @foreach ($actions as $i => $a)
                    <a href="{{ $a['lien'] }}" class="group relative flex items-center gap-5 overflow-hidden border-b border-white/15 py-6 pl-2 pr-3">
                        <span class="absolute inset-0 origin-left scale-x-0 transition-transform duration-500 ease-[cubic-bezier(.22,1,.36,1)] group-hover:scale-x-100 {{ $i === 0 ? 'bg-accent' : 'bg-white/10' }}"></span>
                        <span class="relative font-display text-sm text-white/40 transition-colors group-hover:text-white">0{{ $i + 1 }}</span>
                        <span class="relative min-w-0 flex-1">
                            <span class="block font-display text-[clamp(1.6rem,2.6vw,2.3rem)] uppercase leading-none transition-transform duration-500 group-hover:translate-x-2">{{ $a['titre'] }}</span>
                            <span class="mt-1 block text-sm text-white/65">{{ $a['texte'] }}</span>
                        </span>
                        <span class="relative grid size-11 place-items-center rounded-full border border-white/30 transition-all duration-500 group-hover:-rotate-45 group-hover:border-white group-hover:bg-white group-hover:text-brand"><x-ui.icon name="arrow-right" class="size-5" /></span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-[10px] font-bold uppercase tracking-[0.3em] text-white/50 sm:flex" data-p-in="fondu" data-p-at="1.6">
            Défiler<span class="h-10 w-px animate-pulse bg-gradient-to-b from-white/60 to-transparent"></span>
        </div>
    </section>

    {{-- 2. Raison d'être : le texte s'allume, le fond passe du bleu au sable --}}
    @if (SiteRemote::visible('home', 'about'))
    <section id="a-propos" class="relative bg-sable py-32 text-brand sm:py-44" data-p-teinte="#031d59|#f6eee3">
        <x-ui.container class="max-w-5xl">
            <p class="font-display text-sm uppercase tracking-[0.3em] text-accent" data-p-reveal="up">Notre raison d'être</p>
            <p class="mt-8 font-serif text-[clamp(1.9rem,4.4vw,3.6rem)] leading-[1.18]" data-p-fill="bottom 55%">{{ $site['positioning'] }}</p>
            <div class="mt-12 grid gap-8 sm:grid-cols-2" data-p-reveal="up" data-p-each>
                <div class="border-t-2 border-accent pt-5"><p class="font-display text-xl uppercase">Notre mission</p><p class="mt-2 leading-relaxed text-ink/75">{{ $site['mission'] }}</p></div>
                <div class="border-t-2 border-brand pt-5"><p class="font-display text-xl uppercase">Notre vision</p><p class="mt-2 leading-relaxed text-ink/75">{{ $site['vision'] }}</p></div>
            </div>
            <div class="mt-12" data-p-reveal="up"><x-ui.button href="/a-propos" variant="outline" :with-arrow="true" data-p-magnet>Découvrir notre histoire</x-ui.button></div>
        </x-ui.container>
    </section>
    @endif

    {{-- 3. Valeurs : cartes empilées qui reculent l'une après l'autre --}}
    @if (SiteRemote::visible('home', 'values') && $valeurs->isNotEmpty())
    <section class="bg-sable pb-24 pt-8">
        <x-ui.container>
            <h2 class="font-display text-[clamp(2.6rem,6vw,5rem)] uppercase leading-[0.92] text-brand" data-p-split="words">{{ $valeurs->count() > 1 ? $valeurs->count().' valeurs,' : 'Une valeur,' }} <span class="font-serif normal-case text-azure">une boussole.</span></h2>
            <div class="mt-12 flex flex-col gap-6" data-p-stack>
                @foreach ($valeurs as $i => $v)
                    @php $teinte = ['bg-brand text-white', 'bg-white text-brand', 'bg-accent text-white', 'bg-azure text-white', 'bg-brand-700 text-white'][$i % 5]; @endphp
                    <article class="sticky flex min-h-[62vh] flex-col justify-between overflow-hidden rounded-[2.25rem] p-8 shadow-[0_-20px_50px_-30px_rgba(3,29,89,.45)] sm:p-12 {{ $teinte }}" style="top: {{ 90 + $i * 18 }}px" data-p-stack-card>
                        <div class="flex items-start justify-between">
                            <span class="grid size-16 place-items-center rounded-2xl bg-current/10"><x-ui.icon :name="$v['icon']" class="size-8" /></span>
                            <span class="font-display text-7xl leading-none opacity-20">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="grid items-end gap-6 lg:grid-cols-2">
                            <h3 class="font-display text-[clamp(3rem,9vw,8rem)] uppercase leading-[0.85]">{{ $v['title'] }}</h3>
                            <p class="max-w-md text-lg leading-relaxed opacity-80">{{ $v['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-ui.container>
    </section>
    @endif

    {{-- 4. Parcours d'adhésion : défilement horizontal épinglé --}}
    @if (SiteRemote::visible('home', 'how-to-join') && $etapes->isNotEmpty())
    <section class="relative overflow-hidden bg-brand text-white lg:h-screen" data-p-hscroll>
        <div class="flex flex-col lg:h-full lg:w-max lg:flex-row" data-p-track>
            <div class="flex w-full shrink-0 flex-col justify-center px-6 py-20 lg:w-[45vw] lg:pl-[8vw]">
                <p class="font-display text-sm uppercase tracking-[0.3em] text-accent">Adhérer</p>
                <h2 class="mt-4 font-display text-[clamp(3rem,6vw,5.6rem)] uppercase leading-[0.9]">Votre parcours<br><span class="font-serif normal-case text-azure">en {{ $etapes->count() }} étapes.</span></h2>
                <p class="mt-6 max-w-sm text-white/70">Faites défiler : chaque étape vous rapproche de la communauté.</p>
            </div>
            @foreach ($etapes as $i => $e)
                <div class="flex w-full shrink-0 items-center px-6 pb-10 lg:w-[38vw] lg:px-[2vw] lg:pb-0">
                    <article class="relative w-full overflow-hidden rounded-[2rem] bg-white/[.06] p-8 ring-1 ring-white/10 lg:h-[62vh]" data-p-hin>
                        <span class="absolute -right-4 -top-10 font-display text-[11rem] leading-none text-white/[.06]">{{ $i + 1 }}</span>
                        <span class="grid size-16 place-items-center rounded-2xl bg-accent"><x-ui.icon :name="$e['icon']" class="size-8" /></span>
                        <p class="mt-10 font-display text-sm uppercase tracking-[0.2em] text-[#8fa3d9]">Étape {{ $i + 1 }}</p>
                        <h3 class="mt-2 font-display text-[clamp(2rem,3vw,2.8rem)] uppercase leading-none">{{ $e['title'] }}</h3>
                        <p class="mt-4 max-w-sm text-lg leading-relaxed text-white/75">{{ $e['text'] }}</p>
                    </article>
                </div>
            @endforeach
            <div class="flex w-full shrink-0 flex-col items-start justify-center px-6 pb-20 lg:w-[40vw] lg:pb-0 lg:pl-[4vw]" data-p-hin>
                <h3 class="font-display text-[clamp(2.6rem,5vw,4.6rem)] uppercase leading-[0.9]">C'est<br>parti&nbsp;?</h3>
                <x-ui.button href="/adhesion" size="lg" :with-arrow="true" class="mt-8" data-p-magnet>Commencer mon adhésion</x-ui.button>
            </div>
        </div>
        <div class="absolute inset-x-[8vw] bottom-8 hidden h-1 rounded bg-white/15 lg:block"><span class="block h-full origin-left scale-x-0 rounded bg-accent" data-p-progress></span></div>
    </section>
    @endif

    {{-- 5. CathoImpact : le carrousel défile dans un téléphone pendant que les
         trois étapes du parcours s'allument ; puis les derniers portraits publiés. --}}
    @if (SiteRemote::visible('home', 'cathoimpact'))
    <section id="cathoimpact" class="relative overflow-hidden bg-white" data-test="accueil-cathoimpact">
        <div class="relative lg:h-screen" data-p-catho>
            <div class="pointer-events-none absolute -right-40 top-1/4 size-[36rem] rounded-full bg-azure/10 blur-3xl" aria-hidden="true"></div>
            <x-ui.container class="relative grid h-full items-center gap-14 py-24 lg:grid-cols-[1.05fr_.95fr] lg:gap-20 lg:py-0 lg:pt-16">
                <div>
                    <x-ui.eyebrow>Portraits inspirants</x-ui.eyebrow>
                    <h2 class="mt-5 font-display text-[clamp(2.4rem,4.4vw,4rem)] uppercase leading-[0.92] text-brand" data-p-split="words">CathoImpact&nbsp;: des parcours qui <span class="font-serif normal-case text-azure">inspirent.</span></h2>
                    <p class="mt-5 max-w-lg text-pretty text-[1.05rem] leading-relaxed text-ink/75" data-p-reveal="up">{{ $cathoAccroche }}</p>

                    <ol class="relative mt-8 flex flex-col gap-5" aria-label="Comment ça marche">
                        <span class="absolute bottom-6 left-[1.2rem] top-6 w-0.5 rounded bg-brand/10" aria-hidden="true"><span class="block h-full w-full origin-top rounded bg-accent" data-p-catho-ligne></span></span>
                        @foreach ([
                            ['Vous racontez', 'Un questionnaire simple, en quelques minutes.'],
                            ['La Communication vous recontacte', 'Si votre parcours est retenu, on prépare votre portrait ensemble.'],
                            ['Votre portrait est publié', 'Sur nos réseaux et dans la galerie CathoImpact du site.'],
                        ] as $i => [$titre, $texte])
                            <li class="relative flex items-start gap-4" data-p-catho-etape>
                                <span class="p-etape-num relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-accent font-display text-lg text-white">{{ $i + 1 }}</span>
                                <span class="p-etape-texte pt-1">
                                    <span class="block font-display text-xl uppercase leading-tight text-brand">{{ $titre }}</span>
                                    <span class="block text-sm text-ink/70">{{ $texte }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate :with-arrow="true" data-p-magnet data-test="accueil-cathoimpact-raconter">Raconter mon parcours</x-ui.button>
                        <x-ui.button :href="route('cathoimpact')" wire:navigate variant="outline" data-p-magnet data-test="accueil-cathoimpact-decouvrir">Découvrir CathoImpact</x-ui.button>
                    </div>
                </div>

                {{-- Le carrousel CathoImpact tel qu'il apparaît sur les réseaux --}}
                <div class="relative mx-auto w-full max-w-[300px]">
                    <div class="relative rounded-[2.8rem] bg-brand-900 p-3 shadow-[0_50px_90px_-40px_rgba(3,29,89,.75)] ring-1 ring-black/10" data-p-reveal="up">
                        <span class="absolute left-1/2 top-3 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-brand-900" aria-hidden="true"></span>
                        <div class="overflow-hidden rounded-[2.2rem] bg-white">
                            <div class="flex items-center gap-2.5 px-4 pb-3 pt-7">
                                <span class="grid size-8 place-items-center rounded-full bg-brand p-1.5"><img src="{{ asset('brand/rejcc-monogram-white.svg') }}" alt="" class="h-full"></span>
                                <span class="min-w-0 flex-1 leading-tight"><span class="block text-[13px] font-bold text-brand">REJCC</span><span class="block text-[11px] text-meta">CathoImpact</span></span>
                                <x-ui.icon name="more-horizontal" class="size-4 text-meta" />
                            </div>
                            <div class="overflow-hidden">
                                <div class="flex snap-x snap-mandatory overflow-x-auto" data-p-catho-piste>
                                    @foreach ($slides as $i => $n)
                                        <img src="{{ asset('brand/cathoimpact/'.$n.'.jpg') }}" width="864" height="1080" loading="lazy" decoding="async"
                                             alt="{{ $i === 0 ? "Exemple de carrousel CathoImpact : portrait d'Ance Charlemagne Kouakou, fondateur de X-House Immobilier" : '' }}"
                                             class="aspect-[4/5] w-full shrink-0 snap-center object-cover" data-test="accueil-cathoimpact-slide">
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex items-center justify-between px-4 py-3">
                                <span class="flex gap-3 text-brand" aria-hidden="true"><x-ui.icon name="heart-handshake" class="size-5" /><x-ui.icon name="message-circle" class="size-5" /><x-ui.icon name="send" class="size-5" /></span>
                                <span class="flex items-center gap-1.5">
                                    @foreach ($slides as $i => $n)
                                        <button type="button" class="h-1.5 w-1.5 rounded-full bg-brand/20 {{ $i === 0 ? 'is-actif' : '' }}" data-p-catho-point aria-label="Image {{ $i + 1 }}"></button>
                                    @endforeach
                                </span>
                            </div>
                            <p class="px-4 pb-6 text-[12px] leading-snug text-ink/70"><b class="text-brand">REJCC</b> Il a lancé sa plateforme immobilière à 25 ans. Découvrez son parcours… <span class="text-meta">#CathoImpact</span></p>
                        </div>
                    </div>
                    <span class="absolute -left-10 top-24 hidden rotate-[-6deg] rounded-2xl bg-white px-4 py-3 text-sm font-bold text-brand shadow-[0_20px_40px_-20px_rgba(3,29,89,.5)] ring-1 ring-brand/10 sm:block" data-p-mouse="22">⏱ 5 minutes pour répondre</span>
                    <a href="{{ route('cathoimpact.questionnaire') }}" wire:navigate class="absolute -right-8 bottom-28 hidden rotate-6 rounded-2xl bg-accent px-4 py-3 font-display text-lg uppercase leading-none text-white shadow-xl transition-transform hover:rotate-0 hover:scale-105 sm:block" data-p-mouse="-28">Votre portrait<br>ici&nbsp;?</a>
                </div>
            </x-ui.container>
        </div>

        @if ($portraits->isNotEmpty())
            <x-ui.container class="pb-24 sm:pb-32" data-test="accueil-cathoimpact-galerie">
                <div class="flex items-end justify-between gap-4 border-t border-brand/10 pt-12">
                    <h3 class="font-display text-[clamp(1.8rem,3vw,2.6rem)] uppercase leading-none text-brand">Derniers portraits <span class="font-serif normal-case text-azure">publiés</span></h3>
                    <a href="{{ route('cathoimpact') }}" wire:navigate class="hidden shrink-0 text-sm font-bold text-accent hover:underline sm:inline">Voir tous les portraits →</a>
                </div>
                <div class="mt-8 grid gap-5 md:grid-cols-3" data-p-reveal="up" data-p-each data-p-stagger=".12">
                    @foreach ($portraits as $p)
                        <a href="{{ $p['lien'] }}" target="_blank" rel="noopener noreferrer" data-test="accueil-cathoimpact-portrait" data-p-tilt="5"
                           aria-label="{{ $p['nom'] }} : voir la publication sur {{ $p['reseau_label'] }} (nouvel onglet)"
                           class="p-glare group relative block aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-brand focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent">
                            <img src="{{ $p['image_url'] }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <span class="absolute inset-0 bg-gradient-to-t from-brand-900/95 via-brand-900/30 to-transparent"></span>
                            <span class="absolute inset-x-0 bottom-0 flex flex-col gap-1.5 p-6 text-white">
                                <span class="font-display text-[1.6rem] uppercase leading-none">{{ $p['nom'] }}</span>
                                @if ($p['accroche'] ?? null)
                                    <span class="line-clamp-2 max-h-0 text-sm leading-snug text-white/80 opacity-0 transition-all duration-500 group-hover:max-h-12 group-hover:opacity-100 group-focus-visible:max-h-12 group-focus-visible:opacity-100">{{ $p['accroche'] }}</span>
                                @endif
                                <span class="mt-1 flex items-center gap-1.5 text-xs font-bold text-white/85">
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
                <a href="{{ route('cathoimpact') }}" wire:navigate class="mt-6 inline-block text-sm font-bold text-accent hover:underline sm:hidden">Voir tous les portraits →</a>
            </x-ui.container>
        @endif
    </section>
    @endif

    {{-- 6. Le réseau en chiffres : chaque chiffre a sa petite animation --}}
    @if (SiteRemote::visible('home', 'stats') && $stats->isNotEmpty())
    <section class="relative overflow-hidden bg-brand-900 py-24 text-white sm:py-32">
        <div class="pointer-events-none absolute -left-40 top-0 size-[32rem] rounded-full bg-azure/20 blur-3xl" aria-hidden="true"></div>
        <x-ui.container class="relative">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="font-display text-sm uppercase tracking-[0.3em] text-accent" data-p-reveal="up">Le réseau en chiffres</p>
                    <h2 class="mt-4 font-display text-[clamp(2.6rem,6vw,5rem)] uppercase leading-[0.92]" data-p-split="words">Une communauté <span class="font-serif normal-case text-azure">qui grandit.</span></h2>
                </div>
                <p class="max-w-sm text-white/70" data-p-reveal="up">Étudiants, porteurs de projets, entrepreneurs et cadres, réunis partout en Côte d'Ivoire par la foi et l'envie d'entreprendre.</p>
            </div>

            <div class="mt-14 grid gap-4 lg:grid-cols-4" data-p-reveal="up" data-p-each data-p-stagger=".1">
                @foreach ($stats as $i => $s)
                    @php
                        $visuel = AccueilVitrine::visuel($s['label']);
                        $valeur = $s['value'].($s['suffix'] ?? '');
                        $place = ['lg:col-span-2 lg:row-span-2', 'lg:col-span-2', '', ''][$i] ?? '';
                    @endphp
                    <article class="p-glare relative flex flex-col overflow-hidden rounded-[2rem] bg-white/[.05] p-7 ring-1 ring-white/10 sm:p-8 {{ $place }}" data-p-tilt="3">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#8fa3d9]">{{ $s['label'] }}</p>
                        <p class="mt-3 font-display leading-none {{ $i === 0 ? 'text-[clamp(5rem,11vw,9rem)]' : 'text-[clamp(3.6rem,6vw,5rem)]' }}" data-p-odo="{{ $valeur }}">{{ $valeur }}</p>

                        @if ($visuel === 'points')
                            <div class="mt-auto grid grid-cols-[repeat(18,minmax(0,1fr))] gap-2 pt-10 sm:gap-2.5" data-p-points aria-hidden="true">
                                @for ($k = 0; $k < 144; $k++)<span class="aspect-square rounded-full {{ $k % 7 === 3 ? 'bg-azure' : 'bg-white/80' }}"></span>@endfor
                            </div>
                            <p class="mt-6 text-sm text-white/60">Et chaque mois, de nouveaux membres nous rejoignent.</p>
                        @elseif ($visuel === 'domaines' && $domaines->isNotEmpty())
                            @php [$rang1, $rang2] = $domaines->split(2)->pad(2, collect())->all(); @endphp
                            <div class="-mx-8 mt-6 flex flex-col gap-2.5 overflow-hidden" aria-hidden="true">
                                @foreach ([[$rang1, -1], [$rang2, 1]] as [$rang, $sens])
                                    <div class="flex w-max gap-2.5" data-p-marquee="{{ $sens }}" data-p-duree="38">
                                        @foreach ([0, 1] as $copie)
                                            @foreach ($rang as $d)<span class="whitespace-nowrap rounded-full border border-white/15 bg-white/[.04] px-4 py-2 text-sm font-semibold text-white/85">{{ $d }}</span>@endforeach
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <a href="/domaines" wire:navigate class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-white hover:text-azure">Voir tous les domaines <x-ui.icon name="arrow-right" class="size-4" /></a>
                        @elseif ($visuel === 'calendrier')
                            <div class="mt-auto grid grid-cols-6 gap-1.5 pt-6" data-p-mois aria-hidden="true">
                                @foreach (['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'] as $m)<span class="rounded-lg bg-white/[.06] py-1.5 text-center text-xs font-bold text-white/60">{{ $m }}</span>@endforeach
                            </div>
                            <a href="/evenements" wire:navigate class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-white hover:text-azure">Toute l'année <x-ui.icon name="arrow-right" class="size-4" /></a>
                        @else
                            <div class="mt-auto flex pt-6" data-p-reveal="pop" data-p-each aria-hidden="true">
                                @foreach (['graduation-cap', 'lightbulb', 'award', 'rocket'] as $k => $ic)
                                    <span class="-ml-3 grid size-12 place-items-center rounded-full ring-4 ring-brand-900 first:ml-0 {{ ['bg-accent', 'bg-azure', 'bg-white text-brand', 'bg-brand-700'][$k] }}"><x-ui.icon :name="$ic" class="size-5" /></span>
                                @endforeach
                            </div>
                            <p class="mt-5 text-sm text-white/60">Pour vous former et vous accompagner.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </x-ui.container>
    </section>
    @endif

    {{-- 7. Agenda : bande de cartes à faire glisser --}}
    @if (SiteRemote::visible('home', 'events') && $evenements->isNotEmpty())
    <section class="overflow-hidden bg-white py-24 sm:py-32">
        <x-ui.container class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
            <div>
                <x-ui.eyebrow>Agenda</x-ui.eyebrow>
                <h2 class="mt-4 font-display text-[clamp(2.4rem,5vw,4.2rem)] uppercase leading-[0.95] text-brand" data-p-split="words">À vos <span class="font-serif normal-case text-azure">agendas.</span></h2>
            </div>
            <x-ui.button href="/evenements" variant="outline" :with-arrow="true" data-p-magnet>Tout l'agenda</x-ui.button>
        </x-ui.container>
        <div class="mt-12 flex snap-x snap-mandatory gap-5 overflow-x-auto px-[max(1.5rem,calc((100vw-80rem)/2+2rem))] pb-6 [scrollbar-width:none]" data-p-reveal="droite" data-p-each data-p-stagger=".12">
            @foreach ($evenements as $i => $e)
                <a href="{{ $e['lien'] }}" wire:navigate.hover class="p-glare group flex min-h-[340px] w-[300px] shrink-0 snap-start flex-col justify-between rounded-[2rem] p-7 sm:w-[360px] {{ ['bg-brand text-white', 'bg-cloud text-brand', 'bg-accent text-white', 'bg-azure text-white'][$i % 4] }}" data-p-tilt="8">
                    <div class="flex items-start justify-between">
                        <span class="font-display text-7xl leading-none">{{ $e['date']->format('d') }}</span>
                        <span class="rounded-full bg-current/10 px-3 py-1 text-xs font-bold uppercase tracking-wider">{{ $e['date']->locale('fr')->translatedFormat('F') }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider opacity-70">{{ $e['categorie'] }}</p>
                        <h3 class="mt-2 font-display text-[1.9rem] uppercase leading-[0.95]">{{ $e['titre'] }}</h3>
                        <p class="mt-3 flex items-center gap-2 text-sm opacity-75"><x-ui.icon name="map-pin" class="size-4" />{{ $e['lieu'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 8. Appel final : le titre grandit jusqu'à remplir l'écran --}}
    @if (SiteRemote::visible('home', 'cta-band'))
    <section class="relative overflow-hidden bg-brand py-32 text-center text-white sm:py-44">
        <div class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 opacity-[.06]" aria-hidden="true">
            <div class="flex w-max" data-p-marquee="-1" data-p-duree="40">
                @foreach ([0, 1] as $c)<span class="whitespace-nowrap px-8 font-display text-[18rem] uppercase leading-none">{{ $valeurs->pluck('title')->take(4)->implode(' · ') ?: 'Ensemble pour l\'excellence' }} ·</span>@endforeach
            </div>
        </div>
        <x-ui.container class="relative">
            <h2 class="font-display text-[clamp(3.4rem,10vw,9rem)] uppercase leading-[0.88]" data-p-grandit=".55">{{ $finTitre }}</h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-white/75" data-p-reveal="up">{{ $finTexte }}</p>
            <div class="mt-10 flex flex-wrap justify-center gap-3" data-p-reveal="up" data-p-each>
                <x-ui.button href="/adhesion" size="lg" :with-arrow="true" data-p-magnet=".5">Rejoindre le réseau</x-ui.button>
                <x-ui.button :href="route('login')" size="lg" variant="white" data-p-magnet=".5">Mon espace membre</x-ui.button>
            </div>
        </x-ui.container>
    </section>
    @endif
</div>
</x-site-layout>
