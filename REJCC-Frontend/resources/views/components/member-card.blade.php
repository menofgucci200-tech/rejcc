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
    // Accent distinct selon le statut : la carte diffère visuellement mais garde
    // le même principe (N° membre, QR). Membre = rouge, mentor = or, admin = azur.
    $accent = match ($role) {
        'mentor' => '#F5A623',
        'admin' => '#4F6FBF',
        default => '#AC0100',
    };
    $qrUrl = url('/carte/'.$code);
    // Toutes les tailles sont en cqw (% de la largeur de la carte) : le rendu
    // reste fidèle à la maquette quelle que soit la taille d'affichage.
    $bg = 'background: radial-gradient(125% 135% at 22% 5%, #1B2E63 0%, #142350 45%, #0B1636 100%)';
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto grid w-full max-w-[1040px] gap-6 lg:grid-cols-2']) }}>

    {{-- ═══════════════ RECTO ═══════════════ --}}
    <div class="relative aspect-[317/200] overflow-hidden rounded-[4cqw] text-white shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="container-type: inline-size; {{ $bg }}">

        {{-- Filigranes monogramme --}}
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -left-[10cqw] -top-[8cqw] w-[42cqw] opacity-[.06]">
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -bottom-[14cqw] left-[3cqw] w-[34cqw] opacity-[.05]">

        {{-- Photo (haut-gauche) --}}
        <div class="absolute left-[6cqw] top-[6cqw] z-10">
            @if ($photo)
                <img src="{{ $photo }}" alt="Photo de {{ $name }}"
                     class="size-[24cqw] rounded-[3cqw] object-cover ring-1 ring-white/25">
            @elseif ($editable && $uploadId)
                <label for="{{ $uploadId }}"
                       class="flex size-[24cqw] cursor-pointer flex-col items-center justify-center gap-[1.5cqw] rounded-[3cqw] border border-dashed border-white/35 bg-white/[.05] text-center text-white/55 hover:bg-white/[.12]">
                    <x-ui.icon name="image" class="w-[6cqw]" />
                    <span class="text-[2cqw] font-semibold leading-tight">Photo du membre<br><span class="underline">parcourir</span></span>
                </label>
            @else
                <div class="flex size-[24cqw] items-center justify-center rounded-[3cqw] bg-white/10 text-[7cqw] font-bold text-white/70 ring-1 ring-white/15">
                    {{ mb_strtoupper(mb_substr($name, 0, 2)) }}
                </div>
            @endif
        </div>

        {{-- Contenu centré --}}
        <div class="relative flex h-full flex-col items-center px-[6cqw] pb-[6cqw] pt-[5cqw]">
            <img src="{{ asset('brand/rejcc-logo-white.png') }}" alt="REJCC"
                 class="h-[27cqw] object-contain">

            <p class="mt-[5cqw] text-center font-serif text-[5.4cqw] font-bold uppercase leading-none tracking-[0.02em]">{{ $name }}</p>
            <p class="mt-[2.2cqw] text-center text-[1.9cqw] font-bold uppercase tracking-[0.34em]" style="color: {{ $accent }}">{{ $roleLabel }}</p>

            <div class="mt-auto text-center">
                <p class="text-[1.7cqw] font-bold uppercase tracking-[0.16em] text-white/90">Réseau Entrepreneurial des Jeunes Catholiques</p>
                <p class="mt-[1cqw] text-[1.6cqw] font-bold uppercase tracking-[0.3em]" style="color: {{ $accent }}">de Côte d'Ivoire</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════ VERSO ═══════════════ --}}
    <div class="relative aspect-[317/200] overflow-hidden rounded-[4cqw] text-white shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="container-type: inline-size; background: radial-gradient(125% 135% at 82% 100%, #1B2E63 0%, #142350 45%, #0B1636 100%)">

        {{-- Filigrane monogramme (droite) --}}
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -right-[6cqw] bottom-[-8cqw] w-[40cqw] opacity-[.06]">

        <div class="relative flex h-full flex-col px-[7cqw] py-[7cqw]">
            {{-- QR --}}
            <div class="w-fit rounded-[3cqw] bg-white p-[1.6cqw] shadow-lg">
                <canvas x-data x-init="window.QRCode && window.QRCode.toCanvas($el, '{{ $qrUrl }}', { width: 200, margin: 0, color: { dark: '#0B1636', light: '#ffffff' } })"
                        class="block size-[22cqw]"></canvas>
            </div>

            <p class="mt-[4cqw] max-w-[46cqw] text-[2cqw] font-bold uppercase leading-snug tracking-[0.16em]">Scannez pour accéder à votre profil membre</p>
            <span class="mt-[2cqw] block h-[0.6cqw] w-[13cqw] rounded-full" style="background: {{ $accent }}"></span>

            <div class="mt-auto space-y-[3.5cqw]">
                <div>
                    <p class="text-[1.6cqw] font-bold uppercase tracking-[0.22em] text-white/85">N° Membre</p>
                    <p class="mt-[0.6cqw] text-[2.4cqw] font-bold uppercase tracking-[0.12em]" style="color: {{ $accent }}">{{ $numero }}</p>
                </div>
                @if ($dateAdhesion)
                    <div>
                        <p class="text-[1.6cqw] font-bold uppercase tracking-[0.22em] text-white/85">Date d'adhésion</p>
                        <p class="mt-[0.6cqw] text-[2.4cqw] font-bold uppercase tracking-[0.12em]" style="color: {{ $accent }}">{{ $dateAdhesion }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
