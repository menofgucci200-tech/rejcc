<div>
    <x-admin-light.topbar title="Projets & Incubateur" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Projets du réseau</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Validez les projets proposés par les membres, faites-les entrer dans l'incubateur et suivez leur financement et leurs jalons.</p>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">Gérer le projet</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Statut</label>
                    <select wire:model="status" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        @foreach ($statutOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Membres impliqués</label>
                    <input wire:model="membersCount" type="number" min="1" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="flex items-end pb-1.5">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#5B677A]">
                        <input wire:model="inIncubator" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> Suivi par l'incubateur
                    </label>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Objectif de financement (FCFA)</label>
                    <input wire:model="fundingGoal" type="number" min="0" step="100000" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Montant levé (FCFA)</label>
                    <input wire:model="fundingRaised" type="number" min="0" step="100000" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <p class="mb-2 text-xs font-semibold text-[#5B677A]">Jalons du parcours</p>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($jalons as $i => $j)
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#5B677A]">
                                <input wire:model="jalons.{{ $i }}.done" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> {{ $j['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($projets as $p)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-[10px] bg-brand/10 text-brand">
                        <x-ui.icon name="nav-projects" class="size-4" />
                    </span>
                    <div class="min-w-[220px] flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[13.5px] font-bold text-brand">{{ $p['titre'] }}</p>
                            @if ($p['incube'])
                                <span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[9.5px] font-bold text-[#B87A0D]">INCUBATEUR</span>
                            @endif
                        </div>
                        <p class="mt-0.5 line-clamp-1 text-xs text-[#5B677A]">{{ $p['description'] }}</p>
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">Par {{ $p['porteur'] }} · {{ $p['membres'] }} membre{{ $p['membres'] > 1 ? 's' : '' }}@if ($p['objectif']) · {{ number_format($p['leve'], 0, ',', ' ') }} / {{ number_format($p['objectif'], 0, ',', ' ') }} FCFA @endif</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10.5px] font-bold" style="background: {{ $p['statutColor'] }}1A; color: {{ $p['statutColor'] }}">{{ $p['statut'] }}</span>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $p['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $p['id'] }})" wire:confirm="Supprimer le projet « {{ $p['titre'] }} » ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun projet proposé pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
