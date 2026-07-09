<div>
    <x-admin-light.topbar title="Mentors" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes en attente</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-4.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @foreach ($demandes as $d)
                        <div class="border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                            <p class="text-[13.5px] font-bold text-brand">{{ $d['membre'] }}</p>
                            <p class="my-1 text-xs text-[#5B677A]">Recherche un mentor en {{ $d['domaine'] }}</p>
                            @if ($d['assignee'])
                                <span class="inline-block rounded-full bg-[#EAF6EE] px-2.5 py-1 text-[11.5px] font-bold text-[#22A85A]">Assignée à {{ $d['assignee'] }}</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($d['suggestions'] as $nom)
                                        <button wire:click="assigner({{ $d['index'] }}, '{{ $nom }}')" class="rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-brand hover:text-white">Assigner {{ $nom }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Mentors du réseau</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-4.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @foreach ($mentors as $m)
                        <div class="flex items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                            <span class="relative flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, {{ $m['from'] }}, {{ $m['to'] }})">
                                {{ $m['initiales'] }}
                                <span class="absolute -bottom-0.5 -right-0.5 size-[11px] rounded-full border-2 border-white" style="background: {{ $m['online'] ? '#22A85A' : '#C9D3E6' }}"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $m['nom'] }}</p>
                                <p class="text-xs text-[#5B677A]">{{ $m['specialite'] }} · {{ $m['mentores'] }} mentorés</p>
                            </div>
                            <span class="shrink-0 text-[11.5px] font-bold text-brand">⭐ {{ $m['note'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
