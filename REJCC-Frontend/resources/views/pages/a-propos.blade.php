@php
    use App\Support\Content\SiteRemote;

    $site = \App\Support\Content\SiteConfig::get();
    $headerTitle = SiteRemote::field('a-propos', 'header', 'title');
    $headerSubtitle = SiteRemote::field('a-propos', 'header', 'subtitle', $site['about']);
    $histoireText = SiteRemote::field('a-propos', 'histoire', 'subtitle', "Le REJCC est né de la volonté de jeunes entrepreneurs catholiques de Côte d'Ivoire de conjuguer leur foi, leur ambition et leur sens du service pour bâtir, ensemble, une nouvelle génération d'entreprises à impact.");
    $objectives = [
        "Collaborer et co-créer entre membres",
        "Apprendre et monter en compétences",
        "Entreprendre et innover",
        "Créer de la richesse et de l'emploi",
        "Bâtir des entreprises durables",
        "Servir l'Église et la société",
    ];
@endphp

<x-site-layout title="À propos" description="Découvrez le REJCC : mission, vision et valeurs du réseau de référence des jeunes entrepreneurs catholiques de Côte d'Ivoire — foi, innovation et entrepreneuriat.">
    <x-page-header eyebrow="Le réseau" crumb="À propos" :subtitle="$headerSubtitle">
        @if ($headerTitle) {{ $headerTitle }} @else À propos du <span class="font-serif italic normal-case text-azure">REJCC</span> @endif
    </x-page-header>

    <section class="bg-white py-24 sm:py-28">
        <x-ui.container class="grid items-start gap-14 lg:grid-cols-2 lg:gap-20">
            <x-ui.reveal>
                <x-ui.section-heading
                    align="left"
                    eyebrow="Notre histoire"
                    title="Né d'une vision partagée"
                    :subtitle="$histoireText"
                />
                <p class="mt-6 leading-relaxed text-ink/75">{{ $site['positioning'] }}</p>
            </x-ui.reveal>

            <div class="flex flex-col gap-6">
                <x-ui.reveal>
                    <article class="rounded-3xl border border-brand/10 bg-cloud p-8">
                        <h3 class="font-display text-2xl uppercase tracking-tight text-brand">Notre mission</h3>
                        <p class="mt-3 leading-relaxed text-ink/75">{{ $site['mission'] }}</p>
                    </article>
                </x-ui.reveal>
                <x-ui.reveal :delay="0.1">
                    <article class="rounded-3xl border border-brand/10 bg-brand p-8 text-white">
                        <h3 class="font-display text-2xl uppercase tracking-tight">Notre vision</h3>
                        <p class="mt-3 leading-relaxed text-white/80">{{ $site['vision'] }}</p>
                    </article>
                </x-ui.reveal>
            </div>
        </x-ui.container>

        <x-ui.container class="mt-20">
            <x-ui.reveal>
                <x-ui.section-heading align="left" eyebrow="Nos objectifs" title="Ce que nous poursuivons" />
            </x-ui.reveal>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($objectives as $i => $o)
                    <x-ui.reveal :delay="($i % 3) * 0.08">
                        <div class="flex items-center gap-4 rounded-2xl border border-brand/10 bg-white p-5">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/5 text-accent">
                                <x-ui.icon name="target" class="size-5" />
                            </span>
                            <span class="font-medium text-brand">{{ $o }}</span>
                        </div>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>
    </section>

    <x-sections.values />

    <x-sections.coming-soon
        intro="Le bureau exécutif, l'organigramme et la gouvernance du réseau seront prochainement présentés sur cette page."
        :features="[
            'Présentation du bureau exécutif',
            'Organigramme du réseau',
            'Statuts et gouvernance',
            'Rapport d\'activités annuel',
        ]"
    />

    <x-sections.cta-band />
</x-site-layout>
