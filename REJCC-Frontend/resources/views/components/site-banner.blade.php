@php
    $banner = \App\Support\Content\SiteConfig::banner();
@endphp

@if ($banner)
    {{-- Bandeau d'annonce global : fixé au-dessus de la navbar (décalée via
         --banner-h), refermable pour la session. --}}
    <div
        x-data="{ show: ! sessionStorage.getItem('rejcc-banner-hidden') }"
        x-init="if (show) document.documentElement.style.setProperty('--banner-h', '40px')"
        x-show="show"
        style="display: none;"
        class="fixed inset-x-0 top-0 z-[95] flex h-10 items-center justify-center gap-3 overflow-hidden bg-accent px-10 text-[13px] font-semibold text-white"
    >
        <p class="truncate">
            {{ $banner['text'] }}
            @if ($banner['link'])
                <a href="{{ $banner['link'] }}" class="ml-2 inline-flex items-center gap-1 underline underline-offset-2 hover:opacity-80">{{ $banner['label'] }} →</a>
            @endif
        </p>
        <button
            type="button"
            aria-label="Fermer l'annonce"
            @click="show = false; sessionStorage.setItem('rejcc-banner-hidden', '1'); document.documentElement.style.setProperty('--banner-h', '0px')"
            class="absolute right-3 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-full transition-colors hover:bg-white/20"
        >
            <x-ui.icon name="x" class="size-3.5" />
        </button>
    </div>
@endif
