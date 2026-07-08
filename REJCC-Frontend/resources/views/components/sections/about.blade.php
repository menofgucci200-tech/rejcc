@php
    $site = \App\Support\Content\SiteConfig::get();
    $pillars = [
        ['icon' => 'network', 'label' => 'Notre mission', 'text' => $site['mission']],
        ['icon' => 'target', 'label' => 'Notre vision', 'text' => $site['vision']],
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
                        <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-brand text-white shadow-lg">
                            <x-ui.icon :name="$p['icon']" class="size-6" />
                        </span>
                        <h3 class="mt-6 font-display text-2xl uppercase tracking-tight text-brand">{{ $p['label'] }}</h3>
                        <p class="mt-3 text-pretty leading-relaxed text-ink/75">{{ $p['text'] }}</p>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
