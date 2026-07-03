@props(['id'])

@php
    $logos = [
        'wave' => ['src' => '/brand/wave.jpg', 'alt' => 'Wave'],
        'orange' => ['src' => '/brand/orange_money.png', 'alt' => 'Orange Money'],
        'djamo' => ['src' => '/brand/djamo.jpg', 'alt' => 'Djamo'],
    ];
    $logo = $logos[$id] ?? null;
@endphp

@if ($logo)
    <span {{ $attributes->merge(['class' => 'block overflow-hidden rounded-lg']) }} style="height: 46px; width: 104px;">
        <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] }}" class="size-full object-contain" />
    </span>
@endif
