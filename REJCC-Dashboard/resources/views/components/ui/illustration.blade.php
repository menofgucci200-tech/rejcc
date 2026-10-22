@props(['name', 'size' => 'size-10'])
{{-- Icônes illustrées en couleur (style plat, tons doux), pour sortir du tout-bleu :
     agenda, crayon, cahier, visio, équipe, diplôme, horloge, mégaphone, livre, lien,
     dossier, argent, balance, ordinateur, colombe, poignée de main, carnet, étoile, globe. --}}
@php
    $o = 'stroke="#3B3F5C" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"';
@endphp
<svg {{ $attributes->merge(['class' => $size.' shrink-0']) }} viewBox="0 0 48 48" fill="none" aria-hidden="true">
    @switch($name)
        @case('agenda')
            <rect x="6" y="9" width="36" height="33" rx="5" fill="#FFFFFF" {!! $o !!}/>
            <path d="M6 14a5 5 0 0 1 5-5h26a5 5 0 0 1 5 5v5H6z" fill="#F47C7C" {!! $o !!}/>
            <rect x="14" y="5" width="4" height="8" rx="2" fill="#FFD166" {!! $o !!}/>
            <rect x="30" y="5" width="4" height="8" rx="2" fill="#FFD166" {!! $o !!}/>
            <text x="24" y="36" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="13" fill="#3B3F5C">25</text>
            @break
        @case('crayon')
            <path d="M33 7l8 8-22 22-10 2 2-10z" fill="#FFD166" {!! $o !!}/>
            <path d="M33 7l4-4 8 8-4 4z" fill="#F47C7C" {!! $o !!}/>
            <path d="M11 29l8 8-10 2z" fill="#F6E6C8" {!! $o !!}/>
            <path d="M9 37l2-1 1 1-1 2z" fill="#3B3F5C"/>
            @break
        @case('cahier')
            <rect x="9" y="6" width="26" height="36" rx="3" fill="#FFE3D3" {!! $o !!}/>
            <rect x="13" y="6" width="22" height="36" rx="2" fill="#FFFFFF" {!! $o !!}/>
            @foreach ([14, 20, 26, 32] as $y)<path d="M17 {{ $y }}h13" stroke="#7FB3D5" stroke-width="1.8" stroke-linecap="round"/>@endforeach
            @foreach ([10, 16, 22, 28, 34] as $y)<circle cx="9" cy="{{ $y }}" r="1.8" fill="#FFFFFF" {!! $o !!}/>@endforeach
            <path d="M39 12v24l2 4 2-4V12z" fill="#F4A261" {!! $o !!}/>
            @break
        @case('visio')
            <rect x="5" y="12" width="28" height="24" rx="5" fill="#7FD1AE" {!! $o !!}/>
            <path d="M33 21l10-6v18l-10-6z" fill="#B8E6D1" {!! $o !!}/>
            <circle cx="19" cy="21" r="4" fill="#FFFFFF" {!! $o !!}/>
            <path d="M12 31c1.5-4 12.5-4 14 0" fill="#FFFFFF" {!! $o !!}/>
            <circle cx="10" cy="16" r="1.6" fill="#F47C7C"/>
            @break
        @case('equipe')
            <circle cx="17" cy="17" r="6" fill="#FFD9A8" {!! $o !!}/>
            <circle cx="32" cy="18" r="5" fill="#F9C4B0" {!! $o !!}/>
            <path d="M5 40c0-8 5-12 12-12s12 4 12 12z" fill="#9EC9F0" {!! $o !!}/>
            <path d="M24 30c2-2 5-3 8-3 6 0 11 4 11 13H29" fill="#C3B5F0" {!! $o !!}/>
            @break
        @case('diplome')
            <rect x="5" y="9" width="38" height="26" rx="4" fill="#FFF6DD" {!! $o !!}/>
            <path d="M11 17h18M11 23h14" stroke="#B0A07A" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="35" cy="26" r="6" fill="#FFD166" {!! $o !!}/>
            <path d="M31 31l-2 10 6-3 6 3-2-10" fill="#F47C7C" {!! $o !!}/>
            @break
        @case('horloge')
            <circle cx="24" cy="25" r="17" fill="#C3B5F0" {!! $o !!}/>
            <circle cx="24" cy="25" r="12.5" fill="#FFFFFF" {!! $o !!}/>
            <path d="M24 18v7l5 3" stroke="#3B3F5C" stroke-width="2" stroke-linecap="round"/>
            <path d="M15 6l-5 4M33 6l5 4" stroke="#3B3F5C" stroke-width="1.6" stroke-linecap="round"/>
            @break
        @case('megaphone')
            <path d="M8 20h7l18-10v28L15 28H8z" fill="#FFD166" {!! $o !!}/>
            <rect x="5" y="19" width="6" height="10" rx="2" fill="#F47C7C" {!! $o !!}/>
            <path d="M15 28l3 10h5l-2-8" fill="#F9C4B0" {!! $o !!}/>
            <path d="M38 18c2 1 3 3.5 3 6s-1 5-3 6" stroke="#7FB3D5" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('livre')
            <path d="M24 12c-5-4-12-4-17-2v28c5-2 12-2 17 2z" fill="#9EC9F0" {!! $o !!}/>
            <path d="M24 12c5-4 12-4 17-2v28c-5-2-12-2-17 2z" fill="#7FD1AE" {!! $o !!}/>
            <path d="M24 12v28" stroke="#3B3F5C" stroke-width="1.6"/>
            @break
        @case('lien')
            <rect x="5" y="17" width="20" height="14" rx="7" fill="#9EC9F0" {!! $o !!}/>
            <rect x="23" y="17" width="20" height="14" rx="7" fill="#F9C4B0" {!! $o !!}/>
            <path d="M18 24h12" stroke="#3B3F5C" stroke-width="2.2" stroke-linecap="round"/>
            @break
        @case('dossier')
            <path d="M5 13a3 3 0 0 1 3-3h10l4 4h18a3 3 0 0 1 3 3v21a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3z" fill="#FFD166" {!! $o !!}/>
            <path d="M5 19h38v19a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3z" fill="#FFE29A" {!! $o !!}/>
            @break
        @case('argent')
            <rect x="5" y="13" width="38" height="24" rx="4" fill="#7FD1AE" {!! $o !!}/>
            <circle cx="24" cy="25" r="6" fill="#B8E6D1" {!! $o !!}/>
            <path d="M24 21v8M22 23h3.5a1.5 1.5 0 0 1 0 3H22.5a1.5 1.5 0 0 0 0 3H26" stroke="#3B3F5C" stroke-width="1.4" stroke-linecap="round"/>
            <circle cx="11" cy="25" r="1.6" fill="#3B3F5C"/><circle cx="37" cy="25" r="1.6" fill="#3B3F5C"/>
            @break
        @case('balance')
            <path d="M24 8v32M14 41h20" stroke="#3B3F5C" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M10 14h28" stroke="#3B3F5C" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M4 27l6-13 6 13a6 6 0 0 1-12 0z" fill="#C3B5F0" {!! $o !!}/>
            <path d="M32 27l6-13 6 13a6 6 0 0 1-12 0z" fill="#FFD9A8" {!! $o !!}/>
            <circle cx="24" cy="8" r="3" fill="#FFD166" {!! $o !!}/>
            @break
        @case('ordinateur')
            <rect x="6" y="9" width="36" height="24" rx="3" fill="#9EC9F0" {!! $o !!}/>
            <rect x="10" y="13" width="28" height="16" rx="1.5" fill="#FFFFFF" {!! $o !!}/>
            <path d="M16 24l4-4 4 3 6-6" stroke="#7FD1AE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 33l-2 7h16l-2-7" fill="#C3B5F0" {!! $o !!}/>
            @break
        @case('colombe')
            <path d="M8 26c6-1 9-6 14-8 6-2 11 0 14 4l7-1-5 5c0 7-6 12-14 12-6 0-11-3-14-8l-6-1z" fill="#FFFFFF" {!! $o !!}/>
            <path d="M20 19c-2-6 1-11 6-13 1 6 0 10-3 13" fill="#C3E3F7" {!! $o !!}/>
            <circle cx="35" cy="24" r="1.3" fill="#3B3F5C"/>
            <path d="M42 22l4-1" stroke="#F4A261" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('mains')
            <path d="M4 22l9-8 8 4 6-3 9 5 8 2v8l-12 9-8-5-10-2-10-6z" fill="#FFD9A8" {!! $o !!}/>
            <path d="M21 18l-6 6 3 3 7-5 9 7" fill="none" {!! $o !!}/>
            <path d="M4 22l6 6M44 22l-8 8" stroke="#3B3F5C" stroke-width="1.6" stroke-linecap="round"/>
            @break
        @case('etoile')
            <path d="M24 5l5.6 11.6 12.4 1.8-9 8.8 2.2 12.4L24 33.8 12.8 39.6 15 27.2 6 18.4l12.4-1.8z" fill="#FFD166" {!! $o !!}/>
            @break
        @case('globe')
            <circle cx="24" cy="24" r="18" fill="#9EC9F0" {!! $o !!}/>
            <path d="M12 13c4 2 4 6 8 7s3 6 0 8-2 7 0 9M30 8c-1 4 3 6 6 5s4 4 2 7-5 1-6 5 2 6 4 7" fill="#7FD1AE" stroke="#3B3F5C" stroke-width="1.4" stroke-linejoin="round"/>
            @break
        @default
            <circle cx="24" cy="24" r="17" fill="#C3B5F0" {!! $o !!}/>
    @endswitch
</svg>
