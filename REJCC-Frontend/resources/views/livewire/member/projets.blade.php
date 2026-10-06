<div>
    <x-member-light.topbar title="Projets" />

    @if ($locked ?? false)
        <x-member-light.paywall description="Les projets du réseau sont réservés aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $aTraiter = $mesProjets->where('statut', 'a_completer')->count();
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Projets du réseau</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Découvrez les projets portés par les membres, proposez le vôtre et trouvez dans le réseau les partenaires, compétences et mentors qui le feront avancer.</p>
            </div>
            <button wire:click="openForm" data-test="proposer-projet" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600"><x-ui.icon name="plus" class="size-3.5" /> Proposer un projet</button>
        </div>

        @if ($message)
            <p wire:key="flash-ok" data-test="flash-projet" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm)
            <p wire:key="flash-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
        @endif

        {{-- ══════════ Formulaire ══════════ --}}
        @if ($showForm)
            <div wire:key="form-projet" data-test="form-projet" class="panel-enter mb-6 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? (in_array($statutEdition, ['a_completer', 'refuse'], true) ? 'Compléter mon projet' : 'Modifier mon projet') : 'Proposer un projet au réseau' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand" title="Fermer"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Nom du projet</label>
                        <input wire:model="title" type="text" class="{{ $input }}" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Accroche — le projet en une phrase</label>
                        <input wire:model="accroche" type="text" maxlength="200" placeholder="Ex : Des mangues séchées ivoiriennes pour l'export" class="{{ $input }}" />
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
                        <label class="{{ $lab }}">Stade d'avancement</label>
                        <select wire:model="stade" class="{{ $input }}">
                            @foreach ($stades as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Ville</label>
                        <input wire:model="ville" type="text" placeholder="Ex : Abidjan" class="{{ $input }}" />
                    </div>
                    <div>
                        <label class="{{ $lab }}">Personnes impliquées au total</label>
                        <input wire:model="membersCount" type="number" min="1" class="{{ $input }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Présentation du projet</label>
                        <textarea wire:model="description" rows="4" placeholder="Ce que vous faites, où vous en êtes, ce que vous visez." class="{{ $input }}"></textarea>
                        @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Le problème que vous résolvez</label>
                        <textarea wire:model="probleme" rows="2" class="{{ $input }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Votre solution</label>
                        <textarea wire:model="solution" rows="2" class="{{ $input }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Pour qui ? (public, clients)</label>
                        <textarea wire:model="cible" rows="2" class="{{ $input }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $lab }}">Impact attendu (emplois, communauté…)</label>
                        <textarea wire:model="impact" rows="2" class="{{ $input }}"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Ce que vous recherchez dans le réseau</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($listeBesoins as $k => $v)
                                <label wire:key="besoin-{{ $k }}" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-brand/15 px-3 py-1.5 text-[12.5px] font-semibold text-brand has-[:checked]:border-accent has-[:checked]:bg-accent/[.07] has-[:checked]:text-accent">
                                    <input wire:model="besoins" type="checkbox" value="{{ $k }}" class="size-3.5 rounded border-brand/25 text-accent" /> {{ $v }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Lien (site, page, vidéo de présentation — optionnel)</label>
                        <input wire:model="lien" type="url" placeholder="https://…" class="{{ $input }}" />
                        @error('lien') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Visuel du projet (photo, logo — optionnel)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                        @error('mediaFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    @if ($erreur)
                        <p wire:key="form-erreur" class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p>
                    @endif
                    <div class="sm:col-span-2">
                        <button wire:click="enregistrer" wire:loading.attr="disabled" wire:target="enregistrer" data-test="envoyer-projet" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 disabled:opacity-60">
                            {{ ! $editingId ? "Soumettre à l'équipe" : (in_array($statutEdition, ['a_completer', 'refuse'], true) ? 'Renvoyer en évaluation' : 'Enregistrer') }}
                        </button>
                        <p class="mt-2 text-[11px] text-[#9AA6B8]">{{ $editingId && $statutEdition === 'valide' ? 'Les modifications sont visibles immédiatement.' : "L'équipe REJCC examine chaque projet avant de le rendre visible aux membres." }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════ Onglets ══════════ --}}
        <div class="mb-4 flex flex-wrap gap-1.5">
            <button wire:click="setOnglet('reseau')" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'reseau' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Projets du réseau <span class="{{ $onglet === 'reseau' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $projets->count() }}</span>
            </button>
            <button wire:click="setOnglet('mes')" data-test="onglet-mes-projets" class="btn-tap inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'mes' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Mes projets <span class="{{ $onglet === 'mes' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $mesProjets->count() }}</span>
                @if ($aTraiter) <span class="rounded-full bg-[#B27007] px-1.5 text-[10px] leading-4 text-white" title="À compléter">{{ $aTraiter }}</span> @endif
            </button>
            @php $invitations = $mesEquipes->where('relation', 'invite')->count(); @endphp
            <button wire:click="setOnglet('equipes')" data-test="onglet-equipes" class="btn-tap inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'equipes' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Mes équipes <span class="{{ $onglet === 'equipes' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $mesEquipes->count() }}</span>
                @if ($invitations) <span class="rounded-full bg-accent px-1.5 text-[10px] leading-4 text-white" title="Invitations">{{ $invitations }}</span> @endif
            </button>
            <button wire:click="setOnglet('suivis')" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'suivis' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Suivis <span class="{{ $onglet === 'suivis' ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $suivis->count() }}</span>
            </button>
        </div>

        @php $liste = match ($onglet) { 'mes' => $mesProjets, 'equipes' => $mesEquipes, 'suivis' => $suivis, default => $projets }; @endphp
        @if ($liste->isEmpty())
            <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand/[.06] text-brand"><x-ui.icon name="nav-projects" class="size-6" /></span>
                <p class="mt-3 text-sm font-bold text-brand">{{ ['mes' => "Vous n'avez pas encore proposé de projet", 'equipes' => "Vous ne faites partie d'aucune équipe", 'suivis' => 'Vous ne suivez aucun projet'][$onglet] ?? 'Aucun projet validé pour le moment' }}</p>
                <p class="mx-auto mt-1 max-w-md text-xs text-[#5B677A]">Présentez votre projet : une fois validé par l'équipe, les membres du réseau pourront vous contacter pour y contribuer.</p>
                <button wire:click="openForm" class="btn-tap mt-4 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">Proposer mon projet</button>
            </div>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
                @foreach ($liste as $p)
                    <x-projets.carte :p="$p" :besoins-labels="$listeBesoins" :avec-statut="$onglet === 'mes'" />
                @endforeach
            </div>
        @endif
    </div>

    <x-projets.fiche :fiche="$fiche" :besoins-labels="$listeBesoins" :candidats="$candidats" :info="$infoFiche" :rejoindre-ouvert="$rejoindreOuvert" :recherche="$rechercheCandidat" />
    @endif
</div>
