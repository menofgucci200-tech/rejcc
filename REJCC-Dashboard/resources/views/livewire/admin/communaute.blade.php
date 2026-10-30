@php
    use Illuminate\Support\Carbon;

    $salons = $donnees['salons'] ?? [];
    $signalements = collect($donnees['signalements'] ?? []);
    $aExaminer = $signalements->where('statut', 'nouveau')->values();
    $traites = $signalements->where('statut', '!=', 'nouveau')->values();
    $stats = $donnees['stats'] ?? [];
    $modes = $donnees['modes_lents'] ?? [0 => 'Désactivé'];
    $date = fn (?string $d) => $d ? Carbon::parse($d)->setTimezone(config('app.timezone'))->locale('fr')->isoFormat('D MMM à HH:mm') : '';
    $onglets = [
        'salons' => 'Salons',
        'signalements' => 'Signalements'.($aExaminer->count() ? ' ('.$aExaminer->count().')' : ''),
        'journal' => 'Journal',
        'reglages' => 'Mots interdits',
    ];
@endphp

<div>
    <x-admin-light.topbar title="Communauté" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Communauté REJCC</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-3xl text-xs text-[#5B677A]">Les salons de discussion de tous les membres, qui remplacent les communautés WhatsApp. Créez et réglez les salons, nommez les référents, traitez les messages signalés.</p>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif
        @if ($erreur)
            <p data-test="erreur-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3.5 py-1.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="size-3.5" /> {{ $erreur }}</p>
        @endif

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Communauté indisponible (accès à la rubrique « Communauté (salons de discussion) » requis).</p>
        @else
            {{-- Chiffres de la semaine --}}
            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['Messages (7 jours)', $stats['messages_semaine'] ?? 0],
                    ['Membres actifs (7 jours)', $stats['actifs_semaine'] ?? 0],
                    ['Membres venus dans la Communauté', $stats['membres_venus'] ?? 0],
                    ['Messages signalés à examiner', $stats['signalements'] ?? 0],
                ] as [$libelle, $valeur])
                    <div class="rounded-[16px] border border-brand/10 bg-white p-4">
                        <p class="text-[22px] font-extrabold tabular-nums text-brand">{{ number_format($valeur, 0, ',', ' ') }}</p>
                        <p class="text-[11.5px] font-semibold text-[#5B677A]">{{ $libelle }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-4 flex flex-wrap gap-1.5">
                @foreach ($onglets as $cle => $libelle)
                    <button type="button" wire:click="$set('onglet', '{{ $cle }}')" data-test="onglet-{{ $cle }}" class="rounded-full px-3.5 py-1.5 text-[12px] font-bold {{ $onglet === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }}</button>
                @endforeach
            </div>

            {{-- Publier une annonce --}}
            <div class="mb-6 rounded-[18px] border border-brand/10 bg-white p-5" x-data="{ ouvert: false }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[14px] font-bold text-brand">📣 Publier une annonce</p>
                        <p class="text-[12px] text-[#5B677A]">Publiée dans le canal Annonces de la Communauté. Tous les membres reçoivent une notification.</p>
                    </div>
                    <button type="button" x-on:click="ouvert = ! ouvert" data-test="ouvrir-annonce" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud" x-text="ouvert ? 'Fermer' : 'Rédiger'">Rédiger</button>
                </div>
                <div x-show="ouvert" x-transition style="display: none" class="mt-3">
                    <textarea wire:model="annonce" rows="4" maxlength="4000" data-test="texte-annonce" placeholder="Ex. : Assemblée générale le samedi 15 novembre à 9h00 à la paroisse Saint-Jacques des Deux-Plateaux." class="w-full rounded-[12px] border border-brand/15 p-3 text-[13px] outline-none focus:border-azure"></textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="button" wire:click="publierAnnonce" wire:confirm="Publier cette annonce ? Tous les membres seront prévenus." data-test="publier-annonce" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Publier l'annonce</button>
                    </div>
                </div>
            </div>

            {{-- Salons --}}
            @if ($onglet === 'salons')
                <div class="mb-3 flex justify-end">
                    <button type="button" wire:click="nouveauSalon" data-test="nouveau-salon" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="plus" class="size-4" /> Nouveau salon</button>
                </div>
                <div class="grid gap-3">
                    @foreach ($salons as $i => $s)
                        <div wire:key="salon-{{ $s['id'] }}" data-test="salon-admin" class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.04)] {{ $s['archive'] ? 'opacity-60' : '' }}">
                            <div class="flex flex-wrap items-start gap-4">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px] text-[22px] {{ $s['type'] === 'annonces' ? 'bg-brand text-white' : 'bg-azure/10' }}">{{ $s['emoji'] ?: '💬' }}</span>
                                <div class="min-w-[240px] flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-[15px] font-bold text-brand">
                                        {{ $s['nom'] }}
                                        @if ($s['type'] === 'annonces')<span class="rounded-full bg-brand px-2 text-[10px] font-bold uppercase leading-5 text-white">Annonces</span>@endif
                                        @if ($s['acces'] === 'abonnes')<span class="rounded-full bg-azure/10 px-2 text-[10px] font-bold uppercase leading-5 text-azure">Abonnés</span>@endif
                                        @if ($s['lecture_seule'])<span class="rounded-full bg-[#FBF1DC] px-2 text-[10px] font-bold uppercase leading-5 text-[#8A5A00]">Lecture seule</span>@endif
                                        @if ($s['mode_lent_secondes'])<span class="rounded-full bg-[#FBF1DC] px-2 text-[10px] font-bold uppercase leading-5 text-[#8A5A00]">Mode lent · {{ $modes[$s['mode_lent_secondes']] ?? $s['mode_lent_secondes'].' s' }}</span>@endif
                                        @if ($s['archive'])<span class="rounded-full bg-[#5B677A]/15 px-2 text-[10px] font-bold uppercase leading-5 text-[#5B677A]">Archivé</span>@endif
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#5B677A]">{{ $s['description'] }}</p>
                                    <p class="mt-2 text-[12px] text-ink"><span class="font-bold tabular-nums">{{ $s['messages_semaine'] }}</span> message{{ $s['messages_semaine'] > 1 ? 's' : '' }} et <span class="font-bold tabular-nums">{{ $s['actifs_semaine'] }}</span> membre{{ $s['actifs_semaine'] > 1 ? 's' : '' }} actif{{ $s['actifs_semaine'] > 1 ? 's' : '' }} cette semaine · <span class="tabular-nums">{{ $s['membres'] }}</span> membre{{ $s['membres'] > 1 ? 's' : '' }} venu{{ $s['membres'] > 1 ? 's' : '' }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[12px]">
                                        <span class="font-semibold text-[#5B677A]">Référents :</span>
                                        @forelse ($s['referents'] as $r)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-cloud px-2.5 py-0.5 font-semibold text-brand">{{ $r['prenom'] }} {{ $r['nom'] }}
                                                <button type="button" wire:click="retirerReferent({{ $s['id'] }}, {{ $r['id'] }})" wire:confirm="Retirer {{ $r['prenom'] }} {{ $r['nom'] }} des référents de « {{ $s['nom'] }} » ?" aria-label="Retirer" class="text-[#5B677A] hover:text-accent"><x-ui.icon name="x" class="size-3" /></button>
                                            </span>
                                        @empty
                                            <span class="text-[#5B677A]">aucun (les administrateurs modèrent)</span>
                                        @endforelse
                                    </div>
                                    @if (! empty($s['sanctions']))
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[12px]">
                                            <span class="font-semibold text-[#5B677A]">Sanctions en cours :</span>
                                            @foreach ($s['sanctions'] as $x)
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2.5 py-0.5 font-semibold text-accent">
                                                    {{ $x['user']['prenom'] ?? '' }} {{ $x['user']['nom'] ?? '' }} · {{ $x['exclu'] ? 'exclu' : 'muet jusqu\'au '.$date($x['silence_jusqua']) }}
                                                    <button type="button" wire:click="sanctionner({{ $s['id'] }}, {{ $x['user']['id'] }}, '{{ $x['exclu'] ? 'reintegrer' : 'lever' }}')" class="underline">{{ $x['exclu'] ? 'Réintégrer' : 'Lever' }}</button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button" wire:click="deplacer({{ $s['id'] }}, -1)" @disabled($i === 0) aria-label="Monter" class="icon-btn flex size-8 items-center justify-center rounded-[10px] border border-brand/10 text-[#5B677A] hover:bg-cloud disabled:opacity-30"><x-ui.icon name="chevron-down" class="size-4 rotate-180" /></button>
                                    <button type="button" wire:click="deplacer({{ $s['id'] }}, 1)" @disabled($i === count($salons) - 1) aria-label="Descendre" class="icon-btn flex size-8 items-center justify-center rounded-[10px] border border-brand/10 text-[#5B677A] hover:bg-cloud disabled:opacity-30"><x-ui.icon name="chevron-down" class="size-4" /></button>
                                    <button type="button" wire:click="voirFil({{ $s['id'] }})" data-test="voir-fil" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Voir le fil</button>
                                    <button type="button" wire:click="gererReferents({{ $s['id'] }})" data-test="referents" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Référents</button>
                                    <button type="button" wire:click="editerSalon({{ $s['id'] }})" data-test="modifier-salon" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Modifier</button>
                                    @if ($s['archive'])
                                        <button type="button" wire:click="archiver({{ $s['id'] }}, false)" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Restaurer</button>
                                    @else
                                        <button type="button" wire:click="archiver({{ $s['id'] }})" wire:confirm="Archiver « {{ $s['nom'] }} » ? Le salon disparaît pour les membres ; son historique est conservé." class="btn-tap rounded-full border border-accent/30 px-3 py-1.5 text-[12px] font-bold text-accent hover:bg-accent/5">Archiver</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Signalements --}}
            @if ($onglet === 'signalements')
                @foreach ([['À examiner', $aExaminer], ['Traités récemment', $traites]] as [$titre, $liste])
                    <h3 class="mb-2 mt-4 text-[13px] font-bold text-brand first:mt-0">{{ $titre }}</h3>
                    <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @forelse ($liste as $sig)
                            @php $m = $sig['message']; @endphp
                            <div wire:key="sig-{{ $sig['message_id'] }}" data-test="signalement-communaute" class="-mx-5 flex flex-wrap items-start gap-3 border-t border-[#EDF0F5] px-5 py-4 first:border-t-0">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent/10 text-[18px]">{{ $sig['salon']['emoji'] ?? '💬' }}</span>
                                <div class="min-w-[240px] flex-1">
                                    <p class="text-[12px] text-[#5B677A]"><span class="font-bold text-brand">{{ $m['auteur']['prenom'] ?? '' }} {{ $m['auteur']['nom'] ?? '' }}</span> dans <span class="font-semibold">{{ $sig['salon']['nom'] ?? '' }}</span> · {{ $date($m['date']) }}</p>
                                    <div class="mt-1 whitespace-pre-line break-words rounded-[12px] border border-accent/20 bg-cloud/50 px-3 py-2 text-[13px] text-ink {{ $m['supprime'] ? 'line-through opacity-60' : '' }}">{{ $m['body'] ?: $m['extrait'] }}</div>
                                    @if ($m['a_fichier'])
                                        <a href="{{ route('admin.communaute.fichier', $sig['message_id']) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-[12px] font-semibold text-azure hover:underline"><x-ui.icon name="eye" class="size-3.5" /> Voir la pièce jointe ({{ $m['fichier_nom'] }})</a>
                                    @endif
                                    <p class="mt-1 text-[11.5px] text-[#5B677A]">Signalé {{ $sig['nombre'] }} fois par {{ collect($sig['signale_par'])->join(', ') }}@if (! empty($sig['motifs'])) · « {{ collect($sig['motifs'])->join(' » « ') }} »@endif
                                        @if ($sig['statut'] !== 'nouveau') · <span class="font-semibold">{{ $sig['decision'] === 'supprime' ? 'Message retiré' : 'Classé sans suite' }}</span>@endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="voirFil({{ $sig['salon']['id'] ?? 0 }}, {{ $sig['message_id'] }})" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Contexte</button>
                                    @if ($sig['statut'] === 'nouveau')
                                        @if (! $m['supprime'])
                                            <button type="button" wire:click="supprimerMessage({{ $sig['message_id'] }})" data-test="retirer-message" class="btn-tap rounded-full bg-accent px-3 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600">Retirer le message</button>
                                        @endif
                                        <button type="button" wire:click="classer({{ $sig['message_id'] }})" data-test="classer-communaute" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Classer sans suite</button>
                                        @if (($m['auteur']['id'] ?? null) && ($sig['salon']['id'] ?? null))
                                            <button type="button" wire:click="sanctionner({{ $sig['salon']['id'] }}, {{ $m['auteur']['id'] }}, 'silence', '24h')" wire:confirm="Empêcher {{ $m['auteur']['prenom'] ?? '' }} d'écrire dans ce salon pendant 24 heures ?" class="btn-tap rounded-full border border-accent/30 px-3 py-1.5 text-[12px] font-bold text-accent hover:bg-accent/5">Muet 24 h</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="py-10 text-center text-sm text-[#5B677A]">{{ $titre === 'À examiner' ? 'Aucun message signalé : rien à examiner.' : 'Aucun signalement traité.' }}</p>
                        @endforelse
                    </div>
                @endforeach
            @endif

            {{-- Journal --}}
            @if ($onglet === 'journal')
                <div class="overflow-x-auto rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <table class="w-full min-w-[640px] text-left text-[12.5px]">
                        <thead class="bg-cloud text-[10.5px] font-extrabold uppercase tracking-[0.1em] text-[#5B677A]">
                            <tr><th class="px-4 py-2.5">Date</th><th class="px-4 py-2.5">Action</th><th class="px-4 py-2.5">Salon</th><th class="px-4 py-2.5">Par</th><th class="px-4 py-2.5">Détail</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($donnees['journal'] ?? [] as $j)
                                <tr class="border-t border-[#EDF0F5]" wire:key="j-{{ $j['id'] }}">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-[#5B677A]">{{ $date($j['date']) }}</td>
                                    <td class="px-4 py-2.5 font-semibold text-brand">{{ $j['action'] }}</td>
                                    <td class="px-4 py-2.5">{{ $j['salon'] ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $j['acteur'] ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-[#5B677A]">{{ $j['detail'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-[#5B677A]">Aucune action de modération pour l'instant.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Mots interdits --}}
            @if ($onglet === 'reglages')
                <div class="max-w-2xl rounded-[18px] border border-brand/10 bg-white p-5">
                    <h3 class="text-[14px] font-bold text-brand">Mots interdits</h3>
                    <p class="mb-3 mt-1 text-[12.5px] text-[#5B677A]">Un message qui contient l'un de ces termes est refusé, et son auteur est invité à le reformuler. Un terme par ligne ; les majuscules et les accents sont ignorés.</p>
                    <textarea wire:model="motsInterdits" rows="10" data-test="mots-interdits" class="w-full rounded-[12px] border border-brand/15 p-3 font-mono text-[13px] outline-none focus:border-azure"></textarea>
                    <div class="mt-3 flex justify-end">
                        <button type="button" wire:click="enregistrerMots" data-test="enregistrer-mots" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Enregistrer</button>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Formulaire de salon --}}
    @if ($formulaire)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFormulaire" x-on:keydown.escape.window="$wire.fermerFormulaire()">
            <div role="dialog" aria-modal="true" data-test="formulaire-salon" class="panel-enter max-h-[92vh] w-full max-w-[560px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
                <h3 class="mb-4 text-[16px] font-bold text-brand">{{ $formulaire['id'] ? 'Modifier le salon' : 'Nouveau salon' }}</h3>
                <div class="grid gap-3.5 text-[13px]">
                    <div class="grid grid-cols-[80px_1fr] gap-3">
                        <label class="grid gap-1"><span class="font-semibold text-brand">Émoji</span><input type="text" wire:model="formulaire.emoji" maxlength="16" class="rounded-[10px] border border-brand/15 px-3 py-2 text-center text-[18px] outline-none focus:border-azure"></label>
                        <label class="grid gap-1"><span class="font-semibold text-brand">Nom</span><input type="text" wire:model="formulaire.nom" maxlength="80" data-test="salon-nom" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure"></label>
                    </div>
                    <label class="grid gap-1"><span class="font-semibold text-brand">Description</span><input type="text" wire:model="formulaire.description" maxlength="300" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure"></label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="grid gap-1"><span class="font-semibold text-brand">Type</span>
                            <select wire:model="formulaire.type" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                                <option value="discussion">Salon de discussion</option>
                                <option value="annonces">Annonces (équipe seulement)</option>
                            </select>
                        </label>
                        <label class="grid gap-1"><span class="font-semibold text-brand">Accès</span>
                            <select wire:model="formulaire.acces" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                                <option value="tous">Tous les membres</option>
                                <option value="abonnes">Membres à jour de leur abonnement</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="grid gap-1"><span class="font-semibold text-brand">Mode lent</span>
                            <select wire:model="formulaire.mode_lent_secondes" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure">
                                @foreach ($modes as $sec => $lib)<option value="{{ $sec }}">{{ $lib }}</option>@endforeach
                            </select>
                        </label>
                        <label class="flex items-center gap-2 self-end pb-2"><input type="checkbox" wire:model="formulaire.lecture_seule" class="size-4 accent-[#031D59]"><span class="font-semibold text-brand">Lecture seule</span></label>
                    </div>
                    <label class="grid gap-1"><span class="font-semibold text-brand">Règles du salon</span>
                        <textarea wire:model="formulaire.regles" rows="5" maxlength="2000" class="rounded-[10px] border border-brand/15 px-3 py-2 outline-none focus:border-azure"></textarea>
                        <span class="text-[11.5px] text-[#5B677A]">Affichées à l'entrée du salon : chaque membre les accepte avant d'écrire. Laissez vide pour ne pas en demander.</span>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="fermerFormulaire" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-[#5B677A] hover:bg-cloud">Annuler</button>
                    <button type="button" wire:click="enregistrerSalon" data-test="enregistrer-salon" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Enregistrer</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Référents --}}
    @if ($referentsSalonId)
        @php $salonRef = collect($salons)->firstWhere('id', $referentsSalonId); @endphp
        <div class="fixed inset-0 z-[90] flex items-start justify-center bg-brand/40 p-4 pt-[12vh]" wire:click.self="fermerReferents" x-on:keydown.escape.window="$wire.fermerReferents()">
            <div role="dialog" aria-modal="true" data-test="fenetre-referents" class="panel-enter w-full max-w-[480px] rounded-[20px] bg-white p-6 shadow-2xl">
                <h3 class="text-[16px] font-bold text-brand">Référents · {{ $salonRef['nom'] ?? '' }}</h3>
                <p class="mb-3 mt-1 text-[12.5px] text-[#5B677A]">Un référent modère ce salon depuis l'espace membre : retirer un message, rendre un membre muet ou l'exclure, mode lent, lecture seule.</p>
                <input type="search" wire:model.live.debounce.300ms="rechercheMembre" data-test="recherche-referent" placeholder="Rechercher un membre (nom ou e-mail)…" class="w-full rounded-[12px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure">
                <ul class="mt-2 max-h-[280px] overflow-y-auto">
                    @foreach ($resultats as $u)
                        <li class="flex items-center justify-between gap-3 border-b border-cloud-200 py-2 text-[13px]">
                            <span class="font-semibold text-brand">{{ $u['prenom'] }} {{ $u['nom'] }}</span>
                            <button type="button" wire:click="nommer({{ $u['id'] }})" data-test="nommer" class="btn-tap rounded-full bg-brand px-3 py-1 text-[12px] font-bold text-white">Nommer</button>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex justify-end"><button type="button" wire:click="fermerReferents" class="rounded-full px-4 py-2 text-[12.5px] font-semibold text-[#5B677A] hover:bg-cloud">Fermer</button></div>
            </div>
        </div>
    @endif

    {{-- Fil d'un salon (modération) --}}
    @if ($fil)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFil" x-on:keydown.escape.window="$wire.fermerFil()">
            <div role="dialog" aria-modal="true" data-test="fil-moderation" class="panel-enter flex max-h-[90vh] w-full max-w-[720px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-cloud-200 px-6 py-4">
                    <p class="text-[15px] font-bold text-brand">{{ $fil['salon']['emoji'] }} {{ $fil['salon']['nom'] }}</p>
                    <button wire:click="fermerFil" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto bg-cloud/40 px-6 py-4" x-init="$nextTick(() => { const c = $el.querySelector('[data-cible]'); c ? c.scrollIntoView({ block: 'center' }) : $el.scrollTop = $el.scrollHeight })">
                    <div class="text-center"><button type="button" wire:click="filPlusAncien" class="rounded-full border border-brand/10 bg-white px-3 py-1 text-[12px] font-semibold text-azure">Messages précédents</button></div>
                    @forelse ($fil['messages'] as $m)
                        <div wire:key="fm-{{ $m['id'] }}" @if ($m['cible']) data-cible @endif class="group rounded-[12px] border px-3.5 py-2.5 {{ $m['cible'] ? 'border-accent bg-white ring-2 ring-accent/20' : ($m['signale'] ? 'border-accent/30 bg-white' : 'border-brand/10 bg-white') }}">
                            <div class="flex items-center justify-between gap-3 text-[11.5px] text-[#5B677A]">
                                <span><span class="font-bold text-brand">{{ $m['auteur']['prenom'] ?? '' }} {{ $m['auteur']['nom'] ?? '' }}</span> · {{ $date($m['date']) }}@if ($m['edite']) · modifié @endif @if ($m['signale'])<span class="ml-1 rounded-full bg-accent/10 px-1.5 font-bold text-accent">Signalé</span>@endif</span>
                                @if (! $m['supprime'])
                                    <button type="button" wire:click="supprimerMessage({{ $m['id'] }})" wire:confirm="Retirer ce message du salon ?" class="font-semibold text-accent opacity-0 transition-opacity hover:underline group-hover:opacity-100">Retirer</button>
                                @endif
                            </div>
                            @if ($m['reponse_a'])<p class="mt-1 border-l-2 border-azure pl-2 text-[12px] italic text-[#5B677A]">{{ $m['reponse_a'] }}</p>@endif
                            <p class="mt-1 whitespace-pre-line break-words text-[13px] {{ $m['supprime'] ? 'text-[#5B677A] line-through' : 'text-ink' }}">{{ $m['body'] ?: $m['extrait'] }}</p>
                            @if ($m['a_fichier'])
                                <a href="{{ route('admin.communaute.fichier', $m['id']) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-[12px] font-semibold text-azure hover:underline"><x-ui.icon name="eye" class="size-3.5" /> {{ $m['fichier_nom'] ?: 'Pièce jointe' }}</a>
                            @endif
                            @if ($m['supprime'])<p class="mt-0.5 text-[11px] font-semibold text-accent">Supprimé</p>@endif
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-[#5B677A]">Aucun message dans ce salon.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
