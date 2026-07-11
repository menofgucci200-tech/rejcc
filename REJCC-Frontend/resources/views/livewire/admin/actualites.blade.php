<div>
    <x-admin-light.topbar title="Actualités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Actualités du site</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openCreate" class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Nouvel article</button>
        </div>

        @if ($showForm)
            <div class="mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier l\'article' : 'Nouvel article' }}</p>
                    <button wire:click="closeForm"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie (ex : Réseau, Formation)</label>
                    <input wire:model="category" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Extrait (résumé affiché dans les listes et sur Google)</label>
                    <input wire:model="excerpt" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('excerpt') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Contenu de l'article — séparez les paragraphes par une ligne vide</label>
                    <textarea wire:model="body" rows="8" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('body') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Auteur (optionnel)</label>
                    <input wire:model="author" type="text" placeholder="La rédaction REJCC" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Publier</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($articles as $a)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-[10px] bg-brand/10 text-brand">
                        <x-ui.icon name="file-text" class="size-4" />
                    </span>
                    <div class="min-w-[220px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $a['titre'] }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-[#5B677A]">{{ $a['extrait'] }}</p>
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">{{ $a['categorie'] }} · publié le {{ $a['publie'] }}@if ($a['lecture']) · {{ $a['lecture'] }}@endif</p>
                    </div>
                    <a href="{{ url('/actualites/'.$a['slug']) }}" target="_blank" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Voir</a>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $a['id'] }})" class="rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $a['id'] }})" wire:confirm="Supprimer l'article « {{ $a['titre'] }} » ?" class="rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun article. Cliquez sur « + Nouvel article » pour publier le premier.</p>
            @endforelse
        </div>
    </div>
</div>
