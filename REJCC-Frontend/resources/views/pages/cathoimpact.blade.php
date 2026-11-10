@php
    // Premier carrousel réalisé par le département Communication, slide par slide.
    $slides = [
        'Portrait CathoImpact : Ance Charlemagne Kouakou, fondateur de X-House Immobilier. Zéro visite payante, zéro mois d\'agence, une location dans le respect des règles.',
        'Né en 2003, Ance Charlemagne Kouakou est un jeune entrepreneur ivoirien qui s\'intéresse à l\'immobilier et aux solutions digitales.',
        'Très tôt, il développe une ambition entrepreneuriale tournée vers la création de solutions innovantes et s\'intéresse au secteur immobilier.',
        'Les difficultés rencontrées par les locataires deviennent le point de départ de sa vision entrepreneuriale.',
        'Lancé en 2023, X-House Immobilier est un projet de plateforme digitale : visites virtuelles et absence de frais hormis ceux exigés par l\'État.',
        'Sur le marché immobilier ivoirien, les frais supplémentaires peuvent rendre l\'accès à un logement très coûteux.',
        'Remettre en question des pratiques bien installées représente un véritable défi.',
        'Son ambition : un marché immobilier ivoirien plus transparent, plus accessible et respectueux des droits des propriétaires et des locataires.',
        'Pour Ance Kouakou, entreprendre, c\'est identifier un problème et proposer une solution capable de transformer son environnement.',
        'Rejoignez le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.',
    ];
    // Portraits publiés : cartes gérées depuis le dashboard, qui ouvrent la publication sur le réseau social.
    $portraits = collect(\App\Support\Api::get('/cathoimpact/portraits')['portraits'] ?? [])
        ->filter(fn ($p) => preg_match('#^https?://#i', $p['lien'] ?? '') && preg_match('#^https?://#i', $p['image_url'] ?? ''));
    $icones = ['instagram', 'linkedin', 'facebook', 'tiktok', 'youtube'];
@endphp

