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
                ['label' => 'Inscriptions (QR)', 'icon' => 'qr-code', 'route' => 'admin.inscriptions'],
                ['label' => 'Projets & Incubateur', 'icon' => 'nav-incubator', 'route' => 'admin.projets'],
                ['label' => 'Marketplace', 'icon' => 'store', 'route' => 'admin.marketplace'],
                ['label' => 'Ressources', 'icon' => 'nav-library', 'route' => 'admin.ressources'],
                ['label' => 'Certificats', 'icon' => 'award', 'route' => 'admin.certificats'],
                ['label' => 'Emploi & Stage', 'icon' => 'nav-briefcase', 'route' => 'admin.emplois'],
                ['label' => 'Mentors', 'icon' => 'hand-heart', 'route' => 'admin.mentors'],
            ],
        ],
        [
            'label' => 'Contenu du site',
            'icon' => 'layout-dashboard',
            'items' => [
                ['label' => 'Actualités', 'icon' => 'file-text', 'route' => 'admin.actualites'],
                ['label' => 'Pages du site', 'icon' => 'globe', 'route' => 'admin.pages'],
                ['label' => 'Blocs de contenu', 'icon' => 'layout-dashboard', 'route' => 'admin.contenu'],
                ['label' => 'Médiathèque', 'icon' => 'image', 'route' => 'admin.mediatheque'],
                ['label' => 'Réglages du site', 'icon' => 'settings', 'route' => 'admin.reglages'],
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

    <div class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
        <a
            href="{{ route('admin.dashboard') }}"
            wire:navigate
            class="group flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-all duration-200 ease-out active:scale-[0.98] {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white shadow-[inset_2px_0_0_var(--color-accent)]' : 'text-[#C4D0EC] hover:translate-x-0.5 hover:bg-white/[.08] hover:text-white' }}"
        >
            <x-ui.icon name="layout-dashboard" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
            Vue d'ensemble
        </a>

        @foreach ($groups as $group)
            <div x-data="{ open: {{ $groupActive($group) ? 'true' : 'false' }} }" class="mt-1">
                <button
                    type="button"
                    @click="open = !open"
                    class="group flex w-full items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition-all duration-200 ease-out active:scale-[0.98] {{ $groupActive($group) ? 'text-white' : 'text-[#C4D0EC] hover:translate-x-0.5 hover:bg-white/[.08] hover:text-white' }}"
                >
                    <x-ui.icon :name="$group['icon']" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
                    <span class="flex-1 text-left">{{ $group['label'] }}</span>
                    <x-ui.icon name="chevron-right" class="size-3.5 shrink-0 transition-transform duration-300 ease-out" x-bind:class="open ? 'rotate-90' : ''" />
                </button>
                <div class="grid transition-[grid-template-rows] duration-300 ease-out" x-bind:class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'" x-bind:inert="! open">
                    <div class="overflow-hidden">
                        <div class="mt-0.5 space-y-0.5 border-l border-white/10 py-0.5 pl-3">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ route($item['route']) }}"
                                    wire:navigate
                                    class="group flex items-center gap-2.5 rounded-[9px] px-3 py-2 text-[13px] font-medium transition-all duration-200 ease-out active:scale-[0.97] {{ $active($item) ? 'bg-white/10 text-white shadow-[inset_2px_0_0_var(--color-accent)]' : 'text-[#C4D0EC] hover:translate-x-0.5 hover:bg-white/[.08] hover:text-white' }}"
                                >
                                    <x-ui.icon :name="$item['icon']" class="size-4 shrink-0 transition-transform duration-200 group-hover:scale-110" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="border-t border-white/10 p-3">
        <a
            href="{{ route('espace-membre.dashboard') }}"
            wire:navigate
            class="group flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-[#C4D0EC] transition-all duration-200 ease-out hover:translate-x-0.5 hover:bg-white/[.08] hover:text-white active:scale-[0.98]"
        >
            <x-ui.icon name="nav-home" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
            Espace membre
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="group mt-0.5 flex w-full items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-[#C4D0EC] transition-all duration-200 ease-out hover:translate-x-0.5 hover:bg-white/[.08] hover:text-white active:scale-[0.98]">
                <x-ui.icon name="log-out" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
                Se déconnecter
            </button>
        </form>
    </div>
</aside>
