<div>
    <x-admin-light.topbar title="Boîte à outils de marque" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $carte = 'rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]';
        // Repères clés tirés de la charte graphique officielle du REJCC.
        $couleurs = [
            ['#031D59', 'Bleu REJCC', 'Couleur dominante', 'text-white'],
            ['#AC0100', 'Rouge REJCC', "Couleur d'accent", 'text-white'],
            ['#F4F6F8', 'Gris clair', 'Couleur neutre (fonds)', 'text-brand'],
            ['#4F6FBF', 'Azur', 'Palette secondaire', 'text-white'],
            ['#333333', 'Anthracite', 'Palette secondaire (texte)', 'text-white'],
        ];
        $polices = [
            ['Impact', 'Anton', 'font-display text-[26px] uppercase tracking-wide', 'Attirer l\'attention : grands titres, affiches, campagnes, événements, réseaux sociaux.', 'Niveau 1'],
            ['Avenir Next', 'Manrope', 'font-sans text-[22px] font-bold', 'Structurer les messages : textes, sous-titres, documents, présentations, supports institutionnels.', 'Niveaux 2 à 4 (Bold, Demi Bold, Regular)'],
            ['Big Caslon', 'Libre Caslon', 'font-serif text-[24px] italic', 'Apporter de l\'élégance : citations, accroches, couvertures, éléments de contraste.', 'Niveau 5'],
        ];
        $tailles = [['Logo principal', '30 mm', '150 px'], ['Logo secondaire (monogramme)', '20 mm', '100 px'], ['Signature typographique', '25 mm', '120 px']];
    @endphp

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <x-admin-light.onglets groupe="ressources" />
        {{-- ── Repères de la charte ───────────────────────────────────── --}}
        <div class="mb-4">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Repères de la charte</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">L'essentiel de l'identité visuelle du REJCC, à garder sous la main. Le détail est dans la charte graphique, plus bas.</p>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-[1.25fr_1fr]">
            <div class="{{ $carte }}">
                <p class="mb-3 text-[13px] font-bold text-brand">Couleurs</p>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                    @foreach ($couleurs as [$hex, $nom, $role, $texte])
                        <button type="button" x-data="{ copie: false }" data-test="couleur"
                            @click="navigator.clipboard.writeText('{{ $hex }}'); copie = true; setTimeout(() => copie = false, 1500)"
                            class="btn-tap group overflow-hidden rounded-[12px] border border-brand/10 text-left hover:shadow-md" title="Copier {{ $hex }}">
                            <span class="flex h-16 items-end p-2 {{ $texte }}" style="background: {{ $hex }}">
                                <span class="text-[10.5px] font-bold opacity-0 transition group-hover:opacity-100" x-text="copie ? 'Copié !' : 'Copier'"></span>
                            </span>
                            <span class="block px-2.5 py-2">
                                <span class="block font-mono text-[12.5px] font-bold text-brand">{{ $hex }}</span>
                                <span class="block text-[11.5px] font-semibold text-ink">{{ $nom }}</span>
                                <span class="block text-[10.5px] leading-tight text-[#5B677A]">{{ $role }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-4 rounded-[12px] bg-brand px-4 py-3.5 text-white">
                    <p class="text-[10.5px] font-semibold uppercase tracking-[.14em] text-white/60">Signature institutionnelle</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <p class="font-serif text-[22px] italic">Ensemble pour l'excellence.</p>
                        <button type="button" x-data="{ copie: false }" @click="navigator.clipboard.writeText('Ensemble pour l\'excellence.'); copie = true; setTimeout(() => copie = false, 1500)"
                            class="btn-tap ml-auto rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold hover:bg-white/25" x-text="copie ? 'Copié !' : 'Copier'">Copier</button>
                    </div>
                </div>
            </div>

            <div class="{{ $carte }}">
                <p class="mb-3 text-[13px] font-bold text-brand">Logo : tailles minimales</p>
                <table class="w-full text-[12.5px]">
                    <thead><tr class="text-left text-[11px] text-[#5B677A]"><th class="pb-1.5 font-semibold">Version</th><th class="pb-1.5 font-semibold">Impression</th><th class="pb-1.5 font-semibold">Web</th></tr></thead>
                    <tbody>
                        @foreach ($tailles as [$v, $mm, $px])
                            <tr class="border-t border-[#EDF0F5]"><td class="py-1.5 font-semibold text-brand">{{ $v }}</td><td class="py-1.5">{{ $mm }}</td><td class="py-1.5">{{ $px }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-3 text-[11.5px] text-[#5B677A]"><span class="font-semibold text-brand">Zone de protection :</span> le double de la hauteur de la lettre R du mot REJCC. Aucun élément ne doit y pénétrer.</p>
                <p class="mt-2 text-[11.5px] text-[#5B677A]"><span class="font-semibold text-brand">Fonds autorisés :</span> blanc, bleu REJCC, rouge REJCC, fond sombre, photographie lisible. <span class="font-semibold text-accent">À éviter :</span> fonds surchargés, motifs complexes, dégradés.</p>
                <p class="mt-2 text-[11.5px] text-[#5B677A]"><span class="font-semibold text-accent">Interdit :</span> déformer, pivoter, recolorer le logo, lui ajouter effets ou dégradés, utiliser une version basse résolution.</p>
            </div>
        </div>

        <div class="mb-5 {{ $carte }}">
            <p class="mb-3 text-[13px] font-bold text-brand">Polices officielles et équivalents web</p>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($polices as [$officielle, $web, $style, $usage, $niveau])
                    <div class="rounded-[12px] border border-brand/10 bg-cloud/40 p-3.5">
                        <p class="{{ $style }} leading-none text-brand">Aa REJCC</p>
                        <p class="mt-2 text-[12.5px] font-bold text-brand">{{ $officielle }} <span class="font-semibold text-[#5B677A]">→ {{ $web }}</span></p>
                        <p class="text-[10.5px] font-semibold uppercase tracking-wide text-azure">{{ $niveau }}</p>
                        <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $usage }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-[11.5px] text-[#5B677A]">Les équivalents web (gratuits, Google Fonts) sont ceux du site et du générateur de visuels : utilisez-les dans Canva quand la police officielle n'est pas disponible.</p>
        </div>

        <div class="mb-8 {{ $carte }}">
            <p class="mb-3 text-[13px] font-bold text-brand">Logos officiels</p>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ($logos as [$libelle, $fichierLogo, $fond])
                    <div class="overflow-hidden rounded-[12px] border border-brand/10">
                        <div class="flex h-24 items-center justify-center p-4 {{ $fond }}"><img src="{{ asset('brand/'.$fichierLogo.'.svg') }}" alt="{{ $libelle }}" class="max-h-14 max-w-full"></div>
                        <div class="flex items-center gap-2 px-3 py-2">
                            <span class="flex-1 text-[11.5px] font-semibold text-brand">{{ $libelle }}</span>
                            <a href="{{ asset('brand/'.$fichierLogo.'.svg') }}" download class="text-[11px] font-bold text-azure hover:underline">SVG</a>
                            <a href="{{ asset('brand/'.$fichierLogo.'.png') }}" download class="text-[11px] font-bold text-azure hover:underline">PNG</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Documents de marque ─────────────────────────────────────── --}}
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Documents de marque</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Identité, charte, logos, gabarits Canva ou Photoshop, polices… Réservés à l'équipe Communication : les fichiers sont privés et ne sont jamais publiés sur le site.</p>
            </div>
            <button wire:click="openCreate" data-test="ajouter-fichier" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Ajouter un document</button>
        </div>

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm)
            <p wire:key="flash-ko" class="panel-enter mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if ($showForm)
            <div wire:key="form-fichier" data-test="form-fichier" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le document' : 'Nouveau document de marque' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Fichier</label>
                    @if ($fichier)
                        <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-brand/10 bg-cloud/50 px-3 py-2.5">
                            <x-ui.icon name="file-text" class="size-4 text-azure" />
                            <span data-test="fichier-choisi" class="min-w-0 flex-1 truncate text-[13px] font-semibold text-brand">{{ $fichier['nom'] }}</span>
                            @if ($fichier['octets'] ?? null)<span class="text-[11.5px] text-[#5B677A]">{{ $fichier['octets'] >= 1048576 ? number_format($fichier['octets'] / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($fichier['octets'] / 1024)).' Ko' }}</span>@endif
                            <label class="cursor-pointer text-[12px] font-bold text-azure hover:underline">Remplacer<input type="file" wire:model="fichierUpload" class="hidden" /></label>
                            <button type="button" wire:click="retirerFichier" class="text-[12px] font-semibold text-[#5B677A] hover:text-accent">Retirer</button>
                        </div>
                    @else
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-[12px] border border-dashed border-brand/25 bg-cloud/30 px-4 py-6 text-center hover:bg-cloud/60">
                            <x-ui.icon name="download" class="size-5 rotate-180 text-azure" />
                            <span class="text-[13px] font-bold text-brand">Choisir un fichier</span>
                            <span class="text-[11px] text-[#5B677A]">PDF, image, SVG, AI, EPS, PSD, Word, PowerPoint, police, ZIP — 30 Mo max</span>
                            <input type="file" wire:model="fichierUpload" data-test="fichier-input" class="hidden" />
                        </label>
                        <div class="mt-2 flex items-center gap-2"><span class="shrink-0 text-[11px] text-[#5B677A]">ou</span><input wire:model="lien" type="url" data-test="lien-fichier" placeholder="Coller un lien (gabarit Canva, dossier partagé : https://…)" class="{{ $input }}" /></div>
                    @endif
                    <span wire:loading wire:target="fichierUpload" class="mt-1 text-[11.5px] font-semibold text-azure">Envoi du fichier…</span>
                    @error('fichierUpload') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $lab }}">Titre</label>
                    <input wire:model="titre" type="text" data-test="titre-fichier" maxlength="150" class="{{ $input }}" />
                </div>
                <div>
                    <label class="{{ $lab }}">Catégorie</label>
                    <select wire:model="categorieForm" data-test="categorie-fichier" class="{{ $input }}">
                        @foreach ($categories as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lab }}">Description (facultatif : version, usage, consignes)</label>
                    <textarea wire:model="description" rows="2" class="{{ $input }}"></textarea>
                </div>
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save,fichierUpload" data-test="enregistrer-fichier" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Tous'] + $categories as $cle => $libelle)
                <button wire:click="setCategorie('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }} <span class="{{ $categorie === $cle ? 'text-white/70' : 'text-[#5B677A]' }}">{{ $compteurs[$cle] ?? 0 }}</span></button>
            @endforeach
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Rechercher…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-56" />
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($fichiers as $f)
                <div wire:key="f-{{ $f['id'] }}" data-test="ligne-fichier" class="flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:px-5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-azure/10 text-[10px] font-extrabold text-azure">
                        @if (! $f['a_fichier']) <x-ui.icon name="external-link" class="size-4" /> @else {{ $f['extension'] ?: 'DOC' }} @endif
                    </span>
                    <div class="min-w-[200px] flex-1">
                        <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-bold text-brand">{{ $f['titre'] }}
                            <span class="rounded-full bg-cloud px-2 py-0.5 text-[10px] font-bold text-[#5B677A]">{{ $f['categorie_label'] }}</span>
                        </p>
                        <p class="text-[11.5px] text-[#5B677A]">{{ collect([$f['a_fichier'] ? $f['fichier_nom'] : 'Lien externe', $f['taille'], $f['auteur'] ? 'ajouté par '.$f['auteur'] : null, \Illuminate\Support\Carbon::parse($f['updated_at'])->translatedFormat('j M Y'), $f['telechargements'] ? $f['telechargements'].' téléchargement'.($f['telechargements'] > 1 ? 's' : '') : null])->filter()->join(' · ') }}</p>
                        @if ($f['description'])<p class="mt-0.5 text-[12px] text-ink/80">{{ $f['description'] }}</p>@endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        @if ($f['a_fichier'])
                            <a href="{{ route('admin.marque.fichier', $f['id']) }}" target="_blank" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="Aperçu"><x-ui.icon name="eye" class="size-3.5" /></a>
                            <a href="{{ route('admin.marque.fichier', $f['id']) }}?telecharger=1" data-test="telecharger-fichier" class="btn-tap inline-flex items-center gap-1 rounded-full bg-brand px-3 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-3" /> Télécharger</a>
                        @else
                            <a href="{{ route('admin.marque.fichier', $f['id']) }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1 rounded-full bg-brand px-3 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="external-link" class="size-3" /> Ouvrir</a>
                        @endif
                        @if ($f['modifiable'])
                            <button wire:click="openEdit({{ $f['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $f['id'] }})" wire:confirm="Supprimer « {{ $f['titre'] }} » et son fichier ?" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">{{ $recherche || $categorie ? 'Aucun document ne correspond.' : 'Aucun document pour l\'instant : ajoutez la charte graphique et le guide d\'identité de marque.' }}</p>
            @endforelse
        </div>
    </div>
</div>
