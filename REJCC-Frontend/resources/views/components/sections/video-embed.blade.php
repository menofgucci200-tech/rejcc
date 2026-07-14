@php
    use App\Support\Content\SiteRemote;

    $videoUrl = trim((string) SiteRemote::field('home', 'video', 'url', ''));
    $videoTitle = SiteRemote::field('home', 'video', 'title', 'Découvrez le REJCC en vidéo');

    // Détection de la plateforme et de l'identifiant pour l'intégration.
    $embed = null;
    $poster = null;
    if ($videoUrl !== '') {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,20})~i', $videoUrl, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1';
            $poster = 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg';
        } elseif (preg_match('~tiktok\.com/@[\w.\-]+/video/(\d+)~i', $videoUrl, $m)) {
            $embed = 'https://www.tiktok.com/embed/v2/'.$m[1];
        }
    }
@endphp

@if ($videoUrl !== '')
    <section class="relative overflow-hidden bg-cloud py-24 sm:py-28">
        <div class="pointer-events-none absolute inset-0 bg-dots opacity-50 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
        <x-ui.container class="relative">
            <x-ui.section-heading eyebrow="En vidéo">
                <x-slot:title>{{ $videoTitle }}</x-slot:title>
            </x-ui.section-heading>

            <x-ui.reveal class="mx-auto mt-12 max-w-3xl">
                @if ($embed)
                    {{-- Chargée au clic : aucune requête vers YouTube/TikTok avant l'action du visiteur --}}
                    <div
                        x-data="{ playing: false }"
                        class="relative aspect-video overflow-hidden rounded-3xl bg-brand shadow-[0_30px_70px_-30px_rgba(3,29,89,.5)]"
                    >
                        <template x-if="!playing">
                            <button type="button" @click="playing = true" class="group absolute inset-0 flex w-full items-center justify-center" aria-label="Lire la vidéo">
                                @if ($poster)
                                    <img src="{{ $poster }}" alt="" class="absolute inset-0 size-full object-cover opacity-80" loading="lazy">
                                @endif
                                <span class="absolute inset-0 bg-brand/30 transition-colors duration-300 group-hover:bg-brand/15"></span>
                                <span class="relative flex size-20 items-center justify-center rounded-full bg-accent text-white shadow-[0_12px_30px_rgba(172,1,0,.45)] transition-transform duration-300 group-hover:scale-110">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="ml-1 size-8"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </button>
                        </template>
                        <template x-if="playing">
                            <iframe
                                src="{{ $embed }}"
                                title="{{ $videoTitle }}"
                                class="absolute inset-0 size-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </template>
                    </div>
                @else
                    <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="group flex aspect-video items-center justify-center gap-3 rounded-3xl text-white shadow-[0_30px_70px_-30px_rgba(3,29,89,.5)]" style="background: linear-gradient(135deg,#0B2E7A,#AC0100)">
                        <span class="flex size-16 items-center justify-center rounded-full bg-white/20 transition-transform duration-300 group-hover:scale-110">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="ml-1 size-7"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                        <span class="text-lg font-bold">Voir la vidéo</span>
                    </a>
                @endif
            </x-ui.reveal>
        </x-ui.container>
    </section>
@endif
