@php
    $articles = collect(\App\Support\Api::get('/news')['articles'] ?? [])
        ->map(function ($a) {
            $a['published_at'] = \Carbon\Carbon::parse($a['published_at']);
            return (object) $a;
        })
        ->take(3)
        ->values();
@endphp

@if ($articles->isNotEmpty())
<section class="bg-white py-24 sm:py-32">
    <x-ui.container>
        <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <x-ui.section-heading align="left" eyebrow="Actualités" title="Les dernières nouvelles" subtitle="Suivez la vie du réseau, ses partenariats et ses réussites." class="max-w-2xl" />
            <x-ui.button href="/actualites" variant="outline" :with-arrow="true" class="shrink-0">Toutes les actualités</x-ui.button>
        </div>

        <div class="mt-14 grid gap-6 lg:grid-cols-3">
            @foreach ($articles as $i => $a)
                <x-ui.reveal :delay="$i * 0.1" class="h-full">
                    <a href="/actualites/{{ $a->slug }}" wire:navigate.hover class="group flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-white transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                        <div class="relative aspect-[16/10] overflow-hidden bg-brand">
                            <div class="absolute inset-0 bg-grid opacity-20"></div>
                            <div class="absolute inset-0 bg-linear-to-br from-brand via-brand to-brand-900"></div>
                            <div class="absolute -right-6 -top-6 size-32 rounded-full bg-azure/20 blur-2xl"></div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-90 transition-transform duration-700 group-hover:scale-105">
                                <x-ui.logo-mark kind="mono-white" class="h-16 opacity-80" />
                            </div>
                            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand">{{ $a->category }}</span>
                        </div>

                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="text-lg font-bold leading-snug text-brand transition-colors group-hover:text-accent">{{ $a->title }}</h3>
                            <p class="mt-2 flex-1 text-pretty text-sm leading-relaxed text-ink/65">{{ $a->excerpt }}</p>
                            <div class="mt-5 flex items-center justify-between border-t border-brand/10 pt-4 text-xs text-ink/55">
                                <span>{{ $a->published_at->locale('fr')->translatedFormat('d F Y') }}</span>
                                <span class="flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    {{ $a->reading_time }}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ml-1 size-4 text-accent transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5"><path d="M7 17 17 7M7 7h10v10"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
@endif
