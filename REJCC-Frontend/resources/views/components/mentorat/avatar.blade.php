@props(['personne', 'size' => 'size-11', 'texte' => 'text-sm'])

@php $initiales = mb_strtoupper(mb_substr($personne['prenom'] ?? '', 0, 1).mb_substr($personne['nom'] ?? '', 0, 1)); @endphp
<span x-data="{ erreur: false }" {{ $attributes->merge(['class' => "relative inline-flex $size shrink-0"]) }}>
    @if ($personne['photo'] ?? null)
        <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $personne['photo'] }}" alt="" class="{{ $size }} rounded-xl object-cover">
    @endif
    <span @if ($personne['photo'] ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, #AC0100, #D95B5A)" @else style="background: linear-gradient(135deg, #AC0100, #D95B5A)" @endif class="flex {{ $size }} items-center justify-center rounded-xl {{ $texte }} font-bold text-white">{{ $initiales }}</span>
</span>
