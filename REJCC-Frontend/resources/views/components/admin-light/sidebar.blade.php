@props(['withClose' => false])

{{-- Menu de l'administration : groupes ouverts par défaut (état mémorisé),
     pastilles des éléments à traiter, carte utilisateur avec menu. --}}
@php
    use App\Support\AdminNav;

    $user = \App\Support\Api::user();
    $groups = AdminNav::groupes();
    $pastilles = AdminNav::pastilles();
    $restreint = is_array($user->permissions ?? null);

    $active = fn (array $item) => request()->routeIs($item['route'].'*') || ($item['route'] === 'admin.members' && request()->routeIs('admin.inscription'));
    $groupActive = fn (array $group) => collect($group['items'])->contains($active);
    $initiales = mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1));
@endphp

<aside {{ $attributes->merge(['class' => 'flex h-full w-[248px] shrink-0 flex-col bg-brand text-white']) }} data-test="menu-admin">
    <div class="flex items-center gap-3 border-b border-white/10 px-[18px] py-[18px]">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-white" aria-label="Vue d'ensemble">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-[26px] object-contain">
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-[15px] font-extrabold tracking-[0.04em]">REJCC</p>
            <p class="text-[10px] tracking-[0.08em] text-[#8FA3D9]">ADMINISTRATION</p>
        </div>
        @if ($withClose)
            <button type="button" @click="mobileOpen = false" aria-label="Fermer le menu" class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] border border-white/20 text-white">
                <x-ui.icon name="x" class="size-4" />
            </button>
        @endif
    </div>

    <nav aria-label="Menu d'administration" class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
            class="group flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[13.5px] font-medium transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-white/[.1] text-white shadow-[inset_3px_0_0_var(--color-accent)]' : 'text-[#C4D0EC] hover:bg-white/[.06] hover:text-white' }}">
            <x-ui.icon name="layout-dashboard" class="size-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
            Vue d'ensemble
        </a>

        @foreach ($groups as $group)
            @php $totalGroupe = collect($group['items'])->sum(fn ($i) => $pastilles[$i['route']] ?? 0); @endphp
            <div x-data="{ open: $persist(true).as('rejcc-admin-groupe-{{ \Illuminate\Support\Str::slug($group['label']) }}') }" class="mt-2">
                <button type="button" @click="open = ! open" :aria-expanded="open"
                    class="flex w-full items-center gap-2 rounded-[8px] px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-[0.12em] transition-colors {{ $groupActive($group) ? 'text-white/80' : 'text-white/45 hover:text-white/80' }}">
                    <span class="flex-1 text-left">{{ $group['label'] }}</span>
                    <span x-show="! open && {{ $totalGroupe }} > 0" style="display: none" class="rounded-full bg-accent px-1.5 text-[10px] font-bold leading-4 text-white">{{ $totalGroupe }}</span>
                    <x-ui.icon name="chevron-down" class="size-3.5 shrink-0 transition-transform duration-300" x-bind:class="open ? '' : '-rotate-90'" />
                </button>
                <div class="grid transition-[grid-template-rows] duration-300 ease-out" x-bind:class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'" x-bind:inert="! open">
                    <div class="overflow-hidden">
                        <div class="space-y-0.5 py-0.5">
                            @foreach ($group['items'] as $item)
                                @php $n = $pastilles[$item['route']] ?? 0; @endphp
                                <a href="{{ route($item['route']) }}" wire:navigate data-test="admin-menu-{{ \Illuminate\Support\Str::slug($item['label']) }}" @if ($active($item)) aria-current="page" @endif
                                    class="group flex items-center gap-3 rounded-[10px] px-3 py-[8px] text-[13px] font-medium transition-colors duration-200 {{ $active($item) ? 'bg-white/[.1] text-white shadow-[inset_3px_0_0_var(--color-accent)]' : 'text-[#C4D0EC] hover:bg-white/[.06] hover:text-white' }}">
                                    <x-ui.icon :name="$item['icon']" class="size-[17px] shrink-0 transition-transform duration-200 group-hover:scale-110" />
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                    @if ($n)
                                        <span data-test="pastille-admin" class="shrink-0 rounded-full bg-accent px-1.5 py-px text-[10.5px] font-bold leading-4 text-white">{{ $n > 99 ? '99+' : $n }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Carte utilisateur + menu --}}
    <div x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false" class="relative border-t border-white/10 p-3">
        <div x-show="menu" x-transition.origin.bottom style="display: none" class="absolute bottom-[calc(100%-4px)] left-3 right-3 z-20 overflow-hidden rounded-[14px] bg-white py-1.5 text-[13px] shadow-[0_20px_50px_-12px_rgba(3,29,89,.55)]">
            <a href="{{ route('espace-membre.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="nav-home" class="size-4 text-[#5B677A]" /> Espace membre</a>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="external-link" class="size-4 text-[#5B677A]" /> Voir le site</a>
            <div class="my-1 h-px bg-cloud-200"></div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold text-accent hover:bg-accent/5"><x-ui.icon name="log-out" class="size-4" /> Se déconnecter</button>
            </form>
        </div>
        <button type="button" @click="menu = ! menu" :aria-expanded="menu" data-test="carte-admin" class="flex w-full items-center gap-2.5 rounded-[12px] p-1.5 text-left transition-colors hover:bg-white/[.08]">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ring-2 ring-white/15" style="background: linear-gradient(135deg, #AC0100, #D95B5A)">{{ $initiales }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[13px] font-bold text-white">{{ $user->prenom }} {{ $user->nom }}</span>
                <span class="mt-0.5 inline-block rounded-full px-2 py-px text-[10px] font-bold {{ $restreint ? 'bg-[#F5A623]/20 text-[#F7C873]' : 'bg-white/10 text-white/70' }}">{{ $restreint ? 'Accès limité' : 'Accès complet' }}</span>
            </span>
            <x-ui.icon name="chevron-down" class="size-4 shrink-0 rotate-180 text-white/50" />
        </button>
    </div>
</aside>
