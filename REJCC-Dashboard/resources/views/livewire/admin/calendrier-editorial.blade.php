@php
    use Illuminate\Support\Carbon;

    $styleStatut = [
        'prevu' => 'bg-[#EEF2F8] text-[#41506B]',
        'pret' => 'bg-[#4F6FBF]/10 text-[#3A57A3]',
        'publie' => 'bg-[#22A85A]/10 text-[#1C8F4C]',
        'non_publie' => 'bg-accent/10 text-accent',
        'annule' => 'bg-[#F1F3F7] text-[#8A94A6] line-through',
    ];
    $champ = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] focus:border-brand focus:outline-none';
    $libelle = 'mb-1 block text-[11.5px] font-bold text-[#5B677A]';
    $debutGrille = $debut->copy()->startOfWeek(Carbon::MONDAY);
    $finGrille = $debut->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
    $joursSemaine = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
@endphp

<div>
    <x-admin-light.topbar title="Calendrier éditorial" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-[240px] flex-1">
                <h2 class="mb-1 text-[17px] font-bold text-brand">Calendrier éditorial</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Ce qui sera publié, quand, où et par qui. La veille à 18 h puis le matin à 8 h, chaque responsable reçoit un rappel par e-mail ; à 9 h, l'équipe reçoit la liste des publications de la veille restées non publiées.</p>
            </div>
            <a href="{{ route('admin.bilan-communication', ['mois' => $mois]) }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="bar-chart" class="size-3.5" /> Bilan du mois</a>
            <button type="button" wire:click="nouvelle" data-test="nouvelle-publication" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="plus" class="size-3.5" /> Nouvelle publication</button>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex max-w-full items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && $panneau === '')
            <p data-test="erreur-admin" class="mb-4 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Calendrier indisponible (accès à la rubrique « Espace Communication » requis).</p>
        @else
            {{-- Tour en cours et retards --}}
            <div class="mb-5 grid gap-3 md:grid-cols-2">
                <div data-test="tour-en-cours" class="flex items-center gap-3 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.04)]">
                    <x-ui.illustration name="equipe" size="size-10" />
                    <div class="min-w-0">
                        @if ($tourEnCours)
                            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]">Cette semaine</p>
                            <p class="text-[15px] font-bold text-brand">{{ $tourEnCours['responsable'] }}</p>
                            <p class="text-[11.5px] text-[#5B677A]">Tour du {{ Carbon::parse($tourEnCours['du'])->locale('fr')->isoFormat('dddd D') }} au {{ Carbon::parse($tourEnCours['au'])->locale('fr')->isoFormat('dddd D MMMM') }}</p>
                        @else
                            <p class="text-[13px] font-bold text-brand">Aucun tour de rôle en cours</p>
                            <button type="button" wire:click="ouvrir('tours')" class="text-[12px] font-semibold text-[#4F6FBF] hover:underline">Définir les tours du mois</button>
                        @endif
                    </div>
                </div>
                <div data-test="bloc-retards" class="rounded-[16px] border p-4 {{ $retards ? 'border-accent/30 bg-accent/[0.04]' : 'border-brand/10 bg-white' }}">
                    @if ($retards)
                        <p class="mb-1.5 flex items-center gap-1.5 text-[13px] font-bold text-accent"><x-ui.icon name="alert-circle" class="size-4" /> {{ count($retards) }} publication{{ count($retards) > 1 ? 's' : '' }} en retard</p>
                        <ul class="space-y-1">
                            @foreach (array_slice($retards, 0, 4) as $r)
                                <li class="flex flex-wrap items-center gap-x-2 text-[12px]">
                                    <span class="font-semibold text-accent">{{ Carbon::parse($r['date'])->locale('fr')->isoFormat('ddd D MMM') }}</span>
                                    <button type="button" wire:click="modifier({{ $r['id'] }})" class="min-w-0 flex-1 truncate text-left text-ink hover:underline">{{ $r['titre'] }}</button>
                                    <span class="text-[#5B677A]">{{ $r['responsable_effectif']['nom'] ?? 'Sans responsable' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if (count($retards) > 4)<button type="button" wire:click="$set('fStatut', 'retard')" class="mt-1 text-[11.5px] font-semibold text-accent hover:underline">Voir les {{ count($retards) }} retards du mois affiché</button>@endif
                    @else
                        <p class="flex items-center gap-1.5 text-[13px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> Aucun retard : tout ce qui était prévu est publié.</p>
                    @endif
                </div>
            </div>

            {{-- Navigation, vues, filtres, outils --}}
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 rounded-full border border-brand/15 bg-white p-1">
                    <button type="button" wire:click="changerMois(-1)" aria-label="Mois précédent" data-test="mois-precedent" class="icon-btn rounded-full p-1.5 text-brand hover:bg-cloud"><x-ui.icon name="arrow-left" class="size-4" /></button>
                    <span data-test="mois-affiche" class="min-w-[130px] text-center text-[13.5px] font-bold text-brand">{{ ucfirst($debut->copy()->locale('fr')->isoFormat('MMMM YYYY')) }}</span>
                    <button type="button" wire:click="changerMois(1)" aria-label="Mois suivant" data-test="mois-suivant" class="icon-btn rounded-full p-1.5 text-brand hover:bg-cloud"><x-ui.icon name="arrow-right" class="size-4" /></button>
                </div>
                @if ($mois !== now()->format('Y-m'))
                    <button type="button" wire:click="moisCourant" class="btn-tap rounded-full border border-brand/15 bg-white px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Aujourd'hui</button>
                @endif
                <div class="flex rounded-full border border-brand/15 bg-white p-1 text-[12px] font-bold">
                    <button type="button" wire:click="$set('vue', 'grille')" data-test="vue-grille" class="rounded-full px-3 py-1 {{ $vue === 'grille' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">Grille</button>
                    <button type="button" wire:click="$set('vue', 'liste')" data-test="vue-liste" class="rounded-full px-3 py-1 {{ $vue === 'liste' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">Liste</button>
                </div>
                <div class="ml-auto flex flex-wrap gap-1.5">
                    <button type="button" wire:click="ouvrir('tours')" data-test="ouvrir-tours" class="btn-tap inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[12px] font-bold {{ $panneau === 'tours' ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:bg-cloud' }}"><x-ui.icon name="users" class="size-3.5" /> Tours de rôle</button>
                    <button type="button" wire:click="ouvrir('import')" data-test="ouvrir-import" class="btn-tap inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[12px] font-bold {{ $panneau === 'import' ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:bg-cloud' }}"><x-ui.icon name="file-text" class="size-3.5" /> Importer</button>
                    <a href="{{ route('admin.calendrier-editorial.csv', ['quoi' => 'export', 'mois' => $mois]) }}" data-test="exporter-csv" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Export CSV</a>
                    @if ($publications->isEmpty() && ! $filtres)
                        <button type="button" wire:click="dupliquer" wire:confirm="Recopier les publications du mois précédent dans ce mois (mêmes jours, statut « prévu ») ?" data-test="dupliquer-mois" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="refresh-cw" class="size-3.5" /> Reprendre le mois précédent</button>
                    @endif
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-3" data-test="filtres">
                <select wire:model.live="fResponsable" aria-label="Filtrer par responsable" class="{{ $champ }}">
                    <option value="">Tous les responsables</option>
                    @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                    <option value="aucun">Sans responsable</option>
                </select>
                <select wire:model.live="fCanal" aria-label="Filtrer par canal" class="{{ $champ }}">
                    <option value="">Tous les canaux</option>
                    @foreach ($canauxListe as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach
                </select>
                <select wire:model.live="fStatut" aria-label="Filtrer par statut" class="{{ $champ }}">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuts as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach
                    <option value="retard">En retard</option>
                </select>
            </div>

            {{-- ═════ Panneau : publication ═════ --}}
            @if ($panneau === 'publication')
                <form wire:submit="enregistrer" data-test="formulaire-publication" class="panel-enter mb-6 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-center gap-2">
                        <p class="flex-1 text-[13px] font-bold text-brand">{{ $editId ? 'Modifier la publication' : 'Nouvelle publication' }}@if ($demandeId) <span class="ml-1 rounded-full bg-cloud px-2 py-0.5 text-[10.5px] font-bold text-[#5B677A]">Demande n° {{ $demandeId }}</span>@endif</p>
                        <button type="button" wire:click="ouvrir('publication')" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                    </div>
                    <div class="grid gap-3 md:grid-cols-4">
                        <label class="block md:col-span-1"><span class="{{ $libelle }}">Date</span><input type="date" wire:model="date" required data-test="champ-date" class="{{ $champ }}"></label>
                        <label class="block md:col-span-1"><span class="{{ $libelle }}">Heure (facultative)</span><input type="time" wire:model="heure" class="{{ $champ }}"></label>
                        <label class="block md:col-span-2"><span class="{{ $libelle }}">Type d'information</span>
                            <select wire:model.live="type" data-test="champ-type" class="{{ $champ }}">
                                @foreach ($types as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach
                            </select>
                        </label>
                        <label class="block md:col-span-3"><span class="{{ $libelle }}">Sujet / titre</span><input type="text" wire:model="titre" required maxlength="200" data-test="champ-titre" placeholder="Ex. : Annonce du gala de fin d'année" class="{{ $champ }}"></label>
                        <label class="block md:col-span-1"><span class="{{ $libelle }}">Format ou style</span>
                            <input type="text" wire:model="format" list="formats-publication" maxlength="60" placeholder="Vidéo courte, carrousel…" class="{{ $champ }}">
                            <datalist id="formats-publication">@foreach ($formats as $f)<option value="{{ $f }}">@endforeach</datalist>
                        </label>
                        <div class="md:col-span-4">
                            <span class="{{ $libelle }}">Canaux <span class="font-normal">(pré-cochés selon le plan de diffusion, modifiables)</span></span>
                            <div class="flex flex-wrap gap-1.5" data-test="champ-canaux">
                                @foreach ($canauxListe as $cle => $l)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model="canaux" value="{{ $cle }}" class="peer sr-only">
                                        <span class="inline-block rounded-full border border-brand/15 px-3 py-1 text-[12px] font-semibold text-[#5B677A] peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#4F6FBF]">{{ $l }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <label class="block md:col-span-2"><span class="{{ $libelle }}">Responsable</span>
                            <select wire:model.live="responsableId" class="{{ $champ }}">
                                <option value="">— Celui du tour, ou un nom libre —</option>
                                @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                            </select>
                        </label>
                        @if ($responsableId === '')
                            <label class="block md:col-span-2"><span class="{{ $libelle }}">Nom libre (hors équipe)</span><input type="text" wire:model="responsableLibre" maxlength="150" placeholder="Laisser vide : responsable du tour" class="{{ $champ }}"></label>
                        @endif
                        <label class="block md:col-span-4"><span class="{{ $libelle }}">Notes / brief</span><textarea wire:model="notes" rows="2" maxlength="5000" placeholder="Message clé, visuel à utiliser, hashtags…" class="{{ $champ }}"></textarea></label>
                        <label class="block md:col-span-1"><span class="{{ $libelle }}">Statut</span>
                            <select wire:model="statut" class="{{ $champ }}">@foreach ($statuts as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach</select>
                        </label>
                        <label class="block md:col-span-3"><span class="{{ $libelle }}">Lien(s) de la publication, une fois publiée</span><textarea wire:model="liens" rows="1" placeholder="https://www.facebook.com/… (un lien par ligne)" class="{{ $champ }}"></textarea></label>
                    </div>
                    @if ($erreur)<p data-test="erreur-publication" class="mt-3 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="submit" data-test="enregistrer-publication" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">{{ $editId ? 'Enregistrer' : 'Ajouter au calendrier' }}</button>
                        <button type="button" wire:click="ouvrir('publication')" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Annuler</button>
                        @if ($editId)
                            <button type="button" wire:click="supprimer({{ $editId }})" wire:confirm="Supprimer cette publication du calendrier ?" class="btn-tap ml-auto rounded-full px-4 py-2 text-[12.5px] font-bold text-accent hover:bg-accent/10">Supprimer</button>
                        @endif
                    </div>
                </form>
            @endif

            {{-- ═════ Panneau : tours de rôle ═════ --}}
            @if ($panneau === 'tours')
                <section data-test="panneau-tours" class="panel-enter mb-6 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-center gap-2">
                        <p class="flex-1 text-[13px] font-bold text-brand">Tours de rôle · {{ $debut->copy()->locale('fr')->isoFormat('MMMM YYYY') }}</p>
                        <button type="button" wire:click="ouvrir('tours')" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                    </div>
                    <p class="mb-4 text-[12px] text-[#5B677A]">Une personne par bloc de jours. Une publication sans responsable revient à la personne du tour, qui reçoit les rappels.</p>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <p class="mb-2 text-[12px] font-bold text-brand">Tours du mois</p>
                            <div class="divide-y divide-[#EDF0F5] rounded-[12px] border border-[#EDF0F5]">
                                @forelse ($tours as $t)
                                    <div wire:key="tour-{{ $t['id'] }}" data-test="ligne-tour" class="flex items-center gap-2 px-3 py-2 text-[12.5px]">
                                        <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $couleurs[$t['responsable']] ?? '#C9D3E6' }}"></span>
                                        <span class="w-[150px] shrink-0 text-[#5B677A]">{{ Carbon::parse($t['du'])->locale('fr')->isoFormat('ddd D') }} → {{ Carbon::parse($t['au'])->locale('fr')->isoFormat('ddd D MMM') }}</span>
                                        <span class="min-w-0 flex-1 truncate font-semibold text-ink">{{ $t['responsable'] ?? '—' }}</span>
                                        <button type="button" wire:click="supprimerTour({{ $t['id'] }})" wire:confirm="Supprimer ce tour ?" aria-label="Supprimer le tour" class="icon-btn rounded-lg p-1 text-[#5B677A] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                    </div>
                                @empty
                                    <p class="px-3 py-4 text-center text-[12px] text-[#5B677A]">Aucun tour ce mois-ci. Utilisez l'assistant ci-contre.</p>
                                @endforelse
                            </div>
                            <form wire:submit="ajouterTour" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <input type="date" wire:model="tourDu" required aria-label="Du" class="{{ $champ }}">
                                <input type="date" wire:model="tourAu" required aria-label="Au" class="{{ $champ }}">
                                <select wire:model.live="tourResponsable" aria-label="Responsable du tour" class="{{ $champ }}">
                                    <option value="">Nom libre…</option>
                                    @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                                </select>
                                @if ($tourResponsable === '')
                                    <input type="text" wire:model="tourLibre" maxlength="150" placeholder="Nom" aria-label="Nom libre" class="{{ $champ }}">
                                @endif
                                <button type="submit" class="btn-tap col-span-2 rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud sm:col-span-4">Ajouter ce tour</button>
                            </form>
                        </div>

                        <div class="rounded-[14px] bg-cloud/60 p-4" data-test="assistant-tours">
                            <p class="mb-1 text-[12px] font-bold text-brand">Générer les tours du mois</p>
                            <p class="mb-3 text-[11.5px] text-[#5B677A]">Blocs lundi→mercredi, jeudi→samedi, dimanche→mercredi suivant… Les membres cochés alternent dans l'ordre où vous les cochez ; vous ajustez ensuite chaque bloc.</p>
                            <div class="mb-2 flex flex-wrap gap-1.5">
                                @foreach ($equipe as $m)
                                    @php $rang = array_search((string) $m['id'], $membresChoisis, true); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model.live="membresChoisis" value="{{ $m['id'] }}" class="peer sr-only">
                                        <span class="inline-flex items-center gap-1 rounded-full border border-brand/15 bg-white px-3 py-1 text-[12px] font-semibold text-[#5B677A] peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white">@if ($rang !== false)<span class="text-[10.5px] opacity-80">{{ $rang + 1 }}.</span>@endif {{ $m['nom'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <input type="text" wire:model="membresLibres" placeholder="Autres noms, séparés par des virgules (facultatif)" class="{{ $champ }} mb-2">
                            <button type="button" wire:click="proposerTours" data-test="proposer-tours" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90">Proposer les tours</button>

                            @if ($proposition)
                                <div class="mt-3 space-y-1.5" data-test="proposition-tours">
                                    @foreach ($proposition as $i => $t)
                                        <div wire:key="prop-{{ $i }}-{{ $t['du'] }}" class="flex items-center gap-2 text-[12px]">
                                            <span class="w-[140px] shrink-0 text-[#5B677A]">{{ Carbon::parse($t['du'])->locale('fr')->isoFormat('ddd D') }} → {{ Carbon::parse($t['au'])->locale('fr')->isoFormat('ddd D') }}</span>
                                            <select wire:model="proposition.{{ $i }}.choix" class="{{ $champ }} !py-1.5">
                                                @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                                                @foreach (collect($proposition)->pluck('choix')->filter(fn ($c) => str_starts_with($c, 'libre:'))->unique() as $libre)
                                                    <option value="{{ $libre }}">{{ substr($libre, 6) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="enregistrerTours" @if ($tours->isNotEmpty()) wire:confirm="Remplacer les tours qui commencent ce mois-ci par cette proposition ?" @endif data-test="enregistrer-tours" class="btn-tap mt-2 rounded-full bg-accent px-4 py-2 text-[12px] font-bold text-white hover:bg-accent-600">Enregistrer ces {{ count($proposition) }} tours</button>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if ($erreur)<p data-test="erreur-tours" class="mt-3 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif
                </section>
            @endif

            {{-- ═════ Panneau : import ═════ --}}
            @if ($panneau === 'import')
                <section data-test="panneau-import" class="panel-enter mb-6 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="flex-1 text-[13px] font-bold text-brand">Importer votre calendrier Excel</p>
                        <button type="button" wire:click="ouvrir('import')" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                    </div>
                    <p class="mb-3 text-[12px] text-[#5B677A]">Colonnes : <strong>date</strong>, <strong>sujet</strong>, style, canal, responsable (heure, type et notes facultatives). Fichier .xlsx, ou dans Excel : Fichier → Enregistrer sous → « CSV UTF-8 ». Un aperçu s'affiche avant tout enregistrement.
                        <a href="{{ route('admin.calendrier-editorial.csv', ['quoi' => 'modele']) }}" data-test="telecharger-modele" class="font-semibold text-[#4F6FBF] hover:underline">Télécharger le modèle CSV</a></p>
                    <input type="file" wire:model="fichier" accept=".csv,.txt,.xlsx" data-test="champ-fichier" class="block w-full text-[12.5px] file:mr-3 file:rounded-full file:border-0 file:bg-brand file:px-4 file:py-2 file:text-[12px] file:font-bold file:text-white">
                    <p wire:loading wire:target="fichier" class="mt-2 text-[12px] text-[#5B677A]">Lecture du fichier…</p>
                    @error('fichier')<p class="mt-2 text-[12.5px] font-semibold text-accent">{{ $message }}</p>@enderror
                    @if ($erreur)<p data-test="erreur-import" class="mt-2 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif

                    @if ($apercu)
                        <div class="mt-4" data-test="apercu-import">
                            <p class="mb-2 text-[12.5px] font-semibold text-ink">{{ $apercu['valides'] }} ligne{{ $apercu['valides'] > 1 ? 's' : '' }} prête{{ $apercu['valides'] > 1 ? 's' : '' }} à importer @if ($apercu['erreurs'])· <span class="text-accent">{{ $apercu['erreurs'] }} ligne{{ $apercu['erreurs'] > 1 ? 's' : '' }} en erreur (ignorée{{ $apercu['erreurs'] > 1 ? 's' : '' }})</span>@endif</p>
                            <div class="max-h-[360px] overflow-auto rounded-[12px] border border-[#EDF0F5]">
                                <table class="w-full min-w-[640px] text-left text-[12px]">
                                    <thead class="sticky top-0 bg-cloud text-[11px] uppercase tracking-[0.06em] text-[#5B677A]"><tr><th class="px-2 py-1.5">Ligne</th><th class="px-2 py-1.5">Date</th><th class="px-2 py-1.5">Sujet</th><th class="px-2 py-1.5">Style</th><th class="px-2 py-1.5">Canaux</th><th class="px-2 py-1.5">Responsable</th></tr></thead>
                                    <tbody>
                                        @foreach ($apercu['lignes'] as $l)
                                            <tr class="border-t border-[#EDF0F5] {{ $l['erreurs'] ? 'bg-accent/[0.05]' : '' }}">
                                                <td class="px-2 py-1.5 text-[#5B677A]">{{ $l['ligne'] }}</td>
                                                <td class="whitespace-nowrap px-2 py-1.5">{{ $l['date'] ? Carbon::parse($l['date'])->format('d/m/Y') : '—' }}{{ $l['heure'] ? ' · '.$l['heure'] : '' }}</td>
                                                <td class="px-2 py-1.5">{{ $l['titre'] }}@foreach ($l['erreurs'] as $e)<span class="block font-semibold text-accent">{{ ucfirst($e) }}</span>@endforeach</td>
                                                <td class="px-2 py-1.5">{{ $l['format'] }}</td>
                                                <td class="px-2 py-1.5">{{ collect($l['canaux'])->map(fn ($c) => $canauxListe[$c] ?? $c)->implode(', ') }}</td>
                                                <td class="px-2 py-1.5">{{ $l['responsable_id'] ? ($equipe->firstWhere('id', $l['responsable_id'])['nom'] ?? '') : ($l['responsable'] ?? 'Tour de rôle') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($apercu['valides'] > 0)
                                    <button type="button" wire:click="importer" data-test="confirmer-import" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Importer {{ $apercu['valides'] }} publication{{ $apercu['valides'] > 1 ? 's' : '' }}</button>
                                @endif
                                <button type="button" wire:click="annulerImport" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Annuler</button>
                            </div>
                        </div>
                    @endif
                    @if ($resultatImport && $resultatImport['erreurs'])
                        <div class="mt-3 rounded-[12px] bg-accent/[0.05] p-3 text-[12px]" data-test="erreurs-import">
                            <p class="mb-1 font-bold text-accent">Lignes non importées</p>
                            @foreach ($resultatImport['erreurs'] as $l)<p>Ligne {{ $l['ligne'] }} : {{ implode(', ', $l['erreurs']) }}</p>@endforeach
                        </div>
                    @endif
                </section>
            @endif

            {{-- ═════ Grille du mois ═════ --}}
            @if ($vue === 'grille')
                {{-- Ordinateur : lundi → dimanche --}}
                <div class="hidden overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] md:block" data-test="grille-mois">
                    <div class="grid grid-cols-7 border-b border-brand/10 bg-cloud/60">
                        @foreach ($joursSemaine as $j)<div class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]">{{ $j }}</div>@endforeach
                    </div>
                    <div class="grid grid-cols-7">
                        @for ($jour = $debutGrille->copy(); $jour->lte($finGrille); $jour->addDay())
                            @php
                                $cle = $jour->toDateString();
                                $horsMois = $jour->month !== $debut->month;
                                $estAujourdhui = $cle === $aujourdhui;
                                $tour = $horsMois ? null : $tourDuJour($cle);
                            @endphp
                            <div wire:key="jour-{{ $cle }}" data-jour="{{ $cle }}" class="group relative min-h-[118px] border-b border-r border-[#EDF0F5] p-1.5 [&:nth-child(7n)]:border-r-0 {{ $horsMois ? 'bg-[#FAFBFC]' : '' }} {{ $estAujourdhui ? 'bg-[#4F6FBF]/[0.06] ring-2 ring-inset ring-[#4F6FBF]' : '' }}">
                                <div class="mb-1 flex items-center gap-1">
                                    <span class="flex size-6 items-center justify-center rounded-full text-[12px] font-bold {{ $estAujourdhui ? 'bg-[#4F6FBF] text-white' : ($horsMois ? 'text-[#B4BCCB]' : 'text-brand') }}">{{ $jour->day }}</span>
                                    @if ($estAujourdhui)<span class="text-[10px] font-bold uppercase text-[#4F6FBF]">Aujourd'hui</span>@endif
                                    @if ($tour)
                                        <span class="ml-auto max-w-[70%] truncate rounded-full px-1.5 py-px text-[10px] font-bold text-white" style="background: {{ $couleurs[$tour['responsable']] ?? '#8A94A6' }}" title="Tour : {{ $tour['responsable'] }}">{{ \Illuminate\Support\Str::before((string) $tour['responsable'], ' ') ?: $tour['responsable'] }}</span>
                                    @endif
                                </div>
                                @unless ($horsMois)
                                    <div class="space-y-1">
                                        @foreach ($parJour[$cle] ?? [] as $p)
                                            <button type="button" wire:key="pub-{{ $p['id'] }}" wire:click="modifier({{ $p['id'] }})" data-test="publication-grille"
                                                class="block w-full rounded-[7px] border px-1.5 py-1 text-left text-[11px] leading-tight transition-colors {{ $p['en_retard'] ? 'border-accent/50 bg-accent/10 text-accent' : ($p['statut'] === 'publie' ? 'border-[#22A85A]/30 bg-[#22A85A]/10 text-[#16703C]' : ($p['statut'] === 'annule' ? 'border-transparent bg-[#F1F3F7] text-[#8A94A6] line-through' : 'border-brand/10 bg-white text-ink hover:border-brand/30')) }}">
                                                <span class="line-clamp-2 font-semibold">@if ($p['heure'])<span class="opacity-70">{{ $p['heure'] }}</span> @endif{{ $p['titre'] }}</span>
                                                <span class="mt-0.5 block truncate opacity-75">{{ $p['statut'] === 'publie' ? '✓ Publié' : ($p['en_retard'] ? 'En retard' : implode(' · ', array_slice($p['canaux_labels'], 0, 3)).(count($p['canaux']) > 3 ? '…' : '')) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <button type="button" wire:click="nouvelle('{{ $cle }}')" aria-label="Ajouter une publication le {{ $jour->day }}" class="absolute bottom-1 right-1 rounded-full p-1 text-[#8A94A6] opacity-0 transition-opacity hover:bg-cloud hover:text-brand focus:opacity-100 group-hover:opacity-100"><x-ui.icon name="plus" class="size-3.5" /></button>
                                @endunless
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- Téléphone : une liste par jour (jours avec publications, et aujourd'hui) --}}
                <div class="space-y-3 md:hidden" data-test="liste-jours-mobile">
                    @php $joursMobile = $parJour->keys()->push($debut->month === Carbon::parse($aujourdhui)->month && $debut->year === Carbon::parse($aujourdhui)->year ? $aujourdhui : null)->filter()->unique()->sort()->values(); @endphp
                    @forelse ($joursMobile as $cle)
                        @php $j = Carbon::parse($cle); $estAujourdhui = $cle === $aujourdhui; $tour = $tourDuJour($cle); @endphp
                        <div wire:key="mjour-{{ $cle }}" class="rounded-[16px] border bg-white p-3 {{ $estAujourdhui ? 'border-[#4F6FBF] ring-1 ring-[#4F6FBF]' : 'border-brand/10' }}">
                            <div class="mb-2 flex items-center gap-2">
                                <p class="text-[13px] font-bold {{ $estAujourdhui ? 'text-[#4F6FBF]' : 'text-brand' }}">{{ $estAujourdhui ? "Aujourd'hui · " : '' }}{{ $estAujourdhui ? $j->locale('fr')->isoFormat('dddd D MMMM') : ucfirst($j->locale('fr')->isoFormat('dddd D MMMM')) }}</p>
                                @if ($tour)<span class="ml-auto truncate rounded-full px-2 py-0.5 text-[10.5px] font-bold text-white" style="background: {{ $couleurs[$tour['responsable']] ?? '#8A94A6' }}">Tour : {{ $tour['responsable'] }}</span>@endif
                            </div>
                            @forelse ($parJour[$cle] ?? [] as $p)
                                @include('livewire.admin.partials.publication-ligne', ['p' => $p, 'styleStatut' => $styleStatut, 'compact' => true])
                            @empty
                                <p class="text-[12px] text-[#5B677A]">Rien de prévu.</p>
                            @endforelse
                            <button type="button" wire:click="nouvelle('{{ $cle }}')" class="mt-1 text-[12px] font-semibold text-[#4F6FBF]">+ Ajouter</button>
                        </div>
                    @empty
                        <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucune publication ce mois-ci.</p>
                    @endforelse
                </div>
            @else
                {{-- ═════ Liste ═════ --}}
                <div class="rounded-[18px] border border-brand/10 bg-white px-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-5" data-test="liste-publications">
                    @forelse ($publications as $p)
                        @include('livewire.admin.partials.publication-ligne', ['p' => $p, 'styleStatut' => $styleStatut, 'compact' => false])
                    @empty
                        <p class="py-12 text-center text-sm text-[#5B677A]">{{ $filtres ? 'Aucune publication ne correspond aux filtres.' : 'Aucune publication ce mois-ci. Ajoutez-en une, importez votre fichier Excel ou reprenez le mois précédent.' }}</p>
                    @endforelse
                </div>
            @endif
        @endif
    </div>
</div>
