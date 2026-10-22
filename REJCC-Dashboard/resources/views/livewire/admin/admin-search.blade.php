{{-- Recherche admin : champ dans la barre du haut (loupe + panneau sur mobile), Ctrl+K. --}}
<div
    x-data="{ open: false, mobile: false }"
    @click.outside="open = false; mobile = false"
    @keydown.escape.window="open = false; mobile = false"
    @keydown.window.ctrl.k.prevent="mobile = window.innerWidth < 768; open = true; $nextTick(() => $refs.input.focus())"
    @keydown.window.meta.k.prevent="mobile = window.innerWidth < 768; open = true; $nextTick(() => $refs.input.focus())"
    class="relative md:w-[300px] md:shrink"
>
    <button type="button" @click="mobile = true; open = true; $nextTick(() => $refs.input.focus())" aria-label="Rechercher"
        class="flex size-10 items-center justify-center rounded-[10px] border border-brand/10 bg-white transition-all duration-200 hover:bg-cloud active:scale-90 md:hidden">
        <x-ui.icon name="search" class="size-[18px] text-brand" />
    </button>

    <div :class="mobile ? 'fixed inset-x-3 top-[70px] z-[95] flex shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]' : 'hidden md:flex'"
        class="h-10 items-center gap-2.5 rounded-[10px] border border-brand/10 bg-cloud px-3.5 transition-colors focus-within:border-azure/50 focus-within:bg-white md:relative">
        <x-ui.icon name="search" class="size-4 shrink-0 text-[#5B677A]" />
        <input x-ref="input" type="search" wire:model.live.debounce.300ms="q" @focus="open = true" @input="open = true"
            @keydown.enter.prevent="$refs.results?.querySelector('a')?.click()"
            placeholder="Rechercher…" aria-label="Rechercher dans l'administration" data-test="recherche-admin"
            class="rj-search-input w-full min-w-0 appearance-none border-none bg-transparent text-[13px] text-ink outline-none placeholder:text-[#9AA6B8] focus:outline-none" />
        <span wire:loading wire:target="q" class="size-3.5 shrink-0 animate-spin rounded-full border-2 border-azure/30 border-t-azure"></span>
        <kbd wire:loading.remove wire:target="q" class="hidden shrink-0 rounded-[6px] border border-brand/15 bg-white px-1.5 py-0.5 font-sans text-[10.5px] font-semibold text-[#9AA6B8] lg:inline">Ctrl K</kbd>
    </div>

    <div x-show="open && $wire.q.trim().length >= 2" x-cloak x-transition.origin.top x-ref="results" data-test="resultats-admin"
        :class="mobile ? 'fixed inset-x-3 top-[124px]' : 'absolute right-0 top-[52px] w-[400px] max-w-[calc(100vw-1.5rem)]'"
        class="z-[95] max-h-[70vh] overflow-y-auto rounded-[16px] border border-brand/10 bg-white py-2 shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]" data-lenis-prevent>
        @if ($actif)
            @forelse ($groupes as $titre => $items)
                <p class="px-4 pb-1 pt-2.5 text-[10.5px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $titre }}</p>
                @foreach ($items as $r)
                    <a href="{{ $r['url'] }}" wire:navigate @click="open = false; mobile = false" class="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-cloud/70 focus:bg-cloud/70 focus:outline-none">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand/[.06] text-brand"><x-ui.icon :name="$r['icon']" class="size-4" /></span>
                        <span class="min-w-0">
                            <span class="block truncate text-[13px] font-semibold text-brand">{{ $r['titre'] }}</span>
                            <span class="block truncate text-[11.5px] text-[#9AA6B8]">{{ $r['detail'] }}</span>
                        </span>
                    </a>
                @endforeach
            @empty
                <p class="px-4 py-6 text-center text-[12.5px] text-[#5B677A]">Aucun résultat pour « {{ $q }} ».</p>
            @endforelse
        @endif
    </div>
</div>
