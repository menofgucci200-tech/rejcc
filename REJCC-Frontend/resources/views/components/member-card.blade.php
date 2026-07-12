@props([
    'name',
    'roleLabel',
    'role' => 'member',
    'numero',
    'code',
    'photo' => null,
    'dateAdhesion' => null,
    'editable' => false,
    'uploadId' => null,
])

@php
    // Design officiel « Carte Standard REJCC » (PDF de la charte).
    // Fond navy #1D2556 et accent #A44B4B extraits du fichier source ;
    // les visuels carte-logo.png et carte-cathedrale.png en sont découpés.
    // Les statuts non-membres gardent la même carte avec un accent distinct.
    $accent = match ($role) {
        'mentor' => '#F5A623',
        'admin' => '#4F6FBF',
        default => '#A44B4B',
    };
    $navy = '#1D2556';
    $qrUrl = url('/carte/'.$code);
    // Unités cqw/cqh : proportionnelles à la carte (container queries).
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto grid w-full max-w-[1060px] gap-6 lg:grid-cols-2']) }}>

    {{-- ═══════════════ RECTO ═══════════════ --}}
    <div class="relative aspect-[1586/1000] overflow-hidden rounded-[4.5cqw] shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="container-type: size; background: {{ $navy }}">

        {{-- Filigranes monogramme (haut-gauche fragmenté + bas-gauche) --}}
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -left-[9cqw] -top-[10cqw] w-[34cqw] opacity-[.05]">
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -left-[4cqw] bottom-[-16cqw] w-[36cqw] opacity-[.07]">

        {{-- Photo du membre (haut-gauche) --}}
        <div class="absolute left-[6.5cqw] top-[9cqh]">
            @if ($photo)
                <img src="{{ $photo }}" alt="Photo de {{ $name }}"
                     class="h-[40cqh] w-[22cqw] rounded-[2cqw] object-cover ring-1 ring-white/20">
            @elseif ($editable && $uploadId)
                <label for="{{ $uploadId }}"
                       class="flex h-[40cqh] w-[22cqw] cursor-pointer flex-col items-center justify-center gap-[1.5cqw] rounded-[2cqw] border border-dashed border-white/30 bg-white/[.04] text-center text-white/50 hover:bg-white/[.1]">
                    <x-ui.icon name="image" class="w-[4.5cqw]" />
                    <span class="text-[1.7cqw] font-semibold leading-snug">Photo du membre</span>
                </label>
            @else
                <div class="flex h-[40cqh] w-[22cqw] items-center justify-center rounded-[2cqw] bg-white/[.07] text-[6cqw] font-bold text-white/60 ring-1 ring-white/10">
                    {{ mb_strtoupper(mb_substr($name, 0, 2)) }}
                </div>
            @endif
        </div>

        {{-- Lockup logo officiel (monogramme + REJCC + filet), découpé du PDF --}}
        <img src="{{ asset('brand/carte-logo.png') }}" alt="REJCC"
             class="absolute left-1/2 top-[7cqh] w-[30cqw] -translate-x-1/2">

        {{-- Nom + statut --}}
        <p class="absolute inset-x-[8cqw] top-[57cqh] text-center font-serif text-[5cqw] font-bold uppercase leading-none tracking-[0.06em] text-white">{{ $name }}</p>
        <p class="absolute inset-x-[8cqw] top-[70.5cqh] text-center text-[1.8cqw] font-bold uppercase tracking-[0.4em]" style="color: {{ $accent }}">{{ $roleLabel }}</p>

        {{-- Organisation --}}
        <p class="absolute inset-x-[4cqw] top-[83.5cqh] text-center text-[1.9cqw] font-bold uppercase tracking-[0.18em] text-white">Réseau Entrepreneurial des Jeunes Catholiques</p>
        <p class="absolute inset-x-[4cqw] top-[89.5cqh] text-center text-[1.8cqw] font-bold uppercase tracking-[0.3em]" style="color: {{ $accent }}">de Côte d'Ivoire</p>
    </div>

    {{-- ═══════════════ VERSO ═══════════════ --}}
    <div class="relative aspect-[1586/1000] overflow-hidden rounded-[4.5cqw] shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="container-type: size; background: {{ $navy }}">

        {{-- Cathédrale + liseré monogramme (moitié droite), découpés du PDF --}}
        <img src="{{ asset('brand/carte-cathedrale.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute bottom-0 right-0 h-[82cqh] w-[66cqw] object-cover object-right-bottom">

        {{-- Colonne gauche, centrée sur ~18 % de la largeur --}}
        <div class="absolute left-[2cqw] top-0 flex h-full w-[33cqw] flex-col items-center">
            {{-- QR --}}
            <div class="mt-[12cqh] rounded-[2.6cqw] bg-white p-[1.4cqw] shadow-lg">
                <canvas x-data x-init="window.QRCode && window.QRCode.toCanvas($el, '{{ $qrUrl }}', { width: 220, margin: 0, color: { dark: '{{ $navy }}', light: '#ffffff' } })"
                        class="block size-[15cqw]"></canvas>
            </div>

            <p class="mt-[6.5cqh] text-center text-[1.55cqw] font-bold uppercase leading-relaxed tracking-[0.2em] text-white">Scannez pour accéder à<br>votre profil membre</p>
            <span class="mt-[2.5cqh] block h-[0.28cqw] w-[14.5cqw] rounded-full" style="background: {{ $accent }}"></span>

            <div class="mt-auto w-full pb-[7cqh] text-center">
                <p class="text-[1.6cqw] font-bold uppercase tracking-[0.28em] text-white">N° Membre</p>
                <p class="mt-[1.6cqh] text-[1.75cqw] font-semibold uppercase tracking-[0.14em]" style="color: {{ $accent }}">{{ $numero }}</p>
                @if ($dateAdhesion)
                    <p class="mt-[5.5cqh] text-[1.6cqw] font-bold uppercase tracking-[0.28em] text-white">Date d'adhésion</p>
                    <p class="mt-[1.6cqh] text-[1.75cqw] font-semibold uppercase tracking-[0.14em]" style="color: {{ $accent }}">{{ $dateAdhesion }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
