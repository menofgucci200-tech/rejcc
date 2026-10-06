@php
    $benefits = collect(\App\Support\Api::get('/home-content')['benefits'] ?? [])->map(fn ($b) => (object) $b);
    $whyJoinSubtitle = \App\Support\Content\SiteRemote::field('home', 'why-join', 'subtitle', 'Le REJCC met à votre disposition un environnement complet pour faire grandir vos projets et vos compétences.');
@endphp

<section class="relative bg-cloud py-24 sm:py-32">
    <div class="pointer-events-none absolute inset-0 bg-grid opacity-40 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]"></div>
    <x-ui.container class="relative">
        <x-ui.section-heading eyebrow="Pourquoi nous rejoindre" :subtitle="$whyJoinSubtitle">
            <x-slot:title>
                Tout ce dont vous avez besoin pour <span class="text-gradient">réussir</span>
            </x-slot:title>
        </x-ui.section-heading>

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($benefits as $i => $b)
                <x-ui.reveal :delay="($i % 3) * 0.08">
                    <article class="group relative h-full overflow-hidden rounded-3xl border border-brand/10 bg-white p-8 transition-all duration-500 hover:-translate-y-1.5 hover:border-brand/20 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                        <span class="font-display text-4xl leading-none text-brand/15 tabular-nums transition-colors duration-500 group-hover:text-accent">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-5 text-xl font-bold tracking-tight text-brand">{{ $b->title }}</h3>
                        <p class="mt-2.5 text-pretty leading-relaxed text-ink/70">{{ $b->text }}</p>
                        <span class="absolute bottom-0 left-0 h-1 w-0 bg-accent transition-all duration-500 group-hover:w-full"></span>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
