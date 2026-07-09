@php
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => "Vue d'ensemble", 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => 'MEMBRES',
            'items' => [
                ['label' => 'Membres', 'icon' => 'nav-mentor', 'route' => 'admin.members'],
                ['label' => 'Adhésions & candidatures', 'icon' => 'file-text', 'route' => 'admin.adhesions'],
                ['label' => 'Mentors', 'icon' => 'hand-heart', 'route' => 'admin.mentors'],
            ],
        ],
        [
            'label' => 'ACTIVITÉ RÉSEAU',
            'items' => [
                ['label' => 'Formations', 'icon' => 'graduation-cap', 'route' => 'admin.formations'],
                ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'admin.evenements'],
                ['label' => 'Projets & Incubateur', 'icon' => 'nav-incubator', 'route' => 'admin.projets'],
                ['label' => 'Communauté', 'icon' => 'network', 'route' => 'admin.communaute'],
                ['label' => 'Ressources', 'icon' => 'nav-library', 'route' => 'admin.ressources'],
                ['label' => 'Certificats', 'icon' => 'award', 'route' => 'admin.certificats'],
                ['label' => 'Opportunités', 'icon' => 'nav-briefcase', 'route' => 'admin.emplois'],
            ],
        ],
        [
            'label' => 'CONTENU DU SITE',
            'items' => [
                ['label' => 'Actualités', 'icon' => 'file-text', 'route' => 'admin.actualites'],
                ['label' => 'Contenu du site', 'icon' => 'layout-dashboard', 'route' => 'admin.contenu'],
                ['label' => 'Newsletter', 'icon' => 'send', 'route' => 'admin.newsletter'],
                ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'admin.documents'],
            ],
        ],
        [
            'label' => 'SUPPORT & SYSTÈME',
            'items' => [
                ['label' => 'Contacts', 'icon' => 'message-circle', 'route' => 'admin.contacts'],
                ['label' => 'Partenariats', 'icon' => 'heart-handshake', 'route' => 'admin.partenariats'],
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'admin.notifications'],
                ['label' => "Journal d'audit", 'icon' => 'clock', 'route' => 'admin.audit'],
            ],
        ],
    ];

    $active = fn (array $item) => request()->routeIs($item['route'].'*');
@endphp

<aside {{ $attributes->merge(['class' => 'flex h-full w-[236px] shrink-0 flex-col overflow-y-auto bg-brand text-white']) }}>
    <div class="flex items-center gap-3 border-b border-white/10 px-5 py-[18px]">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-white">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-[26px] object-contain">
        </div>
        <div>
            <p class="text-[15px] font-extrabold tracking-[0.04em]">REJCC</p>
            <p class="text-[10px] tracking-[0.06em] text-[#8FA3D9]">ADMINISTRATION</p>
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1 p-3">
        @foreach ($groups as $group)
            @if ($group['label'])
                <p class="mt-2 px-2 pb-1 text-[10.5px] font-bold tracking-[0.1em] text-[#5C74AE]">{{ $group['label'] }}</p>
            @endif
            @foreach ($group['items'] as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-colors {{ $active($item) ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
                >
                    <x-ui.icon :name="$item['icon']" class="size-[18px] shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        @endforeach
    </div>

    <div class="border-t border-white/10 p-3">
        <a
            href="{{ route('espace-membre.dashboard') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-[#C4D0EC] transition-colors hover:bg-white/[.08] hover:text-white"
        >
            <x-ui.icon name="nav-home" class="size-[18px] shrink-0" />
            Espace membre
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="mt-0.5 flex w-full items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-[#C4D0EC] transition-colors hover:bg-white/[.08] hover:text-white">
                <x-ui.icon name="log-out" class="size-[18px] shrink-0" />
                Se déconnecter
            </button>
        </form>
    </div>
</aside>
