@php
    use App\Support\Content\SiteRemote;

    $albums = collect(\App\Support\Api::get('/albums')['albums'] ?? [])->take(4);
    $photos = $albums->isEmpty() ? collect(\App\Support\Api::get('/gallery')['photos'] ?? [])->take(8) : collect();
    $galleryTitle = SiteRemote::field('home', 'gallery', 'title', 'La vie du réseau');
    $gallerySubtitle = SiteRemote::field('home', 'gallery', 'subtitle', 'Rencontres, formations, événements : le REJCC en images.');
@endphp

@if ($albums->isNotEmpty() || $photos->isNotEmpty())
    <section class="relative bg-white py-24 sm:py-28">
        <x-ui.container>
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <x-ui.section-heading align="left" eyebrow="En images" :subtitle="$gallerySubtitle" class="max-w-2xl">
                    <x-slot:title>{{ $galleryTitle }}</x-slot:title>
                </x-ui.section-heading>
                <x-ui.button href="/galerie" variant="outline" :with-arrow="true" class="shrink-0">Toute la galerie</x-ui.button>
            </div>

            @if ($albums->isNotEmpty())
                {{-- Mosaïque : le dernier album en grand, les suivants autour --}}
                <div class="mt-12 grid gap-4 md:grid-cols-3">
                    @foreach ($albums as $i => $album)
                        <x-ui.reveal :delay="$i * 0.06" class="{{ $i === 0 ? 'md:col-span-2'.($albums->count() >= 3 ? ' md:row-span-2' : '') : '' }}">
                            <x-galerie.carte-album :album="$album" :grand="$i === 0" />
                        </x-ui.reveal>
                    @endforeach
                </div>
            @else
                <div x-data class="mt-12 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ($photos as $i => $p)
                        <x-ui.reveal :delay="($i % 4) * 0.06">
                            <button type="button" @click="$dispatch('rj-visionneuse', { index: {{ $i }} })" class="group relative block w-full overflow-hidden rounded-2xl">
                                <img src="{{ $p['url'] }}" alt="{{ $p['caption'] ?? 'Photo REJCC' }}" loading="lazy"
                                     class="aspect-square w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @if ($p['caption'] ?? null)
                                    <span class="absolute inset-x-0 bottom-0 translate-y-full bg-gradient-to-t from-brand-900/85 to-transparent px-3 pb-2.5 pt-6 text-left text-[11.5px] font-semibold text-white transition-transform duration-300 group-hover:translate-y-0">{{ $p['caption'] }}</span>
                                @endif
                            </button>
                        </x-ui.reveal>
                    @endforeach
                </div>
                <x-galerie.visionneuse :photos="$photos" />
            @endif
        </x-ui.container>
    </section>
@endif
