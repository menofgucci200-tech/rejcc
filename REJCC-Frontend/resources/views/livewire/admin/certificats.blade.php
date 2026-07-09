<div>
    <x-admin-light.topbar title="Certificats" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes en attente de validation</h2>
        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @foreach ($demandes as $d)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold text-white" style="background: linear-gradient(135deg, {{ $d['from'] }}, {{ $d['to'] }})">{{ $d['initiales'] }}</span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $d['membre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $d['formation'] }} · terminée {{ $d['date'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $d['statutColor'] }}; background: {{ $d['statutBg'] }}">{{ $d['statutLabel'] }}</span>
                    @if ($d['enAttente'])
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="emettre({{ $d['index'] }})" class="rounded-lg bg-brand px-3.5 py-1.5 text-xs font-bold text-white hover:bg-brand/90">Émettre</button>
                            <button wire:click="rejeter({{ $d['index'] }})" class="rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Rejeter</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
