<div>
    <x-admin-light.topbar title="Contenu du site" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Contenu éditorial de la vitrine</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 text-xs text-[#9AA6B8]">Tout ce que vous modifiez ici est publié immédiatement sur le site public.</p>
        </div>

        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($onglets as $key => $label)
                <button wire:click="setOnglet('{{ $key }}')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $onglet === $key ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">{{ $label }}</button>
            @endforeach
            <button wire:click="openCreate" class="ml-auto btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Ajouter</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier' : 'Ajouter' }} — {{ $onglets[$onglet] }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>

                @if ($onglet === 'sectors')
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre du secteur</label>
                        <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Icône (ex : sprout, cpu, landmark)</label>
                        <input wire:model="icon" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('icon') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Accroche</label>
                        <input wire:model="blurb" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('blurb') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Filières — une par ligne</label>
                        <textarea wire:model="items" rows="4" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                        @error('items') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                @elseif ($onglet === 'testimonials')
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom du membre</label>
                        <input wire:model="name" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('name') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Rôle / activité (ex : Fondatrice, AgriPlus)</label>
                        <input wire:model="role" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('role') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Témoignage</label>
                        <textarea wire:model="quote" rows="3" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                        @error('quote') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                @elseif ($onglet === 'partners')
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom de l'entreprise / organisation</label>
                        <input wire:model="name" type="text" placeholder="Ex : Ivoire Tech SARL" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('name') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Domaine (optionnel — ex : Partenaire financier)</label>
                        <input wire:model="sector" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('sector') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Site web (optionnel)</label>
                        <input wire:model="site_url" type="url" placeholder="https://…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('site_url') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">Le logo deviendra cliquable et ouvrira ce site.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Logo du partenaire (optionnel)" hint="Uploadez le logo (PNG ou SVG à fond transparent de préférence), ou collez son lien. Sans logo, le nom du partenaire s'affiche à la place sur la vitrine." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                        @error('mediaFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                @elseif ($onglet === 'stats')
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Libellé (ex : Membres actifs)</label>
                        <input wire:model="label" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('label') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Valeur</label>
                            <input wire:model="value" type="number" min="0" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                            @error('value') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Suffixe (ex : +, %)</label>
                            <input wire:model="suffix" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                    </div>
                @elseif ($onglet === 'steps')
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre de l'étape</label>
                        <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Icône (ex : file-text, user-check)</label>
                        <input wire:model="icon" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('icon') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                        <textarea wire:model="text" rows="2" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                        @error('text') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                @elseif ($onglet === 'gallery')
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Photo" hint="Uploadez une photo (JPG, PNG, WebP) ou collez le lien d'une image — par exemple depuis la Médiathèque." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                        @error('mediaFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Légende (optionnel — ex : Rencontre annuelle 2026)</label>
                        <input wire:model="caption" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('caption') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Publier</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($elements as $item)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                    <div class="min-w-[220px] flex-1">
                        @if ($onglet === 'sectors')
                            <p class="text-[13.5px] font-bold text-brand">{{ $item['title'] }}</p>
                            <p class="line-clamp-1 text-xs text-[#5B677A]">{{ $item['blurb'] }}</p>
                            <p class="mt-0.5 text-[11px] text-[#9AA6B8]">{{ count($item['items'] ?? []) }} filière(s) · icône {{ $item['icon'] }}</p>
                        @elseif ($onglet === 'testimonials')
                            <p class="text-[13.5px] font-bold text-brand">{{ $item['name'] }} <span class="font-normal text-[#9AA6B8]">— {{ $item['role'] }}</span></p>
                            <p class="line-clamp-2 text-xs italic text-[#5B677A]">« {{ $item['quote'] }} »</p>
                        @elseif ($onglet === 'partners')
                            <div class="flex items-center gap-3">
                                @if ($item['logo'] ?? null)
                                    <img src="{{ $item['logo'] }}" alt="" class="h-10 w-16 shrink-0 rounded-lg border border-brand/10 bg-white object-contain p-1">
                                @else
                                    <span class="flex h-10 w-16 shrink-0 items-center justify-center rounded-lg bg-brand/[.06] text-[10px] font-bold text-[#9AA6B8]">Sans logo</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-bold text-brand">{{ $item['name'] }}</p>
                                    <p class="truncate text-xs text-[#5B677A]">{{ $item['sector'] ?: '—' }}</p>
                                    @if ($item['site_url'] ?? null)
                                        <a href="{{ $item['site_url'] }}" target="_blank" rel="noopener" class="truncate text-[11px] text-azure hover:underline">{{ $item['site_url'] }}</a>
                                    @endif
                                </div>
                            </div>
                        @elseif ($onglet === 'stats')
                            <p class="text-[13.5px] font-bold text-brand">{{ number_format($item['value'], 0, ',', ' ') }}{{ $item['suffix'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $item['label'] }}</p>
                        @elseif ($onglet === 'steps')
                            <p class="text-[13.5px] font-bold text-brand">{{ $item['title'] }}</p>
                            <p class="line-clamp-1 text-xs text-[#5B677A]">{{ $item['text'] }}</p>
                        @elseif ($onglet === 'gallery')
                            <div class="flex items-center gap-3">
                                <img src="{{ $item['url'] }}" alt="" class="size-12 shrink-0 rounded-lg object-cover">
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-bold text-brand">{{ $item['caption'] ?: 'Sans légende' }}</p>
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="truncate text-[11px] text-azure hover:underline">Voir la photo</a>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button wire:click="openEdit({{ $item['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                            <x-ui.icon name="pencil" class="size-3.5" />
                        </button>
                        <button wire:click="delete({{ $item['id'] }})" wire:confirm="Supprimer cet élément du site ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                            <x-ui.icon name="trash-2" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun élément — cliquez sur « + Ajouter ».</p>
            @endforelse
        </div>
    </div>
</div>
