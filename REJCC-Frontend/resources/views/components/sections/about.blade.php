@php
    $site = \App\Support\Content\SiteConfig::get();
    $pillars = [
        ['label' => 'Notre mission', 'text' => $site['mission']],
        ['label' => 'Notre vision', 'text' => $site['vision']],
    ];
@endphp

<section id="a-propos" class="relative bg-white py-24 sm:py-32">
    <x-ui.container class="grid items-start gap-14 lg:grid-cols-[1fr_1fr] lg:gap-20">
        <div class="lg:sticky lg:top-28">
            <x-ui.section-heading align="left" eyebrow="Qui sommes-nous" :subtitle="$site['about']">
                <x-slot:title>
                    Une communauté qui <span class="text-gradient">entreprend</span>, unie par la foi
                </x-slot:title>
            </x-ui.section-heading>

            <figure class="mt-8 border-l-2 border-accent pl-6">
                <blockquote class="font-serif text-xl italic leading-relaxed text-brand">« {{ $site['positioning'] }} »</blockquote>
            </figure>
            <div class="mt-9">
                <x-ui.button href="/a-propos" variant="outline" :with-arrow="true">Découvrir notre histoire</x-ui.button>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            @foreach ($pillars as $i => $p)
                <x-ui.reveal :delay="$i * 0.1">
                    <article class="group relative overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_30px_60px_-30px_rgba(3,29,89,0.35)]">
                        <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-azure/10 blur-2xl transition-opacity duration-500 group-hover:opacity-100"></div>
                        <div class="flex items-baseline gap-4">
                            <span class="font-display text-5xl leading-none text-brand/15 tabular-nums">0{{ $i + 1 }}</span>
                            <h3 class="font-display text-2xl uppercase tracking-tight text-brand">{{ $p['label'] }}</h3>
                        </div>
                        <span class="mt-5 block h-0.5 w-10 bg-accent"></span>
                        <p class="mt-4 text-pretty leading-relaxed text-ink/75">{{ $p['text'] }}</p>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
