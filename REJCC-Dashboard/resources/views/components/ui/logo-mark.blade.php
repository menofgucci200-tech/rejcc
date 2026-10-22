@props([
    'kind' => 'mono-color',
    'alt' => 'REJCC',
])

@php
    // Logos officiels (charte 2026), vectoriels : nets à toutes les tailles.
    $sources = [
        'lockup-color' => ['src' => '/brand/rejcc-logo-color.svg', 'w' => 501, 'h' => 917],
        'lockup-white' => ['src' => '/brand/rejcc-logo-white.svg', 'w' => 501, 'h' => 917],
        'mono-color' => ['src' => '/brand/rejcc-monogram-color.svg', 'w' => 428, 'h' => 646],
        'mono-white' => ['src' => '/brand/rejcc-monogram-white.svg', 'w' => 428, 'h' => 646],
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
