@props([
    'value',
    'suffix' => '',
    'duration' => 2,
])

<span
    data-counter
    data-counter-value="{{ $value }}"
    data-counter-suffix="{{ $suffix }}"
    data-counter-duration="{{ $duration }}"
    {{ $attributes }}
>0{{ $suffix }}</span>
