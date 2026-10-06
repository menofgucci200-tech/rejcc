<div>
    <x-admin-light.topbar title="Événements" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $statuts = ['publie' => ['Publié', 'bg-[#22A85A]/10 text-[#1C8F4C]'], 'brouillon' => ['Brouillon', 'bg-[#E0A100]/15 text-[#9A6B00]'], 'annule' => ['Annulé', 'bg-accent/10 text-accent']];
    @endphp

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Événements</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Préparez vos événements en brouillon, publiez-les en les annonçant aux membres, ouvrez si besoin l'inscription publique par QR code pour les non-membres, et suivez tous les inscrits dans une seule liste.</p>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Nouvel événement</button>
        </div>

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm && ! $annulerId)
            <p wire:key="flash-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
        @endif

        {{-- ══════════ Formulaire ══════════ --}}
        @if ($showForm)
            <div wire:key="form-evenement" class="panel-enter mb-6 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">
                        {{ $editingId ? "Modifier l'événement" : 'Nouvel événement' }}
                        @if ($editingId)
                            <span class="ml-1.5 rounded-full px-2 py-0.5 text-[10.5px] font-bold {{ $statuts[$statut][1] ?? '' }}">{{ $statuts[$statut][0] ?? $statut }}</span>
                        @endif
                    </p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand" title="Fermer"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8] sm:col-span-2">L'événement</p>
                    <div>
                        <label class="{{ $lab }}">Titre</label>
                        <input wire:model="title" type="text" class="{{ $input }}" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Catégorie (ex : Atelier, Forum, Networking)</label>
                        <input wire:model="category" type="text" list="categories-evenements" class="{{ $input }}" />
                        <datalist id="categories-evenements">
                            @foreach ($categories as $c) <option value="{{ $c }}"></option> @endforeach
                        </datalist>
                        @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Début</label>
                        <input wire:model="startsAt" type="datetime-local" class="{{ $input }}" />
                        @error('startsAt') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Fin (optionnel)</label>
                        <input wire:model="endsAt" type="datetime-local" class="{{ $input }}" />
                        @error('endsAt') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Horaire affiché (ex : 14 h – 17 h)</label>
                        <input wire:model="timeLabel" type="text" class="{{ $input }}" />
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="flex items-center gap-2.5 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                            <input wire:model.live="enLigne" type="checkbox" class="size-4 rounded border-brand/25 text-brand" />
                            Événement en ligne (visioconférence)
                        </label>
                    </div>
                    @if ($enLigne)
                        <div wire:key="champ-visio" class="sm:col-span-2">
                            <label class="{{ $lab }}">Lien de connexion (Zoom, Google Meet…)</label>
                            <input wire:model="lienVisio" type="url" placeholder="https://…" class="{{ $input }}" />
                            <p class="mt-1 text-[11px] text-[#9AA6B8]">Visible uniquement par les inscrits, sur la fiche de l'événement et dans le rappel.</p>
                            @error('lienVisio') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div wire:key="champ-lieu" class="sm:col-span-2">
                            <label class="{{ $lab }}">Lieu (adresse précise : elle ouvre l'itinéraire)</label>
                            <input wire:model="location" type="text" placeholder="Ex : Abidjan, Plateau — Salle Saint-Paul" class="{{ $input }}" />
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Accroche (une phrase, affichée dans les listes)</label>
                        <input wire:model="excerpt" type="text" class="{{ $input }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $lab }}">Description et programme</label>
                        <textarea wire:model="description" rows="4" class="{{ $input }}"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Affiche / visuel de l'événement (image ou lien)" :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                        @error('mediaFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <p class="mt-2 text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8] sm:col-span-2">Inscriptions</p>
                    <div>
                        <label class="{{ $lab }}">Nombre de places (vide = illimité)</label>
                        <input wire:model="capacity" type="number" min="1" class="{{ $input }}" />
                        @error('capacity') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $lab }}">Date limite d'inscription (optionnel)</label>
                        <input wire:model="dateLimite" type="datetime-local" class="{{ $input }}" />
                        @error('dateLimite') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <label class="flex items-start gap-2.5 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                            <input wire:model="inscriptionsOuvertes" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                            <span><span class="font-semibold text-brand">Inscriptions ouvertes</span><span class="block text-[11.5px] text-[#9AA6B8]">Décochez pour fermer les inscriptions sans dépublier l'événement.</span></span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                            <input wire:model.live="reserveAbonnes" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                            <span><span class="font-semibold text-brand">Réservé aux abonnés</span><span class="block text-[11.5px] text-[#9AA6B8]">Seuls les membres à jour de leur abonnement annuel peuvent s'inscrire (les autres voient l'événement).</span></span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                            <input wire:model.live="inscriptionPublique" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                            <span><span class="font-semibold text-brand">Inscription publique par QR code</span><span class="block text-[11.5px] text-[#9AA6B8]">Les non-membres s'inscrivent via un lien et un QR code à imprimer. Ils rejoignent la même liste et comptent dans les places.</span></span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                            <input wire:model="attestation" type="checkbox" data-test="attestation-evenement" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                            <span><span class="font-semibold text-brand">Délivrer une attestation de participation</span><span class="block text-[11.5px] text-[#9AA6B8]">Après l'événement, chaque présent pointé (membre ou invité) reçoit une attestation officielle vérifiable.</span></span>
                        </label>
                    </div>

                    @if ($inscriptionPublique)
                        <div wire:key="questions" class="sm:col-span-2">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[13px] font-bold text-brand">Questions du formulaire public</p>
                                <button type="button" wire:click="addChamp" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">
                                    <x-ui.icon name="plus" class="size-3.5" /> Ajouter une question
                                </button>
                            </div>
                            <p class="mb-3 text-[11px] text-[#9AA6B8]">Le formulaire demande toujours prénom, nom, téléphone et e-mail (facultatif). Ajoutez ce qui vous est utile : domaine d'activité, statut, document…</p>
                            @forelse ($champs as $i => $f)
                                <div class="mb-2.5 rounded-[12px] border border-brand/10 bg-cloud/40 p-3" wire:key="champ-{{ $i }}">
                                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[1fr_170px_auto]">
                                        <input wire:model="champs.{{ $i }}.label" type="text" placeholder="Intitulé (ex : Domaine d'activité)" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                        <select wire:model.live="champs.{{ $i }}.type" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure">
                                            <option value="text">Texte court</option>
                                            <option value="textarea">Paragraphe</option>
                                            <option value="select">Liste déroulante</option>
                                            <option value="checkbox">Case à cocher</option>
                                            <option value="file">Fichier à joindre</option>
                                        </select>
                                        <div class="flex items-center gap-2">
                                            <label class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#5B677A]">
                                                <input wire:model="champs.{{ $i }}.required" type="checkbox" class="size-3.5 rounded border-brand/25 text-brand" /> Obligatoire
                                            </label>
                                            <button type="button" wire:click="removeChamp({{ $i }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer cette question"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                        </div>
                                    </div>
                                    @if (($f['type'] ?? '') === 'select')
                                        <textarea wire:model="champs.{{ $i }}.options" rows="3" placeholder="Une option par ligne" class="mt-2.5 w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-[10px] border border-dashed border-brand/15 py-4 text-center text-[12px] text-[#9AA6B8]">Aucune question supplémentaire.</p>
                            @endforelse
                        </div>
                    @endif

                    @if (! $dejaAnnonce && $statut !== 'annule')
                        <label wire:key="annonce" class="flex items-start gap-2.5 rounded-[9px] border border-azure/20 bg-azure/[.05] px-3 py-2.5 text-sm text-ink/80 sm:col-span-2">
                            <input wire:model="annoncer" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                            <span><span class="font-semibold text-brand">Annoncer aux membres à la publication</span><span class="block text-[11.5px] text-[#9AA6B8]">Chaque membre reçoit une notification sur la plateforme avec le lien vers l'événement (une seule fois).</span></span>
                        </label>
                    @endif
                    @if ($editingId && $statut === 'publie' && $nbInscritsForm > 0)
                        <p wire:key="avert-report" class="flex items-start gap-1.5 rounded-[9px] bg-[#E0A100]/10 px-3 py-2 text-[12px] text-[#7A5600] sm:col-span-2">
                            <x-ui.icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                            Si vous changez la date ou le lieu, les {{ $nbInscritsForm }} inscrit{{ $nbInscritsForm > 1 ? 's' : '' }} en {{ $nbInscritsForm > 1 ? 'seront prévenus' : 'sera prévenu' }} automatiquement.
                        </p>
                    @endif

                    @if ($erreur)
                        <p wire:key="form-erreur" class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2">{{ $erreur }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2 sm:col-span-2">
                        @if ($statut === 'publie' || $statut === 'annule')
                            <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 disabled:opacity-60">Enregistrer les modifications</button>
                            @if ($statut === 'publie')
                                <button wire:click="save('brouillon')" wire:loading.attr="disabled" wire:target="save" wire:confirm="Repasser en brouillon ? L'événement ne sera plus visible des membres." class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2.5 text-sm font-bold text-brand hover:bg-cloud disabled:opacity-60">Repasser en brouillon</button>
                            @endif
                        @else
                            <button wire:click="save('publie')" wire:loading.attr="disabled" wire:target="save" class="btn-tap rounded-[9px] bg-accent px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-accent-600 disabled:opacity-60">Publier</button>
                            <button wire:click="save('brouillon')" wire:loading.attr="disabled" wire:target="save" class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2.5 text-sm font-bold text-brand hover:bg-cloud disabled:opacity-60">Enregistrer en brouillon</button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════ Filtres ══════════ --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Livewire\Admin\Evenements::FILTRES as $cle => $libelle)
                    <button wire:click="setFiltre('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $filtre === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                        {{ $libelle }} <span class="{{ $filtre === $cle ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $compteurs[$cle] }}</span>
                    </button>
                @endforeach
            </div>
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Rechercher un événement…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
            <a href="{{ route('admin.export', ['dataset' => 'evenements']) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-2 text-xs font-bold text-brand hover:bg-cloud" title="Exporter la liste des événements">
                <x-ui.icon name="download" class="size-3.5" /> Exporter
            </a>
        </div>

        {{-- ══════════ Liste ══════════ --}}
        <div class="space-y-3">
            @forelse ($evenements as $ev)
                @php
                    $n = (int) $ev['registrations_count'];
                    $pres = (int) $ev['presents_count'];
                    $pct = $ev['capacity'] ? min(100, (int) round($n / $ev['capacity'] * 100)) : null;
                @endphp
                <div wire:key="ev-{{ $ev['id'] }}" class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $ev['statut'] === 'annule' || ($ev['passe'] && $filtre !== 'passes') ? 'opacity-75' : '' }}">
                    <div class="flex flex-wrap items-center gap-4 p-4 sm:p-5">
                        <div class="flex size-14 shrink-0 flex-col items-center justify-center rounded-[12px] bg-cloud text-brand">
                            <span class="text-base font-extrabold leading-none">{{ $ev['jour'] }}</span>
                            <span class="text-[9.5px] font-bold tracking-wide">{{ $ev['mois'] }}</span>
                            <span class="text-[8.5px] font-semibold text-[#9AA6B8]">{{ $ev['annee'] }}</span>
                        </div>
                        <div class="min-w-[200px] flex-1">
                            <div class="mb-1 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full px-2 py-0.5 text-[9.5px] font-bold" style="background: {{ $ev['tagColor'] }}1A; color: {{ $ev['tagColor'] }}">{{ mb_strtoupper($ev['category']) }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[9.5px] font-bold {{ $statuts[$ev['statut']][1] ?? '' }}">{{ $statuts[$ev['statut']][0] ?? $ev['statut'] }}</span>
                                @if ($ev['passe'] && $ev['statut'] === 'publie') <span class="rounded-full bg-[#9AA6B8]/15 px-2 py-0.5 text-[9.5px] font-bold text-[#5B677A]">Passé</span> @endif
                                @if (! $ev['inscriptions_ouvertes'] && $ev['statut'] === 'publie' && ! $ev['passe']) <span class="rounded-full bg-[#9AA6B8]/15 px-2 py-0.5 text-[9.5px] font-bold text-[#5B677A]">Inscriptions fermées</span> @endif
                                @if ($ev['reserve_abonnes']) <span class="rounded-full bg-brand/10 px-2 py-0.5 text-[9.5px] font-bold text-brand">Réservé aux abonnés</span> @endif
                                @if ($ev['inscription_publique']) <span class="inline-flex items-center gap-1 rounded-full bg-azure/10 px-2 py-0.5 text-[9.5px] font-bold text-azure"><x-ui.icon name="qr-code" class="size-2.5" /> Inscription publique</span> @endif
                                @if ($ev['en_ligne']) <span class="rounded-full bg-[#7C3AED]/10 px-2 py-0.5 text-[9.5px] font-bold text-[#7C3AED]">En ligne</span> @endif
                            </div>
                            <p class="text-[14px] font-bold text-brand">{{ $ev['title'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $ev['date_label'] }}{{ $ev['en_ligne'] ? ' · En ligne' : ($ev['location'] ? ' · '.$ev['location'] : '') }}</p>
                            @if ($ev['statut'] === 'annule' && $ev['motif_annulation'])
                                <p class="mt-1 text-[11.5px] text-accent">Motif : {{ $ev['motif_annulation'] }}</p>
                            @endif
                        </div>
                        <div class="w-full sm:w-48">
                            <div class="mb-1 flex items-baseline justify-between">
                                <span class="text-[18px] font-extrabold text-brand">{{ $n }}</span>
                                <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $ev['capacity'] ? 'sur '.$ev['capacity'].' places' : 'inscrit'.($n > 1 ? 's' : '') }}</span>
                            </div>
                            @if ($pct !== null)
                                <div class="h-1.5 overflow-hidden rounded-full bg-cloud">
                                    <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $pct >= 100 ? '#AC0100' : 'linear-gradient(90deg,#4F6FBF,#22A85A)' }}"></div>
                                </div>
                            @endif
                            <p class="mt-1 text-[11px] text-[#9AA6B8]">
                                @if ((int) $ev['invites_count']) dont {{ $ev['invites_count'] }} invité{{ $ev['invites_count'] > 1 ? 's' : '' }} @endif
                                @if ($ev['passe'] && $n) · {{ $pres }} présent{{ $pres > 1 ? 's' : '' }} ({{ (int) round($pres / $n * 100) }} %) @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1 border-t border-[#EDF0F5] px-3 py-2 sm:px-4">
                        <button wire:click="openDetail({{ $ev['id'] }}, 'inscrits')" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold {{ $detailId === $ev['id'] && $detailTab === 'inscrits' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                            <x-ui.icon name="users" class="size-3.5" /> Inscrits ({{ $n }})
                        </button>
                        @if ($ev['inscription_publique'])
                            <button wire:click="openDetail({{ $ev['id'] }}, 'qr')" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold {{ $detailId === $ev['id'] && $detailTab === 'qr' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                                <x-ui.icon name="qr-code" class="size-3.5" /> QR &amp; lien public
                            </button>
                        @endif
                        @if ($ev['statut'] !== 'brouillon')
                            <button wire:click="openDetail({{ $ev['id'] }}, 'message')" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold {{ $detailId === $ev['id'] && $detailTab === 'message' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                                <x-ui.icon name="send" class="size-3.5" /> Écrire aux inscrits
                            </button>
                        @endif
                        @if ($ev['statut'] === 'publie')
                            <a href="{{ route('admin.evenements.pointage', $ev['id']) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">
                                <x-ui.icon name="user-check" class="size-3.5" /> Pointage
                            </a>
                        @endif
                        <div class="ml-auto flex items-center gap-1">
                            @if ($ev['statut'] === 'publie' && ! $ev['passe'])
                                <button wire:click="ouvrirAnnulation({{ $ev['id'] }})" class="btn-tap rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#5B677A] hover:bg-accent/10 hover:text-accent">Annuler l'événement</button>
                            @elseif ($ev['statut'] === 'annule' && ! $ev['passe'])
                                <button wire:click="retablir({{ $ev['id'] }})" wire:confirm="Rétablir « {{ $ev['title'] }} » ? Les inscrits seront prévenus." class="btn-tap rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#5B677A] hover:bg-cloud hover:text-brand">Rétablir</button>
                            @endif
                            <button wire:click="openEdit({{ $ev['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $ev['id'] }})" wire:confirm="Supprimer définitivement « {{ $ev['title'] }} » et ses {{ $n }} inscription(s) ? Pour prévenir les inscrits, utilisez plutôt « Annuler l'événement »." class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>

                    {{-- Annulation --}}
                    @if ($annulerId === $ev['id'])
                        <div wire:key="annuler-{{ $ev['id'] }}" class="panel-enter border-t border-[#EDF0F5] bg-accent/[.03] p-4 sm:p-5">
                            <p class="text-[13px] font-bold text-accent">Annuler « {{ $ev['title'] }} »</p>
                            <p class="mb-2 text-[11.5px] text-[#5B677A]">Les {{ $n }} inscrit{{ $n > 1 ? 's' : '' }} recevront ce motif (notification pour les membres, e-mail pour les invités). L'événement restera visible avec la mention « Annulé ».</p>
                            <textarea wire:model="motif" rows="2" placeholder="Ex : la salle n'est plus disponible ; l'événement sera reprogrammé en novembre." class="{{ $input }}"></textarea>
                            @if ($erreur) <p class="mt-1 text-xs font-semibold text-accent">{{ $erreur }}</p> @endif
                            <div class="mt-2 flex gap-2">
                                <button wire:click="confirmerAnnulation" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600 disabled:opacity-60">Confirmer l'annulation</button>
                                <button wire:click="fermerAnnulation" class="btn-tap rounded-[9px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Retour</button>
                            </div>
                        </div>
                    @endif

                    {{-- Détail --}}
                    @if ($detailId === $ev['id'])
                        <div wire:key="detail-{{ $ev['id'] }}-{{ $detailTab }}" class="panel-enter border-t border-[#EDF0F5] bg-[#F8FAFC] p-4 sm:p-5">
                            @if ($detailTab === 'inscrits')
                                @php $champsEv = $detail['event']['champs'] ?? []; @endphp
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <p class="text-[12px] text-[#5B677A]">
                                        <span class="font-bold text-brand">{{ $detail['total'] ?? 0 }}</span> inscrit{{ ($detail['total'] ?? 0) > 1 ? 's' : '' }}
                                        · {{ $detail['membres'] ?? 0 }} membre{{ ($detail['membres'] ?? 0) > 1 ? 's' : '' }}
                                        · {{ $detail['invites'] ?? 0 }} invité{{ ($detail['invites'] ?? 0) > 1 ? 's' : '' }}
                                        · {{ $detail['presents'] ?? 0 }} présent{{ ($detail['presents'] ?? 0) > 1 ? 's' : '' }}
                                    </p>
                                    <input wire:model.live.debounce.400ms="q" type="search" placeholder="Nom, téléphone, e-mail, billet…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
                                    <a href="{{ route('admin.export', ['dataset' => 'participants', 'event' => $ev['id']]) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                        <x-ui.icon name="download" class="size-3.5" /> Exporter (CSV)
                                    </a>
                                    @if (($detail['event']['attestation'] ?? false) && ($detail['presents'] ?? 0) > 0)
                                        <button wire:click="delivrerAttestations({{ $ev['id'] }})" wire:confirm="Délivrer les attestations de participation ? Chaque présent pointé ({{ $detail['presents'] }}) reçoit son attestation officielle." data-confirm-ok="Délivrer" data-test="delivrer-attestations" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                                            <x-ui.icon name="award" class="size-3.5" /> Délivrer les attestations
                                        </button>
                                    @endif
                                </div>
                                @if (empty($detail['inscrits']))
                                    <p class="rounded-[12px] border border-brand/10 bg-white py-8 text-center text-sm text-[#5B677A]">{{ trim($q) !== '' ? 'Aucun inscrit ne correspond à cette recherche.' : 'Aucun inscrit pour le moment.' }}</p>
                                @else
                                    <div class="overflow-x-auto rounded-[12px] border border-brand/10 bg-white">
                                        <table class="w-full min-w-[640px] text-left">
                                            <thead>
                                                <tr class="border-b border-[#EDF0F5] text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">
                                                    <th class="px-4 py-2.5">Participant</th>
                                                    <th class="px-3 py-2.5">Contact</th>
                                                    <th class="px-3 py-2.5">Billet</th>
                                                    <th class="px-3 py-2.5">Inscrit le</th>
                                                    <th class="px-3 py-2.5">Présence</th>
                                                    <th class="px-3 py-2.5"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($detail['inscrits'] as $p)
                                                    <tr wire:key="ins-{{ $p['id'] }}" class="border-b border-[#EDF0F5] last:border-b-0 hover:bg-[#FAFBFD]">
                                                        <td class="px-4 py-2.5">
                                                            <div class="flex items-center gap-2.5">
                                                                <x-messagerie.avatar :personne="['prenom' => $p['prenom'], 'nom' => $p['nom_famille'], 'photo' => $p['photo'], 'role' => $p['role']]" taille="size-8" texte="text-[10px]" />
                                                                <div class="min-w-0">
                                                                    <p class="flex items-center gap-1.5 text-[13px] font-semibold text-brand">
                                                                        {{ $p['nom'] }}
                                                                        @if (count($champsEv) && ! empty($p['reponses']))
                                                                            <button wire:click="basculer({{ $p['id'] }})" class="icon-btn rounded p-0.5 text-azure hover:bg-azure/10" title="Voir les réponses"><x-ui.icon name="{{ $ouvert === $p['id'] ? 'chevron-down' : 'chevron-right' }}" class="size-3.5" /></button>
                                                                        @endif
                                                                    </p>
                                                                    @if ($p['type'] === 'membre')
                                                                        <span class="text-[10.5px] font-bold text-brand/70">Membre n° {{ $p['numero'] }}</span>
                                                                    @else
                                                                        <span class="text-[10.5px] font-bold text-azure">Invité{{ $p['se_dit_membre'] ? ' · se dit membre' : '' }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2.5 text-[12px] text-[#5B677A]">
                                                            <span class="block">{{ $p['telephone'] ?: '—' }}</span>
                                                            <span class="block text-[11.5px] text-[#9AA6B8]">{{ $p['email'] ?: '' }}</span>
                                                        </td>
                                                        <td class="px-3 py-2.5 font-mono text-[11.5px] text-[#5B677A]">{{ $p['billet'] }}</td>
                                                        <td class="px-3 py-2.5 text-[12px] text-[#9AA6B8]">{{ \Carbon\Carbon::parse($p['inscrit_le'])->format('d/m/Y H:i') }}</td>
                                                        <td class="px-3 py-2.5">
                                                            @if ($p['present_at'])
                                                                <span class="rounded-full bg-[#22A85A]/10 px-2 py-0.5 text-[10.5px] font-bold text-[#1C8F4C]">Présent · {{ \Carbon\Carbon::parse($p['present_at'])->format('H\hi') }}</span>
                                                            @else
                                                                <span class="text-[11.5px] text-[#9AA6B8]">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2.5 text-right">
                                                            <button wire:click="retirerInscrit({{ $p['id'] }})" wire:confirm="Retirer {{ $p['nom'] }} de la liste des inscrits ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Retirer"><x-ui.icon name="x" class="size-3.5" /></button>
                                                        </td>
                                                    </tr>
                                                    @if ($ouvert === $p['id'])
                                                        <tr wire:key="rep-{{ $p['id'] }}" class="bg-[#F8FAFC]">
                                                            <td colspan="6" class="px-4 py-3">
                                                                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                                                                    @foreach ($champsEv as $f)
                                                                        @php $val = $p['reponses'][$f['key']] ?? null; @endphp
                                                                        <div>
                                                                            <p class="text-[11px] font-bold text-[#5B677A]">{{ $f['label'] }}</p>
                                                                            @if ($f['type'] === 'file' && $val)
                                                                                <a href="{{ $val }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[12.5px] font-semibold text-azure hover:underline"><x-ui.icon name="file-text" class="size-3.5" /> Ouvrir le fichier</a>
                                                                            @elseif ($f['type'] === 'checkbox')
                                                                                <p class="text-[12.5px] text-ink">{{ $val ? 'Oui' : 'Non' }}</p>
                                                                            @else
                                                                                <p class="text-[12.5px] text-ink">{{ $val !== null && $val !== '' ? $val : '—' }}</p>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                            @elseif ($detailTab === 'qr')
                                <div class="flex flex-col gap-6 sm:flex-row sm:items-center"
                                     x-data="{
                                        copied: false,
                                        download() {
                                            const c = $refs.qr; if (!c) return;
                                            const a = document.createElement('a');
                                            a.href = c.toDataURL('image/png');
                                            a.download = 'qr-' + $el.dataset.slug + '.png';
                                            a.click();
                                        },
                                        copy() { navigator.clipboard.writeText($el.dataset.url); this.copied = true; setTimeout(() => this.copied = false, 1800); }
                                     }" data-url="{{ $ev['url_public'] }}" data-slug="{{ $ev['slug'] }}">
                                    <div wire:ignore class="shrink-0 self-center rounded-2xl border border-brand/10 bg-white p-3 shadow-sm">
                                        <canvas x-ref="qr" x-init="window.QRCode && window.QRCode.toCanvas($refs.qr, $el.closest('[data-url]').dataset.url, { width: 190, margin: 1, color: { dark: '#031D59' } })"></canvas>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] font-bold text-brand">Lien public d'inscription</p>
                                        <p class="mt-1 break-all rounded-lg border border-brand/10 bg-white px-3 py-2 text-[12.5px] text-azure">{{ $ev['url_public'] }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button" @click="download()" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                                                <x-ui.icon name="download" class="size-3.5" /> Télécharger le QR (PNG)
                                            </button>
                                            <button type="button" @click="copy()" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                                <span x-show="!copied" class="inline-flex items-center gap-1.5"><x-ui.icon name="external-link" class="size-3.5" /> Copier le lien</span>
                                                <span x-show="copied" x-cloak class="text-[#1C8F4C]">✓ Lien copié</span>
                                            </button>
                                            <a href="{{ $ev['url_public'] }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                                <x-ui.icon name="eye" class="size-3.5" /> Aperçu
                                            </a>
                                        </div>
                                        <p class="mt-3 text-[11px] text-[#9AA6B8]">Imprimez ce QR sur vos affiches et flyers. Chaque personne inscrite reçoit un billet avec son propre QR code, scanné à l'entrée lors du pointage.</p>
                                        @if ($ev['statut'] !== 'publie')
                                            <p class="mt-2 text-[11.5px] font-semibold text-[#9A6B00]">L'événement n'est pas publié : le formulaire affiche « inscriptions pas encore ouvertes ».</p>
                                        @endif
                                    </div>
                                </div>

                            @else
                                <p class="text-[13px] font-bold text-brand">Écrire à tous les inscrits</p>
                                <p class="mb-2 text-[11.5px] text-[#5B677A]">Les membres le reçoivent en notification sur la plateforme ; les invités qui ont laissé une adresse, par e-mail.</p>
                                <textarea wire:model="texteMessage" rows="3" maxlength="1000" placeholder="Ex : l'accueil ouvre à 8 h 30, pensez à votre billet." class="{{ $input }}"></textarea>
                                <button wire:click="envoyerMessage" wire:loading.attr="disabled" wire:target="envoyerMessage" class="btn-tap mt-2 inline-flex items-center gap-1.5 rounded-[9px] bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90 disabled:opacity-60">
                                    <x-ui.icon name="send" class="size-3.5" /> Envoyer à {{ $n }} inscrit{{ $n > 1 ? 's' : '' }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-[18px] border border-dashed border-brand/20 bg-white py-14 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand/[.06] text-brand"><x-ui.icon name="calendar-days" class="size-6" /></span>
                    <p class="mt-3 text-sm font-bold text-brand">{{ trim($recherche) !== '' ? 'Aucun événement ne correspond à cette recherche.' : 'Aucun événement dans cette liste.' }}</p>
                    <p class="mt-1 text-xs text-[#9AA6B8]">Cliquez sur « + Nouvel événement » pour en préparer un.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
