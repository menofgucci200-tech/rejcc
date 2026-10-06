<div>
    <x-admin-light.topbar title="Projets" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-4">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Projets du réseau</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Examinez les projets proposés par les membres : validez-les pour les rendre visibles du réseau, demandez des précisions ou refusez-les avec un motif. Le porteur est notifié à chaque décision.</p>
        </div>

        @if ($erreur && ! $decision && ! $corrigerId)
            <p wire:key="flash-ko" class="panel-enter mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif
        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Livewire\Admin\Projets::FILTRES as $cle => $libelle)
                    <button wire:click="setFiltre('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $filtre === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                        {{ $libelle }} <span class="{{ $filtre === $cle ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $compteurs[$cle] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Projet, porteur, ville…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
            <a href="{{ route('admin.export', ['dataset' => 'projets']) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-2 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Exporter</a>
        </div>

        <div class="space-y-3">
            @forelse ($projets as $p)
                @php
                    $sc = \App\Support\ProjectStatus::color($p['statut']);
                    $ouv = $ouvert === $p['id'];
                @endphp
                <div wire:key="pr-{{ $p['id'] }}" class="rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <button type="button" wire:click="basculer({{ $p['id'] }})" class="flex w-full flex-wrap items-center gap-4 p-4 text-left sm:px-5">
                        @if ($p['image'])
                            <img src="{{ $p['image'] }}" alt="" class="size-12 shrink-0 rounded-[10px] object-cover">
                        @else
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-[10px] text-white" style="background: {{ $p['groupe']['couleur'] ?? '#031D59' }}"><x-ui.icon :name="$p['groupe']['icone'] ?? 'nav-projects'" class="size-5" /></span>
                        @endif
                        <div class="min-w-[200px] flex-1">
                            <div class="mb-0.5 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: {{ $sc }}1A; color: {{ $sc }}">{{ $p['statut_label'] }}</span>
                                @if ($p['a_la_une']) <span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[10px] font-bold text-[#B27007]">★ À la une</span> @endif
                                @if ($p['public_ok']) <span class="rounded-full bg-azure/10 px-2 py-0.5 text-[10px] font-bold text-azure">Site public accepté</span> @endif
                                <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $p['stade_label'] }}{{ $p['groupe'] ? ' · '.$p['groupe']['nom'] : '' }}{{ $p['ville'] ? ' · '.$p['ville'] : '' }}</span>
                            </div>
                            <p class="text-[14px] font-bold text-brand">{{ $p['title'] }}</p>
                            <p class="text-[11.5px] text-[#5B677A]">Par {{ $p['porteur'] ? $p['porteur']['prenom'].' '.$p['porteur']['nom'] : '—' }} · soumis {{ $p['soumis_at'] ? \Illuminate\Support\Carbon::parse($p['soumis_at'])->locale('fr')->diffForHumans() : '—' }}</p>
                        </div>
                        <x-ui.icon :name="$ouv ? 'chevron-down' : 'chevron-right'" class="size-4 shrink-0 text-[#9AA6B8]" />
                    </button>

                    @if ($ouv)
                        <div wire:key="detail-{{ $p['id'] }}" class="panel-enter border-t border-[#EDF0F5] bg-[#F8FAFC] p-4 sm:p-5">
                            <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
                                <div class="min-w-0 space-y-3 text-[13px] text-ink">
                                    @if ($p['accroche']) <p class="font-semibold text-[#5B677A]">{{ $p['accroche'] }}</p> @endif
                                    <p class="whitespace-pre-line leading-relaxed">{{ $p['description'] }}</p>
                                    @foreach (['probleme' => 'Le problème', 'solution' => 'La solution', 'cible' => 'Pour qui ?', 'impact' => 'Impact attendu'] as $k => $t)
                                        @if ($p[$k] ?? null)
                                            <div><p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ $t }}</p><p class="whitespace-pre-line">{{ $p[$k] }}</p></div>
                                        @endif
                                    @endforeach
                                    @if (! empty($p['besoins']))
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($p['besoins'] as $b) <span class="rounded-full bg-accent/[.08] px-2.5 py-0.5 text-[11.5px] font-bold text-accent">{{ $besoins[$b] ?? $b }}</span> @endforeach
                                        </div>
                                    @endif
                                    @if ($p['lien']) <a href="{{ $p['lien'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-azure hover:underline"><x-ui.icon name="external-link" class="size-3.5" /> {{ $p['lien'] }}</a> @endif
                                    @if ($p['motif'] ?? null)
                                        <p class="rounded-lg px-3 py-2 text-[12px] font-semibold" style="background: {{ $sc }}12; color: {{ $sc }}">Dernier message au porteur : {{ $p['motif'] }}</p>
                                    @endif
                                </div>
                                <aside class="h-fit rounded-[12px] border border-brand/10 bg-white p-4 text-[12.5px]">
                                    <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Porteur</p>
                                    @if ($p['porteur'])
                                        <div class="flex items-center gap-2.5">
                                            <x-messagerie.avatar :personne="$p['porteur']" taille="size-9" texte="text-[11px]" />
                                            <div class="min-w-0"><p class="font-bold text-brand">{{ $p['porteur']['prenom'] }} {{ $p['porteur']['nom'] }}</p><p class="truncate text-[11.5px] text-[#5B677A]">{{ $p['porteur']['titre'] }}</p></div>
                                        </div>
                                        <p class="mt-2 break-all text-[#5B677A]">{{ $p['porteur_email'] }}</p>
                                        <p class="text-[#5B677A]">{{ $p['porteur_telephone'] }}</p>
                                        <a href="{{ route('admin.members', ['q' => $p['porteur_email']]) }}" class="mt-2 inline-block text-[12px] font-semibold text-azure hover:underline">Voir le compte</a>
                                    @else
                                        <p class="text-[#9AA6B8]">Compte supprimé</p>
                                    @endif
                                    @if (! empty($p['equipe']))
                                        <p class="mb-1.5 mt-3 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Équipe sur la plateforme</p>
                                        @foreach ($p['equipe'] as $x)
                                            <p class="text-[12px] text-[#5B677A]"><span class="font-semibold text-brand">{{ $x['membre']['prenom'] }} {{ $x['membre']['nom'] }}</span>{{ $x['role'] ? ' · '.$x['role'] : '' }}</p>
                                        @endforeach
                                    @endif
                                    <p class="mt-3 text-[11.5px] text-[#9AA6B8]">{{ $p['equipe_taille'] }} sur la plateforme · {{ $p['members_count'] }} déclarée{{ $p['members_count'] > 1 ? 's' : '' }} par le porteur · {{ $p['vues'] }} vue{{ $p['vues'] > 1 ? 's' : '' }} · {{ $p['nb_suivis'] ?? 0 }} suivi{{ ($p['nb_suivis'] ?? 0) > 1 ? 's' : '' }}</p>
                                </aside>
                            </div>

                            {{-- Correction de la fiche --}}
                            @if ($corrigerId === $p['id'])
                                <div wire:key="corr-{{ $p['id'] }}" class="panel-enter mt-4 grid gap-2.5 rounded-[12px] border border-brand/10 bg-white p-4 sm:grid-cols-2">
                                    <p class="text-[13px] font-bold text-brand sm:col-span-2">Corriger la fiche</p>
                                    <input wire:model="correction.title" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm sm:col-span-2" />
                                    <input wire:model="correction.accroche" type="text" placeholder="Accroche" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm sm:col-span-2" />
                                    <select wire:model="correction.group_id" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm">
                                        <option value="">— Secteur —</option>
                                        @foreach ($categories as $c) <option value="{{ $c['id'] }}">{{ $c['nom'] }}</option> @endforeach
                                    </select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select wire:model="correction.stade" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm">
                                            @foreach ($stades as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                                        </select>
                                        <input wire:model="correction.ville" type="text" placeholder="Ville" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm" />
                                    </div>
                                    <textarea wire:model="correction.description" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm sm:col-span-2"></textarea>
                                    <input wire:model="noteCorrection" type="text" placeholder="Note au porteur (optionnel)" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm sm:col-span-2" />
                                    @if ($erreur) <p class="text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p> @endif
                                    <div class="flex gap-2 sm:col-span-2">
                                        <button wire:click="enregistrerCorrection" class="btn-tap rounded-[9px] bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">Enregistrer et prévenir le porteur</button>
                                        <button wire:click="fermerCorrection" class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Annuler</button>
                                    </div>
                                </div>
                            @endif

                            {{-- Décision --}}
                            @if ($decision)
                                <div wire:key="decision-{{ $decision }}" class="panel-enter mt-4 rounded-[12px] border border-brand/10 bg-white p-4">
                                    <p class="text-[13px] font-bold text-brand">{{ ['valider' => 'Valider le projet', 'completer' => 'Demander des précisions', 'refuser' => 'Refuser le projet'][$decision] }}</p>
                                    @if ($decision === 'valider')
                                        <label class="mt-2 block text-xs font-semibold text-[#5B677A]">Stade du projet</label>
                                        <select wire:model="stadeDecision" class="mt-1 rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm">
                                            @foreach ($stades as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                                        </select>
                                        <label class="mt-3 block text-xs font-semibold text-[#5B677A]">Message au porteur (optionnel)</label>
                                    @else
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach (\App\Livewire\Admin\Projets::MOTIFS[$decision] as $m)
                                                <button type="button" wire:click="$set('motif', @js($m))" class="rounded-full border border-brand/15 px-3 py-1 text-left text-[11.5px] text-brand hover:bg-cloud">{{ $m }}</button>
                                            @endforeach
                                        </div>
                                        <label class="mt-3 block text-xs font-semibold text-[#5B677A]">{{ $decision === 'refuser' ? 'Motif du refus (transmis au porteur)' : 'Ce que le porteur doit compléter' }}</label>
                                    @endif
                                    <textarea wire:model="motif" rows="2" class="mt-1 w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                                    @if ($erreur) <p class="mt-1 text-xs font-semibold text-accent">{{ $erreur }}</p> @endif
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="confirmer" wire:loading.attr="disabled" data-test="confirmer-decision" class="btn-tap rounded-[9px] px-4 py-2 text-xs font-bold text-white disabled:opacity-60 {{ $decision === 'refuser' ? 'bg-accent hover:bg-accent-600' : 'bg-brand hover:bg-brand/90' }}">Confirmer et prévenir le porteur</button>
                                        <button wire:click="annulerDecision" class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Retour</button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    @if ($p['statut'] !== 'valide')
                                        <button wire:click="preparer({{ $p['id'] }}, 'valider', '{{ $p['stade'] }}')" data-test="valider-projet" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#1C8F4C] px-4 py-2 text-xs font-bold text-white hover:bg-[#177A40]"><x-ui.icon name="check" class="size-3.5" /> Valider</button>
                                    @endif
                                    @if (in_array($p['statut'], ['evaluation', 'valide'], true))
                                        <button wire:click="preparer({{ $p['id'] }}, 'completer')" data-test="completer-projet" class="btn-tap rounded-full border border-[#B27007]/40 bg-white px-4 py-2 text-xs font-bold text-[#B27007] hover:bg-[#B27007]/5">Demander des précisions</button>
                                    @endif
                                    @if ($p['statut'] !== 'refuse')
                                        <button wire:click="preparer({{ $p['id'] }}, 'refuser')" data-test="refuser-projet" class="btn-tap rounded-full border border-accent/30 bg-white px-4 py-2 text-xs font-bold text-accent hover:bg-accent/5">Refuser</button>
                                    @endif
                                    @if ($p['statut'] === 'valide')
                                        <button wire:click="basculerUne({{ $p['id'] }})" data-test="une-projet" class="btn-tap rounded-full border border-[#F5A623]/50 bg-white px-4 py-2 text-xs font-bold text-[#B27007] hover:bg-[#F5A623]/10">{{ $p['a_la_une'] ? 'Retirer de la une' : '★ Mettre à la une' }}</button>
                                    @endif
                                    <button wire:click="ouvrirCorrection({{ $p['id'] }})" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Corriger la fiche</button>
                                    <button wire:click="delete({{ $p['id'] }})" wire:confirm="Supprimer définitivement « {{ $p['title'] }} » ? Le porteur ne sera pas prévenu." class="ml-auto icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-[16px] border border-dashed border-brand/20 bg-white py-12 text-center">
                    <p class="text-sm font-bold text-brand">{{ $filtre === 'evaluation' ? 'Aucun projet à évaluer' : 'Aucun projet dans cette liste' }}</p>
                    <p class="mt-1 text-xs text-[#9AA6B8]">Les projets proposés par les membres apparaissent ici.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
