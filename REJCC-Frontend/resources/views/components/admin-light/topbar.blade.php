@props(['title' => "Vue d'ensemble"])

{{-- Barre du haut de l'administration : fil d'Ariane, recherche (Ctrl+K),
     cloche « À traiter » et menu du compte. --}}
@php
    use App\Support\AdminNav;

    $user = \App\Support\Api::user();
    $aTraiter = AdminNav::aTraiter();
    [$rubrique, $parent] = AdminNav::fil(request()->route()?->getName() ?? '');
    $restreint = is_array($user->permissions ?? null);
    $initiales = mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1));
@endphp

<header class="sticky top-0 z-50 flex h-16 items-center gap-3 border-b border-brand/10 border-t-[3px] border-t-accent bg-white/90 px-4 backdrop-blur-lg lg:px-7">
    <button type="button" @click="mobileOpen = true" aria-label="Ouvrir le menu" data-test="ouvrir-menu-admin" class="flex size-10 shrink-0 items-center justify-center rounded-[10px] border border-brand/10 text-brand transition-colors hover:bg-cloud active:scale-95 lg:hidden">
        <x-ui.icon name="menu" class="size-[18px]" />
    </button>

    <nav aria-label="Fil d'Ariane" data-test="fil-ariane-admin" class="flex min-w-0 items-center gap-1.5 text-[13px]">
        @if ($rubrique)
            <span class="hidden shrink-0 font-semibold text-[#9AA6B8] md:inline">{{ $rubrique }}</span>
            <x-ui.icon name="chevron-right" class="hidden size-3.5 shrink-0 text-[#C9D3E6] md:block" />
        @endif
        @if ($parent)
            <a href="{{ route($parent[1]) }}" wire:navigate class="hidden shrink-0 font-semibold text-[#5B677A] hover:text-brand sm:inline">{{ $parent[0] }}</a>
            <x-ui.icon name="chevron-right" class="hidden size-3.5 shrink-0 text-[#C9D3E6] sm:block" />
        @endif
        <span class="truncate text-[15px] font-extrabold text-brand">{{ $title }}</span>
    </nav>

    <div class="flex-1"></div>

    <livewire:admin.admin-search />

    {{-- Cloche « À traiter » --}}
    <div x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape.window="ouvert = false" class="relative shrink-0">
        <button type="button" @click="ouvert = ! ouvert" :aria-expanded="ouvert" aria-label="Éléments à traiter" data-test="cloche-admin"
            class="relative flex size-10 items-center justify-center rounded-[10px] border border-brand/10 bg-white transition-all duration-200 hover:bg-cloud active:scale-95">
            <x-ui.icon name="bell" class="size-[18px] text-brand" />
            @if ($aTraiter['total'] > 0)
                <span class="tab-dot absolute -right-1 -top-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-white bg-accent px-1 text-[10px] font-bold text-white">{{ $aTraiter['total'] > 99 ? '99+' : $aTraiter['total'] }}</span>
            @endif
        </button>
        <div x-show="ouvert" x-transition.origin.top.right style="display: none" data-test="panneau-a-traiter"
            class="absolute right-0 top-[calc(100%+8px)] z-[95] w-[320px] max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-[14px] border border-brand/10 bg-white shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
            <p class="border-b border-cloud-200 px-4 py-3 text-[13px] font-bold text-brand">À traiter</p>
            @php $enAttente = collect($aTraiter['elements'])->where('nombre', '>', 0); @endphp
            @forelse ($enAttente as $e)
                <a href="{{ route($e['route']) }}" wire:navigate class="flex items-center justify-between gap-3 px-4 py-2.5 text-[13px] transition-colors hover:bg-cloud/70">
                    <span class="text-ink">{{ $e['libelle'] }}</span>
                    <span class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $e['nombre'] }}</span>
                </a>
            @empty
                <p class="flex items-center gap-2 px-4 py-5 text-[13px] text-[#5B677A]"><x-ui.icon name="check-circle" class="size-4 text-[#22A85A]" /> Rien à traiter pour le moment.</p>
            @endforelse
        </div>
    </div>

    {{-- Menu du compte --}}
    <div x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape.window="ouvert = false" class="relative shrink-0">
        <button type="button" @click="ouvert = ! ouvert" :aria-expanded="ouvert" aria-label="Menu du compte" data-test="menu-compte-admin"
            class="flex h-10 items-center gap-2.5 rounded-xl border border-brand/10 bg-white py-1 pl-1 pr-1 transition-all duration-200 hover:bg-cloud active:scale-95 sm:pl-3">
            <span class="hidden text-right sm:block">
                <span class="block whitespace-nowrap text-[13px] font-bold leading-tight text-brand">{{ $user->prenom }} {{ $user->nom }}</span>
                <span class="block whitespace-nowrap text-[11px] font-semibold leading-tight {{ $restreint ? 'text-[#B27007]' : 'text-[#5B677A]' }}">{{ $restreint ? 'Accès limité' : 'Administration' }}</span>
            </span>
            <span class="flex size-8 items-center justify-center rounded-[9px] text-[11px] font-bold text-white" style="background: linear-gradient(135deg, #AC0100, #D95B5A)">{{ $initiales }}</span>
            <x-ui.icon name="chevron-down" class="hidden size-3.5 text-[#9AA6B8] transition-transform sm:block" x-bind:class="ouvert ? 'rotate-180' : ''" />
        </button>
        <div x-show="ouvert" x-transition.origin.top.right style="display: none" class="absolute right-0 top-[calc(100%+8px)] z-[95] w-[230px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1.5 text-[13px] shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
            <div class="border-b border-cloud-200 px-4 pb-2.5 pt-1.5">
                <p class="truncate font-bold text-brand">{{ $user->prenom }} {{ $user->nom }}</p>
                <p class="truncate text-[11.5px] text-[#9AA6B8]">{{ $user->email ?? '' }}</p>
            </div>
            <div class="py-1">
                <a href="{{ route('espace-membre.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="nav-home" class="size-4 text-[#5B677A]" /> Espace membre</a>
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="external-link" class="size-4 text-[#5B677A]" /> Voir le site</a>
            </div>
            <div class="border-t border-cloud-200 pt-1">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold text-accent hover:bg-accent/5"><x-ui.icon name="log-out" class="size-4" /> Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>