<x-site-layout title="CathoImpact : des parcours qui inspirent" description="CathoImpact met en avant des entrepreneurs et des professionnels catholiques au parcours inspirant. Racontez votre histoire au REJCC en répondant au questionnaire."
    :image="asset('brand/cathoimpact-partage.jpg')" :image-large="true" image-alt="Carrousel CathoImpact : portraits d'entrepreneurs catholiques au parcours inspirant">
    <x-page-header eyebrow="Portraits inspirants" crumb="CathoImpact">
        Catho<span class="font-serif italic normal-case text-azure">Impact</span>
    </x-page-header>

    <section class="bg-white py-10 sm:py-20">
        <x-ui.container>
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-pretty text-base leading-relaxed text-ink/80 sm:text-lg">
                    CathoImpact est une série de carrousels qui met en lumière des entrepreneurs, des dirigeants, des cadres et des professionnels catholiques&nbsp;: de leurs débuts modestes aux défis surmontés, jusqu'à la réussite.
                </p>
                <p class="mt-4 text-pretty text-base leading-relaxed text-ink/80 sm:text-lg">
                    Ces parcours montrent que la foi, l'excellence professionnelle et la réussite concrète peuvent aller de pair. Le vôtre peut en inspirer d'autres.
                </p>
                {{-- Bouton visible dès l'arrivée, sans défiler (téléphone compris). --}}
                <div class="mt-6 sm:mt-7">
                    <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate size="lg" :withArrow="true" data-test="cathoimpact-repondre">Répondre au questionnaire</x-ui.button>
                </div>
            </div>

            <div class="mx-auto mt-10 w-full max-w-[440px]" role="group" aria-roledescription="carrousel" aria-label="Exemple de carrousel CathoImpact"
                x-data="{
                    i: 0, n: {{ count($slides) }}, pause: false, minuteur: null, depart: null,
                    init() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        this.minuteur = setInterval(() => { if (! this.pause && ! document.hidden) this.suivant() }, 5000);
                    },
                    destroy() { clearInterval(this.minuteur) },
                    suivant() { this.i = (this.i + 1) % this.n },
                    precedent() { this.i = (this.i - 1 + this.n) % this.n },
                    fin(e) {
                        if (this.depart === null) return;
                        const d = e.changedTouches[0].clientX - this.depart; this.depart = null;
                        if (Math.abs(d) > 40) d < 0 ? this.suivant() : this.precedent();
                    },
                }"
                @pointerenter="if ($event.pointerType === 'mouse') pause = true"
                @pointerleave="if ($event.pointerType === 'mouse') pause = false"
                @focusin="pause = true" @focusout="pause = false"
                @touchstart.passive="depart = $event.touches[0].clientX" @touchend.passive="fin($event)">
                <div class="relative overflow-hidden rounded-2xl bg-brand shadow-[0_24px_60px_-28px_rgba(3,29,89,.55)]">
                    <div class="flex transition-transform duration-500 ease-out motion-reduce:transition-none" :style="`transform: translateX(-${i * 100}%)`">
                        @foreach ($slides as $n => $alt)
                            <img src="{{ asset('brand/cathoimpact/'.str_pad($n + 1, 2, '0', STR_PAD_LEFT).'.jpg') }}" alt="{{ $alt }}" width="864" height="1080" @if ($n > 1) loading="lazy" @endif decoding="async" class="aspect-[4/5] w-full shrink-0 object-cover" data-test="cathoimpact-slide">
                        @endforeach
                    </div>
                    <button type="button" @click="precedent()" aria-label="Slide précédente" class="absolute left-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white">
                        <x-ui.icon name="arrow-left" class="size-5" />
                    </button>
                    <button type="button" @click="suivant()" aria-label="Slide suivante" class="absolute right-2 top-1/2 flex size-11 -translate-y-1/2 rotate-180 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white">
                        <x-ui.icon name="arrow-left" class="size-5" />
                    </button>
                </div>
                <div class="mt-1 flex justify-center">
                    @foreach ($slides as $n => $alt)
                        <button type="button" @click="i = {{ $n }}" aria-label="Aller à la slide {{ $n + 1 }}" :aria-current="i === {{ $n }} ? 'true' : 'false'" class="flex size-6 items-center justify-center">
                            <span class="h-2 rounded-full transition-all" :class="i === {{ $n }} ? 'w-5 bg-accent' : 'w-2 bg-brand/20'"></span>
                        </button>
                    @endforeach
                </div>
            </div>

        </x-ui.container>
    </section>

    @if ($portraits->isNotEmpty())
        <section class="bg-cloud py-14 sm:py-20" data-test="cathoimpact-galerie">
            <x-ui.container>
                <x-ui.section-heading eyebrow="Portraits publiés" title="Ils ont inspiré" subtitle="Découvrez les parcours déjà mis en lumière par CathoImpact, sur nos réseaux sociaux." />
                <div class="mt-10 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($portraits as $p)
                        <a href="{{ $p['lien'] }}" target="_blank" rel="noopener noreferrer" data-test="cathoimpact-portrait"
                            aria-label="{{ $p['nom'] }} : voir la publication sur {{ $p['reseau_label'] }} (nouvel onglet)"
                            class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_12px_32px_-20px_rgba(3,29,89,.45)] ring-1 ring-brand/5 transition hover:-translate-y-1 hover:shadow-[0_22px_44px_-22px_rgba(3,29,89,.55)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                            <span class="relative block aspect-[4/5] overflow-hidden bg-brand/10">
                                <img src="{{ $p['image_url'] }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
                            </span>
                            <span class="flex flex-1 flex-col gap-1 p-3.5 sm:p-4">
                                <span class="font-display text-[1.15rem] uppercase leading-tight tracking-[0.01em] text-brand sm:text-[1.3rem]">{{ $p['nom'] }}</span>
                                @if ($p['accroche'])
                                    <span class="line-clamp-3 text-[13px] leading-snug text-ink/75 sm:text-sm">{{ $p['accroche'] }}</span>
                                @endif
                                <span class="mt-auto flex items-center gap-1.5 pt-2 text-[12px] font-bold text-accent">
                                    @if (in_array($p['reseau'], $icones, true))
                                        <x-ui.social-icon :type="$p['reseau']" class="size-3.5" />
                                    @else
                                        <x-ui.icon name="external-link" class="size-3.5" />
                                    @endif
                                    {{ $p['reseau'] === 'autre' ? 'Voir la publication' : $p['reseau_label'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-ui.container>
        </section>
    @endif

    {{-- Rappel du déroulé, puis l'action. --}}
    <section class="bg-white py-14 sm:py-20" data-test="cathoimpact-etapes">
        <x-ui.container>
            <x-ui.section-heading eyebrow="Votre portrait" title="Comment ça marche ?" />
            <ol class="mx-auto mt-10 grid max-w-5xl gap-4 sm:grid-cols-3 sm:gap-6">
                @foreach ([
                    ['Je réponds au questionnaire', 'Quelques questions simples sur votre parcours, en quelques minutes.'],
                    ['La Communication me recontacte', 'Si votre parcours est retenu, l\'équipe vous contacte pour préparer le portrait.'],
                    ['Mon portrait est publié', 'Il est diffusé sur nos réseaux sociaux et sur le site du REJCC.'],
                ] as $n => [$titre, $texte])
                    <li class="rounded-2xl border border-brand/10 bg-cloud p-6">
                        <span class="font-display text-4xl leading-none text-brand/20 tabular-nums">0{{ $n + 1 }}</span>
                        <h3 class="mt-3 font-display text-xl uppercase tracking-tight text-brand">{{ $titre }}</h3>
                        <span class="mt-3 block h-0.5 w-8 bg-accent"></span>
                        <p class="mt-3 text-pretty text-sm leading-relaxed text-ink/75">{{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-10 text-center">
                <x-ui.button :href="route('cathoimpact.questionnaire')" wire:navigate size="lg" :withArrow="true">Répondre au questionnaire</x-ui.button>
            </div>
        </x-ui.container>
    </section>
</x-site-layout>
