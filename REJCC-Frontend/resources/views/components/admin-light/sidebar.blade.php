@php
    use App\Support\AdminSections;

    $user = session('api_user');

    // Menus organisés en groupes repliables. Chaque item porte le nom de sa
    // route ; sa section de permission est résolue via AdminSections::ROUTES.
    $groups = [
        [
            'label' => 'Base de données',
            'icon' => 'folder-open',
            'items' => [
                ['label' => 'Adhésions', 'icon' => 'file-text', 'route' => 'admin.adhesions'],
                ['label' => 'Membres', 'icon' => 'users', 'route' => 'admin.members'],
                ['label' => 'Nouvelle inscription', 'icon' => 'user-plus', 'route' => 'admin.inscription'],
            ],
        ],
        [
            'label' => 'Activité réseau',
            'icon' => 'network',
            'items' => [
                ['label' => 'Formations', 'icon' => 'graduation-cap', 'route' => 'admin.formations'],
                ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'admin.evenements'],
                ['label' => 'Projets & Incubateur', 'icon' => 'nav-incubator', 'route' => 'admin.projets'],
                ['label' => 'Communauté', 'icon' => 'message-circle', 'route' => 'admin.communaute'],
                ['label' => 'Ressources', 'icon' => 'nav-library', 'route' => 'admin.ressources'],
                ['label' => 'Certificats', 'icon' => 'award', 'route' => 'admin.certificats'],
                ['label' => 'Opportunités', 'icon' => 'nav-briefcase', 'route' => 'admin.emplois'],
                ['label' => 'Mentors', 'icon' => 'hand-heart', 'route' => 'admin.mentors'],
            ],
        ],
        [
            'label' => 'Contenu du site',
            'icon' => 'layout-dashboard',
            'items' => [
                ['label' => 'Actualités', 'icon' => 'file-text', 'route' => 'admin.actualites'],
                ['label' => 'Contenu du site', 'icon' => 'globe', 'route' => 'admin.contenu'],
                ['label' => 'Newsletter', 'icon' => 'send', 'route' => 'admin.newsletter'],
                ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'admin.documents'],
            ],
        ],
        [
            'label' => 'Support & système',
            'icon' => 'settings',
            'items' => [
                ['label' => 'Contacts', 'icon' => 'message-circle', 'route' => 'admin.contacts'],
                ['label' => 'Partenariats', 'icon' => 'heart-handshake', 'route' => 'admin.partenariats'],
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'admin.notifications'],
                ['label' => "Journal d'audit", 'icon' => 'clock', 'route' => 'admin.audit'],
            ],
        ],
    ];

    // Filtre selon les permissions de l'admin connecté, puis retire les groupes vides.
    $groups = collect($groups)
        ->map(function (array $group) use ($user) {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn (array $item) => AdminSections::allowedRoute($user, $item['route']),
            ));

            return $group;
        })
        ->filter(fn (array $group) => $group['items'] !== [])
        ->values();

    $active = fn (array $item) => request()->routeIs($item['route'].'*');
    $groupActive = fn (array $group) => collect($group['items'])->contains($active);
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

    <div class="flex flex-1 flex-col gap-0.5 p-3">
        <a
            href="{{ route('admin.dashboard') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
        >
            <x-ui.icon name="layout-dashboard" class="size-[18px] shrink-0" />
            Vue d'ensemble
        </a>

        @foreach ($groups as $group)
            <div x-data="{ open: {{ $groupActive($group) ? 'true' : 'false' }} }" class="mt-1">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex w-full items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition-colors {{ $groupActive($group) ? 'text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
                >
                    <x-ui.icon :name="$group['icon']" class="size-[18px] shrink-0" />
                    <span class="flex-1 text-left">{{ $group['label'] }}</span>
                    <x-ui.icon name="chevron-right" class="size-3.5 shrink-0 transition-transform duration-200" x-bind:class="open ? 'rotate-90' : ''" />
                </button>
                <div x-show="open" style="display: none;" class="mt-0.5 space-y-0.5 border-l border-white/10 pl-3">
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="flex items-center gap-2.5 rounded-[9px] px-3 py-2 text-[13px] font-medium transition-colors {{ $active($item) ? 'bg-white/10 text-white' : 'text-[#C4D0EC] hover:bg-white/[.08] hover:text-white' }}"
                        >
                            <x-ui.icon :name="$item['icon']" class="size-4 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
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
