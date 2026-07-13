<div>
    <x-admin-light.topbar title="Formations" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Catalogue des formations</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Nouvelle formation</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier la formation' : 'Nouvelle formation' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie</label>
                    <input wire:model="category" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Durée (ex : 4 semaines)</label>
                    <input wire:model="duration" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Niveau (ex : Débutant)</label>
                    <input wire:model="level" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nombre de modules</label>
                    <input wire:model="modulesCount" type="number" min="1" max="50" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('modulesCount') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end gap-5 pb-1.5">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#5B677A]">
                        <input wire:model="isFree" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> Gratuite
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#5B677A]">
                        <input wire:model="isCertifying" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> Certifiante
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Support de la formation (PDF, vidéo, image ou lien)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($formations as $f)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                    <div class="h-11 w-[60px] shrink-0 rounded-[9px]" style="background: {{ $f['visuel'] }}"></div>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $f['titre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $f['categorie'] }} · {{ $f['duree'] }}</p>
                    </div>
                    <span class="w-[110px] shrink-0 text-xs text-[#5B677A]">{{ $f['inscrits'] }} {{ $f['inscrits'] > 1 ? 'inscrits' : 'inscrit' }}</span>
                    <span class="w-20 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $f['publiee'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $f['publiee'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $f['publiee'] ? 'Publiée' : 'Brouillon' }}</span>
                    <button wire:click="togglePublication({{ $f['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $f['publiee'] ? 'Dépublier' : 'Publier' }}</button>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $f['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $f['id'] }})" wire:confirm="Supprimer « {{ $f['titre'] }} » et toutes ses inscriptions ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucune formation. Cliquez sur « + Nouvelle formation » pour créer la première.</p>
            @endforelse
        </div>
    </div>
</div>
