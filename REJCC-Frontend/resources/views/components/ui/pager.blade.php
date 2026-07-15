@props(['meta' => []])

{{-- Barre de pagination. Le composant Livewire hôte doit exposer une méthode
     gotoPage(int $page). Ne s'affiche que s'il y a plus d'une page. --}}
@php
    $current = (int) ($meta['current_page'] ?? 1);
    $last = (int) ($meta['last_page'] ?? 1);
    $total = (int) ($meta['total'] ?? 0);
    $start = max(1, $current - 2);
    $end = min($last, $current + 2);
@endphp

@if ($last > 1)
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-[#9AA6B8]">{{ $total }} résultat{{ $total > 1 ? 's' : '' }} · page {{ $current }} / {{ $last }}</p>
        <div class="flex items-center gap-1">
            <button wire:click="gotoPage({{ $current - 1 }})" @disabled($current <= 1)
                class="btn-tap rounded-lg border border-brand/15 bg-white px-3 py-1.5 text-xs font-semibold text-brand hover:bg-cloud disabled:opacity-40">Précédent</button>

            @if ($start > 1)
                <button wire:click="gotoPage(1)" class="btn-tap rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#5B677A] hover:bg-cloud">1</button>
                @if ($start > 2)
                    <span class="px-1 text-[#9AA6B8]">…</span>
                @endif
            @endif

            @for ($p = $start; $p <= $end; $p++)
                <button wire:click="gotoPage({{ $p }})"
                    class="btn-tap min-w-[32px] rounded-lg px-2.5 py-1.5 text-xs font-bold {{ $p === $current ? 'bg-brand text-white' : 'text-[#5B677A] hover:bg-cloud' }}">{{ $p }}</button>
            @endfor

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="px-1 text-[#9AA6B8]">…</span>
                @endif
                <button wire:click="gotoPage({{ $last }})" class="btn-tap rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#5B677A] hover:bg-cloud">{{ $last }}</button>
            @endif

            <button wire:click="gotoPage({{ $current + 1 }})" @disabled($current >= $last)
                class="btn-tap rounded-lg border border-brand/15 bg-white px-3 py-1.5 text-xs font-semibold text-brand hover:bg-cloud disabled:opacity-40">Suivant</button>
        </div>
    </div>
@endif
