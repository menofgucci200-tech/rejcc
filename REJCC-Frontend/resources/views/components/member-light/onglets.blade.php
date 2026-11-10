@props(['groupe' => null])

{{-- Barre d'onglets commune d'un groupe du menu (Formations, Réseau,
     Opportunités) : passe d'une page du groupe à l'autre en un clic. Les pages
     gardent leurs routes ; un onglet réservé aux abonnés porte un cadenas et
     mène, comme avant, à sa page (qui propose l'abonnement). Affichée par la
     barre du haut sur les pages principales du groupe. --}}
@php
    $groupe ??= \App\Support\MenuMembre::groupeCourant();
@endphp

@if ($groupe)
    <nav aria-label="{{ $groupe['label'] }}" data-test="onglets-{{ $groupe['cle'] }}"
        {{ $attributes->merge(['class' => 'border-b border-brand/10 bg-white']) }}>
        {{-- Sur téléphone, la barre défile horizontalement ; l'onglet actif est ramené en vue. --}}
        <div x-data x-init="const a = $el.querySelector('[aria-current]'); if (a && $el.scrollWidth > $el.clientWidth) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2"
            class="mx-auto flex max-w-[1280px] gap-1 overflow-x-auto px-3 sm:px-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($groupe['onglets'] as $o)
                <a href="{{ route($o['route']) }}" wire:navigate
                    data-test="onglet-{{ \Illuminate\Support\Str::slug($o['label']) }}"
                    title="{{ $o['label'] }}{{ $o['verrouille'] ? ' — réservé aux abonnés' : '' }}"
                    @if ($o['active']) aria-current="page" @endif
                    class="relative flex shrink-0 items-center gap-2 whitespace-nowrap px-3 py-3 text-[13px] transition-colors duration-200 {{ $o['active'] ? 'font-bold text-brand' : 'font-semibold text-[#5B677A] hover:text-brand' }}">
                    <x-ui.icon :name="$o['icon']" class="size-4 {{ $o['active'] ? 'text-accent' : '' }}" />
                    {{ $o['label'] }}
                    @if ($o['badge'])
                        <span data-test="pastille-onglet" class="rounded-full bg-accent px-1.5 py-px text-[10.5px] font-bold leading-4 text-white">{{ $o['badge'] > 99 ? '99+' : $o['badge'] }}</span>
                    @elseif ($o['verrouille'])
                        <x-ui.icon name="lock" class="size-[13px] text-[#B27007]" />
                    @endif
                    @if ($o['active'])
                        <span class="absolute inset-x-2 bottom-0 h-[3px] rounded-t bg-accent"></span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
@endif
