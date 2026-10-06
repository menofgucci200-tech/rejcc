<div>
    <x-member-light.topbar title="Documents & ressources" />

    @php
        $icones = ['PDF' => 'file-text', 'Word' => 'file-text', 'Excel' => 'list-checks', 'PowerPoint' => 'layout-dashboard', 'Image' => 'image', 'Vidéo' => 'video', 'Audio' => 'play', 'Lien' => 'external-link'];
        $couleurs = ['PDF' => '#AC0100', 'Word' => '#2B579A', 'Excel' => '#1C7C45', 'PowerPoint' => '#C2410C', 'Image' => '#7C3AED', 'Vidéo' => '#031D59', 'Audio' => '#B27007', 'Lien' => '#4F6FBF'];
        $statuts = ['en_attente' => ['En attente de validation', 'bg-[#F5A623]/15 text-[#B27007]'], 'publie' => ['Publié', 'bg-[#22A85A]/10 text-[#1C8F4C]'], 'refuse' => ['Non retenu', 'bg-accent/10 text-accent']];
        $input = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] text-ink outline-none focus:border-azure';
        $lab = 'mb-1 block text-[12px] font-semibold text-brand';
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Documents &amp; ressources</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-xl text-xs text-[#5B677A]">Guides, modèles et ressources du réseau, à consulter directement sur la plateforme ou à télécharger.</p>
            </div>
            @if ($onglet !== 'personnels')
                @if ($peutProposer)
                    <button wire:click="ouvrirProposer" data-test="proposer-doc" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600"><x-ui.icon name="plus" class="size-3.5" /> Proposer un document</button>
                @else
                    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate data-test="proposer-verrouille" class="inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-[#5B677A] hover:bg-cloud"><x-ui.icon name="lock" class="size-3.5" /> Proposer un document (abonnés)</a>
                @endif
            @endif
        </div>

        <div class="mb-5 flex flex-wrap gap-2" role="tablist">
            <button wire:click="setOnglet('bibliotheque')" role="tab" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'bibliotheque' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Bibliothèque</button>
            @if ($propositions->isNotEmpty() || $onglet === 'propositions')
                <button wire:click="setOnglet('propositions')" role="tab" data-test="onglet-propositions" class="btn-tap inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'propositions' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                    Mes propositions <span class="{{ $onglet === 'propositions' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $propositions->count() }}</span>
                </button>
            @endif
        </div>

        @if ($message)
            <p wire:key="flash-ok" data-test="flash-doc" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showProposer)
            <p wire:key="flash-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
        @endif

        {{-- ══════════ Proposer un document ══════════ --}}
        @if ($showProposer)
            <div wire:key="form-proposer" data-test="form-proposer" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-start justify-between gap-3 sm:col-span-2">
                    <div>
                        <p class="text-sm font-bold text-brand">Proposer un document</p>
                        <p class="mt-0.5 text-[11.5px] text-[#5B677A]">Un guide, un modèle, une fiche utile aux membres ? L'équipe REJCC le relit puis le publie dans la bibliothèque avec votre nom.</p>
                    </div>
                    <button wire:click="fermerProposer" aria-label="Fermer" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Fichier</label>
                    @if ($pFichier && ! $errors->has('pFichier'))
                        <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-brand/10 bg-cloud/50 px-3 py-2.5">
                            <x-ui.icon name="file-text" class="size-4 text-azure" />
                            <span data-test="fichier-propose" class="min-w-0 flex-1 truncate text-[13px] font-semibold text-brand">{{ $pFichier->getClientOriginalName() }}</span>
                            <span class="text-[11.5px] text-[#9AA6B8]">{{ $pFichier->getSize() >= 1048576 ? number_format($pFichier->getSize() / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($pFichier->getSize() / 1024)).' Ko' }}</span>
                            <label class="cursor-pointer text-[12px] font-bold text-azure hover:underline">Remplacer<input type="file" wire:model="pFichier" class="hidden" /></label>
                        </div>
                    @else
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-[12px] border border-dashed border-brand/25 bg-cloud/30 px-4 py-6 text-center hover:bg-cloud/60">
                            <x-ui.icon name="download" class="size-5 rotate-180 text-azure" />
                            <span class="text-[13px] font-bold text-brand">Choisir un fichier</span>
                            <span class="text-[11px] text-[#9AA6B8]">PDF, Word, Excel, PowerPoint, image, audio ou vidéo MP4 — 20 Mo max</span>
                            <input type="file" wire:model="pFichier" data-test="fichier-proposition" class="hidden" />
                        </label>
                    @endif
                    <span wire:loading wire:target="pFichier" class="mt-1 text-[11.5px] font-semibold text-azure">Envoi du fichier…</span>
                    @error('pFichier') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $lab }}">Titre</label>
                    <input wire:model="pTitre" type="text" data-test="titre-proposition" class="{{ $input }}" placeholder="Ex. : Modèle de facture pour artisan" />
                    @error('pTitre') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $lab }}">Catégorie</label>
                    <select wire:model="pCategorie" data-test="categorie-proposition" class="{{ $input }}">
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $c) <option value="{{ $c['id'] }}">{{ $c['nom'] }}</option> @endforeach
                    </select>
                    @error('pCategorie') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Description (ce que contient le document, à qui il sert)</label>
                    <textarea wire:model="pDescription" rows="2" data-test="description-proposition" class="{{ $input }}"></textarea>
                    @error('pDescription') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
                </div>
                <p class="flex items-start gap-1.5 text-[11.5px] text-[#5B677A] sm:col-span-2"><x-ui.icon name="info" class="mt-px size-3.5 shrink-0 text-azure" /> Ne partagez que des documents dont vous avez les droits et qui ne contiennent pas de données personnelles.</p>
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
                <button wire:click="proposer" wire:loading.attr="disabled" wire:target="proposer,pFichier" data-test="envoyer-proposition" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Envoyer à l'équipe</button>
            </div>
        @endif

        @if ($onglet === 'propositions')
            {{-- ══════════ Mes propositions ══════════ --}}
            @if ($propositions->isEmpty())
                <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                    <x-ui.icon name="folder-open" class="mx-auto mb-3 size-9 text-[#9AA6B8]" />
                    <p class="text-sm font-bold text-brand">Vous n'avez encore proposé aucun document</p>
                </div>
            @else
                <div class="overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @foreach ($propositions as $d)
                        @php [$sl, $sc] = $statuts[$d['statut']] ?? [$d['statut'], 'bg-cloud text-brand']; @endphp
                        <div wire:key="prop-{{ $d['id'] }}" data-test="ligne-proposition" class="flex flex-wrap items-start gap-3 border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:px-5">
                            <button type="button" wire:click="ouvrir({{ $d['id'] }})" class="min-w-0 flex-1 text-left">
                                <p class="flex flex-wrap items-center gap-2 text-[13.5px] font-semibold text-brand">{{ $d['title'] }} <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold {{ $sc }}">{{ $sl }}</span></p>
                                <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ collect([$d['category'], $d['type'], $d['taille'], 'envoyé le '.\Illuminate\Support\Carbon::parse($d['created_at'])->locale('fr')->isoFormat('D MMMM YYYY')])->filter()->join(' · ') }}</p>
                                @if ($d['statut'] === 'publie')
                                    <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $d['vues'] }} consultation{{ $d['vues'] > 1 ? 's' : '' }} · {{ $d['telechargements'] }} téléchargement{{ $d['telechargements'] > 1 ? 's' : '' }}</p>
                                @endif
                                @if ($d['statut'] === 'refuse' && $d['motif'])
                                    <p class="mt-1.5 rounded-[9px] bg-accent/[.06] px-3 py-2 text-[12px] text-ink"><span class="font-semibold text-accent">Motif :</span> {{ $d['motif'] }}</p>
                                @endif
                            </button>
                            @if ($d['statut'] !== 'publie')
                                <button wire:click="retirer({{ $d['id'] }})" wire:confirm="Retirer « {{ $d['title'] }} » ?" data-test="retirer-proposition" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-[#5B677A] hover:bg-cloud">Retirer</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- ══════════ Bibliothèque ══════════ --}}
            <div class="mb-4 flex flex-col gap-3 rounded-[16px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:flex-row sm:items-center">
                <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Rechercher un document…" data-test="recherche-docs" class="w-full rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure sm:max-w-sm" />
                <select wire:model.live="tri" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none sm:ml-auto">
                    <option value="recents">Plus récents</option>
                    <option value="titre">Par titre (A → Z)</option>
                    <option value="populaires">Plus téléchargés</option>
                </select>
            </div>
            @if ($groupe !== '')
                <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-azure/10 px-3 py-1.5 text-xs font-bold text-brand">
                    Documents du groupe {{ $groupeNom ? '« '.$groupeNom.' »' : '' }}
                    <button wire:click="retirerGroupe" aria-label="Voir tous les documents" class="text-[#5B677A] hover:text-accent"><x-ui.icon name="x" class="size-3.5" /></button>
                </p>
            @endif
            <div class="mb-5 flex flex-wrap gap-1.5">
                <button wire:click="setCategorie('')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === '' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Toutes</button>
                @foreach ($categories->where('nombre', '>', 0) as $c)
                    <button wire:click="setCategorie('{{ $c['id'] }}')" wire:key="cat-{{ $c['id'] }}" data-test="categorie-doc" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === (string) $c['id'] ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                        {{ $c['nom'] }} <span class="{{ $categorie === (string) $c['id'] ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $c['nombre'] }}</span>
                    </button>
                @endforeach
            </div>

            @if ($docs->isEmpty())
                <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                    <x-ui.icon name="folder-open" class="mx-auto mb-3 size-9 text-[#9AA6B8]" />
                    <p class="text-sm font-bold text-brand">{{ $filtresActifs ? 'Aucun document ne correspond à votre recherche' : 'Aucun document disponible pour le moment' }}</p>
                </div>
            @else
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(min(300px, 100%), 1fr))">
                    @foreach ($docs as $d)
                        @php $tc = $couleurs[$d['type']] ?? '#4F6FBF'; @endphp
                        <button type="button" wire:click="ouvrir({{ $d['id'] }})" wire:key="doc-{{ $d['id'] }}" data-test="carte-doc"
                            class="card-hover flex items-start gap-3.5 rounded-2xl border border-brand/10 bg-white px-[18px] py-4 text-left shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $d['verrouille'] ? 'opacity-80' : '' }}">
                            <span class="relative flex size-[44px] shrink-0 items-center justify-center rounded-[11px]" style="background: {{ $tc }}14; color: {{ $tc }}">
                                <x-ui.icon :name="$icones[$d['type']] ?? 'file-text'" class="size-[19px]" />
                                @if ($d['verrouille'])<span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-[#F5A623] text-white ring-2 ring-white"><x-ui.icon name="lock" class="size-2.5" /></span>@endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-semibold text-brand">
                                    <span class="min-w-0">{{ $d['title'] }}</span>
                                    @if ($d['nouveau'])<span class="rounded-full bg-accent px-1.5 text-[9.5px] font-bold uppercase leading-4 text-white">Nouveau</span>@endif
                                </p>
                                @if ($d['description'])<p class="mt-0.5 line-clamp-2 text-xs text-[#5B677A]">{{ $d['description'] }}</p>@endif
                                <p class="mt-1.5 text-[11px] text-[#9AA6B8]">{{ collect([$d['category'], ($d['disponible'] ?? true) ? $d['type'] : 'Bientôt disponible', $d['taille'], $d['verrouille'] ? $d['acces_label'] : null, $d['contributeur'] ? 'par '.$d['contributeur'] : null])->filter()->join(' · ') }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <x-documents.visionneuse :doc="$doc" />
</div>
