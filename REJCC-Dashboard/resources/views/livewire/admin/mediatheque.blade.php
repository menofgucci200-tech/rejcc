<div>
    <x-admin-light.topbar title="Médiathèque" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Médiathèque</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Tous les fichiers uploadés sur le site. Copiez le lien d'un fichier pour le réutiliser dans n'importe quel formulaire (galerie, articles, annonces…). Les documents personnels des membres n'apparaissent pas ici.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['tous' => 'Tous', 'images' => 'Images', 'videos' => 'Vidéos', 'documents' => 'Documents'] as $value => $label)
                    <button wire:click="setFiltre('{{ $value }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">{{ $label }} ({{ $compteurs[$value] }})</button>
                @endforeach
            </div>
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        {{-- Zone d'upload --}}
        <label class="mb-6 flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[16px] border border-dashed border-brand/25 bg-white px-4 py-6 text-center transition-colors hover:bg-cloud/60" wire:loading.class="opacity-50" wire:target="fichiers">
            <x-ui.icon name="image" class="size-7 text-[#9AA6B8]" />
            <span class="text-[13px] font-semibold text-[#5B677A]">Cliquez pour ajouter des fichiers à la médiathèque</span>
            <span class="text-[11px] text-[#9AA6B8]">Images, vidéos, PDF, documents — 20 Mo max par fichier, sélection multiple possible</span>
            <input type="file" wire:model="fichiers" multiple class="hidden">
        </label>
        <div wire:loading wire:target="fichiers" class="-mt-4 mb-4 text-[12px] font-semibold text-azure">Envoi des fichiers…</div>
        @error('fichiers.*') <p class="-mt-4 mb-4 text-xs text-accent">{{ $message }}</p> @enderror

        {{-- Grille des fichiers --}}
        @if ($files->isEmpty())
            <p class="rounded-[16px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucun fichier{{ $filtre !== 'tous' ? ' dans cette catégorie' : ' pour le moment' }}.</p>
        @else
            <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))">
                @foreach ($files as $f)
                    <div class="card-hover overflow-hidden rounded-[14px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]" x-data="{ copied: false }">
                        @if ($f['kind'] === 'images')
                            <img src="{{ $f['url'] }}" alt="{{ $f['name'] }}" class="h-28 w-full object-cover" loading="lazy">
                        @elseif ($f['kind'] === 'videos')
                            <video preload="metadata" muted class="h-28 w-full bg-black object-contain">
                                <source src="{{ $f['url'] }}">
                            </video>
                        @else
                            <div class="flex h-28 items-center justify-center bg-cloud">
                                <x-ui.icon name="file-text" class="size-8 text-[#9AA6B8]" />
                            </div>
                        @endif
                        <div class="p-3">
                            <p class="truncate text-[11.5px] font-bold text-brand" title="{{ $f['name'] }}">{{ $f['name'] }}</p>
                            <p class="mt-0.5 text-[10.5px] text-[#9AA6B8]">{{ $f['size'] }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($f['modified'])->translatedFormat('d M Y') }}</p>
                            <div class="mt-2.5 flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $f['url'] }}'); copied = true; setTimeout(() => copied = false, 1600)"
                                    class="btn-tap flex-1 rounded-lg border border-azure/25 bg-azure/10 px-2 py-1.5 text-[10.5px] font-bold text-azure hover:bg-azure/20"
                                >
                                    <span x-show="!copied">Copier le lien</span>
                                    <span x-show="copied" x-cloak class="text-[#1C8F4C]">✓ Copié !</span>
                                </button>
                                <a href="{{ $f['url'] }}" target="_blank" rel="noopener" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Ouvrir">
                                    <x-ui.icon name="external-link" class="size-3.5" />
                                </a>
                                <button wire:click="supprimer('{{ $f['path'] }}')" wire:confirm="Supprimer définitivement « {{ $f['name'] }} » ? Les contenus qui l'utilisent afficheront une image manquante." class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer">
                                    <x-ui.icon name="trash-2" class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
