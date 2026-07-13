<div>
    <x-member-light.topbar title="Opportunités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Opportunités</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="setFiltre('tous')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                <button wire:click="setFiltre('emploi')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'emploi' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Emplois</button>
                <button wire:click="setFiltre('stage')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'stage' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Stages</button>
                <button wire:click="setFiltre('annonce')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'annonce' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Annonces</button>
                <button wire:click="openForm" class="btn-tap rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Publier</button>
            </div>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">Publier une opportunité</p>
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
                    <textarea wire:model="description" rows="3" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Contact (email ou téléphone)</label>
                    <input wire:model="contact" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date limite (optionnel)</label>
                    <input wire:model="deadline" type="date" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <button wire:click="publier" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Publier</button>
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($offres as $o)
                <article class="card-hover flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                        {{ mb_strtoupper(mb_substr($o['titre'], 0, 1)) }}
                    </span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $o['titre'] }}</p>
                        <p class="mt-0.5 line-clamp-2 text-xs text-[#5B677A]">{{ $o['description'] }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-3 text-[11px] text-[#9AA6B8]">
                            <span>Par {{ $o['auteur'] }}</span>
                            <span>{{ $o['date'] }}</span>
                            @if ($o['deadline'])
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="size-3" /> Avant le {{ $o['deadline'] }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $o['type'] === 'emploi' ? 'bg-brand/10 text-brand' : ($o['type'] === 'stage' ? 'bg-[#F5A623]/10 text-[#F5A623]' : 'bg-azure/10 text-azure') }}">
                        {{ ucfirst($o['type']) }}
                    </span>
                    @if ($o['media'])
                        <a href="{{ $o['media'] }}" target="_blank" rel="noopener" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-xs font-semibold text-brand hover:bg-cloud">
                            <x-ui.icon name="file-text" class="size-3.5" /> Document
                        </a>
                    @endif
                    @if ($o['contact'])
                        @if (str_contains($o['contact'], '@'))
                            <a href="mailto:{{ $o['contact'] }}" class="btn-tap shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20">Contacter</a>
                        @else
                            <span class="shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure">{{ $o['contact'] }}</span>
                        @endif
                    @endif
                </article>
            @empty
                <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucune opportunité{{ $filtre !== 'tous' ? ' dans cette catégorie' : '' }} pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
