@php
    $events = collect(\App\Support\Api::get('/public-events')['events'] ?? [])
        ->map(function ($e) {
            $e['starts_at'] = \Carbon\Carbon::parse($e['starts_at']);
            return (object) $e;
        })
        ->sortBy('starts_at')
        ->take(4)
        ->values();
@endphp

<section class="bg-cloud py-24 sm:py-32">
    <x-ui.container>
        <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <x-ui.section-heading align="left" eyebrow="Agenda" title="Prochains événements" subtitle="Forums, ateliers, visites et galas : la vie du réseau tout au long de l'année." class="max-w-2xl" />
            <x-ui.button href="/evenements" variant="outline" :with-arrow="true" class="shrink-0">Voir l'agenda complet</x-ui.button>
        </div>

        <div class="mt-14 grid gap-4 sm:grid-cols-2">
            @foreach ($events as $i => $e)
                <x-ui.reveal :delay="($i % 2) * 0.08">
                    <a href="/evenements/{{ $e->slug }}" class="group flex items-stretch gap-5 overflow-hidden rounded-3xl border border-brand/10 bg-white p-5 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]">
                        <div class="flex w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand py-4 text-white">
                            <span class="font-display text-3xl leading-none">{{ $e->starts_at->format('d') }}</span>
                            <span class="mt-1 text-xs uppercase tracking-wider text-white/70">{{ $e->starts_at->locale('fr')->translatedFormat('M') }}</span>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col justify-center">
                            <span class="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $e->category }}</span>
                            <h3 class="mt-2 truncate text-lg font-bold text-brand">{{ $e->title }}</h3>
                            <p class="mt-1 line-clamp-1 text-sm text-ink/65">{{ $e->excerpt }}</p>
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-ink/55">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ $e->location }} · {{ $e->starts_at->format('Y') }}
                            </p>
                        </div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 self-center text-brand/20 transition-all duration-500 group-hover:translate-x-1 group-hover:text-accent"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
