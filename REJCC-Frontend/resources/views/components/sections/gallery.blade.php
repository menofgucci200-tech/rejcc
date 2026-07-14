@php
    use App\Support\Content\SiteRemote;

    $photos = collect(\App\Support\Api::get('/gallery')['photos'] ?? []);
    $galleryTitle = SiteRemote::field('home', 'gallery', 'title', 'La vie du réseau');
    $gallerySubtitle = SiteRemote::field('home', 'gallery', 'subtitle', 'Rencontres, formations, événements : le REJCC en images.');
@endphp

@if ($photos->isNotEmpty())
    <section
        class="relative bg-white py-24 sm:py-28"
        x-data="{
            open: false,
            index: 0,
            photos: {{ $photos->map(fn ($p) => ['url' => $p['url'], 'caption' => $p['caption'] ?? ''])->values()->toJson() }},
            show(i) { this.index = i; this.open = true; document.body.style.overflow = 'hidden'; },
            close() { this.open = false; document.body.style.overflow = ''; },
            next() { this.index = (this.index + 1) % this.photos.length; },
            prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
        }"
        @keydown.escape.window="close()"
        @keydown.arrow-right.window="open && next()"
        @keydown.arrow-left.window="open && prev()"
    >
        <x-ui.container>
            <x-ui.section-heading eyebrow="Galerie" :subtitle="$gallerySubtitle">
                <x-slot:title>{{ $galleryTitle }}</x-slot:title>
            </x-ui.section-heading>

            <div class="mt-14 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($photos as $i => $p)
                    <x-ui.reveal :delay="($i % 4) * 0.06">
                        <button
                            type="button"
                            @click="show({{ $i }})"
                            class="group relative block w-full overflow-hidden rounded-2xl {{ $i % 5 === 0 ? 'row-span-2' : '' }}"
                        >
                            <img src="{{ $p['url'] }}" alt="{{ $p['caption'] ?? 'Photo REJCC' }}" loading="lazy"
                                 class="aspect-square w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <span class="absolute inset-0 bg-brand/0 transition-colors duration-300 group-hover:bg-brand/25"></span>
                            @if ($p['caption'] ?? null)
                                <span class="absolute inset-x-0 bottom-0 translate-y-full bg-gradient-to-t from-brand-900/85 to-transparent px-3 pb-2.5 pt-6 text-left text-[11.5px] font-semibold text-white transition-transform duration-300 group-hover:translate-y-0">{{ $p['caption'] }}</span>
                            @endif
                        </button>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>

        {{-- Visionneuse plein écran --}}
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-brand-900/95 p-4" @click.self="close()">
            <button type="button" @click="close()" aria-label="Fermer" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20">
                <x-ui.icon name="x" class="size-5" />
            </button>
            <button type="button" @click="prev()" aria-label="Photo précédente" class="absolute left-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:left-6">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" @click="next()" aria-label="Photo suivante" class="absolute right-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:right-6">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="m9 18 6-6-6-6"/></svg>
            </button>
            <figure class="max-h-[85vh] max-w-5xl">
                <img :src="photos[index].url" :alt="photos[index].caption" class="max-h-[78vh] w-auto rounded-xl object-contain shadow-2xl">
                <figcaption x-show="photos[index].caption" x-text="photos[index].caption" class="mt-3 text-center text-sm text-white/80"></figcaption>
            </figure>
        </div>
    </section>
@endif
