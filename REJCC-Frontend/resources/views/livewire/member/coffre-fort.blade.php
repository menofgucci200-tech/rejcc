<div>
    @php
        $input = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] text-ink outline-none focus:border-azure';
        $lab = 'mb-1 block text-[12px] font-semibold text-brand';
        $iconesType = ['cni' => 'id-card', 'passeport' => 'globe', 'extrait' => 'file-text', 'casier' => 'shield-check', 'residence' => 'home', 'diplome' => 'graduation-cap', 'cv' => 'user', 'attestation' => 'award', 'autre' => 'folder-open'];
        $etats = ['expire' => ['Expiré', 'bg-accent/10 text-accent'], 'bientot' => ['Expire bientôt', 'bg-[#F5A623]/15 text-[#B27007]'], 'valide' => ['Valide', 'bg-[#22A85A]/10 text-[#1C8F4C]']];
        $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->locale('fr')->isoFormat('D MMM YYYY') : null;
    @endphp

    <div class="mb-5 flex flex-col gap-3 rounded-[16px] border border-brand/10 bg-gradient-to-br from-brand to-[#0A2F7A] p-5 text-white sm:flex-row sm:items-center">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px] bg-white/10"><x-ui.icon name="lock" class="size-5" /></span>
        <div class="min-w-0 flex-1">
            <p class="text-[14px] font-bold">Votre espace privé</p>
            <p class="mt-0.5 text-[12px] leading-relaxed text-white/75">Vous seul voyez ces documents : ils sont chiffrés et ne sont visibles de personne d'autre, ni des membres ni de l'équipe — sauf si vous choisissez de partager une pièce avec l'équipe REJCC (dossier de financement, vérification…).</p>
        </div>
        <button wire:click="ouvrirAjout" data-test="ajouter-perso" class="btn-tap inline-flex shrink-0 items-center justify-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="plus" class="size-3.5" /> Ajouter un document</button>
    </div>

    @if ($message)
        <p wire:key="perso-ok" data-test="flash-perso" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
    @endif
    @if ($erreur && ! $showForm)
        <p wire:key="perso-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
    @endif

    @foreach ($alertes as $a)
        <p wire:key="alerte-{{ $a['id'] }}" data-test="alerte-expiration" class="mb-2 flex flex-wrap items-center gap-2 rounded-[12px] px-3.5 py-2.5 text-xs font-semibold {{ $a['etat'] === 'expire' ? 'bg-accent/10 text-accent' : 'bg-[#F5A623]/12 text-[#8A5A00]' }}">
            <x-ui.icon name="alert-circle" class="size-3.5 shrink-0" />
            {{ $a['libelle'] }} {{ $a['etat'] === 'expire' ? 'a expiré le' : 'expire le' }} {{ $date($a['expire_le']) }}.
            <button wire:click="modifier({{ $a['id'] }})" class="underline underline-offset-2">Mettre à jour</button>
        </p>
    @endforeach

    {{-- ══════════ Formulaire ══════════ --}}
    @if ($showForm)
        <div wire:key="form-perso" data-test="form-perso" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
            <div class="flex items-center justify-between sm:col-span-2">
                <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le document' : 'Ajouter un document personnel' }}</p>
                <button wire:click="fermer" aria-label="Fermer" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
            </div>
            <div>
                <label class="{{ $lab }}">Type de document</label>
                <select wire:model.live="type" data-test="type-perso" class="{{ $input }}">
                    <option value="">— Choisir —</option>
                    @foreach ($types as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                </select>
                @error('type') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="{{ $lab }}">{{ $type === 'autre' ? 'Nom du document' : 'Précision (facultatif)' }}</label>
                <input wire:model="titre" type="text" data-test="titre-perso" class="{{ $input }}" placeholder="{{ $type === 'diplome' ? 'Ex. : Licence en gestion' : ($type === 'autre' ? 'Ex. : Permis de conduire' : 'Ex. : recto-verso') }}" />
                @error('titre') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lab }}">Fichier {{ $editingId ? '(laisser vide pour garder le fichier actuel)' : '' }}</label>
                @if ($fichier && ! $errors->has('fichier'))
                    <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-brand/10 bg-cloud/50 px-3 py-2.5">
                        <x-ui.icon name="file-text" class="size-4 text-azure" />
                        <span data-test="fichier-perso-choisi" class="min-w-0 flex-1 truncate text-[13px] font-semibold text-brand">{{ $fichier->getClientOriginalName() }}</span>
                        <label class="cursor-pointer text-[12px] font-bold text-azure hover:underline">Remplacer<input type="file" wire:model="fichier" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*" class="hidden" /></label>
                    </div>
                @else
                    @if ($fichierActuel)
                        <p class="mb-2 text-[12px] text-[#5B677A]">Fichier actuel : <span class="font-semibold text-brand">{{ $fichierActuel }}</span></p>
                    @endif
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-[12px] border border-dashed border-brand/25 bg-cloud/30 px-4 py-5 text-center hover:bg-cloud/60">
                        <x-ui.icon name="download" class="size-5 rotate-180 text-azure" />
                        <span class="text-[13px] font-bold text-brand">{{ $fichierActuel ? 'Choisir un nouveau fichier' : 'Choisir un fichier ou prendre une photo' }}</span>
                        <span class="text-[11px] text-[#9AA6B8]">PDF, photo (JPG, PNG, WEBP) ou Word — 10 Mo max</span>
                        <input type="file" wire:model="fichier" data-test="fichier-perso" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*" class="hidden" />
                    </label>
                @endif
                <span wire:loading wire:target="fichier" class="mt-1 text-[11.5px] font-semibold text-azure">Envoi du fichier…</span>
                @error('fichier') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="{{ $lab }}">Délivré le (facultatif)</label>
                <input wire:model.live="delivreLe" type="date" max="{{ now()->toDateString() }}" class="{{ $input }}" />
                @error('delivreLe') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="{{ $lab }}">Expire le (facultatif)</label>
                <input wire:model="expireLe" type="date" data-test="expire-perso" class="{{ $input }}" />
                <span class="mt-1 block text-[11px] text-[#9AA6B8]">Vous serez prévenu 30 jours avant.</span>
                @error('expireLe') <span class="mt-1 block text-xs font-semibold text-accent">{{ $message }}</span> @enderror
            </div>
            <label class="flex items-start gap-2.5 rounded-[9px] border border-azure/20 bg-azure/[.05] px-3 py-2.5 text-sm text-ink/80 sm:col-span-2">
                <input wire:model="partage" type="checkbox" data-test="partage-perso" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                <span><span class="font-semibold text-brand">Partager ce document avec l'équipe REJCC</span><span class="block text-[11.5px] text-[#5B677A]">Utile pour un dossier de financement, une candidature ou une vérification. Vous pouvez retirer le partage à tout moment ; chaque consultation par l'équipe vous est indiquée.</span></span>
            </label>
            @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
            <button wire:click="enregistrer" wire:loading.attr="disabled" wire:target="enregistrer,fichier" data-test="enregistrer-perso" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">{{ $editingId ? 'Enregistrer' : 'Ajouter' }}</button>
        </div>
    @endif

    {{-- ══════════ Liste ══════════ --}}
    @if ($docs->isEmpty() && ! $showForm)
        <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-10 text-center">
            <x-ui.icon name="folder-open" class="mx-auto mb-3 size-9 text-[#9AA6B8]" />
            <p class="text-sm font-bold text-brand">Rangez vos papiers importants au même endroit</p>
            <p class="mx-auto mt-1 max-w-md text-xs text-[#5B677A]">Pièce d'identité, passeport, extrait de naissance, diplômes… toujours à portée de main, même depuis votre téléphone.</p>
        </div>
    @elseif ($docs->isNotEmpty())
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(min(300px, 100%), 1fr))">
            @foreach ($docs as $d)
                <div wire:key="perso-{{ $d['id'] }}" data-test="carte-perso" class="flex flex-col rounded-2xl border border-brand/10 bg-white px-[18px] py-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <button type="button" wire:click="voir({{ $d['id'] }})" class="flex items-start gap-3.5 text-left">
                        <span class="flex size-[44px] shrink-0 items-center justify-center rounded-[11px] bg-brand/[.06] text-brand"><x-ui.icon :name="$iconesType[$d['type']] ?? 'file-text'" class="size-[19px]" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13.5px] font-semibold text-brand">{{ $d['libelle'] }}</p>
                            @if ($d['titre'] && $d['type'] !== 'autre')<p class="text-[11.5px] text-[#5B677A]">{{ $d['type_label'] }}</p>@endif
                            <p class="mt-1 truncate text-[11px] text-[#9AA6B8]">{{ collect([$d['fichier_nom'], $d['taille']])->filter()->join(' · ') }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if ($d['etat'])
                                    @php [$el, $ec] = $etats[$d['etat']]; @endphp
                                    <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold {{ $ec }}">{{ $el }}{{ $d['expire_le'] ? ' · '.$date($d['expire_le']) : '' }}</span>
                                @endif
                                @if ($d['partage'])
                                    <span data-test="badge-partage" class="rounded-full bg-azure/10 px-2 py-0.5 text-[10.5px] font-bold text-azure">Partagé avec l'équipe{{ $d['consulte_equipe_at'] ? ' · consulté le '.$date($d['consulte_equipe_at']) : '' }}</span>
                                @endif
                            </div>
                        </div>
                    </button>
                    <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] pt-2.5 text-[12px] font-bold">
                        <button wire:click="voir({{ $d['id'] }})" class="text-brand hover:text-azure">Voir</button>
                        <a href="{{ route('espace-membre.mes-documents.fichier', ['id' => $d['id'], 'telecharger' => 1]) }}" class="text-brand hover:text-azure">Télécharger</a>
                        <button wire:click="modifier({{ $d['id'] }})" class="text-brand hover:text-azure">Modifier</button>
                        <button wire:click="supprimer({{ $d['id'] }})" wire:confirm="Supprimer définitivement « {{ $d['libelle'] }} » ?" data-test="supprimer-perso" class="ml-auto text-[#9AA6B8] hover:text-accent">Supprimer</button>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-[11px] text-[#9AA6B8]">{{ $docs->count() }} / {{ $max }} documents</p>
    @endif

    @if ($manquants->isNotEmpty())
        <div class="mt-5">
            <p class="mb-2 text-[11.5px] font-bold uppercase tracking-[0.05em] text-[#5B677A]">Pièces souvent demandées</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($manquants as $k => $v)
                    <button wire:click="ouvrirAjout('{{ $k }}')" wire:key="manque-{{ $k }}" class="btn-tap inline-flex items-center gap-1 rounded-full border border-dashed border-brand/25 bg-white px-3 py-1.5 text-xs font-semibold text-brand hover:bg-cloud"><x-ui.icon name="plus" class="size-3" /> {{ $v }}</button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════ Visionneuse privée ══════════ --}}
    @if ($voir)
        @php
            $nomUrl = (\Illuminate\Support\Str::slug($voir['libelle']) ?: 'document').($voir['apercu'] === 'pdf' ? '.pdf' : '');
            $src = route('espace-membre.mes-documents.fichier', ['id' => $voir['id'], 'nom' => $nomUrl]);
        @endphp
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/50 p-3 sm:p-6" wire:click.self="voir(null)" x-on:keydown.escape.window="$wire.voir(null)">
            <div data-test="visionneuse-perso" role="dialog" aria-modal="true" class="panel-enter flex h-full max-h-[94vh] w-full max-w-[1000px] flex-col overflow-hidden rounded-[18px] bg-white shadow-2xl">
                <div class="flex shrink-0 items-center gap-3 border-b border-[#EDF0F5] px-4 py-3 sm:px-5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-brand/[.06] text-brand"><x-ui.icon name="lock" class="size-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-bold text-brand">{{ $voir['libelle'] }}</p>
                        <p class="text-[11.5px] text-[#5B677A]">{{ collect([$voir['fichier_nom'], $voir['delivre_le'] ? 'délivré le '.$date($voir['delivre_le']) : null, $voir['expire_le'] ? 'expire le '.$date($voir['expire_le']) : null])->filter()->join(' · ') }}</p>
                    </div>
                    <a href="{{ route('espace-membre.mes-documents.fichier', ['id' => $voir['id'], 'telecharger' => 1]) }}" class="btn-tap hidden shrink-0 items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 sm:inline-flex"><x-ui.icon name="download" class="size-3.5" /> Télécharger</a>
                    <button type="button" wire:click="voir(null)" aria-label="Fermer" class="flex size-9 shrink-0 items-center justify-center rounded-full text-brand hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                </div>
                <div class="relative min-h-0 flex-1 bg-[#F4F6FA]">
                    @if ($voir['apercu'] === 'pdf')
                        <iframe src="{{ $src }}#view=FitH" title="{{ $voir['libelle'] }}" data-test="apercu-perso" class="h-full w-full border-0"></iframe>
                    @elseif ($voir['apercu'] === 'image')
                        <div class="flex h-full items-center justify-center overflow-auto p-4"><img src="{{ $src }}" alt="{{ $voir['libelle'] }}" data-test="apercu-perso" class="max-h-full max-w-full rounded-lg object-contain shadow"></div>
                    @else
                        <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                            <p class="text-[13px] text-[#5B677A]">L'aperçu n'est pas disponible pour ce format.</p>
                            <a href="{{ route('espace-membre.mes-documents.fichier', ['id' => $voir['id'], 'telecharger' => 1]) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white"><x-ui.icon name="download" class="size-4" /> Télécharger</a>
                        </div>
                    @endif
                </div>
                <a href="{{ route('espace-membre.mes-documents.fichier', ['id' => $voir['id'], 'telecharger' => 1]) }}" class="flex shrink-0 items-center justify-center gap-1.5 border-t border-[#EDF0F5] py-3 text-[13px] font-bold text-brand sm:hidden"><x-ui.icon name="download" class="size-4" /> Télécharger</a>
            </div>
        </div>
    @endif
</div>
