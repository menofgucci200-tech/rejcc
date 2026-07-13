<div>
    <x-admin-light.topbar title="Opportunités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Opportunités &amp; annonces</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Publier une annonce</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier l\'annonce' : 'Nouvelle annonce' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type</label>
                    <select wire:model="type" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        <option value="emploi">Emploi</option>
                        <option value="stage">Stage</option>
                        <option value="annonce">Annonce</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                    <textarea wire:model="description" rows="4" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Contact (email ou téléphone, optionnel)</label>
                    <input wire:model="contact" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date limite (optionnel)</label>
                    <input wire:model="deadline" type="date" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Fiche de poste / affiche (PDF, image ou lien)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($annonces as $o)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-[10px] bg-brand/10 text-brand">
                        <x-ui.icon name="nav-briefcase" class="size-4" />
                    </span>
                    <div class="min-w-[220px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $o['titre'] }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-[#5B677A]">{{ $o['description'] }}</p>
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">Par {{ $o['auteur'] }} · {{ $o['date'] }}@if ($o['deadline']) · limite : {{ $o['deadline'] }}@endif</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $o['type'] === 'emploi' ? 'bg-brand/10 text-brand' : ($o['type'] === 'stage' ? 'bg-[#F5A623]/10 text-[#F5A623]' : 'bg-azure/10 text-azure') }}">
                        {{ ucfirst($o['type']) }}
                    </span>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $o['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $o['id'] }})" wire:confirm="Supprimer l'annonce « {{ $o['titre'] }} » ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucune annonce pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
