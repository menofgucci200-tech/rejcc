@props(['c'])
{{--
    Vignette d'un certificat dans les listes. Volontairement, elle ne reproduit
    PAS le document (ni nom, ni code, ni mise en page) : le certificat n'existe
    qu'au format PDF officiel, et une capture de cette vignette ne peut pas
    passer pour un certificat.
--}}
@php
    $intitule = match ($c['type']) {
        'evenement' => 'Attestation de participation',
        'parcours' => 'Attestation de parcours',
        default => 'Certificat de réussite',
    };
@endphp
<div {{ $attributes->merge(['class' => 'relative aspect-[297/210] overflow-hidden rounded-[10px] bg-[#EEF2F8]']) }}>
    <div aria-hidden="true" class="absolute inset-0 opacity-70" style="background: repeating-linear-gradient(135deg, rgba(3,29,89,.035) 0 1px, transparent 1px 9px)"></div>
    {{-- Feuille stylisée, sans contenu lisible --}}
    <svg viewBox="0 0 120 160" class="absolute left-1/2 top-[9%] h-[68%] w-auto -translate-x-1/2 drop-shadow-[0_6px_14px_rgba(3,29,89,.16)]" aria-hidden="true">
        <path d="M6 0h84l30 30v124a6 6 0 0 1-6 6H6a6 6 0 0 1-6-6V6a6 6 0 0 1 6-6z" fill="#fff" />
        <path d="M90 0v24a6 6 0 0 0 6 6h24z" fill="#E3E8F1" />
        <circle cx="60" cy="50" r="19" fill="#031D59" />
        <g fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="60" cy="46" r="6.5" /><path d="m64 51.5 2 11.5-6-3.5-6 3.5 2-11.5" />
        </g>
        <rect x="28" y="82" width="64" height="4" rx="2" fill="#031D59" fill-opacity=".16" />
        <rect x="36" y="93" width="48" height="4" rx="2" fill="#031D59" fill-opacity=".1" />
        <rect x="32" y="104" width="56" height="4" rx="2" fill="#031D59" fill-opacity=".1" />
        <rect x="40" y="126" width="40" height="17" rx="4" fill="#AC0100" />
        <text x="60" y="138.2" text-anchor="middle" font-family="Manrope, sans-serif" font-size="10" font-weight="800" letter-spacing="1.4" fill="#fff">PDF</text>
    </svg>
    <p class="absolute bottom-[6%] left-0 right-0 text-center text-[10px] font-bold uppercase tracking-[0.14em] text-brand/60">{{ $intitule }}</p>
    @if ($c['statut'] !== 'valide')
        <div class="absolute inset-0 flex items-center justify-center bg-brand/55">
            <span class="-rotate-12 rounded-md border-2 border-white px-3 py-1 text-[13px] font-extrabold tracking-[0.2em] text-white">RÉVOQUÉ</span>
        </div>
    @endif
</div>
