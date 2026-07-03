@props(['tone' => 'light'])

@php
    $toneClasses = $tone === 'light'
        ? 'border-brand/15 bg-brand/5 text-brand'
        : 'border-white/20 bg-white/10 text-white';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] $toneClasses"]) }}>
    <span class="size-1.5 rounded-full bg-accent"></span>
    {{ $slot }}
</span>
