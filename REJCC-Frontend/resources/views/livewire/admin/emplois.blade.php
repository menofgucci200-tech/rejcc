<div>
    <x-admin-light.topbar title="Emploi & Stage" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Emploi &amp; Stage</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Vérifiez les offres proposées par les membres avant publication : publiez-les, demandez une correction ou refusez-les avec un motif. L'auteur est notifié à chaque décision.</p>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Publier une offre</button>
        </div>

        @if (! empty($stats))
            <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
                @foreach ([['En ligne', $stats['en_ligne'], 'nav-briefcase'], ['Candidatures', $stats['candidatures'], 'send'], ['Retenues', $stats['retenues'], 'check-circle'], ['Postes pourvus', $stats['pourvues'], 'award'], ['Alertes actives', $stats['alertes'], 'bell']] as [$l, $v, $i])
                    <div class="flex items-center gap-3 rounded-[14px] border border-brand/10 bg-white p-3.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand/[.06] text-brand"><x-ui.icon :name="$i" class="size-4" /></span>
                        <div><p class="text-[18px] font-extrabold leading-none text-brand">{{ $v }}</p><p class="mt-1 text-[11px] text-[#5B677A]">{{ $l }}</p></div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif

        {{-- Formulaire : publication directe par l'équipe ou correction --}}
        @if ($showForm)
            <div wire:key="form-admin-offre" class="panel-enter mb-6 grid grid-cols-1 gap-3 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? "Corriger l'offre" : "Publier une offre au nom du REJCC" }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2"><label class="{{ $lab }}">Intitulé</label><input wire:model="f.title" type="text" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Type</label>
                    <select wire:model.live="f.type" class="{{ $input }}">@foreach ($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                @if (($f['type'] ?? '') === 'emploi')
                    <div wire:key="a-contrat"><label class="{{ $lab }}">Contrat</label><select wire:model="f.contrat" class="{{ $input }}">@foreach ($contrats as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                @else
                    <div wire:key="a-duree"><label class="{{ $lab }}">Durée</label><input wire:model="f.duree" type="text" class="{{ $input }}" /></div>
                @endif
                <div><label class="{{ $lab }}">Entreprise</label><input wire:model="f.entreprise" type="text" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Secteur</label>
                    <select wire:model="f.group_id" class="{{ $input }}"><option value="">— Choisir —</option>@foreach ($categories as $c)<option value="{{ $c['id'] }}">{{ $c['nom'] }}</option>@endforeach</select></div>
                <div><label class="{{ $lab }}">Ville</label><input wire:model="f.lieu" type="text" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Mode de travail</label>
                    <select wire:model="f.teletravail" class="{{ $input }}">@foreach ($modesTravail as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div><label class="{{ $lab }}">Rémunération</label><input wire:model="f.remuneration" type="text" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Date limite de candidature</label><input wire:model="f.deadline" type="date" class="{{ $input }}" /></div>
                <div class="sm:col-span-2"><label class="{{ $lab }}">Présentation</label><textarea wire:model="f.description" rows="3" class="{{ $input }}"></textarea></div>
                <div><label class="{{ $lab }}">Missions</label><textarea wire:model="f.missions" rows="2" class="{{ $input }}"></textarea></div>
                <div><label class="{{ $lab }}">Profil recherché</label><textarea wire:model="f.profil" rows="2" class="{{ $input }}"></textarea></div>
                <div class="sm:col-span-2"><label class="{{ $lab }}">Compétences (séparées par des virgules)</label><input wire:model="f.competences" type="text" class="{{ $input }}" /></div>
                <div class="sm:col-span-2"><x-ui.media-field label="Fiche de poste (optionnel)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" /></div>
                @if ($editingId)
                    <div class="sm:col-span-2"><label class="{{ $lab }}">Note à l'auteur (optionnel)</label><input wire:model="note" type="text" class="{{ $input }}" /></div>
                @endif
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
                <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">{{ $editingId ? "Enregistrer et prévenir l'auteur" : 'Publier' }}</button>
            </div>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Livewire\Admin\Emplois::FILTRES as $cle => $libelle)
                    <button wire:click="setFiltre('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $filtre === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                        {{ $libelle }} <span class="{{ $filtre === $cle ? 'text-white/70' : ($cle === 'signalee' && ($compteurs['signalee'] ?? 0) ? 'font-extrabold text-accent' : 'text-[#9AA6B8]') }}">{{ $compteurs[$cle] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Offre, entreprise, ville, auteur…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
            <a href="{{ route('admin.export', ['dataset' => 'opportunites']) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-2 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Exporter</a>
        </div>

        <div class="space-y-3">
            @forelse ($offres as $o)
                @php $sc = \App\Support\OffreStatus::statut($o['statut']); $ouv = $ouvert === $o['id']; @endphp
                <div wire:key="of-{{ $o['id'] }}" class="rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <button type="button" wire:click="basculer({{ $o['id'] }})" class="flex w-full flex-wrap items-center gap-4 p-4 text-left sm:px-5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-[10px] text-white" style="background: {{ $o['groupe']['couleur'] ?? '#031D59' }}"><x-ui.icon :name="$o['groupe']['icone'] ?? 'nav-briefcase'" class="size-5" /></span>
                        <div class="min-w-[200px] flex-1">
                            <div class="mb-0.5 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: {{ $sc }}1A; color: {{ $sc }}">{{ $o['statut_label'] }}</span>
                                @if (! empty($o['signalements']))<span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-white">{{ count($o['signalements']) }} signalement{{ count($o['signalements']) > 1 ? 's' : '' }}</span>@endif
                                <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $o['type_label'] }}{{ $o['contrat_label'] ? ' '.$o['contrat_label'] : '' }} · {{ $o['entreprise'] }} · {{ $o['lieu'] }}</span>
                            </div>
                            <p class="text-[14px] font-bold text-brand">{{ $o['title'] }}</p>
                            <p class="text-[11.5px] text-[#5B677A]">Par {{ $o['auteur'] ? $o['auteur']['prenom'].' '.$o['auteur']['nom'] : 'REJCC' }} · proposée {{ \Illuminate\Support\Carbon::parse($o['created_at'])->locale('fr')->diffForHumans() }}{{ $o['statut'] === 'publiee' ? ' · '.$o['vues'].' vue'.($o['vues'] > 1 ? 's' : '') : '' }}{{ ($o['nb_candidatures'] ?? 0) ? ' · '.$o['nb_candidatures'].' candidature'.($o['nb_candidatures'] > 1 ? 's' : '') : '' }}</p>
                        </div>
                        <x-ui.icon :name="$ouv ? 'chevron-down' : 'chevron-right'" class="size-4 shrink-0 text-[#9AA6B8]" />
                    </button>

                    @if ($ouv)
                        <div wire:key="d-{{ $o['id'] }}" class="panel-enter border-t border-[#EDF0F5] bg-[#F8FAFC] p-4 sm:p-5">
                            <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
                                <div class="min-w-0 space-y-2.5 text-[13px] text-ink">
                                    <p class="whitespace-pre-line leading-relaxed">{{ $o['description'] }}</p>
                                    @foreach (['missions' => 'Missions', 'profil' => 'Profil'] as $k => $t)
                                        @if ($o[$k]) <div><p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ $t }}</p><p class="whitespace-pre-line">{{ $o[$k] }}</p></div> @endif
                                    @endforeach
                                    <p class="text-[12px] text-[#5B677A]">{{ collect([$o['teletravail_label'], $o['remuneration'], $o['duree'], $o['deadline'] ? 'Limite : '.\Illuminate\Support\Carbon::parse($o['deadline'])->format('d/m/Y') : null, $o['expire_le'] ? 'En ligne jusqu\'au '.\Illuminate\Support\Carbon::parse($o['expire_le'])->format('d/m/Y') : null])->filter()->join(' · ') }}</p>
                                    @if (! empty($o['competences']))<p class="text-[12px] text-brand">{{ implode(' · ', $o['competences']) }}</p>@endif
                                    @if ($o['site_url'])<a href="{{ $o['site_url'] }}" target="_blank" rel="noopener" class="text-[12px] text-azure hover:underline">{{ $o['site_url'] }}</a>@endif
                                    @if ($o['motif'] ?? null)<p class="rounded-lg px-3 py-2 text-[12px] font-semibold" style="background: {{ $sc }}12; color: {{ $sc }}">Dernier message à l'auteur : {{ $o['motif'] }}</p>@endif
                                </div>
                                <aside class="h-fit rounded-[12px] border border-brand/10 bg-white p-4 text-[12.5px]">
                                    <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Auteur</p>
                                    @if ($o['auteur'])
                                        <div class="flex items-center gap-2.5">
                                            <x-messagerie.avatar :personne="$o['auteur']" taille="size-9" texte="text-[11px]" />
                                            <p class="font-bold text-brand">{{ $o['auteur']['prenom'] }} {{ $o['auteur']['nom'] }}</p>
                                        </div>
                                        <p class="mt-2 break-all text-[#5B677A]">{{ $o['auteur_email'] }}</p>
                                        <p class="text-[#5B677A]">{{ $o['auteur_telephone'] }}</p>
                                    @endif
                                    @if ($o['contact'] ?? null)<p class="mt-2 text-[#5B677A]"><span class="font-semibold text-brand">Contact de l'offre :</span> {{ $o['contact'] }}</p>@endif
                                    @if (! empty($o['candidatures_par_statut']))
                                        <p class="mb-1 mt-3 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Candidatures</p>
                                        @foreach ($o['candidatures_par_statut'] as $st => $n)
                                            <p class="text-[12px] text-[#5B677A]">{{ ['recue' => 'Reçues', 'preselection' => 'Présélectionnées', 'retenue' => 'Retenues', 'non_retenue' => 'Non retenues', 'retiree' => 'Retirées'][$st] ?? $st }} : <span class="font-bold text-brand">{{ $n }}</span></p>
                                        @endforeach
                                    @endif
                                </aside>
                            </div>

                            @if (! empty($o['signalements']))
                                <div class="mt-4 rounded-[12px] border border-accent/25 bg-accent/[.04] p-4">
                                    <p class="text-[12.5px] font-bold text-accent">Signalée par des membres</p>
                                    @foreach ($o['signalements'] as $sg)
                                        <p class="mt-1 text-[12.5px] text-ink">« {{ $sg['motif'] }} » <span class="text-[#9AA6B8]">— {{ $sg['par'] }}, {{ \Illuminate\Support\Carbon::parse($sg['date'])->locale('fr')->diffForHumans() }}</span></p>
                                    @endforeach
                                    <button wire:click="classerSignalements({{ $o['id'] }})" class="mt-2 text-[12px] font-semibold text-[#5B677A] hover:text-brand hover:underline">Classer sans suite</button>
                                </div>
                            @endif

                            @if ($decision)
                                <div wire:key="dec-{{ $decision }}" class="panel-enter mt-4 rounded-[12px] border border-brand/10 bg-white p-4">
                                    <p class="text-[13px] font-bold text-brand">{{ ['publier' => "Publier l'offre", 'corriger' => 'Demander une correction', 'refuser' => "Refuser l'offre", 'retirer' => "Retirer l'offre"][$decision] }}</p>
                                    @if ($decision !== 'publier')
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach (\App\Livewire\Admin\Emplois::MOTIFS[$decision] as $m)
                                                <button type="button" wire:click="$set('motif', @js($m))" class="rounded-full border border-brand/15 px-3 py-1 text-left text-[11.5px] text-brand hover:bg-cloud">{{ $m }}</button>
                                            @endforeach
                                        </div>
                                        <textarea wire:model="motif" rows="2" placeholder="Motif transmis à l'auteur" class="mt-2 w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                                    @else
                                        <p class="mt-1 text-[12px] text-[#5B677A]">L'offre sera en ligne {{ $o['deadline'] ? "jusqu'à sa date limite" : 'pendant 60 jours' }} et l'auteur sera notifié.</p>
                                    @endif
                                    @if ($erreur) <p class="mt-1 text-xs font-semibold text-accent">{{ $erreur }}</p> @endif
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="confirmer" wire:loading.attr="disabled" data-test="confirmer-decision-offre" class="btn-tap rounded-[9px] px-4 py-2 text-xs font-bold text-white disabled:opacity-60 {{ in_array($decision, ['refuser', 'retirer'], true) ? 'bg-accent' : 'bg-brand' }}">Confirmer et prévenir l'auteur</button>
                                        <button wire:click="annulerDecision" class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Retour</button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    @if (in_array($o['statut'], ['en_attente', 'a_corriger', 'refusee'], true))
                                        <button wire:click="preparer({{ $o['id'] }}, 'publier')" data-test="publier-offre" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#1C8F4C] px-4 py-2 text-xs font-bold text-white"><x-ui.icon name="check" class="size-3.5" /> Publier</button>
                                    @endif
                                    @if ($o['statut'] === 'en_attente')
                                        <button wire:click="preparer({{ $o['id'] }}, 'corriger')" data-test="corriger-offre" class="btn-tap rounded-full border border-[#B27007]/40 bg-white px-4 py-2 text-xs font-bold text-[#B27007]">Demander une correction</button>
                                        <button wire:click="preparer({{ $o['id'] }}, 'refuser')" class="btn-tap rounded-full border border-accent/30 bg-white px-4 py-2 text-xs font-bold text-accent">Refuser</button>
                                    @endif
                                    @if (in_array($o['statut'], ['publiee', 'expiree'], true))
                                        <button wire:click="preparer({{ $o['id'] }}, 'retirer')" class="btn-tap rounded-full border border-accent/30 bg-white px-4 py-2 text-xs font-bold text-accent">Retirer</button>
                                    @endif
                                    <button wire:click="openEdit({{ $o['id'] }})" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Corriger l'offre</button>
                                    <button wire:click="delete({{ $o['id'] }})" wire:confirm="Supprimer définitivement « {{ $o['title'] }} » ?" class="icon-btn ml-auto rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-[16px] border border-dashed border-brand/20 bg-white py-12 text-center">
                    <p class="text-sm font-bold text-brand">{{ $filtre === 'en_attente' ? 'Aucune offre à valider' : 'Aucune offre dans cette liste' }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
