<div>
    <x-admin-light.topbar title="Ressources" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Bibliothèque de ressources</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Ajouter une ressource</button>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @foreach ($ressources as $r)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-base" style="background: {{ $r['tint'] }}">{{ $r['emoji'] }}</span>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $r['titre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $r['type'] }} · {{ $r['taille'] }}</p>
                    </div>
                    <span class="w-[100px] shrink-0 text-xs text-[#5B677A]">{{ $r['telechargements'] }} téléch.</span>
                    <button wire:click="toggleVisibilite({{ $r['index'] }})" class="shrink-0 rounded-[9px] border px-3 py-1.5 text-xs font-bold {{ $r['visible'] ? 'border-[#C9D3E6] text-brand' : 'border-[#E6EAF0] text-[#9AA6B8]' }} bg-white hover:bg-cloud">{{ $r['visible'] ? 'Masquer' : 'Publier' }}</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
