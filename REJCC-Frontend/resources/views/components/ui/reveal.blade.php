@props([
    'direction' => 'up',
    'delay' => 0,
    'duration' => 0.7,
])

<div
    data-reveal
    data-reveal-direction="{{ $direction }}"
    {{ $attributes->merge(['style' => "--reveal-delay: {$delay}s; --reveal-duration: {$duration}s;"]) }}
>
    {{ $slot }}
</div>
