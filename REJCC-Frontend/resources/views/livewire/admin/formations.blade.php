<div>
    <x-admin-light.topbar title="Formations" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Catalogue des formations</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Nouvelle formation</button>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @foreach ($formations as $f)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <div class="h-11 w-[60px] shrink-0 rounded-[9px]" style="background: {{ $f['visuel'] }}"></div>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $f['titre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $f['categorie'] }} · {{ $f['duree'] }}</p>
                    </div>
                    <span class="w-[110px] shrink-0 text-xs text-[#5B677A]">{{ $f['inscrits'] }} inscrits</span>
                    <span class="w-20 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $f['publiee'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $f['publiee'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $f['publiee'] ? 'Publiée' : 'Brouillon' }}</span>
                    <button wire:click="togglePublication({{ $f['index'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $f['publiee'] ? 'Dépublier' : 'Publier' }}</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
