@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'align' => 'center',
    'tone' => 'light',
    'titleClass' => '',
])

@php
    $alignClasses = $align === 'center' ? 'items-center text-center' : 'items-start text-left';
    $titleTone = $tone === 'light' ? 'text-brand' : 'text-white';
    $subtitleTone = $tone === 'light' ? 'text-ink/70' : 'text-white/70';
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col gap-5 $alignClasses"]) }}>
    @if ($eyebrow)
        <x-ui.eyebrow :tone="$tone">{{ $eyebrow }}</x-ui.eyebrow>
    @endif

    <h2 class="font-display uppercase leading-[0.95] tracking-[-0.01em] text-[clamp(2rem,5.2vw,3.75rem)] {{ $titleTone }} {{ $titleClass }}">
        {{ $title }}
    </h2>

    @if ($subtitle)
        <p class="max-w-2xl text-pretty text-[1.05rem] leading-relaxed {{ $align === 'center' ? 'mx-auto' : '' }} {{ $subtitleTone }}">
            {{ $subtitle }}
        </p>
    @endif
</div>
