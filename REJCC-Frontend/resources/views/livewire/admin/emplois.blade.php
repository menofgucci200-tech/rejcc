<div>
    <x-admin-light.topbar title="Opportunités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Offres partenaires</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Publier une offre</button>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @foreach ($offres as $o)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-4 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#E8EDF8] text-xs font-extrabold text-brand">{{ $o['initiales'] }}</span>
                    <div class="min-w-[200px] flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[14px] font-bold text-brand">{{ $o['poste'] }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="color: {{ $o['typeColor'] }}; background: {{ $o['typeBg'] }}">{{ $o['type'] }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-[#5B677A]">{{ $o['entreprise'] }} · {{ $o['lieu'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $o['statutColor'] }}; background: {{ $o['statutBg'] }}">{{ $o['statutLabel'] }}</span>
                    @if ($o['enAttente'])
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="approuver({{ $o['index'] }})" class="rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">Approuver</button>
                            <button wire:click="rejeter({{ $o['index'] }})" class="rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Rejeter</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
