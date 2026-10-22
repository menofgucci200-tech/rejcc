@props([
    'url' => null,
    'alt' => '',
    'mode' => 'card', // card : grand aperçu (lecteur vidéo intégré) | thumb : vignette
    'fallbackIcon' => 'nav-briefcase',
])

@php
    // Détection du type de média par l'extension (ou l'hébergeur vidéo).
    $ext = strtolower(pathinfo(parse_url((string) $url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    $isVideo = in_array($ext, ['mp4', 'webm', 'mov', 'm4v'], true);
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true);
    $isExternalVideo = $url && preg_match('/youtube\.com|youtu\.be|tiktok\.com|facebook\.com\/.*video|fb\.watch|instagram\.com\/(reel|p)\//i', $url);
@endphp

@if ($mode === 'card')
    @if ($isVideo)
        <video controls preload="metadata" class="h-40 w-full bg-black object-contain">
            <source src="{{ $url }}">
        </video>
    @elseif ($isImage)
        <img src="{{ $url }}" alt="{{ $alt }}" class="h-36 w-full object-cover" loading="lazy">
    @elseif ($isExternalVideo)
        <a href="{{ $url }}" target="_blank" rel="noopener" class="group flex h-28 w-full items-center justify-center gap-2.5 text-white transition-opacity hover:opacity-90" style="background: linear-gradient(135deg,#0B2E7A,#AC0100)">
            <span class="flex size-11 items-center justify-center rounded-full bg-white/20 transition-transform duration-200 group-hover:scale-110">
                <svg viewBox="0 0 24 24" fill="currentColor" class="ml-0.5 size-5"><path d="M8 5v14l11-7z"/></svg>
            </span>
            <span class="text-[12.5px] font-bold">Voir la vidéo</span>
        </a>
    @elseif ($url)
        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex h-24 w-full items-center justify-center gap-2 bg-cloud text-[12.5px] font-semibold text-brand transition-colors hover:bg-cloud-200">
            <x-ui.icon name="file-text" class="size-4" /> Voir le document joint
        </a>
    @else
        <div class="flex h-24 items-center justify-center" style="background: linear-gradient(135deg,#0B2E7A,#4F6FBF)">
            <x-ui.icon :name="$fallbackIcon" class="size-8 text-white/75" />
        </div>
    @endif
@else
    {{-- Vignette compacte (listes) --}}
    @if ($isImage)
        <img src="{{ $url }}" alt="{{ $alt }}" class="size-14 shrink-0 rounded-xl object-cover" loading="lazy">
    @elseif ($isVideo || $isExternalVideo)
        <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
            <svg viewBox="0 0 24 24" fill="currentColor" class="ml-0.5 size-5"><path d="M8 5v14l11-7z"/></svg>
        </span>
    @else
        <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
            <x-ui.icon :name="$fallbackIcon" class="size-5" />
        </span>
    @endif
@endif
