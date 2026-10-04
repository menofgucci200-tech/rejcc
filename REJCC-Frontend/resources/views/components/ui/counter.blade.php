{{-- La vraie valeur est rendue côté serveur (lisible sans JS, et intacte quand
     Livewire rafraîchit la page) ; le JS la remet à 0 puis l'anime une seule fois. --}}
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
>{{ number_format((float) $value, 0, ',', ' ') }}{{ $suffix }}</span>
