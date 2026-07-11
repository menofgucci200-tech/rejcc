<div>
    <x-admin-light.topbar title="Ressources" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Bibliothèque de ressources</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openCreate" class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Ajouter une ressource</button>
        </div>

        @if ($showForm)
            <div class="mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier la ressource' : 'Nouvelle ressource' }}</p>
                    <button wire:click="closeForm"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type</label>
                    <select wire:model="type" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        <option value="Ebook">Ebook</option>
                        <option value="Modèle">Modèle</option>
                        <option value="Vidéo">Vidéo</option>
                        <option value="Audio">Audio</option>
                        <option value="Document">Document</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien du fichier (URL de téléchargement ou de consultation)</label>
                    <input wire:model="url" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('url') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Taille (ex : 2.4 Mo, optionnel)</label>
                    <input wire:model="size" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description (optionnel)</label>
                    <input wire:model="description" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($ressources as $r)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0 {{ $r['visible'] ? '' : 'opacity-55' }}">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-base" style="background: {{ $r['tint'] }}">{{ $r['emoji'] }}</span>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $r['titre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $r['type'] }} · {{ $r['taille'] }}</p>
                    </div>
                    <span class="w-[100px] shrink-0 text-xs text-[#5B677A]">{{ $r['telechargements'] }} téléch.</span>
                    <button wire:click="toggleVisibilite({{ $r['id'] }})" class="shrink-0 rounded-[9px] border px-3 py-1.5 text-xs font-bold {{ $r['visible'] ? 'border-[#C9D3E6] text-brand' : 'border-[#E6EAF0] text-[#9AA6B8]' }} bg-white hover:bg-cloud">{{ $r['visible'] ? 'Masquer' : 'Publier' }}</button>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $r['id'] }})" class="rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $r['id'] }})" wire:confirm="Supprimer « {{ $r['titre'] }} » ?" class="rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucune ressource. Cliquez sur « + Ajouter une ressource » pour commencer.</p>
            @endforelse
        </div>
    </div>
</div>
