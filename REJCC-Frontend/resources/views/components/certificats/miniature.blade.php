@props(['c'])
{{-- Miniature fidèle au modèle « Prestige » (aperçu dans les listes). --}}
@php
    [$t1, $t2] = match ($c['type']) {
        'evenement' => ['ATTESTATION', 'DE PARTICIPATION'],
        'parcours' => ['ATTESTATION', 'DE PARCOURS'],
        default => ['CERTIFICAT', 'DE RÉUSSITE'],
    };
@endphp
<div {{ $attributes->merge(['class' => 'relative aspect-[297/210] overflow-hidden rounded-[10px] text-white']) }}
    style="background: radial-gradient(ellipse at 50% 0%, #0a2c6e 0%, #031d59 45%, #021541 100%)">
    <div aria-hidden="true" class="absolute inset-0 opacity-60" style="background: repeating-linear-gradient(135deg, rgba(79,111,191,.07) 0 1px, transparent 1px 7px)"></div>
    <div aria-hidden="true" class="absolute inset-[3.5%] border border-[#8FA3D9]/40"></div>
    <div aria-hidden="true" class="absolute right-[8%] top-0 h-[22%] w-[8%] bg-gradient-to-b from-[#c8160f] to-accent" style="clip-path: polygon(0 0,100% 0,100% 100%,50% 82%,0 100%)"></div>
    <div class="absolute inset-0 flex flex-col items-center justify-center px-[10%] text-center">
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" class="mb-[3%] h-[11%]">
        <p class="font-display text-[19px] leading-none tracking-[0.2em]">{{ $t1 }}</p>
        <p class="mt-[1.5%] text-[7px] font-bold tracking-[0.35em] text-[#8FA3D9]">{{ $t2 }}</p>
        <p class="mt-[4%] w-full truncate font-serif text-[19px] leading-tight">{{ $c['nom'] }}</p>
        <span class="my-[2.5%] h-px w-1/3 bg-[#8FA3D9]/50"></span>
        <p class="line-clamp-2 text-[9.5px] font-bold leading-snug">{{ $c['titre'] }}</p>
    </div>
    @if ($c['statut'] !== 'valide')
        <div class="absolute inset-0 flex items-center justify-center bg-brand/60">
            <span class="-rotate-12 rounded-md border-2 border-white px-3 py-1 text-[13px] font-extrabold tracking-[0.2em] text-white">RÉVOQUÉ</span>
        </div>
    @endif
</div>
