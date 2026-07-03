@props(['navItems', 'user'])

<aside class="flex h-full w-64 flex-col border-r border-brand/10 bg-white">
    <div class="flex h-16 items-center gap-3 border-b border-brand/10 px-5">
        <span class="inline-flex size-8 items-center justify-center rounded-lg bg-brand text-xs font-bold text-white">R</span>
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand">REJCC</p>
            <p class="text-[0.65rem] uppercase tracking-wider text-ink/50">Administration</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto p-3">
        <ul class="flex flex-col gap-0.5">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-brand text-white' : 'text-ink/70 hover:bg-brand/5 hover:text-brand' }}"
                    >
                        <x-ui.icon :name="$item['icon']" class="size-4 shrink-0" />
                        {{ $item['label'] }}
                        @if ($active)
                            <x-ui.icon name="chevron-right" class="ml-auto size-3.5" />
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-brand/10 p-4">
        <p class="truncate text-xs font-semibold text-brand">{{ $user->prenom }} {{ $user->nom }}</p>
        <p class="truncate text-[0.65rem] text-ink/50">{{ $user->email }}</p>
        <form action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-ink/60 transition-colors hover:bg-accent/10 hover:text-accent">
                <x-ui.icon name="log-out" class="size-3.5" /> Se déconnecter
            </button>
        </form>
    </div>
</aside>
