<div>
    <x-member-light.topbar title="Opportunités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Opportunités</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('tous')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                <button wire:click="setFiltre('emploi')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'emploi' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Emplois</button>
                <button wire:click="setFiltre('stage')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'stage' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Stages</button>
            </div>
        </div>

        <div class="space-y-3">
            @foreach ($offres as $o)
                <article class="flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                        {{ mb_substr($o['entreprise'], 0, 1) }}
                    </span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $o['poste'] }}</p>
                        <p class="mt-0.5 text-xs text-[#5B677A]">{{ $o['entreprise'] }}</p>
                        <p class="mt-1 flex items-center gap-3 text-[11px] text-[#9AA6B8]">
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3" /> {{ $o['lieu'] }}</span>
                            <span>{{ $o['date'] }}</span>
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $o['type'] === 'emploi' ? 'bg-brand/10 text-brand' : 'bg-[#F5A623]/10 text-[#F5A623]' }}">
                        {{ $o['type'] === 'emploi' ? 'Emploi' : 'Stage' }}
                    </span>
                    <button class="shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure">Postuler</button>
                </article>
            @endforeach
        </div>
    </div>
</div>
