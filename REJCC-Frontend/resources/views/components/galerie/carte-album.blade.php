@props(['album', 'grand' => false])
@php
    $date = ($album['date'] ?? null) ? \Illuminate\Support\Carbon::parse($album['date'])->locale('fr')->translatedFormat('j F Y') : null;
@endphp
<a href="{{ url('/galerie/'.$album['slug']) }}" wire:navigate
   {{ $attributes->class(['group relative isolate block h-full overflow-hidden rounded-3xl bg-brand', $grand ? 'min-h-[22rem] sm:min-h-[28rem]' : 'min-h-[17rem]']) }}>
    @if ($album['couverture'] ?? null)
        <img src="{{ $album['couverture'] }}" alt="" loading="lazy" class="absolute inset-0 -z-10 size-full object-cover transition-transform duration-700 group-hover:scale-105">
    @endif
    <span class="absolute inset-0 -z-10 bg-linear-to-t from-brand-900/95 via-brand-900/35 to-transparent"></span>
    <div class="flex h-full flex-col justify-end p-6 sm:p-7">
        <p class="text-[0.7rem] font-bold uppercase tracking-[0.16em] text-[#8fa3d9]">
            {{ $date ?? 'Album' }}@if ($album['lieu'] ?? null) <span class="text-white/40">·</span> {{ $album['lieu'] }}@endif
        </p>
        <h3 class="mt-2 text-balance font-display uppercase leading-[0.95] text-white {{ $grand ? 'text-[clamp(1.9rem,3.6vw,3rem)]' : 'text-2xl' }}">{{ $album['titre'] }}</h3>
        <span class="mt-4 flex items-center gap-3 text-sm font-semibold text-white/80">
            <span class="h-0.5 w-8 bg-accent transition-all duration-500 group-hover:w-12"></span>
            {{ $album['photos'] }} photo{{ $album['photos'] > 1 ? 's' : '' }}
        </span>
    </div>
</a>
