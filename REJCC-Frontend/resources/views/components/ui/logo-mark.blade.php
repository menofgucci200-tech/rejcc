@props([
    'kind' => 'mono-color',
    'alt' => 'REJCC',
])

@php
    $sources = [
        'lockup-color' => ['src' => '/brand/rejcc-logo-color.png', 'w' => 649, 'h' => 1213],
        'lockup-white' => ['src' => '/brand/rejcc-logo-white.png', 'w' => 649, 'h' => 1213],
        'mono-color' => ['src' => '/brand/rejcc-monogram-color.png', 'w' => 649, 'h' => 837],
        'mono-white' => ['src' => '/brand/rejcc-monogram-white.png', 'w' => 649, 'h' => 837],
    ];
    $source = $sources[$kind] ?? $sources['mono-color'];
@endphp

<img
    src="{{ $source['src'] }}"
    width="{{ $source['w'] }}"
    height="{{ $source['h'] }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'w-auto select-none']) }}
/>
