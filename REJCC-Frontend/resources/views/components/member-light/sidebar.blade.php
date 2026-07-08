@php
    $navItems = [
        ['label' => 'Accueil', 'icon' => 'nav-home', 'route' => 'espace-membre.dashboard'],
        ['label' => 'Mes formations', 'icon' => 'graduation-cap', 'page' => 'formations'],
        ['label' => 'Catalogue', 'icon' => 'nav-compass', 'page' => 'catalogue'],
        ['label' => 'Mes parcours', 'icon' => 'nav-route', 'page' => 'parcours'],
        ['label' => 'Mentorat', 'icon' => 'nav-mentor', 'page' => 'mentorat'],
        ['label' => 'Annuaire', 'icon' => 'users', 'route' => 'espace-membre.directory'],
        ['label' => 'Communauté', 'icon' => 'message-circle', 'page' => 'communaute'],
        ['label' => 'Événements', 'icon' => 'calendar-days', 'page' => 'evenements'],
        ['label' => 'Projets', 'icon' => 'nav-projects', 'page' => 'projets'],
        ['label' => 'Incubateur', 'icon' => 'nav-incubator', 'page' => 'incubateur'],
        ['label' => 'Opportunités', 'icon' => 'nav-briefcase', 'page' => 'emplois'],
        ['label' => 'Ressources', 'icon' => 'nav-library', 'page' => 'ressources'],
        ['label' => 'Certificats', 'icon' => 'award', 'page' => 'certificats'],
    ];

    $isActive = function (array $item) {
        return isset($item['route'])
            ? request()->routeIs($item['route'].'*')
            : request()->routeIs('espace-membre.coming-soon') && request()->route('page') === $item['page'];
    };

    $itemHref = fn (array $item) => isset($item['route']) ? route($item['route']) : route('espace-membre.coming-soon', ['page' => $item['page']]);
@endphp

<aside {{ $attributes->merge(['class' => 'flex h-full w-[236px] shrink-0 flex-col bg-brand text-white']) }}>
    <div class="flex items-center gap-3 border-b border-white/10 px-5 py-[18px]">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-white">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-[26px] object-contain">
        </div>
        <div>
            <p class="text-[15px] font-extrabold tracking-[0.04em]">REJCC</p>
            <p class="text-[10px] tracking-[0.06em] text-[#8FA3D9]">ESPACE MEMBRE</p>
        </div>
    </div>

    <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
        @foreach ($navItems as $item)
            @php $active = $isActive($item); @endphp
            <a
                href="{{ $itemHref($item) }}"
                wire:navigate
                class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
            >
                <x-ui.icon :name="$item['icon']" class="size-[18px] shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        @php $paramActive = request()->routeIs('espace-membre.profile'); @endphp
        <a
            href="{{ route('espace-membre.profile') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-colors {{ $paramActive ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
        >
            <x-ui.icon name="settings" class="size-[18px] shrink-0" />
            Paramètres
        </a>
    </div>
</aside>
