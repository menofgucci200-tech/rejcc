@props(['personne', 'size' => 'size-11', 'texte' => 'text-sm'])

@if ($personne['photo'] ?? null)
    <img src="{{ $personne['photo'] }}" alt="" {{ $attributes->merge(['class' => "$size shrink-0 rounded-xl object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "flex $size shrink-0 items-center justify-center rounded-xl $texte font-bold text-white"]) }} style="background: linear-gradient(135deg, #AC0100, #D95B5A)">
        {{ mb_strtoupper(mb_substr($personne['prenom'] ?? '', 0, 1).mb_substr($personne['nom'] ?? '', 0, 1)) }}
    </span>
@endif
