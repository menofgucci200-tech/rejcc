<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-brand">Documents</h1>
            <p class="text-sm text-ink/60">{{ $docs->count() }} fichier{{ $docs->count() > 1 ? 's' : '' }}</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand/90">
            <x-ui.icon name="plus" class="size-4" /> Ajouter
        </button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-2xl border border-brand/15 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <p class="font-semibold text-brand">{{ $editingId ? 'Modifier le document' : 'Nouveau document' }}</p>
                <button wire:click="closeForm"><x-ui.icon name="x" class="size-4 text-ink/50" /></button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Titre</label>
                    <input wire:model="title" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Catégorie</label>
                    <input wire:model="category" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-brand">URL du fichier</label>
                    <input wire:model="url" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('url') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Taille (ex: 2.4 Mo)</label>
                    <input wire:model="size" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-brand">Description</label>
                    <input wire:model="description" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                </div>
            </div>
            <button wire:click="save" wire:loading.attr="disabled" class="mt-4 inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2 text-sm font-semibold text-white transition hover:bg-accent-600 disabled:opacity-60">
                <x-ui.icon name="check" class="size-3.5" /> Enregistrer
            </button>
        </div>
    @endif

    <div class="flex flex-col gap-8">
        @foreach ($categories as $cat)
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-ink/50">{{ $cat }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($docs->where('category', $cat) as $d)
                        <div class="flex items-center gap-3 rounded-2xl border border-brand/10 bg-white p-4">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand/5 text-brand">
                                <x-ui.icon name="file-text" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-brand">{{ $d->title }}</p>
                                @if ($d->description)
                                    <p class="truncate text-xs text-ink/55">{{ $d->description }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button wire:click="openEdit({{ $d->id }})" class="rounded-lg p-1.5 text-ink/40 hover:bg-brand/10 hover:text-brand">
                                    <x-ui.icon name="pencil" class="size-3.5" />
                                </button>
                                <button wire:click="delete({{ $d->id }})" wire:confirm="Supprimer « {{ $d->title }} » ?" class="rounded-lg p-1.5 text-ink/40 hover:bg-accent/10 hover:text-accent">
                                    <x-ui.icon name="trash-2" class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        @if ($docs->isEmpty())
            <p class="py-12 text-center text-sm text-ink/50">Aucun document.</p>
        @endif
    </div>
</div>
