<div>
    <x-member-light.topbar title="Groupes sectoriels" />

    <div class="mx-auto max-w-[1120px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Groupes sectoriels</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Ces pôles regroupent les membres par domaine d'activité pour des échanges ciblés et des synergies sectorielles. Rejoignez autant de groupes que vous voulez — par exemple pour suivre plusieurs formations en parallèle.</p>
            </div>
            @if ($message)
                <span class="panel-enter inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                    <x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}
                </span>
            @endif
        </div>

        @if ($mesGroupes->isNotEmpty())
            <div class="mb-6 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-2.5 text-[12px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Mes groupes ({{ $mesGroupes->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($mesGroupes as $g)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand/[.06] px-3 py-1.5 text-[12px] font-semibold text-brand">
                            <x-ui.icon name="network" class="size-3.5 text-azure" /> {{ $g['name'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $g)
                <div class="card-hover flex flex-col rounded-[16px] border bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $g['joined'] ? 'border-azure/40' : 'border-brand/10' }}" wire:key="groupe-{{ $g['id'] }}">
                    <div class="mb-2.5 flex items-start justify-between gap-2">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-brand/[.06] text-brand">
                            <x-ui.icon name="network" class="size-5" />
                        </span>
                        @if ($g['joined'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[10.5px] font-bold text-[#1C8F4C]">
                                <x-ui.icon name="check" class="size-3" /> Membre
                            </span>
                        @endif
                    </div>

                    <p class="text-[14px] font-bold leading-snug text-brand">{{ $g['name'] }}</p>
                    <p class="mt-1.5 flex-1 text-[12px] leading-relaxed text-[#5B677A]">{{ $g['description'] }}</p>

                    <div class="mt-4 flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#9AA6B8]">
                            <x-ui.icon name="users" class="size-3.5" /> {{ $g['members'] }} membre{{ $g['members'] > 1 ? 's' : '' }}
                        </span>
                        @if ($g['joined'])
                            <button
                                type="button"
                                wire:click="quitter({{ $g['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="quitter({{ $g['id'] }})"
                                class="btn-tap rounded-full border border-brand/15 px-3.5 py-1.5 text-[11.5px] font-bold text-[#5B677A] hover:border-accent/40 hover:text-accent disabled:opacity-60"
                            >Quitter</button>
                        @else
                            <button
                                type="button"
                                wire:click="rejoindre({{ $g['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="rejoindre({{ $g['id'] }})"
                                class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90 disabled:opacity-60"
                            >Rejoindre</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
