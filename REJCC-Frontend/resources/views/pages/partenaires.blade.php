@php
    use App\Support\Content\SiteRemote;

    $partners = collect(\App\Support\Api::get('/partners')['partners'] ?? [])->map(fn ($p) => (object) $p);
    $benefits = \App\Support\Content\PartnersContent::partnershipBenefits();
    $headerTitle = SiteRemote::field('partenaires', 'header', 'title');
    $headerSubtitle = SiteRemote::field('partenaires', 'header', 'subtitle', "Entreprises, institutions et organisations qui soutiennent l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire.");
@endphp

<x-site-layout title="Partenaires" description="Les partenaires qui soutiennent le REJCC et l'entrepreneuriat des jeunes catholiques en Côte d'Ivoire. Devenez partenaire du réseau.">
    <x-page-header eyebrow="Ensemble, plus loin" crumb="Partenaires" :subtitle="$headerSubtitle">
        @if ($headerTitle) {{ $headerTitle }} @else Nos <span class="font-serif italic normal-case text-azure">partenaires</span> @endif
    </x-page-header>

    <section class="overflow-hidden bg-white py-20 sm:py-24">
        <x-ui.container>
            <x-ui.section-heading
                eyebrow="Ils nous soutiennent"
                title="Un réseau de confiance"
                :subtitle="$partners->isNotEmpty() ? 'Les entreprises et organisations qui accompagnent le REJCC. Cliquez sur un logo pour découvrir le partenaire.' : 'La liste officielle de nos partenaires sera bientôt mise à jour.'"
            />
        </x-ui.container>

        @if ($partners->isNotEmpty())
            {{-- Bandeau animé : défile en continu, se met en pause au survol.
                 Hors container pour occuper toute la largeur de l'écran. --}}
            <x-ui.reveal class="mt-12">
                <x-partners-marquee :partners="$partners" class="py-2" />
            </x-ui.reveal>
        @endif
    </section>

    <section class="bg-cloud py-20 sm:py-24">
        <x-ui.container>
            <x-ui.section-heading eyebrow="Pourquoi nous rejoindre" title="Devenir partenaire, c'est…" />
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($benefits as $i => $b)
                    <x-ui.reveal :delay="($i % 4) * 0.07" class="h-full">
                        <div class="h-full rounded-3xl border border-brand/10 bg-white p-7">
                            <span class="font-display text-3xl text-accent">0{{ $i + 1 }}</span>
                            <h3 class="mt-3 text-lg font-bold text-brand">{{ $b['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink/70">{{ $b['text'] }}</p>
                        </div>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>
    </section>

    <section class="bg-white py-20 sm:py-24">
        <x-ui.container class="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
            <div>
                <x-ui.section-heading
                    align="left"
                    eyebrow="Devenir partenaire"
                    title="Construisons ensemble"
                    subtitle="Vous partagez nos valeurs et souhaitez soutenir le réseau ? Parlons-en. Remplissez ce formulaire et notre équipe vous recontacte."
                />
            </div>
            <livewire:partenariat-form />
        </x-ui.container>
    </section>
</x-site-layout>
