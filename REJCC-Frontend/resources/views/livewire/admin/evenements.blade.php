<div>
    <x-admin-light.topbar title="Événements" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Événements à venir</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Créer un événement</button>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @foreach ($evenements as $ev)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <div class="w-[46px] shrink-0 rounded-[10px] bg-cloud py-1.5 text-center">
                        <p class="text-base font-extrabold leading-none text-accent">{{ $ev['jour'] }}</p>
                        <p class="mt-1 text-[9.5px] font-bold tracking-[0.06em] text-[#5B677A]">{{ $ev['mois'] }}</p>
                    </div>
                    <div class="min-w-[200px] flex-1">
                        <span class="text-[10.5px] font-bold tracking-[0.06em]" style="color: {{ $ev['tagColor'] }}">{{ $ev['tag'] }}</span>
                        <p class="my-0.5 text-[13.5px] font-bold text-brand">{{ $ev['titre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $ev['detail'] }}</p>
                    </div>
                    <span class="w-[90px] shrink-0 text-[12.5px] font-bold text-brand">{{ $ev['inscrits'] }} inscrits</span>
                    <button wire:click="toggle({{ $ev['index'] }})" class="shrink-0 rounded-[9px] border px-3 py-1.5 text-xs font-bold {{ $ev['annule'] ? 'border-[#BEE6CD] text-[#22A85A]' : 'border-[#F0C9C9] text-accent' }} bg-white">{{ $ev['annule'] ? 'Réactiver' : 'Annuler' }}</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
