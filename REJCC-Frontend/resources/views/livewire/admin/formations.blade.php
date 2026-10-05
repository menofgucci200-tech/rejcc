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
                <div class="sm:col-span-2 rounded-[12px] border border-brand/10 p-4" data-test="editeur-examen">
                    <div class="mb-3 flex flex-wrap items-center gap-3">
                        <p class="text-[13px] font-bold text-brand">Examen final de certification</p>
                        <label class="ml-auto inline-flex items-center gap-2 text-xs font-semibold text-[#5B677A]">Seuil de réussite (quiz et examen)
                            <input wire:model="seuilReussite" type="number" min="50" max="100" class="w-16 rounded-[8px] border border-brand/15 px-2 py-1 text-sm outline-none focus:border-azure" /> %
                        </label>
                    </div>
                    <p class="mb-3 text-[11.5px] text-[#9AA6B8]">Facultatif. S'il est défini, le membre le passe sur la plateforme une fois tous les modules validés ; la formation n'est terminée (et le certificat délivré) qu'après réussite. 3 essais, puis pause de 24 h.</p>
                    <x-admin-light.quiz-editor champ="examen" :questions="$examen" label="Questions de l'examen" :max="40" />
                </div>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($formations as $f)
                <div class="-mx-5 border-t border-[#EDF0F5] px-5 first:border-t-0">
                    <div class="row-hover flex flex-wrap items-center gap-4 py-3.5">
                        <div class="h-11 w-[60px] shrink-0 rounded-[9px]" style="background: {{ $f['visuel'] }}"></div>
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $f['titre'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $f['categorie'] }} · {{ $f['duree'] }}</p>
                            @if ($f['sansContenu'])
                                <p data-test="alerte-sans-contenu" class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[10.5px] font-bold text-[#8A5A00]">
                                    <x-ui.icon name="alert-circle" class="size-3" /> Aucun module : les membres voient « contenu en préparation »{{ $f['certifiante'] ? ', aucun certificat délivré' : '' }}
                                </p>
                            @endif
                        </div>
                        <span class="w-[110px] shrink-0 text-xs text-[#5B677A]">{{ $f['inscrits'] }} {{ $f['inscrits'] > 1 ? 'inscrits' : 'inscrit' }}</span>
                        <span class="w-20 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $f['publiee'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $f['publiee'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $f['publiee'] ? 'Publiée' : 'Brouillon' }}</span>
                        <button wire:click="togglePublication({{ $f['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $f['publiee'] ? 'Dépublier' : 'Publier' }}</button>
                        <button wire:click="toggleModules({{ $f['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $modulesFormationId === $f['id'] ? 'Fermer' : 'Modules' }}</button>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button wire:click="openEdit({{ $f['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand">
                                <x-ui.icon name="pencil" class="size-3.5" />
                            </button>
                            <button wire:click="delete({{ $f['id'] }})" wire:confirm="Supprimer « {{ $f['titre'] }} » et toutes ses inscriptions ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                                <x-ui.icon name="trash-2" class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Panneau de gestion des modules --}}
                    @if ($modulesFormationId === $f['id'])
                        <div class="panel-enter mb-4 rounded-[14px] bg-cloud/50 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-[12.5px] font-bold text-brand">Modules de « {{ $f['titre'] }} »</p>
                                <button wire:click="openModuleCreate" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90">+ Module</button>
                            </div>

                            @if ($moduleFormOpen)
                                <div class="mb-3 grid grid-cols-1 gap-3 rounded-[12px] border border-brand/10 bg-white p-4 sm:grid-cols-2">
                                    <div class="flex items-center justify-between sm:col-span-2">
                                        <p class="text-[12.5px] font-bold text-brand">{{ $moduleEditingId ? 'Modifier le module' : 'Nouveau module' }}</p>
                                        <button wire:click="closeModuleForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-3.5 text-[#5B677A]" /></button>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre du module</label>
                                        <input wire:model="moduleTitre" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                        @error('moduleTitre') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description / contenu</label>
                                        <textarea wire:model="moduleDescription" rows="2" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Leçon (texte suivi sur la plateforme)</label>
                                        <textarea wire:model="moduleContenu" rows="8" data-test="module-contenu" placeholder="## Titre de partie&#10;Votre texte…&#10;- point clé&#10;- autre point" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 font-mono text-[12.5px] outline-none focus:border-azure"></textarea>
                                        <p class="mt-1 text-[10.5px] text-[#9AA6B8]">Mise en forme : « ## » pour un titre, « - » pour une liste, **gras**, *italique*, [lien](https://…).</p>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Vidéo — lien YouTube/Vimeo ou fichier</label>
                                        <input wire:model="moduleVideoUrl" type="text" placeholder="https://www.youtube.com/watch?v=…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                        <label class="mt-1.5 inline-flex cursor-pointer items-center gap-1.5 text-[11.5px] font-semibold text-azure hover:underline">
                                            <x-ui.icon name="video" class="size-3.5" /> Ou envoyer un fichier vidéo (MP4, 100 Mo max)
                                            <input type="file" wire:model="moduleVideoFile" accept="video/mp4,video/webm,video/quicktime" class="hidden">
                                        </label>
                                        <span wire:loading wire:target="moduleVideoFile" class="ml-1 text-[11px] font-semibold text-azure">Envoi…</span>
                                        @error('moduleVideoUrl') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        @error('moduleVideoFile') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Support du module (PDF consultable en ligne)</label>
                                        <input wire:model="moduleDocumentUrl" type="text" placeholder="https://…/support.pdf" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                        <label class="mt-1.5 inline-flex cursor-pointer items-center gap-1.5 text-[11.5px] font-semibold text-azure hover:underline">
                                            <x-ui.icon name="file-text" class="size-3.5" /> Ou envoyer un fichier (PDF, Word, PowerPoint…)
                                            <input type="file" wire:model="moduleDocumentFile" class="hidden">
                                        </label>
                                        <span wire:loading wire:target="moduleDocumentFile" class="ml-1 text-[11px] font-semibold text-azure">Envoi…</span>
                                        @error('moduleDocumentUrl') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        @error('moduleDocumentFile') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Ressources téléchargeables (réservées aux abonnés)</label>
                                        @foreach ($moduleRessources as $i => $r)
                                            <div wire:key="ress-{{ $i }}" class="mb-1.5 flex items-center gap-2 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-1.5">
                                                <x-ui.icon name="folder-open" class="size-3.5 text-accent" />
                                                <span class="min-w-0 flex-1 truncate text-[12px] font-semibold text-brand">{{ $r['nom'] }} <span class="font-normal text-[#9AA6B8]">{{ $r['taille'] ?? '' }}</span></span>
                                                <button type="button" wire:click="retirerRessource({{ $i }})" class="text-[11px] font-semibold text-accent hover:underline">Retirer</button>
                                            </div>
                                        @endforeach
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-[9px] border border-dashed border-brand/25 px-3 py-2 text-[12px] font-bold text-brand hover:bg-cloud">
                                            <x-ui.icon name="plus" class="size-3.5" /> Ajouter des fichiers (fiches, modèles, tableurs…)
                                            <input type="file" wire:model="moduleRessourceFiles" multiple class="hidden">
                                        </label>
                                        <span wire:loading wire:target="moduleRessourceFiles" class="ml-1 text-[11px] font-semibold text-azure">Envoi…</span>
                                        @error('moduleRessourceFiles.*') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                    </div>
                                    <x-admin-light.quiz-editor class="sm:col-span-2" data-test="editeur-quiz" champ="moduleQuiz" :questions="$moduleQuiz" label="Quiz de validation (facultatif) — le membre doit le réussir pour valider le module" />
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Durée (ex : 15 min)</label>
                                        <input wire:model="moduleDuree" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Ordre</label>
                                        <input wire:model="moduleOrdre" type="number" min="0" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                    </div>
                                    <button wire:click="saveModule" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
                                </div>
                            @endif

                            @forelse ($modulesList as $m)
                                <div class="flex items-center gap-3 border-t border-white py-2.5 first:border-t-0">
                                    <span class="w-8 shrink-0 text-center text-[11.5px] font-bold text-[#9AA6B8]">{{ $m['ordre'] }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[12.5px] font-bold text-brand">{{ $m['titre'] }}</p>
                                        <p class="truncate text-[11px] text-[#9AA6B8]">
                                            {{ collect([
                                                ($m['contenu'] ?? null) ? 'Leçon' : null,
                                                $m['video_url'] ? 'Vidéo' : null,
                                                $m['document_url'] ? 'Document' : null,
                                                ! empty($m['ressources']) ? count($m['ressources']).' ressource(s)' : null,
                                                ! empty($m['quiz']) ? 'Quiz ('.count($m['quiz']).' q.)' : null,
                                                $m['duree'] ?: null,
                                            ])->filter()->join(' · ') }}
                                        </p>
                                    </div>
                                    <button wire:click="openModuleEdit({{ $m['id'] }})" class="icon-btn shrink-0 rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="pencil" class="size-3.5" /></button>
                                    <button wire:click="deleteModule({{ $m['id'] }})" wire:confirm="Supprimer ce module ?" class="icon-btn shrink-0 rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                </div>
                            @empty
                                <p class="py-4 text-center text-[12px] text-[#9AA6B8]">Aucun module. Ajoutez-en pour donner un vrai contenu à cette formation.</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucune formation. Cliquez sur « + Nouvelle formation » pour créer la première.</p>
            @endforelse
        </div>
    </div>
</div>
