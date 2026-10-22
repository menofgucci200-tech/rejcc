@props(['statut', 'label'])

@php
    $styles = [
        'en_attente' => 'bg-[#F5A623]/15 text-[#8A5A00]',
        'accepte' => 'bg-[#22A85A]/10 text-[#1C8F4C]',
        'refuse' => 'bg-cloud text-[#5B677A]',
        'annule' => 'bg-cloud text-[#9AA6B8]',
        'termine' => 'bg-brand/10 text-brand',
    ];
@endphp
<span data-test="statut-mentorat" {{ $attributes->merge(['class' => 'shrink-0 rounded-full px-2.5 py-1 text-[10.5px] font-bold '.($styles[$statut] ?? 'bg-cloud text-[#5B677A]')]) }}>{{ $label }}</span>
