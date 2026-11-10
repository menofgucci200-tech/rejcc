@php
    use App\Support\DemandesCom;
    $champ = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
    $calendrier = \Illuminate\Support\Facades\Route::has('admin.calendrier-editorial');
    $ouvertes = ($compteurs['nouvelle'] ?? 0) + ($compteurs['acceptee'] ?? 0) + ($compteurs['planifiee'] ?? 0);
@endphp
<div>
    <x-admin-light.topbar title="Demandes des départements" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes de communication</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-[12.5px] text-[#5B677A]">Les départements vous adressent ici leurs demandes de visuels et d'annonces, avec 1 à 2 semaines de délai. Acceptez, planifiez dans le calendrier, publiez ou refusez avec un motif : le demandeur est prévenu par e-mail à chaque étape.</p>
            </div>
            <a href="{{ route('admin.mes-demandes-communication') }}" wire:navigate class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Mes demandes</a>
        </div>

        @if ($message)
            <p class="mb-4 rounded-[12px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-2.5 text-[13px] font-semibold text-[#2E7D5B]" data-test="message">{{ $message }}</p>
        @endif
        @if ($erreur)
            <p class="mb-4 rounded-[12px] border border-[#F0C9C9] bg-[#FDF7F7] px-4 py-2.5 text-[13px] font-semibold text-accent" data-test="erreur">{{ $erreur }}</p>
        @endif

        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-[14px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Titre, message, contact…" class="w-full rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure sm:w-56" />
            <select wire:model.live="filtreDepartement" data-test="filtre-departement" class="w-full rounded-full border border-brand/15 bg-white px-3 py-2 text-xs outline-none focus:border-azure sm:w-52">
                <option value="">Tous les départements</option>
                @foreach ($departements as $cle => $libelle)
                    <option value="{{ $cle }}">{{ $libelle }}</option>
                @endforeach
            </select>
            <span class="mx-1 hidden h-5 w-px bg-brand/10 sm:block"></span>
            @php $filtres = ['ouvertes' => "À traiter ({$ouvertes})"] + collect($statuts)->map(fn ($l, $s) => $l.' ('.($compteurs[$s] ?? 0).')')->all() + ['tous' => 'Toutes ('.array_sum($compteurs).')']; @endphp
            @foreach ($filtres as $valeur => $libelle)
                <button wire:click="filtrer('{{ $valeur }}')" data-test="filtre-{{ $valeur }}" class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $filtreStatut === $valeur ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30 hover:text-brand' }}">{{ $libelle }}</button>
            @endforeach
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-5" data-test="liste-demandes">
            @forelse ($liste as $d)
                @php [$fg, $bg] = DemandesCom::teinte($d['statut']); @endphp
                <div wire:key="demande-{{ $d['id'] }}" class="row-hover -mx-4 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:-mx-5 sm:gap-4 sm:px-5" data-test="demande-{{ $d['id'] }}">
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $d['titre'] }}</p>
                        <p class="mt-0.5 text-xs text-[#5B677A]"><span class="font-semibold text-ink">{{ $d['departement_label'] }}</span> · {{ $d['demandeur'] }}@if (mb_strtolower($d['type_label']) !== mb_strtolower($d['departement_label'])) · {{ $d['type_label'] }}@endif</p>
                        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                            <span class="rounded-full px-2 py-0.5 font-bold {{ in_array($d['statut'], ['nouvelle', 'acceptee'], true) ? DemandesCom::classesDelai($d['niveau_delai']) : 'bg-[#F1F3F7] text-[#5B677A]' }}">Souhaitée le {{ DemandesCom::date($d['date_souhaitee']) }}@if (in_array($d['statut'], ['nouvelle', 'acceptee', 'planifiee'], true)) · {{ DemandesCom::echeance($d['jours_avant']) }}@endif</span>
                            @foreach ($d['canaux_labels'] as $c)<span class="rounded-full bg-[#EEF6FD] px-2 py-0.5 font-semibold text-[#2D5C8F]">{{ $c }}</span>@endforeach
                            @if ($d['nb_fichiers'])<span class="text-[#5B677A]">· {{ $d['nb_fichiers'] }} pièce{{ $d['nb_fichiers'] > 1 ? 's' : '' }} jointe{{ $d['nb_fichiers'] > 1 ? 's' : '' }}</span>@endif
                        </p>
                    </div>
                    @if ($d['urgente'])<span class="shrink-0 rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-white" data-test="badge-urgente">Urgente</span>@endif
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $fg }}; background: {{ $bg }}">{{ $d['statut_label'] }}</span>
                    <button wire:click="ouvrir({{ $d['id'] }})" data-test="ouvrir-{{ $d['id'] }}" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-azure/25 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                        <x-ui.icon name="eye" class="size-3.5" /> {{ $ouverte === $d['id'] ? 'Fermer' : 'Ouvrir' }}
                    </button>
                </div>

                @if ($ouverte === $d['id'] && $fiche)
                    <div class="panel-enter mb-4 grid gap-4 rounded-xl bg-[#F8FAFC] p-4 lg:grid-cols-[1fr_340px]" data-test="fiche">
                        <div class="min-w-0 space-y-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Demande n° {{ $fiche['id'] }} · {{ $fiche['type_label'] }}</p>
                                <p class="mt-1 text-[16px] font-bold text-brand">{{ $fiche['titre'] }}</p>
                                <p class="mt-0.5 text-[12.5px] text-ink">{{ $fiche['departement_label'] }} · {{ $fiche['demandeur'] }} · reçue le {{ DemandesCom::date($fiche['created_at'], 'j M Y à H\hi') }}</p>
                                <p class="mt-1 text-[12.5px] text-ink">Contact : <span class="font-semibold">{{ $fiche['contact'] }}</span>@if ($fiche['demandeur_email']) · <a href="mailto:{{ $fiche['demandeur_email'] }}" class="text-azure hover:underline">{{ $fiche['demandeur_email'] }}</a>@endif</p>
                            </div>

                            <div class="grid gap-x-6 gap-y-2 sm:grid-cols-3">
                                <div><p class="text-[11px] font-bold text-[#5B677A]">Publication souhaitée</p><p class="mt-0.5 text-[12.5px] text-ink">{{ DemandesCom::date($fiche['date_souhaitee'], 'l j F Y') }}</p></div>
                                <div><p class="text-[11px] font-bold text-[#5B677A]">Date de l'événement</p><p class="mt-0.5 text-[12.5px] text-ink">{{ $fiche['date_evenement'] ? DemandesCom::date($fiche['date_evenement'], 'l j F Y') : '—' }}</p></div>
                                <div><p class="text-[11px] font-bold text-[#5B677A]">Publication prévue</p><p class="mt-0.5 text-[12.5px] text-ink">{{ $fiche['date_publication_prevue'] ? DemandesCom::date($fiche['date_publication_prevue'], 'l j F Y') : '—' }}</p></div>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Message à faire passer</p>
                                <p class="mt-1 whitespace-pre-line text-[13px] leading-relaxed text-ink" data-test="fiche-message">{{ $fiche['message'] }}</p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Canaux souhaités</p>
                                <p class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($plan['canaux'] ?? [] as $c)
                                        @php
                                            $voulu = in_array($c['cle'], $fiche['canaux'], true);
                                            $conseille = in_array($c['cle'], $fiche['canaux_recommandes'], true);
                                        @endphp
                                        @if ($voulu || $conseille)
                                            <span class="rounded-full px-2.5 py-0.5 text-[11.5px] font-semibold {{ $voulu ? 'bg-[#EEF6FD] text-[#2D5C8F]' : 'border border-dashed border-[#C9D3E6] text-[#5B677A]' }}" title="{{ $voulu ? 'Demandé' : 'Recommandé par le plan, non demandé' }}">{{ $c['label'] }}@if (! $voulu) (conseillé)@endif</span>
                                        @endif
                                    @endforeach
                                </p>
                            </div>

                            @if ($fiche['fichiers'])
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Pièces jointes</p>
                                    <div class="mt-1.5 flex flex-wrap gap-2" data-test="pieces-jointes">
                                        @foreach ($fiche['fichiers'] as $f)
                                            <a href="{{ route('admin.demandes-communication.fichier', ['id' => $fiche['id'], 'n' => $f['index']]) }}" target="_blank" rel="noopener" class="flex max-w-[220px] items-center gap-2 rounded-[10px] border border-brand/10 bg-white p-1.5 pr-3 text-[12px] font-semibold text-brand hover:bg-cloud">
                                                @if ($f['image'])
                                                    <img src="{{ route('admin.demandes-communication.fichier', ['id' => $fiche['id'], 'n' => $f['index']]) }}" alt="" class="size-10 shrink-0 rounded-md object-cover">
                                                @else
                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-[#FDEEEE] text-[10px] font-bold text-accent">PDF</span>
                                                @endif
                                                <span class="truncate">{{ $f['nom'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($fiche['historique'])
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Historique</p>
                                    <ol class="mt-1.5 space-y-1.5" data-test="historique">
                                        @foreach (array_reverse($fiche['historique']) as $h)
                                            <li class="text-[12px] text-ink">
                                                <span class="inline-flex flex-wrap items-center gap-x-2">
                                                    <span class="size-1.5 rounded-full {{ ($h['type'] ?? '') === 'commentaire' ? 'bg-accent' : 'bg-azure' }}"></span>
                                                    <span class="font-semibold">{{ $h['label'] }}</span>
                                                    <span class="text-[#5B677A]">· {{ DemandesCom::date($h['at'] ?? null, 'j M Y, H\hi') }}@if (! empty($h['par'])) · {{ $h['par'] }}@endif</span>
                                                </span>
                                                @if (! empty($h['texte']))<span class="mt-0.5 block whitespace-pre-line pl-3.5 text-[#3D4A60]">« {{ $h['texte'] }} »</span>@endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 self-start rounded-xl border border-brand/10 bg-white p-4" data-test="traitement">
                            @php [$fg, $bg] = DemandesCom::teinte($fiche['statut']); @endphp
                            <p class="flex items-center gap-2 text-[13px] font-bold text-brand">Traitement <span class="rounded-full px-2.5 py-0.5 text-[11px]" style="color: {{ $fg }}; background: {{ $bg }}">{{ $fiche['statut_label'] }}</span></p>

                            @if ($fiche['publication'])
                                <div class="rounded-[10px] border border-[#E4DDF6] bg-[#F7F4FD] px-3 py-2 text-[12px] text-ink" data-test="publication-liee">
                                    <p class="font-bold text-[#5B3FA8]">Dans le calendrier éditorial</p>
                                    <p class="mt-0.5">{{ DemandesCom::date($fiche['publication']['date'], 'l j F Y') }}@if ($fiche['publication']['heure']) à {{ $fiche['publication']['heure'] }}@endif · {{ $fiche['publication']['statut_label'] }}</p>
                                    @if ($calendrier)<a href="{{ route('admin.calendrier-editorial', ['mois' => substr($fiche['publication']['date'], 0, 7)]) }}" wire:navigate class="mt-1 inline-block font-semibold text-azure hover:underline">Voir dans le calendrier</a>@endif
                                </div>
                            @endif
                            @if ($fiche['lien_publication'])
                                <a href="{{ $fiche['lien_publication'] }}" target="_blank" rel="noopener noreferrer" class="break-all text-[12px] font-semibold text-azure hover:underline">Voir la publication</a>
                            @endif

                            @if ($fiche['transitions'] === [])
                                <p class="text-[12px] text-[#5B677A]">{{ $fiche['statut'] === 'annulee' ? 'Le demandeur a annulé cette demande.' : 'Cette demande est close.' }}</p>
                            @endif

                            @if (isset($fiche['transitions']['acceptee']))
                                <button wire:click="changerStatut('acceptee')" data-test="action-accepter" class="btn-tap rounded-[9px] bg-brand px-4 py-2 text-[13px] font-bold text-white hover:bg-brand/90">{{ $fiche['statut'] === 'nouvelle' ? 'Accepter la demande' : 'Repasser en « acceptée »' }}</button>
                            @endif

                            @if (in_array($fiche['statut'], ['nouvelle', 'acceptee', 'planifiee'], true)) {{-- planifier une demande nouvelle vaut acceptation --}}
                                <button wire:click="choisirAction('planifier')" data-test="action-planifier" class="btn-tap inline-flex items-center justify-center gap-1.5 rounded-[9px] border border-[#5B3FA8]/30 px-4 py-2 text-[13px] font-bold text-[#5B3FA8] hover:bg-[#F7F4FD]"><x-ui.icon name="calendar" class="size-4" /> {{ $fiche['publication'] ? 'Replanifier' : 'Planifier dans le calendrier' }}</button>
                                @if ($action === 'planifier')
                                    <form wire:submit="planifier" class="space-y-2 rounded-[10px] bg-[#F7F4FD] p-3" data-test="form-planifier">
                                        <div class="grid grid-cols-[1fr_96px] gap-2">
                                            <div><label for="dc-date" class="mb-1 block text-xs font-semibold text-[#5B677A]">Date de publication</label><input wire:model="datePublication" id="dc-date" type="date" data-test="planifier-date" class="{{ $champ }}"></div>
                                            <div><label for="dc-heure" class="mb-1 block text-xs font-semibold text-[#5B677A]">Heure</label><input wire:model="heurePublication" id="dc-heure" type="time" class="{{ $champ }}"></div>
                                        </div>
                                        @error('datePublication') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        @error('heurePublication') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        <div>
                                            <label for="dc-resp" class="mb-1 block text-xs font-semibold text-[#5B677A]">Responsable</label>
                                            <select wire:model="responsable" id="dc-resp" class="{{ $champ }}">
                                                <option value="">Celui du tour de rôle</option>
                                                @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                                            </select>
                                        </div>
                                        <textarea wire:model="commentaire" rows="2" placeholder="Message au demandeur (facultatif)" class="{{ $champ }}"></textarea>
                                        <button type="submit" wire:loading.attr="disabled" data-test="planifier-valider" class="btn-tap w-full rounded-[9px] bg-[#5B3FA8] px-4 py-2 text-[13px] font-bold text-white hover:brightness-110 disabled:opacity-60">Ajouter au calendrier</button>
                                    </form>
                                @endif
                            @endif

                            @if (isset($fiche['transitions']['publiee']))
                                <button wire:click="choisirAction('publier')" data-test="action-publier" class="btn-tap rounded-[9px] border border-[#1C8A49]/30 px-4 py-2 text-[13px] font-bold text-[#1C8A49] hover:bg-[#EAF6EE]">Marquer publiée</button>
                                @if ($action === 'publier')
                                    <form wire:submit="publier" class="space-y-2 rounded-[10px] bg-[#EFFAF4] p-3" data-test="form-publier">
                                        <input wire:model="lienPublication" type="url" placeholder="Lien de la publication (facultatif)" class="{{ $champ }}">
                                        @error('lienPublication') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        <textarea wire:model="commentaire" rows="2" placeholder="Message au demandeur (facultatif)" class="{{ $champ }}"></textarea>
                                        <button type="submit" data-test="publier-valider" class="btn-tap w-full rounded-[9px] bg-[#1C8A49] px-4 py-2 text-[13px] font-bold text-white hover:brightness-110">Confirmer la publication</button>
                                    </form>
                                @endif
                            @endif

                            @if (isset($fiche['transitions']['refusee']))
                                <button wire:click="choisirAction('refuser')" data-test="action-refuser" class="btn-tap rounded-[9px] border border-accent/25 px-4 py-2 text-[13px] font-bold text-accent hover:bg-accent/5">Refuser</button>
                                @if ($action === 'refuser')
                                    <form wire:submit="refuser" class="space-y-2 rounded-[10px] bg-[#FDF7F7] p-3" data-test="form-refuser">
                                        <label for="dc-motif" class="block text-xs font-semibold text-[#5B677A]">Motif du refus (transmis au demandeur)</label>
                                        <textarea wire:model="motif" id="dc-motif" rows="3" data-test="refuser-motif" class="{{ $champ }}"></textarea>
                                        @error('motif') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                        <button type="submit" data-test="refuser-valider" class="btn-tap w-full rounded-[9px] bg-accent px-4 py-2 text-[13px] font-bold text-white hover:brightness-110">Confirmer le refus</button>
                                    </form>
                                @endif
                            @endif

                            @if ($fiche['statut'] !== 'annulee')
                                <div class="border-t border-[#EDF0F5] pt-3">
                                    <button wire:click="choisirAction('commenter')" data-test="action-commenter" class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-azure hover:underline"><x-ui.icon name="message-circle" class="size-4" /> Répondre au demandeur</button>
                                    @if ($action === 'commenter')
                                        <form wire:submit="commenter" class="mt-2 space-y-2" data-test="form-commenter">
                                            <textarea wire:model="commentaire" rows="3" placeholder="Question, précision, fichier à renvoyer…" data-test="commentaire" class="{{ $champ }}"></textarea>
                                            @error('commentaire') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                                            <button type="submit" data-test="commenter-valider" class="btn-tap inline-flex items-center gap-1.5 rounded-[9px] bg-azure px-4 py-2 text-[13px] font-bold text-white hover:brightness-110"><x-ui.icon name="send" class="size-3.5" /> Envoyer par e-mail</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div class="px-2 py-12 text-center">
                    <x-ui.illustration name="megaphone" size="size-14" class="mx-auto" />
                    <p class="mt-3 text-[13px] font-semibold text-brand">Aucune demande ici.</p>
                    <p class="mt-1 text-[12px] text-[#5B677A]">Les demandes des départements apparaîtront dans cette liste.</p>
                </div>
            @endforelse
        </div>
        <x-ui.pager :meta="$meta" />

        @if ($plan)
            <div class="mt-8">@include('livewire.admin.partials.plan-diffusion', ['plan' => $plan, 'replie' => true])</div>
        @endif
    </div>
</div>
