<div>
    <x-admin-light.topbar title="Événements" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Événements</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Nouvel événement</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier l\'événement' : 'Nouvel événement' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie (ex : Atelier, Networking)</label>
                    <input wire:model="category" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date et heure</label>
                    <input wire:model="startsAt" type="datetime-local" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('startsAt') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Horaire affiché (ex : 14 h – 17 h)</label>
                    <input wire:model="timeLabel" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Lieu</label>
                    <input wire:model="location" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Capacité (places, optionnel)</label>
                    <input wire:model="capacity" type="number" min="1" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Accroche (affichée sur la vitrine)</label>
                    <input wire:model="excerpt" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Affiche / visuel de l'événement (image ou lien)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($evenements as $ev)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0 {{ $ev['passe'] ? 'opacity-55' : '' }}">
                    <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-[10px] bg-cloud text-brand">
                        <span class="text-sm font-extrabold leading-none">{{ $ev['jour'] }}</span>
                        <span class="text-[9px] font-bold tracking-wide">{{ $ev['mois'] }}</span>
                    </div>
                    <div class="min-w-[200px] flex-1">
                        <span class="mb-1 inline-block rounded-full px-2 py-0.5 text-[9.5px] font-bold" style="background: {{ $ev['tagColor'] }}1A; color: {{ $ev['tagColor'] }}">{{ $ev['tag'] }}</span>
                        <p class="text-[13.5px] font-bold text-brand">{{ $ev['titre'] }}</p>
                        @if ($ev['detail'])
                            <p class="text-xs text-[#5B677A]">{{ $ev['detail'] }}</p>
                        @endif
                    </div>
                    <span class="w-[90px] shrink-0 text-xs text-[#5B677A]">{{ $ev['inscrits'] }} inscrit{{ $ev['inscrits'] > 1 ? 's' : '' }}</span>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $ev['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $ev['id'] }})" wire:confirm="Supprimer « {{ $ev['titre'] }} » et toutes ses inscriptions ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun événement. Cliquez sur « + Nouvel événement » pour créer le premier.</p>
            @endforelse
        </div>
    </div>
</div>
