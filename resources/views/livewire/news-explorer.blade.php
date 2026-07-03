<div>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach ($categories as $c)
                <button
                    wire:click="$set('category', '{{ $c }}')"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $category === $c ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:border-brand/30 hover:text-brand' }}"
                >
                    {{ $c }}
                </button>
            @endforeach
        </div>
        <div class="relative lg:w-72">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink/40"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Rechercher un article…"
                aria-label="Rechercher un article"
                class="w-full rounded-full border border-brand/15 bg-white py-2.5 pl-11 pr-4 text-sm text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20"
            />
        </div>
    </div>

    @if ($articles->isEmpty())
        <p class="mt-16 text-center text-ink/60">Aucun article ne correspond à votre recherche.</p>
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($articles as $a)
                <a href="/actualites/{{ $a->slug }}" wire:navigate class="group flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-white transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                    <div class="relative aspect-[16/10] overflow-hidden bg-brand">
                        <div class="absolute inset-0 bg-grid opacity-20"></div>
                        <div class="absolute inset-0 bg-linear-to-br from-brand via-brand to-brand-900"></div>
                        <div class="absolute -right-6 -top-6 size-32 rounded-full bg-azure/20 blur-2xl"></div>
                        <div class="absolute inset-0 flex items-center justify-center transition-transform duration-700 group-hover:scale-105">
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
            @endforeach
        </div>
    @endif
</div>
