<x-site-layout title="Galerie" description="La vie du REJCC en images : assemblées, formations, rencontres et célébrations du Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.">
    <x-page-header eyebrow="La vie du réseau" crumb="Galerie" subtitle="Assemblées, formations, rencontres et célébrations : retrouvez les moments forts du REJCC, album par album.">
        En <span class="font-serif italic normal-case text-azure">images</span>
    </x-page-header>

    <section class="bg-white py-16 sm:py-24">
        <x-ui.container>
            @if ($albums->isNotEmpty())
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($albums as $i => $album)
                        <x-ui.reveal :delay="($i % 3) * 0.06" class="{{ $i === 0 ? 'md:col-span-2 lg:col-span-2'.($albums->count() >= 3 ? ' lg:row-span-2' : '') : '' }}">
                            <x-galerie.carte-album :album="$album" :grand="$i === 0" />
                        </x-ui.reveal>
                    @endforeach
                </div>
            @elseif ($photos->isNotEmpty())
                <div x-data class="columns-2 gap-3 sm:columns-3 lg:columns-4 [&>*]:mb-3">
                    @foreach ($photos as $i => $p)
                        <button type="button" @click="$dispatch('rj-visionneuse', { index: {{ $i }} })" class="group relative block w-full overflow-hidden rounded-2xl">
                            <img src="{{ $p['url'] }}" alt="{{ $p['caption'] ?? 'Photo REJCC' }}" loading="lazy" class="w-full transition-transform duration-500 group-hover:scale-105">
                        </button>
                    @endforeach
                </div>
                <x-galerie.visionneuse :photos="$photos" />
            @else
                <div class="mx-auto max-w-xl rounded-3xl border border-brand/10 bg-cloud px-8 py-14 text-center">
                    <p class="font-display text-3xl uppercase text-brand">Bientôt en ligne</p>
                    <span class="mx-auto mt-4 block h-0.5 w-10 bg-accent"></span>
                    <p class="mt-4 text-pretty text-ink/70">Les premiers albums photo du réseau seront publiés après nos prochaines rencontres.</p>
                    <div class="mt-7 flex justify-center">
                        <x-ui.button href="/evenements" variant="outline" :with-arrow="true">Voir les événements</x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.container>
    </section>
</x-site-layout>
