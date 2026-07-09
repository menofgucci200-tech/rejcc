<div>
    <x-admin-light.topbar title="Journal d'audit" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Journal d'audit</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('toutes')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'toutes' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Toutes</button>
                <button wire:click="setFiltre('membres')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'membres' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Membres</button>
                <button wire:click="setFiltre('contenu')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'contenu' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Contenu</button>
            </div>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($entrees as $e)
                <div class="flex items-start gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] text-[11px] font-extrabold" style="color: {{ $e['iconColor'] }}; background: {{ $e['tint'] }}">{{ $e['initiales'] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] leading-relaxed text-ink"><strong class="text-brand">{{ $e['admin'] }}</strong> {{ $e['action'] }}</p>
                        <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $e['date'] }}</p>
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-[#5B677A]">Aucune entrée pour ce filtre.</p>
            @endforelse
        </div>
    </div>
</div>
