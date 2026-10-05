@props(['title' => 'Tableau de bord'])

{{-- Barre du haut de l'espace membre : fil d'Ariane (rubrique › page),
     recherche (Ctrl+K), notifications et menu du compte. --}}
@php
    $user = \App\Support\Api::user();
    $estMentor = ($user->role ?? null) === 'mentor';
    $route = request()->route()?->getName() ?? '';

    // Rubrique de la page courante, et page parente pour les pages de détail.
    $rubriques = [
        'carte' => 'Mon parcours', 'formations' => 'Mon parcours', 'catalogue' => 'Mon parcours', 'parcours' => 'Mon parcours', 'certificats' => 'Mon parcours',
        'mentorat' => 'Réseau', 'directory' => 'Réseau', 'groupes' => 'Réseau', 'messaging' => 'Réseau', 'evenements' => 'Réseau',
        'marketplace' => 'Opportunités', 'projets' => 'Opportunités', 'emplois' => 'Opportunités',
        'documents' => 'Ressources',
        'profile' => 'Mon compte', 'abonnement' => 'Mon compte', 'notifications' => 'Mon compte',
    ];
    $parents = [
        'espace-membre.formations.detail' => ['Mes formations', 'espace-membre.formations'],
        'espace-membre.catalogue.fiche' => ['Catalogue', 'espace-membre.catalogue'],
        'espace-membre.parcours.detail' => ['Mes parcours', 'espace-membre.parcours'],
        'espace-membre.mentorat.suivi' => ['Mentorat', 'espace-membre.mentorat'],
        'espace-membre.groupes.membres' => ['Groupes sectoriels', 'espace-membre.groupes'],
    ];
    $segment = explode('.', $route)[1] ?? '';
    $rubrique = $rubriques[$segment] ?? null;
    $parent = $parents[$route] ?? null;
    $initiales = mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1));
@endphp

<header class="sticky top-0 z-50 flex h-16 items-center gap-3 border-b border-brand/10 border-t-[3px] border-t-accent bg-white/90 px-4 backdrop-blur-lg lg:px-7">
    <button type="button" @click="mobileOpen = true" aria-label="Ouvrir le menu" data-test="ouvrir-menu" class="flex size-10 shrink-0 items-center justify-center rounded-[10px] border border-brand/10 text-brand transition-colors hover:bg-cloud active:scale-95 lg:hidden">
        <x-ui.icon name="menu" class="size-[18px]" />
    </button>

    <nav aria-label="Fil d'Ariane" data-test="fil-ariane" class="flex min-w-0 items-center gap-1.5 text-[13px]">
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

    <livewire:member.global-search />

    <livewire:member.notification-bell />

    {{-- Menu du compte --}}
    <div x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape.window="ouvert = false" class="relative shrink-0">
        <button type="button" @click="ouvert = ! ouvert" :aria-expanded="ouvert" aria-label="Menu du compte" data-test="menu-compte"
            class="group flex h-10 items-center gap-2.5 rounded-xl border border-brand/10 bg-white py-1 pl-1 pr-1 transition-all duration-200 ease-out hover:bg-cloud active:scale-95 sm:pl-3">
            <span class="hidden text-right sm:block">
                <span class="block whitespace-nowrap text-[13px] font-bold leading-tight text-brand">{{ $user->prenom }} {{ $user->nom }}</span>
                <span data-test="role-topbar" class="block whitespace-nowrap text-[11px] font-semibold leading-tight {{ $estMentor ? 'text-accent' : 'text-[#5B677A]' }}">{{ $user->role_label ?? 'Membre' }}</span>
            </span>
            <span x-data="{ erreur: false }" class="relative">
                @if ($user->photo ?? null)
                    <img x-show="! erreur" x-on:error="erreur = true" src="{{ $user->photo }}" alt="" class="size-8 rounded-[9px] object-cover">
                    <span x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})" class="flex size-8 items-center justify-center rounded-[9px] text-[11px] font-bold text-white">{{ $initiales }}</span>
                @else
                    <span class="flex size-8 items-center justify-center rounded-[9px] text-[11px] font-bold text-white" style="background: linear-gradient(135deg, {{ $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})">{{ $initiales }}</span>
                @endif
            </span>
            <x-ui.icon name="chevron-down" class="hidden size-3.5 text-[#9AA6B8] transition-transform sm:block" x-bind:class="ouvert ? 'rotate-180' : ''" />
        </button>

        <div x-show="ouvert" x-transition.origin.top.right style="display: none" class="absolute right-0 top-[calc(100%+8px)] z-[95] w-[230px] overflow-hidden rounded-[14px] border border-brand/10 bg-white py-1.5 text-[13px] shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
            <div class="border-b border-cloud-200 px-4 pb-2.5 pt-1.5">
                <p class="truncate font-bold text-brand">{{ $user->prenom }} {{ $user->nom }}</p>
                <p class="truncate text-[11.5px] text-[#9AA6B8]">{{ $user->email ?? '' }}</p>
            </div>
            <div class="py-1">
                @foreach ([
                    ['Mon profil', 'user', route('espace-membre.profile')],
                    [$estMentor ? 'Ma carte mentor' : 'Ma carte membre', 'qr-code', route('espace-membre.carte')],
                    ['Mon abonnement', 'shield-check', route('espace-membre.abonnement')],
                    ['Notifications', 'bell', route('espace-membre.notifications')],
                ] as [$label, $icon, $href])
                    <a href="{{ $href }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon :name="$icon" class="size-4 text-[#5B677A]" /> {{ $label }}</a>
                @endforeach
                @if (($user->role ?? null) === 'admin')
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="layout-dashboard" class="size-4 text-[#5B677A]" /> Administration</a>
                @endif
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="globe" class="size-4 text-[#5B677A]" /> Retour au site</a>
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
