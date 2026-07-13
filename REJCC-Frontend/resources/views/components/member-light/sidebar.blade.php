@props(['withClose' => false])

@php
    $navItems = [
        ['label' => 'Accueil', 'icon' => 'nav-home', 'route' => 'espace-membre.dashboard'],
        ['label' => 'Ma carte membre', 'icon' => 'qr-code', 'route' => 'espace-membre.carte'],
        ['label' => 'Mes formations', 'icon' => 'graduation-cap', 'route' => 'espace-membre.formations'],
        ['label' => 'Catalogue', 'icon' => 'nav-compass', 'route' => 'espace-membre.catalogue'],
        ['label' => 'Mes parcours', 'icon' => 'nav-route', 'route' => 'espace-membre.parcours'],
        ['label' => 'Mentorat', 'icon' => 'nav-mentor', 'route' => 'espace-membre.mentorat'],
        ['label' => 'Annuaire', 'icon' => 'users', 'route' => 'espace-membre.directory'],
        ['label' => 'Messagerie', 'icon' => 'message-circle', 'route' => 'espace-membre.messaging'],
        ['label' => 'Communauté', 'icon' => 'network', 'route' => 'espace-membre.communaute'],
        ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'espace-membre.evenements'],
        ['label' => 'Projets', 'icon' => 'nav-projects', 'route' => 'espace-membre.projets'],
        ['label' => 'Incubateur', 'icon' => 'nav-incubator', 'route' => 'espace-membre.incubateur'],
        ['label' => 'Opportunités', 'icon' => 'nav-briefcase', 'route' => 'espace-membre.emplois'],
        ['label' => 'Ressources', 'icon' => 'nav-library', 'route' => 'espace-membre.ressources'],
        ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'espace-membre.documents'],
        ['label' => 'Certificats', 'icon' => 'award', 'route' => 'espace-membre.certificats'],
    ];

    $isActive = fn (array $item) => request()->routeIs($item['route'].'*');

    $itemHref = fn (array $item) => route($item['route']);
@endphp

<aside {{ $attributes->merge(['class' => 'flex h-full w-[236px] shrink-0 flex-col bg-brand text-white']) }}>
    <div class="flex items-center gap-3 border-b border-white/10 px-5 py-[18px]">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-white">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-[26px] object-contain">
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[15px] font-extrabold tracking-[0.04em]">REJCC</p>
            <p class="text-[10px] tracking-[0.06em] text-[#8FA3D9]">ESPACE MEMBRE</p>
        </div>
        @if ($withClose)
            <button type="button" @click="mobileOpen = false" aria-label="Fermer le menu" class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] border border-white/20 text-white">
                <x-ui.icon name="x" class="size-4" />
            </button>
        @endif
    </div>

    <nav
        x-data="{
            top: 0, height: 0, visible: false,
            place(el) {
                const nav = el.closest('nav');
                const r = el.getBoundingClientRect();
                const nr = nav.getBoundingClientRect();
                this.top = r.top - nr.top + nav.scrollTop;
                this.height = r.height;
                this.visible = true;
            },
            hide() { this.visible = false; },
        }"
        @mouseleave="hide()"
        class="relative flex flex-1 flex-col gap-0.5 overflow-y-auto p-3"
    >
        <div
            class="pointer-events-none absolute inset-x-3 rounded-[10px] bg-white/[.09] transition-all duration-300 ease-out"
            x-bind:style="`top:${top}px; height:${height}px; opacity:${visible ? 1 : 0}`"
        ></div>
        @foreach ($navItems as $item)
            @php $active = $isActive($item); @endphp
            <a
                href="{{ $itemHref($item) }}"
                wire:navigate
                @mouseenter="place($el)"
                class="group relative flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-all duration-200 ease-out active:scale-[0.98] {{ $active ? 'bg-white/[.07] text-white shadow-[inset_2px_0_0_var(--color-accent)]' : 'text-[#C4D0EC] hover:text-white' }}"
            >
                <x-ui.icon :name="$item['icon']" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        @php $paramActive = request()->routeIs('espace-membre.profile'); @endphp
        <a
            href="{{ route('espace-membre.profile') }}"
            wire:navigate
            class="group flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-all duration-200 ease-out hover:translate-x-0.5 active:scale-[0.98] {{ $paramActive ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
        >
            <x-ui.icon name="settings" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:rotate-45" />
            Paramètres
        </a>
    </div>
</aside>
