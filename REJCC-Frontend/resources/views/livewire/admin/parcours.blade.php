<div>
    <x-admin-light.topbar title="Parcours" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Parcours guidés</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">Séquences de formations vers un objectif, avec déblocage progressif et badge de fin.</p>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Nouveau parcours</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le parcours' : 'Nouveau parcours' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" placeholder="Ex : Parcours Entrepreneur Junior" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Objectif (phrase courte)</label>
                    <input wire:model="objectif" type="text" placeholder="Ex : Lancer son projet en 3 formations." class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Icône du badge</label>
                    <select wire:model="badgeIcon" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        @foreach (\App\Livewire\Admin\Parcours::BADGE_ICONS as $icon)
                            <option value="{{ $icon }}">{{ $icon }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Couleur du badge</label>
                    <input wire:model="badgeCouleur" type="text" placeholder="#4F6FBF" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Ordre d'affichage</label>
                    <input wire:model="ordre" type="number" min="0" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <label class="flex items-end gap-2 pb-1.5 text-xs font-semibold text-[#5B677A]">
                    <input wire:model="isPublished" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> Publié
                </label>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($paths as $p)
                <div class="-mx-5 border-t border-[#EDF0F5] px-5 first:border-t-0">
                    <div class="row-hover flex flex-wrap items-center gap-4 py-3.5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-[10px]" style="background: {{ $p['badge_couleur'] ?? '#4F6FBF' }}1A; color: {{ $p['badge_couleur'] ?? '#4F6FBF' }}">
                            <x-ui.icon :name="$p['badge_icon'] ?: 'rocket'" class="size-5" />
                        </span>
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $p['title'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $p['formations_count'] ?? 0 }} formation{{ ($p['formations_count'] ?? 0) > 1 ? 's' : '' }}</p>
                        </div>
                        <span class="w-20 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $p['is_published'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $p['is_published'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $p['is_published'] ? 'Publié' : 'Brouillon' }}</span>
                        <button wire:click="togglePublication({{ $p['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $p['is_published'] ? 'Dépublier' : 'Publier' }}</button>
                        <button wire:click="toggleFormations({{ $p['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $formationsPathId === $p['id'] ? 'Fermer' : 'Formations' }}</button>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button wire:click="openEdit({{ $p['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                                <x-ui.icon name="pencil" class="size-3.5" />
                            </button>
                            <button wire:click="delete({{ $p['id'] }})" wire:confirm="Supprimer « {{ $p['title'] }} » ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                                <x-ui.icon name="trash-2" class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Panneau de gestion des formations du parcours --}}
                    @if ($formationsPathId === $p['id'])
                        <div class="panel-enter mb-4 grid grid-cols-1 gap-4 rounded-[14px] bg-cloud/50 p-4 sm:grid-cols-2">
                            <div>
                                <p class="mb-2 text-[12px] font-bold uppercase tracking-[0.05em] text-[#9AA6B8]">Dans le parcours (ordre)</p>
                                <div class="flex flex-col gap-1.5 rounded-[10px] bg-white p-2">
                                    @forelse ($formationsChoisies as $i => $f)
                                        <div class="flex items-center gap-2 rounded-[8px] bg-cloud/60 px-2.5 py-2">
                                            <span class="w-5 shrink-0 text-center text-[11px] font-bold text-[#9AA6B8]">{{ $i + 1 }}</span>
                                            <span class="min-w-0 flex-1 truncate text-[12.5px] font-semibold text-brand">{{ $f['title'] }}</span>
                                            <button wire:click="monter({{ $i }})" class="icon-btn shrink-0 rounded p-1 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Monter"><x-ui.icon name="chevron-right" class="size-3.5 -rotate-90" /></button>
                                            <button wire:click="descendre({{ $i }})" class="icon-btn shrink-0 rounded p-1 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Descendre"><x-ui.icon name="chevron-right" class="size-3.5 rotate-90" /></button>
                                            <button wire:click="retirerFormation({{ $f['id'] }})" class="icon-btn shrink-0 rounded p-1 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="x" class="size-3.5" /></button>
                                        </div>
                                    @empty
                                        <p class="px-2 py-3 text-center text-[12px] text-[#9AA6B8]">Aucune formation ajoutée.</p>
                                    @endforelse
                                </div>
                                <button wire:click="enregistrerFormations" wire:loading.attr="disabled" class="btn-tap mt-3 rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Enregistrer l'ordre</button>
                            </div>
                            <div>
                                <p class="mb-2 text-[12px] font-bold uppercase tracking-[0.05em] text-[#9AA6B8]">Formations disponibles</p>
                                <div class="flex max-h-[260px] flex-col gap-1.5 overflow-y-auto rounded-[10px] bg-white p-2">
                                    @forelse ($formationsDisponibles as $f)
                                        <button type="button" wire:click="ajouterFormation({{ $f['id'] }})" class="flex items-center justify-between gap-2 rounded-[8px] px-2.5 py-2 text-left hover:bg-cloud/60">
                                            <span class="min-w-0 flex-1 truncate text-[12.5px] font-semibold text-ink">{{ $f['title'] }}</span>
                                            <x-ui.icon name="plus" class="size-3.5 shrink-0 text-azure" />
                                        </button>
                                    @empty
                                        <p class="px-2 py-3 text-center text-[12px] text-[#9AA6B8]">Toutes les formations sont déjà dans ce parcours.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun parcours. Cliquez sur « + Nouveau parcours » pour créer le premier.</p>
            @endforelse
        </div>
    </div>
</div>
