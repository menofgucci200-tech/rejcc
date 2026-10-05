@php
    $compteurs = \App\Support\NavCompteurs::get();
    $tabs = [
        ['label' => 'Accueil', 'icon' => 'nav-home', 'route' => 'espace-membre.dashboard'],
        ['label' => 'Formations', 'icon' => 'graduation-cap', 'route' => 'espace-membre.formations'],
        ['label' => 'Messages', 'icon' => 'message-circle', 'route' => 'espace-membre.messaging', 'badge' => $compteurs['messages'] ?? 0],
        ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'espace-membre.evenements'],
    ];

    $isActive = fn (array $item) => request()->routeIs($item['route'].'*');
    $noneActive = collect($tabs)->every(fn ($item) => ! $isActive($item));
@endphp

<nav class="fixed inset-x-0 bottom-0 z-[70] flex items-center justify-around border-t border-brand/10 bg-white px-1 py-1.5 shadow-[0_-4px_16px_rgba(3,29,89,.08)] lg:hidden" style="padding-bottom: calc(0.375rem + env(safe-area-inset-bottom, 0px));">
    @foreach ($tabs as $item)
        @php $active = $isActive($item); @endphp
        <a
            href="{{ route($item['route']) }}"
            wire:navigate
            class="relative flex min-w-14 flex-col items-center gap-0.5 rounded-[10px] px-2 py-1.5 transition-all duration-150 ease-out active:scale-90 {{ $active ? 'text-accent' : 'text-[#5B677A]' }}"
        >
            @if ($active)
                <span class="tab-dot absolute top-0.5 size-1.5 rounded-full bg-accent"></span>
            @endif
            <span class="relative">
                <x-ui.icon :name="$item['icon']" class="size-[21px] transition-transform duration-200 {{ $active ? '-translate-y-0.5' : '' }}" />
                @if ($item['badge'] ?? 0)
                    <span data-test="pastille-mobile" class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-white bg-accent px-1 text-[9px] font-bold text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                @endif
            </span>
            <span class="text-[10.5px] {{ $active ? 'font-bold' : 'font-semibold' }}">{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button
        type="button"
        @click="mobileOpen = true"
        aria-label="Menu"
        class="relative flex min-w-14 flex-col items-center gap-0.5 rounded-[10px] px-2 py-1.5 transition-all duration-150 ease-out active:scale-90 {{ $noneActive ? 'text-accent' : 'text-[#5B677A]' }}"
    >
        @if ($noneActive)
            <span class="tab-dot absolute top-0.5 size-1.5 rounded-full bg-accent"></span>
        @endif
        <x-ui.icon name="menu" class="size-[21px] transition-transform duration-200 {{ $noneActive ? '-translate-y-0.5' : '' }}" />
        <span class="text-[10.5px] {{ $noneActive ? 'font-bold' : 'font-semibold' }}">Menu</span>
    </button>
</nav>
