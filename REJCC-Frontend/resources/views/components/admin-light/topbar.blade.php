@props(['title' => "Vue d'ensemble"])

@php
    $user = \App\Support\Api::user();
    $stats = \App\Support\Api::get('/admin/stats', [], \App\Support\Api::token())['stats'] ?? [];
    $attention = ($stats['candidatures_attente'] ?? 0) + ($stats['adhesions_attente'] ?? 0) + ($stats['non_traites'] ?? 0) + ($stats['partenariats_attente'] ?? 0);
@endphp

<header class="sticky top-0 z-50 flex h-16 items-center gap-4 border-b border-brand/10 border-t-[3px] border-t-accent bg-white/85 px-7 backdrop-blur-lg">
    <button type="button" @click="mobileOpen = true" class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-brand/10 text-brand lg:hidden">
        <x-ui.icon name="menu" class="size-[17px]" />
    </button>

    <p class="truncate text-base font-extrabold text-brand">{{ $title }}</p>

    <div class="flex-1"></div>

    <div class="hidden w-[280px] shrink items-center gap-2.5 rounded-[10px] border border-brand/10 bg-cloud px-3.5 py-2.5 md:flex">
        <x-ui.icon name="search" class="size-4 shrink-0 text-[#5B677A]" />
        <input type="text" placeholder="Rechercher un membre, un projet…" class="w-full min-w-0 border-none bg-transparent text-[13px] text-ink outline-none placeholder:text-[#9AA6B8]" />
    </div>

    <span class="relative flex size-10 shrink-0 items-center justify-center rounded-[10px] border border-brand/10 bg-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md">
        <x-ui.icon name="bell" class="size-[18px] text-brand" />
        @if ($attention > 0)
            <span class="tab-dot absolute -right-1 -top-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-white bg-accent px-1 text-[10px] font-bold text-white">{{ $attention }}</span>
        @endif
    </span>

    <div class="group flex shrink-0 items-center gap-2.5 rounded-xl border border-brand/10 bg-white py-[5px] pl-3 pr-1.5 transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md">
        <div class="hidden text-right sm:block">
            <p class="whitespace-nowrap text-[13px] font-bold leading-tight text-brand">{{ $user->prenom }} {{ $user->nom }}</p>
            <p class="whitespace-nowrap text-[11px] leading-tight text-[#5B677A]">Administrateur·rice</p>
        </div>
        <div class="flex size-9 shrink-0 items-center justify-center rounded-[10px] text-xs font-bold text-white transition-transform duration-200 group-hover:scale-105" style="background: linear-gradient(135deg, #AC0100, #D95B5A)">
            {{ mb_substr($user->prenom, 0, 1) }}{{ mb_substr($user->nom, 0, 1) }}
        </div>
    </div>
</header>
