@php
    $date = ($album['date'] ?? null) ? \Illuminate\Support\Carbon::parse($album['date'])->locale('fr')->translatedFormat('l j F Y') : null;
@endphp

<x-site-layout :title="$album['titre'].' — Galerie'" :description="$album['description'] ?: 'Album photo du REJCC : '.$album['titre'].'.'">
    <x-page-header :eyebrow="$date ? ucfirst($date) : 'Album photo'" crumb="Galerie" :subtitle="$album['description'] ?? null">
        {{ $album['titre'] }}
    </x-page-header>

    <section class="bg-white py-14 sm:py-20">
        <x-ui.container>
            <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-semibold text-ink/70">
                    {{ $photos->count() }} photo{{ $photos->count() > 1 ? 's' : '' }}
                    @if ($album['lieu'] ?? null) <span class="text-ink/30">·</span> {{ $album['lieu'] }} @endif
                </p>
                <a href="{{ url('/galerie') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-accent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                    Tous les albums
                </a>
            </div>

            <div x-data class="columns-2 gap-3 sm:columns-3 lg:columns-4 [&>*]:mb-3">
                @foreach ($photos as $i => $p)
                    <button type="button" @click="$dispatch('rj-visionneuse', { index: {{ $i }} })"
                            class="group relative block w-full break-inside-avoid overflow-hidden rounded-2xl bg-cloud"
                            aria-label="Agrandir {{ $p['caption'] ?: 'la photo '.($i + 1) }}">
                        <img src="{{ $p['url'] }}" alt="{{ $p['caption'] ?? '' }}" loading="{{ $i < 8 ? 'eager' : 'lazy' }}" class="w-full transition-transform duration-500 group-hover:scale-[1.04]">
                        @if ($p['caption'] ?? null)
                            <span class="absolute inset-x-0 bottom-0 bg-linear-to-t from-brand-900/85 to-transparent px-3 pb-2.5 pt-8 text-left text-xs font-semibold text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">{{ $p['caption'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </x-ui.container>
    </section>

    @if ($autres->isNotEmpty())
        <section class="bg-cloud py-16 sm:py-20">
            <x-ui.container>
                <h2 class="font-display text-3xl uppercase text-brand">Autres albums</h2>
                <span class="mt-3 block h-0.5 w-10 bg-accent"></span>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($autres as $a)
                        <x-galerie.carte-album :album="$a" />
                    @endforeach
                </div>
            </x-ui.container>
        </section>
    @endif

    <x-galerie.visionneuse :photos="$photos" />
</x-site-layout>
