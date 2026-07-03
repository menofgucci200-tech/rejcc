@php
    $partners = \App\Models\Partner::orderBy('ordre')->get();
    $benefits = \App\Support\Content\PartnersContent::partnershipBenefits();
@endphp

<x-site-layout title="Partenaires">
    <x-page-header eyebrow="Ensemble, plus loin" crumb="Partenaires" subtitle="Entreprises, institutions et organisations qui soutiennent l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire.">
        Nos <span class="font-serif italic normal-case text-azure">partenaires</span>
    </x-page-header>

    <section class="bg-white py-20 sm:py-24">
        <x-ui.container>
            <x-ui.section-heading
                eyebrow="Ils nous soutiennent"
                title="Un réseau de confiance"
                subtitle="Logos provisoires — la liste officielle de nos partenaires sera bientôt mise à jour."
            />
            <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($partners as $i => $p)
                    <x-ui.reveal :delay="($i % 4) * 0.06">
                        <div class="flex h-28 flex-col items-center justify-center gap-2 rounded-2xl border border-brand/10 bg-cloud transition-colors hover:border-brand/25">
                            <span class="flex size-12 items-center justify-center rounded-xl bg-brand font-display text-lg text-white">{{ $p->initials }}</span>
                            <span class="px-2 text-center text-xs font-medium text-ink/70">{{ $p->name }}</span>
                        </div>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>
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
