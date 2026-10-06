<div>
    <x-member-light.topbar title="Emploi & Stage" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $aCorriger = $mesOffres->where('statut', 'a_corriger')->count();
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Emploi &amp; Stage</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-xl text-xs text-[#5B677A]">Offres d'emploi, de stage, d'alternance et de missions partagées par le réseau, vérifiées par l'équipe REJCC.</p>
            </div>
            @if ($peutPublier)
                <button wire:click="openForm" data-test="proposer-offre" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600"><x-ui.icon name="plus" class="size-3.5" /> Proposer une offre</button>
            @else
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate data-test="proposer-offre-verrou" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-[#F5A623]/40 bg-[#F5A623]/10 px-4 py-2 text-xs font-bold text-[#B27007]"><x-ui.icon name="lock" class="size-3.5" /> Proposer une offre (abonnés)</a>
            @endif
        </div>

        @if ($message)
            <p wire:key="flash-ok" data-test="flash-offre" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm)
            <p wire:key="flash-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
        @endif

        {{-- ══════════ Formulaire ══════════ --}}
        @if ($showForm)
            <div wire:key="form-offre" data-test="form-offre" class="panel-enter mb-6 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? (in_array($statutEdition, ['a_corriger', 'refusee'], true) ? 'Corriger mon offre' : 'Modifier mon offre') : "Proposer une offre d'emploi ou de stage" }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand" title="Fermer"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Intitulé du poste ou du stage</label>
                        <input wire:model="title" type="text" placeholder="Ex : Développeur web junior" class="{{ $input }}" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Type d'offre</label>
                        <select wire:model.live="type" class="{{ $input }}">
                            @foreach ($types as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                        </select>
                    </div>
                    @if ($type === 'emploi')
                        <div wire:key="champ-contrat">
                            <label class="{{ $lab }}">Contrat</label>
                            <select wire:model="contrat" class="{{ $input }}">
                                @foreach ($contrats as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                            </select>
                        </div>
                    @else
                        <div wire:key="champ-duree">
                            <label class="{{ $lab }}">Durée</label>
                            <input wire:model="duree" type="text" placeholder="Ex : 3 mois" class="{{ $input }}" />
                        </div>
                    @endif
                    <div>
                        <label class="{{ $lab }}">Entreprise / structure</label>
                        <input wire:model="entreprise" type="text" placeholder="Ex : Ivoire Tech SARL" class="{{ $input }}" />
                        @error('entreprise') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Secteur</label>
                        <select wire:model="groupId" class="{{ $input }}">
                            <option value="">— Choisir —</option>
                            @foreach ($categories as $c) <option value="{{ $c['id'] }}">{{ $c['nom'] }}</option> @endforeach
                        </select>
                        @error('groupId') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Ville</label>
                        <input wire:model="lieu" type="text" placeholder="Ex : Abidjan, Cocody" class="{{ $input }}" />
                        @error('lieu') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Mode de travail</label>
                        <select wire:model="teletravail" class="{{ $input }}">
                            @foreach ($modesTravail as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Rémunération (optionnel)</label>
                        <input wire:model="remuneration" type="text" placeholder="Ex : 350 000 F / mois, à négocier" class="{{ $input }}" />
                    </div>
                    <div>
                        <label class="{{ $lab }}">Date de début (optionnel)</label>
                        <input wire:model="debut" type="date" class="{{ $input }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Présentation de l'offre</label>
                        <textarea wire:model="description" rows="3" placeholder="L'entreprise, le poste, le contexte." class="{{ $input }}"></textarea>
                        @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Missions</label>
                        <textarea wire:model="missions" rows="3" class="{{ $input }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Profil recherché</label>
                        <textarea wire:model="profil" rows="3" class="{{ $input }}"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Compétences clés (séparées par des virgules)</label>
                        <input wire:model="competences" type="text" placeholder="Ex : Laravel, comptabilité, permis B" class="{{ $input }}" />
                    </div>
                    <div>
                        <label class="{{ $lab }}">Date limite de candidature (optionnel)</label>
                        <input wire:model="deadline" type="date" min="{{ now()->toDateString() }}" class="{{ $input }}" />
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">Sans date, l'offre reste en ligne 60 jours (prolongeable).</p>
                        @error('deadline') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Site de l'entreprise (optionnel)</label>
                        <input wire:model="site_url" type="url" placeholder="https://…" class="{{ $input }}" />
                        @error('site_url') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Contact pour l'équipe REJCC (non publié)</label>
                        <input wire:model="contact" type="text" placeholder="E-mail ou téléphone, visible uniquement par l'équipe" class="{{ $input }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Fiche de poste / affiche (PDF ou image — optionnel)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                    </div>
                    @if ($erreur)
                        <p wire:key="form-erreur" class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p>
                    @endif
                    <div class="sm:col-span-2">
                        <button wire:click="publier" wire:loading.attr="disabled" wire:target="publier" data-test="envoyer-offre" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 disabled:opacity-60">
                            {{ ! $editingId ? "Envoyer à l'équipe pour publication" : (in_array($statutEdition, ['a_corriger', 'refusee'], true) ? 'Renvoyer en validation' : 'Enregistrer') }}
                        </button>
                        <p class="mt-2 text-[11px] text-[#9AA6B8]">{{ $editingId && $statutEdition === 'publiee' ? 'Les modifications sont visibles immédiatement.' : "L'équipe REJCC vérifie chaque offre avant de la publier." }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════ Onglets et filtres ══════════ --}}
        <div class="mb-4 flex flex-wrap items-center gap-1.5">
            <button wire:click="setOnglet('offres')" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'offres' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Offres du réseau</button>
            <button wire:click="setOnglet('mes')" data-test="onglet-mes-offres" class="btn-tap inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'mes' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Mes offres <span class="{{ $onglet === 'mes' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $mesOffres->count() }}</span>
                @if ($aCorriger) <span class="rounded-full bg-[#B27007] px-1.5 text-[10px] leading-4 text-white">{{ $aCorriger }}</span> @endif
            </button>
            <button wire:click="setOnglet('candidatures')" data-test="onglet-candidatures" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'candidatures' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Mes candidatures</button>
        </div>

        @if ($onglet === 'offres')
            <div class="mb-4 rounded-[16px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="grid grid-cols-2 gap-2 md:grid-cols-[1fr_140px_170px_140px_130px_140px]">
                    <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Métier, entreprise, compétence…" data-test="recherche-offres" class="col-span-2 rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure md:col-span-1" />
                    <select wire:model.live="filtre" data-test="filtre-type" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none">
                        <option value="tous">Tous les types</option>
                        @foreach ($types as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                    <select wire:model.live="groupe" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none">
                        <option value="">Tous les secteurs</option>
                        @foreach ($categories as $c) <option value="{{ $c['id'] }}">{{ $c['nom'] }}</option> @endforeach
                    </select>
                    <select wire:model.live="villeFiltre" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none">
                        <option value="">Toutes les villes</option>
                        @foreach ($villes as $v) <option value="{{ $v }}">{{ $v }}</option> @endforeach
                    </select>
                    <select wire:model.live="modeFiltre" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none">
                        <option value="">Tout mode</option>
                        @foreach ($modesTravail as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                    <select wire:model.live="tri" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none">
                        <option value="recents">Plus récentes</option>
                        <option value="limite">Date limite proche</option>
                        <option value="vues">Plus consultées</option>
                    </select>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 px-1 text-[11.5px]">
                    <label class="inline-flex cursor-pointer items-center gap-1.5 font-semibold text-[#5B677A]"><input wire:model.live="favoris" type="checkbox" class="size-3.5 rounded border-brand/25 text-accent" /> Mes offres sauvegardées ({{ $nbFavoris }})</label>
                    @if ($filtresActifs)
                        <span class="text-[#9AA6B8]">· {{ $offres->count() }} offre{{ $offres->count() > 1 ? 's' : '' }}</span>
                        <button wire:click="effacerFiltres" class="font-semibold text-azure hover:underline">Effacer les filtres</button>
                    @endif
                    <span class="ml-auto flex items-center gap-2">
                        <button wire:click="creerAlerte" data-test="creer-alerte" class="btn-tap inline-flex items-center gap-1 rounded-full border border-azure/30 bg-azure/[.06] px-3 py-1 font-bold text-azure hover:bg-azure/10"><x-ui.icon name="bell" class="size-3.5" /> M'alerter{{ $filtresActifs ? ' pour cette recherche' : ' des nouvelles offres' }}</button>
                        @if (! empty($alertes))
                            <button wire:click="$toggle('alertesOuvertes')" class="font-semibold text-brand hover:underline">Mes alertes ({{ count($alertes) }})</button>
                        @endif
                    </span>
                </div>
                @if ($alertesOuvertes && ! empty($alertes))
                    <div wire:key="liste-alertes" class="mt-2 space-y-1 border-t border-[#EDF0F5] px-1 pt-2">
                        @foreach ($alertes as $al)
                            <p wire:key="al-{{ $al['id'] }}" data-test="alerte" class="flex items-center gap-2 text-[12px] text-brand"><x-ui.icon name="bell" class="size-3.5 text-azure" /> {{ $al['libelle'] }}
                                <button wire:click="supprimerAlerte({{ $al['id'] }})" class="ml-auto text-[11.5px] font-semibold text-[#9AA6B8] hover:text-accent">Supprimer</button></p>
                        @endforeach
                        <p class="text-[11px] text-[#9AA6B8]">Vous recevez une notification dès qu'une offre correspondante est publiée.</p>
                    </div>
                @endif
            </div>
        @endif

        @if ($onglet === 'candidatures')
            <div class="space-y-3">
                @forelse ($mesCandidatures as $c)
                    @php $cc = ['recue' => '#4F6FBF', 'preselection' => '#B27007', 'retenue' => '#1C8F4C', 'non_retenue' => '#9AA6B8', 'retiree' => '#9AA6B8'][$c['statut']] ?? '#4F6FBF'; @endphp
                    <button type="button" wire:key="mc-{{ $c['id'] }}" wire:click="voir({{ $c['offre']['id'] }})" data-test="ma-candidature-ligne" class="card-hover flex w-full flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 text-left shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-white" style="background: {{ $c['offre']['groupe']['couleur'] ?? '#031D59' }}"><x-ui.icon :name="$c['offre']['groupe']['icone'] ?? 'nav-briefcase'" class="size-5" /></span>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-[14px] font-bold text-brand">{{ $c['offre']['title'] }}</p>
                            <p class="text-[12px] text-[#5B677A]">{{ $c['offre']['entreprise'] }} · {{ $c['offre']['lieu'] }} · postulé {{ \App\Support\Texte::depuis($c['date']) }}{{ $c['vue'] ? ' · vue par le recruteur' : '' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="background: {{ $cc }}1A; color: {{ $cc }}">{{ $c['statut_label'] }}</span>
                    </button>
                @empty
                    <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center text-sm text-[#5B677A]">Vous n'avez pas encore postulé. Ouvrez une offre et cliquez sur « Postuler ».</div>
                @endforelse
            </div>
        @else
        @php $liste = $onglet === 'mes' ? $mesOffres : $offres; @endphp
        <div class="space-y-3">
            @forelse ($liste as $o)
                <x-emplois.carte :o="$o" :avec-statut="$onglet === 'mes'" />
            @empty
                <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand/[.06] text-brand"><x-ui.icon name="nav-briefcase" class="size-6" /></span>
                    <p class="mt-3 text-sm font-bold text-brand">{{ $onglet === 'mes' ? "Vous n'avez pas encore proposé d'offre" : ($filtresActifs ? 'Aucune offre ne correspond à ces critères' : 'Aucune offre en ligne pour le moment') }}</p>
                    <p class="mx-auto mt-1 max-w-md text-xs text-[#5B677A]">Votre entreprise recrute ? Proposez l'offre aux membres du réseau : l'équipe la vérifie puis la publie.</p>
                </div>
            @endforelse
        </div>
        @endif
    </div>

    <x-emplois.fiche :fiche="$fiche" :candidatures="$candidatures" :voir-candidatures="$voirCandidatures" :postuler-ouvert="$postulerOuvert" :cv-name="$cvName" :info="$infoFiche" />
</div>
