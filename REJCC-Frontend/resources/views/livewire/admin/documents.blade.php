<div>
    <x-admin-light.topbar title="Documents" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $statuts = ['publie' => ['Publié', '#1C8F4C'], 'en_attente' => ['Proposé, à valider', '#4F6FBF'], 'refuse' => ['Refusé', '#AC0100']];
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Documents &amp; ressources</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Les fichiers sont privés : ils ne s'ouvrent que sur la plateforme, pour les membres qui y ont accès. {{ ($stats['vues'] ?? 0) }} consultations · {{ ($stats['telechargements'] ?? 0) }} téléchargements au total.</p>
            </div>
            <div class="flex gap-2">
                <button wire:click="$toggle('gererCategories')" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Catégories</button>
                <button wire:click="openCreate" data-test="ajouter-doc" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Ajouter un document</button>
            </div>
        </div>

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm && ! $refusId)
            <p wire:key="flash-ko" class="panel-enter mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif

        {{-- Catégories --}}
        @if ($gererCategories)
            <div wire:key="categories" class="panel-enter mb-5 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-2 text-[13px] font-bold text-brand">Catégories</p>
                <div class="space-y-1.5">
                    @foreach ($categories as $c)
                        <div wire:key="c-{{ $c['id'] }}" class="flex items-center gap-2 text-[13px]">
                            @if ($categorieEditee === $c['id'])
                                <input wire:model="nomCategorie" type="text" class="rounded-[8px] border border-brand/15 px-2 py-1 text-sm" />
                                <button wire:click="renommerCategorie" class="text-xs font-bold text-azure">Enregistrer</button>
                            @else
                                <span class="font-semibold text-brand">{{ $c['nom'] }}</span> <span class="text-[11.5px] text-[#9AA6B8]">{{ $c['nombre'] }} document{{ $c['nombre'] > 1 ? 's' : '' }}</span>
                                <button wire:click="editerCategorie({{ $c['id'] }}, @js($c['nom']))" class="ml-auto text-xs font-semibold text-[#5B677A] hover:text-brand">Renommer</button>
                                <button wire:click="supprimerCategorie({{ $c['id'] }})" class="text-xs font-semibold text-[#9AA6B8] hover:text-accent">Supprimer</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex gap-2">
                    <input wire:model="nouvelleCategorie" type="text" placeholder="Nouvelle catégorie" class="rounded-[9px] border border-brand/15 px-3 py-1.5 text-sm" />
                    <button wire:click="ajouterCategorie" class="btn-tap rounded-[9px] bg-brand px-3.5 py-1.5 text-xs font-bold text-white">Ajouter</button>
                </div>
            </div>
        @endif

        {{-- Formulaire --}}
        @if ($showForm)
            <div wire:key="form-doc" data-test="form-doc" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le document' : 'Nouveau document' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Fichier</label>
                    @if ($fichier)
                        <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-brand/10 bg-cloud/50 px-3 py-2.5">
                            <x-ui.icon name="file-text" class="size-4 text-azure" />
                            <span data-test="fichier-choisi" class="min-w-0 flex-1 truncate text-[13px] font-semibold text-brand">{{ $fichier['nom'] }}</span>
                            <span class="text-[11.5px] text-[#9AA6B8]">{{ $fichier['taille'] ?? (($fichier['octets'] ?? 0) >= 1048576 ? number_format($fichier['octets'] / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round(($fichier['octets'] ?? 0) / 1024)).' Ko') }}</span>
                            <label class="cursor-pointer text-[12px] font-bold text-azure hover:underline">Remplacer<input type="file" wire:model="fichierUpload" class="hidden" /></label>
                            <button type="button" wire:click="retirerFichier" class="text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Retirer</button>
                        </div>
                    @else
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-[12px] border border-dashed border-brand/25 bg-cloud/30 px-4 py-6 text-center hover:bg-cloud/60">
                            <x-ui.icon name="download" class="size-5 rotate-180 text-azure" />
                            <span class="text-[13px] font-bold text-brand">Choisir un fichier</span>
                            <span class="text-[11px] text-[#9AA6B8]">PDF, Word, Excel, PowerPoint, image, vidéo, audio — 50 Mo max</span>
                            <input type="file" wire:model="fichierUpload" data-test="fichier-doc" class="hidden" />
                        </label>
                        <span wire:loading wire:target="fichierUpload" class="mt-1 text-[11.5px] font-semibold text-azure">Envoi du fichier…</span>
                        <div class="mt-2 flex items-center gap-2"><span class="text-[11px] text-[#9AA6B8]">ou</span><input wire:model="lien" type="url" placeholder="Coller un lien externe (https://…)" class="{{ $input }}" /></div>
                    @endif
                    @error('fichierUpload') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $lab }}">Titre</label>
                    <input wire:model="title" type="text" class="{{ $input }}" />
                </div>
                <div>
                    <label class="{{ $lab }}">Catégorie</label>
                    <select wire:model="categoryId" class="{{ $input }}">
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $c) <option value="{{ $c['id'] }}">{{ $c['nom'] }}</option> @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Description (ce que contient le document, à qui il sert)</label>
                    <textarea wire:model="description" rows="2" class="{{ $input }}"></textarea>
                </div>
                <div>
                    <label class="{{ $lab }}">Qui peut l'ouvrir ?</label>
                    <select wire:model.live="acces" data-test="acces-doc" class="{{ $input }}">
                        @foreach ($listeAcces as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                </div>
                @if ($acces === 'groupe')
                    <div wire:key="champ-groupe">
                        <label class="{{ $lab }}">Groupe</label>
                        <select wire:model="groupId" class="{{ $input }}">
                            <option value="">— Choisir —</option>
                            @foreach ($groupes as $g) <option value="{{ $g['id'] }}">{{ $g['nom'] }}</option> @endforeach
                        </select>
                    </div>
                @endif
                <label class="flex items-start gap-2.5 rounded-[9px] border border-azure/20 bg-azure/[.05] px-3 py-2.5 text-sm text-ink/80 sm:col-span-2">
                    <input wire:model="notifier" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                    <span><span class="font-semibold text-brand">{{ $editingId ? 'Prévenir à nouveau les membres (nouvelle version)' : 'Prévenir les membres par notification' }}</span><span class="block text-[11.5px] text-[#9AA6B8]">Seuls les membres qui ont accès au document sont prévenus.</span></span>
                </label>
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save,fichierUpload" data-test="enregistrer-doc" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">{{ $editingId ? 'Enregistrer' : 'Publier' }}</button>
            </div>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Tous', 'en_attente' => 'Propositions à valider', 'publie' => 'Publiés', 'refuse' => 'Refusés'] as $cle => $libelle)
                <button wire:click="setFiltre('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $filtre === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }} <span class="{{ $filtre === $cle ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $compteurs[$cle] ?? 0 }}</span></button>
            @endforeach
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Rechercher…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-56" />
            <a href="{{ route('admin.export', ['dataset' => 'documents']) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-2 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Exporter</a>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($docs as $d)
                @php [$sl, $sc] = $statuts[$d['statut']] ?? ['?', '#9AA6B8']; @endphp
                <div wire:key="d-{{ $d['id'] }}" data-test="ligne-doc" class="border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:px-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent/10 text-accent"><x-ui.icon name="file-text" class="size-4" /></span>
                        <div class="min-w-[200px] flex-1">
                            <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-bold text-brand">{{ $d['title'] }}
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: {{ $sc }}1A; color: {{ $sc }}">{{ $sl }}</span>
                                @if ($d['acces'] !== 'tous')<span class="inline-flex items-center gap-1 rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[10px] font-bold text-[#B27007]"><x-ui.icon name="lock" class="size-2.5" /> {{ $d['acces_label'] }}</span>@endif
                            </p>
                            <p class="text-[11.5px] text-[#5B677A]">{{ collect([$d['category'], $d['type'], $d['taille'], $d['contributeur'] ? 'proposé par '.$d['contributeur'] : null])->filter()->join(' · ') }}{{ ! $d['a_fichier'] && ! $d['externe'] ? ' · ⚠ aucun fichier' : '' }}</p>
                            <p class="text-[11px] text-[#9AA6B8]">{{ $d['vues'] }} consultation{{ $d['vues'] > 1 ? 's' : '' }} · {{ $d['telechargements'] }} téléchargement{{ $d['telechargements'] > 1 ? 's' : '' }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if ($d['a_fichier'] || $d['externe'])
                                <a href="{{ route('espace-membre.documents.fichier', $d['id']) }}" target="_blank" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Ouvrir"><x-ui.icon name="eye" class="size-3.5" /></a>
                            @endif
                            @if ($d['statut'] === 'en_attente')
                                <button wire:click="publier({{ $d['id'] }})" data-test="publier-doc" class="btn-tap rounded-full bg-[#1C8F4C] px-3 py-1.5 text-[11.5px] font-bold text-white">Publier</button>
                                <button wire:click="ouvrirRefus({{ $d['id'] }})" class="btn-tap rounded-full border border-accent/30 px-3 py-1.5 text-[11.5px] font-bold text-accent">Refuser</button>
                            @endif
                            <button wire:click="openEdit({{ $d['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $d['id'] }})" wire:confirm="Supprimer « {{ $d['title'] }} » et son fichier ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>
                    @if ($d['description'])<p class="mt-1.5 pl-[52px] text-[12px] text-[#5B677A]">{{ $d['description'] }}</p>@endif
                    @if ($refusId === $d['id'])
                        <div wire:key="refus-{{ $d['id'] }}" class="panel-enter mt-2 rounded-[12px] border border-accent/20 bg-accent/[.03] p-3">
                            <textarea wire:model="motif" rows="2" placeholder="Motif transmis au membre (ex. : document déjà disponible, droits d'auteur non vérifiables…)" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm"></textarea>
                            @if ($erreur) <p class="mt-1 text-xs font-semibold text-accent">{{ $erreur }}</p> @endif
                            <div class="mt-2 flex gap-2">
                                <button wire:click="confirmerRefus" class="btn-tap rounded-[9px] bg-accent px-3.5 py-1.5 text-xs font-bold text-white">Refuser et prévenir</button>
                                <button wire:click="$set('refusId', null)" class="btn-tap rounded-[9px] border border-brand/15 px-3.5 py-1.5 text-xs font-bold text-brand">Annuler</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun document dans cette liste.</p>
            @endforelse
        </div>
    </div>
</div>
