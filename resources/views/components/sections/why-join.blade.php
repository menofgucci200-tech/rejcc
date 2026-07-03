@php $benefits = \App\Models\HomeBenefit::orderBy('ordre')->get(); @endphp

<section class="relative bg-cloud py-24 sm:py-32">
    <div class="pointer-events-none absolute inset-0 bg-grid opacity-40 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]"></div>
    <x-ui.container class="relative">
        <x-ui.section-heading eyebrow="Pourquoi nous rejoindre" subtitle="Le REJCC met à votre disposition un environnement complet pour faire grandir vos projets et vos compétences.">
            <x-slot:title>
                Tout ce dont vous avez besoin pour <span class="text-gradient">réussir</span>
            </x-slot:title>
        </x-ui.section-heading>

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($benefits as $i => $b)
                <x-ui.reveal :delay="($i % 3) * 0.08">
                    <article class="group relative h-full overflow-hidden rounded-3xl border border-brand/10 bg-white p-8 transition-all duration-500 hover:-translate-y-1.5 hover:border-brand/20 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                        <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-brand/5 text-brand transition-all duration-500 group-hover:bg-brand group-hover:text-white">
                            <x-ui.icon :name="$b->icon" class="size-6" />
                        </span>
                        <h3 class="mt-6 text-xl font-bold tracking-tight text-brand">{{ $b->title }}</h3>
                        <p class="mt-2.5 text-pretty leading-relaxed text-ink/70">{{ $b->text }}</p>
                        <span class="absolute bottom-0 left-0 h-1 w-0 bg-accent transition-all duration-500 group-hover:w-full"></span>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
