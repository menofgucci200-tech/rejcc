<div>
    <x-admin-light.topbar title="Projets & Incubateur" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Projets soumis à validation</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="grid gap-3.5 lg:grid-cols-3">
                @foreach ($projets as $p)
                    <div class="flex flex-col gap-2.5 rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="flex items-center justify-between">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $p['statutColor'] }}; background: {{ $p['statutBg'] }}">{{ $p['statutLabel'] }}</span>
                            <span class="text-[11.5px] text-[#5B677A]">{{ $p['membres'] }} membres</span>
                        </div>
                        <p class="text-[14.5px] font-bold text-brand">{{ $p['titre'] }}</p>
                        <p class="text-xs leading-relaxed text-[#5B677A]">{{ $p['description'] }}</p>
                        <p class="text-[11.5px] text-[#9AA6B8]">Objectif : {{ $p['objectif'] }} FCFA</p>
                        @if ($p['enAttente'])
                            <div class="mt-1 flex gap-2">
                                <button wire:click="valider({{ $p['index'] }})" class="flex-1 rounded-[9px] bg-[#22A85A] py-2 text-xs font-bold text-white hover:bg-[#1C8F4C]">Valider</button>
                                <button wire:click="rejeter({{ $p['index'] }})" class="flex-1 rounded-[9px] border border-[#F0C9C9] py-2 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Rejeter</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Suivi financier de l'incubateur</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="grid gap-3.5 lg:grid-cols-3">
                @foreach ($suivi as $s)
                    <div class="flex flex-col gap-3 rounded-2xl border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <p class="text-[14.5px] font-bold text-brand">{{ $s['titre'] }}</p>
                        <div>
                            <div class="mb-1.5 flex justify-between text-xs text-[#5B677A]">
                                <span>{{ $s['leve'] }} FCFA levés</span>
                                <span class="font-bold text-brand">{{ $s['pct'] }} %</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[#EDF0F5]">
                                <div class="h-full rounded-full" style="width: {{ $s['pct'] }}%; background: linear-gradient(90deg,#031D59,#4F6FBF)"></div>
                            </div>
                        </div>
                        <button class="rounded-[9px] border border-[#C9D3E6] py-2 text-xs font-bold text-brand hover:bg-cloud">Voir le détail financier</button>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
