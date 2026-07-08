@php $activities = collect(\App\Support\Api::get('/activities')['activities'] ?? [])->map(fn ($a) => (object) $a); @endphp

<x-site-layout title="Nos activités">
    <x-page-header eyebrow="Ce que nous faisons" crumb="Activités" subtitle="Un programme riche pour apprendre, entreprendre et grandir ensemble, tout au long de l'année.">
        Nos <span class="font-serif italic normal-case text-azure">activités</span>
    </x-page-header>

    <section class="bg-white py-24 sm:py-28">
        <x-ui.container>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($activities as $i => $a)
                    <x-ui.reveal :delay="($i % 4) * 0.07" class="h-full">
                        <article class="group flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-7 transition-all duration-500 hover:-translate-y-1.5 hover:bg-brand">
                            <span class="inline-flex size-13 items-center justify-center rounded-2xl bg-white text-accent shadow-sm transition-transform duration-500 group-hover:scale-110">
                                <x-ui.icon :name="$a->icon" class="size-6" />
                            </span>
                            <h3 class="mt-5 font-display text-xl uppercase tracking-tight text-brand transition-colors duration-500 group-hover:text-white">{{ $a->title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink/70 transition-colors duration-500 group-hover:text-white/80">{{ $a->text }}</p>
                        </article>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
