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
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto grid w-full max-w-[1060px] gap-6 lg:grid-cols-2']) }}>

    {{-- ═══ RECTO ═══ --}}
    <div class="relative aspect-[1.585/1] overflow-hidden rounded-[22px] text-white shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="background: radial-gradient(120% 130% at 20% 0%, #1A2E63 0%, #14224E 45%, #0C1838 100%)">

        {{-- Filigrane monogramme --}}
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -left-16 -top-14 w-[46%] opacity-[.06]">
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -bottom-20 left-6 w-[38%] opacity-[.05]">

        <div class="relative flex h-full flex-col items-center px-[6%] py-[6%]">
            {{-- Zone photo (haut-gauche) --}}
            <div class="absolute left-[6%] top-[6%]">
                @if ($photo)
                    <img src="{{ $photo }}" alt="Photo de {{ $name }}"
                         class="size-[74px] rounded-[14px] object-cover ring-2 ring-white/25 sm:size-[96px]">
                @elseif ($editable && $uploadId)
                    <label for="{{ $uploadId }}"
                           class="flex size-[74px] cursor-pointer flex-col items-center justify-center gap-1 rounded-[14px] border border-dashed border-white/35 bg-white/[.06] text-center text-white/60 hover:bg-white/[.12] sm:size-[96px]">
                        <x-ui.icon name="image" class="size-5" />
                        <span class="text-[8.5px] font-semibold leading-tight">Photo du membre<br><span class="underline">parcourir</span></span>
                    </label>
                @else
                    <div class="flex size-[74px] items-center justify-center rounded-[14px] bg-white/10 text-lg font-bold text-white/70 ring-2 ring-white/15 sm:size-[96px]">
                        {{ mb_strtoupper(mb_substr($name, 0, 2)) }}
                    </div>
                @endif
            </div>

            {{-- Logo REJCC centré --}}
            <img src="{{ asset('brand/rejcc-logo-white.png') }}" alt="REJCC"
                 class="mt-[2%] h-[38%] max-h-[190px] object-contain">

            {{-- Nom --}}
            <p class="mt-auto text-center font-serif text-[clamp(20px,4.2vw,40px)] font-bold uppercase leading-none tracking-[0.02em]">{{ $name }}</p>
            <p class="mt-2 text-center text-[clamp(9px,1.5vw,13px)] font-bold uppercase tracking-[0.32em]" style="color: {{ $accent }}">{{ $roleLabel }}</p>

            {{-- Organisation --}}
            <div class="mb-[2%] mt-auto text-center">
                <p class="text-[clamp(8px,1.4vw,12px)] font-bold uppercase tracking-[0.14em] text-white/90">Réseau Entrepreneurial des Jeunes Catholiques</p>
                <p class="mt-1 text-[clamp(8px,1.3vw,12px)] font-bold uppercase tracking-[0.28em]" style="color: {{ $accent }}">de Côte d'Ivoire</p>
            </div>
        </div>
    </div>

    {{-- ═══ VERSO ═══ --}}
    <div class="relative aspect-[1.585/1] overflow-hidden rounded-[22px] text-white shadow-[0_24px_60px_-24px_rgba(3,29,89,.55)]"
         style="background: radial-gradient(120% 130% at 80% 100%, #1A2E63 0%, #14224E 45%, #0C1838 100%)">

        {{-- Filigrane monogramme (à droite) --}}
        <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -right-10 bottom-[-10%] w-[42%] opacity-[.06]">

        <div class="relative flex h-full flex-col px-[7%] py-[7%]">
            {{-- QR code --}}
            <div class="w-fit rounded-[16px] bg-white p-2.5 shadow-lg sm:p-3">
                <canvas x-data x-init="window.QRCode && window.QRCode.toCanvas($el, '{{ $qrUrl }}', { width: 150, margin: 0, color: { dark: '#0C1838', light: '#ffffff' } })"
                        class="block size-[96px] sm:size-[130px]"></canvas>
            </div>
            <p class="mt-4 max-w-[220px] text-[clamp(10px,1.5vw,14px)] font-bold uppercase leading-snug tracking-[0.16em]">Scannez pour accéder à votre profil membre</p>
            <span class="mt-2 block h-[3px] w-16 rounded-full" style="background: {{ $accent }}"></span>

            <div class="mt-auto space-y-3.5">
                <div>
                    <p class="text-[clamp(9px,1.3vw,12px)] font-bold uppercase tracking-[0.2em] text-white/85">N° Membre</p>
                    <p class="text-[clamp(12px,2vw,17px)] font-bold uppercase tracking-[0.12em]" style="color: {{ $accent }}">{{ $numero }}</p>
                </div>
                @if ($dateAdhesion)
                    <div>
                        <p class="text-[clamp(9px,1.3vw,12px)] font-bold uppercase tracking-[0.2em] text-white/85">Date d'adhésion</p>
                        <p class="text-[clamp(12px,2vw,17px)] font-bold uppercase tracking-[0.12em]" style="color: {{ $accent }}">{{ $dateAdhesion }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
