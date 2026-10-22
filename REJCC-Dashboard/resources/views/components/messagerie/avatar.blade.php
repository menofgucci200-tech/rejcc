@props(['personne', 'taille' => 'size-[42px]', 'texte' => 'text-[13px]'])

{{-- Avatar d'un interlocuteur : photo (initiales en secours), dégradé rouge pour les mentors. --}}
@php
    $degrade = ($personne['role'] ?? '') === 'mentor' ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100';
    $initiales = mb_strtoupper(mb_substr($personne['prenom'] ?? '', 0, 1).mb_substr($personne['nom'] ?? '', 0, 1));
@endphp
<span x-data="{ erreur: false }" {{ $attributes->merge(['class' => 'relative shrink-0']) }}>
    @if ($personne['photo'] ?? null)
        <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $personne['photo'] }}" alt="" class="{{ $taille }} rounded-full object-cover">
    @endif
    <span @if ($personne['photo'] ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $degrade }})" @else style="background: linear-gradient(135deg, {{ $degrade }})" @endif class="flex {{ $taille }} items-center justify-center rounded-full {{ $texte }} font-bold text-white">{{ $initiales }}</span>
</span>
