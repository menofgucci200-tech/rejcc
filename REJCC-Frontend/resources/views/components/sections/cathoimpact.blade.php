@php
    // Les 3 derniers portraits publiés de la galerie CathoImpact (gérée par la Com
    // dans le dashboard). Cache court comme le reste de la vitrine ; une réponse en
    // erreur n'est pas mise en cache pour ne pas masquer la galerie 5 minutes.
    $portraits = \Illuminate\Support\Facades\Cache::get('cathoimpact-portraits');
    if ($portraits === null) {
        $r = \App\Support\Api::get('/cathoimpact/portraits');
        $portraits = $r['portraits'] ?? [];
        if ($r['ok'] ?? false) {
            \Illuminate\Support\Facades\Cache::put('cathoimpact-portraits', $portraits, 300);
        }
    }
    $portraits = collect($portraits)
        ->filter(fn ($p) => preg_match('#^https?://#i', $p['lien'] ?? '') && preg_match('#^https?://#i', $p['image_url'] ?? ''))
        ->sortByDesc('id')
        ->take(3)
        ->values();
    $icones = ['instagram', 'linkedin', 'facebook', 'tiktok', 'youtube'];

    $accroche = \App\Support\Content\SiteRemote::field('home', 'cathoimpact', 'subtitle', "Entrepreneurs, dirigeants, cadres : des catholiques qui réussissent en restant fidèles à leur foi. Votre parcours peut en inspirer d'autres.");

    // Trois slides du premier carrousel, disposées en éventail (la première devant).
    $slides = [
        ['n' => '05', 'alt' => ''],
        ['n' => '08', 'alt' => ''],
        ['n' => '01', 'alt' => "Exemple de carrousel CathoImpact : portrait d'Ance Charlemagne Kouakou, fondateur de X-House Immobilier"],
    ];
@endphp

<section id="cathoimpact" class="relative overflow-hidden bg-white py-24 sm:py-32" data-test="accueil-cathoimpact">
    <div class="pointer-events-none absolute -right-32 top-10 size-[28rem] rounded-full bg-azure/10 blur-3xl"></div>

    <x-ui.container class="relative">
        <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-20">
            <div>
                <x-ui.section-heading align="left" eyebrow="Portraits inspirants" :subtitle="$accroche">
                    <x-slot:title>
                        CathoImpact&nbsp;: des parcours qui <span class="text-gradient">inspirent</span>
                    </x-slot:title>
                </x-ui.section-heading>

                <ul class="mt-8 flex flex-col gap-3 text-[0.95rem] text-ink/75">
                    <li class="flex items-start gap-3">
                        <span class="mt-2 h-0.5 w-5 shrink-0 bg-accent"></span>
                        Un questionnaire simple, en quelques minutes.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-2 h-0.5 w-5 shrink-0 bg-accent"></span>
                        La Communication vous recontacte si votre parcours est retenu.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-2 h-0.5 w-5 shrink-0 bg-accent"></span>
                        Votre portrait est publié sur nos réseaux et sur le site.
                    </li>
                </ul>

                <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate :with-arrow="true" data-test="accueil-cathoimpact-raconter">Raconter mon parcours</x-ui.button>
                    <x-ui.button :href="route('cathoimpact')" wire:navigate variant="outline" data-test="accueil-cathoimpact-decouvrir">Découvrir CathoImpact</x-ui.button>
                </div>
            </div>

            {{-- Éventail de 3 slides : décoratif sauf la première, qui porte le texte alternatif. --}}
            <a href="{{ route('cathoimpact') }}" wire:navigate class="group relative mx-auto block w-full max-w-[230px] sm:max-w-[340px] focus-visible:outline-none" aria-label="Voir le carrousel CathoImpact">
                <div class="relative aspect-[4/5]">
                    @foreach ($slides as $i => $s)
                        @php
                            $pose = [
                                'translate-x-[-22%] -rotate-[7deg] scale-[0.9] opacity-80 group-hover:-rotate-[10deg] group-hover:translate-x-[-27%]',
                                'translate-x-[22%] rotate-[7deg] scale-[0.9] opacity-80 group-hover:rotate-[10deg] group-hover:translate-x-[27%]',
                                'group-hover:-translate-y-1.5 group-focus-visible:ring-4 group-focus-visible:ring-accent',
                            ][$i];
                        @endphp
                        <img src="{{ asset('brand/cathoimpact/'.$s['n'].'.jpg') }}" alt="{{ $s['alt'] }}" width="864" height="1080" loading="lazy" decoding="async"
                            class="absolute inset-0 size-full rounded-2xl object-cover shadow-[0_24px_60px_-28px_rgba(3,29,89,.6)] ring-1 ring-brand/10 transition-transform duration-500 ease-out motion-reduce:transition-none {{ $pose }}"
                            data-test="accueil-cathoimpact-slide">
                    @endforeach
                </div>
            </a>
        </div>

        @if ($portraits->isNotEmpty())
            <div class="mt-20" data-test="accueil-cathoimpact-galerie">
                <div class="flex items-end justify-between gap-4">
                    <h3 class="font-display text-2xl uppercase tracking-tight text-brand sm:text-3xl">Derniers portraits publiés</h3>
                    <a href="{{ route('cathoimpact') }}" wire:navigate class="hidden shrink-0 text-sm font-bold text-accent hover:underline sm:inline">Voir tous les portraits</a>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @foreach ($portraits as $i => $p)
                        <x-ui.reveal :delay="$i * 0.08">
                            <a href="{{ $p['lien'] }}" target="_blank" rel="noopener noreferrer" data-test="accueil-cathoimpact-portrait"
                                aria-label="{{ $p['nom'] }} : voir la publication sur {{ $p['reseau_label'] }} (nouvel onglet)"
                                class="group flex h-full overflow-hidden rounded-2xl bg-cloud ring-1 ring-brand/5 transition hover:-translate-y-1 hover:shadow-[0_22px_44px_-22px_rgba(3,29,89,.55)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                <span class="relative block aspect-[4/5] w-28 shrink-0 self-start overflow-hidden bg-brand/10 sm:w-32">
                                    <img src="{{ $p['image_url'] }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col gap-1 p-4">
                                    <span class="font-display text-[1.15rem] uppercase leading-tight tracking-[0.01em] text-brand sm:text-[1.3rem]">{{ $p['nom'] }}</span>
                                    @if ($p['accroche'] ?? null)
                                        <span class="line-clamp-3 text-[13px] leading-snug text-ink/75 sm:text-sm">{{ $p['accroche'] }}</span>
                                    @endif
                                    <span class="mt-auto flex items-center gap-1.5 pt-2 text-[12px] font-bold text-accent">
                                        @if (in_array($p['reseau'] ?? '', $icones, true))
                                            <x-ui.social-icon :type="$p['reseau']" class="size-3.5" />
                                        @else
                                            <x-ui.icon name="external-link" class="size-3.5" />
                                        @endif
                                        {{ ($p['reseau'] ?? 'autre') === 'autre' ? 'Voir la publication' : $p['reseau_label'] }}
                                    </span>
                                </span>
                            </a>
                        </x-ui.reveal>
                    @endforeach
                </div>
                <a href="{{ route('cathoimpact') }}" wire:navigate class="mt-5 inline-block text-sm font-bold text-accent hover:underline sm:hidden">Voir tous les portraits</a>
            </div>
        @endif
    </x-ui.container>
</section>
