@props(['navItems', 'user'])

<div {{ $attributes->merge(['class' => 'flex h-full flex-col border-r border-[var(--ms-bc)] bg-[var(--ms-sidebar-bg)]']) }}>
    <!-- Logo -->
    <div class="flex h-[72px] shrink-0 items-center gap-3 border-b border-[var(--ms-bc)]" :class="sidebarMode === 'rail' ? 'justify-center px-0' : 'px-5'">
        <span class="relative inline-flex size-10 shrink-0 items-center justify-center rounded-xl font-display text-lg text-white shadow-[0_4px_18px_rgba(79,111,191,0.35)]" style="background: linear-gradient(135deg, #031D59, #4F6FBF)">
            R
            <span class="absolute -right-0.5 -top-0.5 size-[9px] rounded-full border border-[#021541] bg-accent"></span>
        </span>
        <div x-show="sidebarMode !== 'rail'" style="display: none;">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--ms-text)]">REJCC</p>
            <p class="mt-0.5 text-[10px] tracking-[0.06em] text-[var(--ms-dim)]">Espace Membre</p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-2.5 py-3.5">
        <ul class="flex list-none flex-col gap-0.5 p-0">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        :title="sidebarMode === 'rail' ? '{{ $item['label'] }}' : null"
                        class="relative flex items-center overflow-hidden rounded-xl border-l-[3px] transition-all"
                        :class="[sidebarMode === 'rail' ? 'justify-center py-2.5 px-0' : 'justify-start gap-3 py-[9px] px-3', '{{ $active ? 'border-accent' : 'border-transparent' }}']"
                        style="background: {{ $active ? 'var(--ms-surf2)' : 'transparent' }}"
                    >
                        <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px]" style="background: {{ $active ? 'rgba(172,1,0,0.18)' : 'rgba(255,255,255,0.04)' }}">
                            <x-ui.icon :name="$item['icon']" class="size-4" style="color: {{ $active ? 'var(--ms-text)' : 'var(--ms-muted)' }}" />
                        </span>
                        <span x-show="sidebarMode !== 'rail'" style="display: none;" class="flex-1 truncate text-[13.5px]" :class="{{ $active ? "'font-semibold'" : "'font-medium'" }}" style="color: {{ $active ? 'var(--ms-text)' : 'var(--ms-muted)' }}">
                            {{ $item['label'] }}
                        </span>
                        @if ($active)
                            <x-ui.icon x-show="sidebarMode !== 'rail'" style="display: none;" name="chevron-right" class="size-3 shrink-0 text-[var(--ms-dim)]" />
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Paramètres + profil -->
    <div class="border-t border-[var(--ms-bc)] px-2.5 py-3">
        @php $profileActive = request()->routeIs('espace-membre.profile'); @endphp
        <a
            href="{{ route('espace-membre.profile') }}"
            wire:navigate
            :title="sidebarMode === 'rail' ? 'Paramètres' : null"
            class="flex items-center rounded-xl border-l-[3px] transition-all"
            :class="sidebarMode === 'rail' ? 'justify-center py-2.5 px-0' : 'justify-start gap-3 py-[9px] px-3'"
            style="background: {{ $profileActive ? 'var(--ms-surf2)' : 'transparent' }}; border-color: {{ $profileActive ? '#AC0100' : 'transparent' }};"
        >
            <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px]" style="background: {{ $profileActive ? 'rgba(172,1,0,0.18)' : 'rgba(255,255,255,0.04)' }}">
                <x-ui.icon name="settings" class="size-4" style="color: {{ $profileActive ? 'var(--ms-text)' : 'var(--ms-muted)' }}" />
            </span>
            <span x-show="sidebarMode !== 'rail'" style="display: none;" class="flex-1 truncate text-[13.5px] font-medium" style="color: var(--ms-muted)">
                Paramètres
            </span>
        </a>

        <div x-show="sidebarMode !== 'rail'" style="display: none;" class="mt-2.5 flex items-center gap-2.5 rounded-xl border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-3 py-2.5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                {{ mb_substr($user->prenom, 0, 1) }}{{ mb_substr($user->nom, 0, 1) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold text-[var(--ms-text)]">{{ $user->prenom }} {{ $user->nom }}</p>
                <p class="mt-0.5 truncate text-[10px] text-[var(--ms-dim)]">{{ $user->email }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" title="Se déconnecter" class="flex size-7 shrink-0 items-center justify-center rounded-lg text-[var(--ms-dim)] transition-colors hover:bg-accent/15 hover:text-[#E84A43]">
                    <x-ui.icon name="log-out" class="size-[13px]" />
                </button>
            </form>
        </div>

        <form x-show="sidebarMode === 'rail'" style="display: none;" action="{{ route('logout') }}" method="POST" class="mt-1">
            @csrf
            <button type="submit" title="Se déconnecter" class="flex w-full items-center justify-center rounded-[10px] py-2.5 text-[var(--ms-dim)]">
                <x-ui.icon name="log-out" class="size-4" />
            </button>
        </form>
    </div>
</div>
