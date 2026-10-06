<div>
    <x-admin-light.topbar title="Certificats" />

    @php
        $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->locale('fr')->isoFormat('D MMM YYYY') : '';
        $types = ['formation' => 'Formations', 'evenement' => 'Événements', 'parcours' => 'Parcours'];
        $input = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] text-ink';
        $lab = 'mb-1 block text-[12px] font-semibold text-brand';
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Certificats &amp; attestations</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Délivrés automatiquement par la plateforme et inscrits au registre officiel. Toute personne peut vérifier un certificat sur <a href="{{ route('verifier') }}" target="_blank" class="font-semibold text-azure hover:underline">rejcc.site/verifier</a>.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.certificats.apercu') }}" target="_blank" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Aperçu du modèle</a>
                <a href="{{ route('admin.export', ['dataset' => 'certificats']) }}" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Exporter (CSV)</a>
            </div>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ([
                ['Délivrés et valides', $stats['total'] ?? 0, ($stats['formation'] ?? 0).' formation · '.($stats['evenement'] ?? 0).' événement · '.($stats['parcours'] ?? 0).' parcours', 'text-brand'],
                ['Vérifications (30 j)', $stats['verifications_30j'] ?? 0, ($stats['verifications'] ?? 0).' au total', 'text-[#1C8F4C]'],
                ['Tentatives suspectes (30 j)', $stats['tentatives_suspectes'] ?? 0, 'codes inconnus ou fichiers modifiés', ($stats['tentatives_suspectes'] ?? 0) ? 'text-accent' : 'text-brand'],
                ['Corrections demandées', $stats['corrections'] ?? 0, ($stats['revoques'] ?? 0).' révoqué(s)', ($stats['corrections'] ?? 0) ? 'text-[#B27007]' : 'text-brand'],
            ] as [$l, $n, $s, $c])
                <div class="rounded-[16px] border border-brand/10 bg-white px-4 py-3.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="text-[11.5px] font-semibold text-[#5B677A]">{{ $l }}</p>
                    <p class="mt-1 text-[24px] font-extrabold leading-none {{ $c }}">{{ $n }}</p>
                    <p class="mt-1 text-[10.5px] text-[#9AA6B8]">{{ $s }}</p>
                </div>
            @endforeach
        </div>

        <div class="mb-4 flex gap-2">
            <button wire:click="setOnglet('registre')" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'registre' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Registre</button>
            <button wire:click="setOnglet('reglages')" data-test="onglet-reglages" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'reglages' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Signataires &amp; cachet</button>
        </div>

        @if ($message && ! $ouvert)
            <p wire:key="ok-{{ md5($message) }}" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $ouvert)
            <p class="mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if ($onglet === 'reglages')
            {{-- ══════════ Signataires & cachet ══════════ --}}
            <div class="grid gap-4 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="text-[12.5px] text-[#5B677A]">Ces réglages s'impriment sur les <strong class="text-brand">prochains</strong> certificats : un certificat déjà délivré garde les signataires de son jour de délivrance. Pour la signature et le cachet, utilisez de préférence une image PNG à fond transparent (blanc ou clair, le fond du certificat étant bleu nuit).</p>
                <div class="sm:w-1/2">
                    <label class="{{ $lab }}">Lieu de délivrance</label>
                    <input wire:model="lieu" type="text" class="{{ $input }}" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($signataires as $i => $s)
                        <div wire:key="sig-{{ $i }}" class="rounded-[14px] border border-brand/10 p-4" data-test="signataire">
                            <p class="mb-3 text-[13px] font-bold text-brand">Signataire {{ $i + 1 }} <span class="font-normal text-[#9AA6B8]">· {{ $i === 0 ? 'à gauche' : 'à droite' }}</span></p>
                            <div class="grid gap-3">
                                <div><label class="{{ $lab }}">Nom (facultatif)</label><input wire:model="signataires.{{ $i }}.nom" type="text" placeholder="Ex. : Jean Kouadio" class="{{ $input }}" /></div>
                                <div><label class="{{ $lab }}">Fonction</label><input wire:model="signataires.{{ $i }}.fonction" type="text" class="{{ $input }}" /></div>
                                <div>
                                    <label class="{{ $lab }}">Signature scannée</label>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-14 w-36 shrink-0 items-center justify-center rounded-[10px] bg-brand">
                                            @if ($signatureUploads[$i] ?? null)
                                                <img src="{{ $signatureUploads[$i]->temporaryUrl() }}" alt="" class="max-h-12 max-w-[130px]">
                                            @elseif ($s['apercu'] && ! $s['supprimer'])
                                                <img src="{{ $s['apercu'] }}" alt="" class="max-h-12 max-w-[130px]">
                                            @else
                                                <span class="text-[10.5px] text-white/50">Aucune signature</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-col gap-1 text-[12px]">
                                            <label class="cursor-pointer font-bold text-azure hover:underline">Choisir une image<input type="file" wire:model="signatureUploads.{{ $i }}" accept="image/png,image/jpeg" class="hidden" data-test="upload-signature-{{ $i }}" /></label>
                                            @if ($s['apercu'])<label class="flex items-center gap-1.5 text-[#5B677A]"><input type="checkbox" wire:model.live="signataires.{{ $i }}.supprimer" class="size-3.5 rounded" /> Retirer</label>@endif
                                        </div>
                                    </div>
                                    @error('signatureUploads.'.$i) <p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="rounded-[14px] border border-brand/10 p-4">
                    <p class="mb-3 text-[13px] font-bold text-brand">Cachet officiel <span class="font-normal text-[#9AA6B8]">· facultatif, apposé près du second signataire</span></p>
                    <div class="flex items-center gap-3">
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-[10px] bg-brand">
                            @if ($cachetUpload)
                                <img src="{{ $cachetUpload->temporaryUrl() }}" alt="" class="max-h-16 max-w-16">
                            @elseif (($signataires[0]['cachet_apercu'] ?? null) && ! $supprimerCachet)
                                <img src="{{ $signataires[0]['cachet_apercu'] }}" alt="" class="max-h-16 max-w-16">
                            @else
                                <span class="text-[10px] text-white/50">Aucun</span>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1 text-[12px]">
                            <label class="cursor-pointer font-bold text-azure hover:underline">Choisir une image<input type="file" wire:model="cachetUpload" accept="image/png,image/jpeg" class="hidden" /></label>
                            @if ($signataires[0]['cachet_apercu'] ?? null)<label class="flex items-center gap-1.5 text-[#5B677A]"><input type="checkbox" wire:model.live="supprimerCachet" class="size-3.5 rounded" /> Retirer</label>@endif
                        </div>
                    </div>
                    @error('cachetUpload') <p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="enregistrerReglages" wire:loading.attr="disabled" data-test="enregistrer-reglages" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60">Enregistrer</button>
                    <a href="{{ route('admin.certificats.apercu') }}" target="_blank" class="btn-tap rounded-[9px] border border-brand/15 px-5 py-2.5 text-sm font-bold text-brand hover:bg-cloud">Voir l'aperçu du modèle</a>
                </div>
            </div>
        @else
            {{-- ══════════ Registre ══════════ --}}
            <div class="mb-4 flex flex-wrap items-center gap-2">
                @foreach ($types as $k => $l)
                    <button wire:click="filtrer('type', '{{ $k }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $type === $k ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $l }}</button>
                @endforeach
                <span class="mx-1 h-5 w-px bg-brand/15"></span>
                @foreach (['valide' => 'Valides', 'revoque' => 'Révoqués', 'correction' => 'Corrections demandées'] as $k => $l)
                    <button wire:click="filtrer('statut', '{{ $k }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $statut === $k ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $l }}@if ($k === 'correction' && ($stats['corrections'] ?? 0)) <span class="ml-1 rounded-full bg-[#F5A623] px-1.5 text-[10px] text-white">{{ $stats['corrections'] }}</span>@endif</button>
                @endforeach
                <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Nom, titre, référence ou code…" data-test="recherche-certificats" class="w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs sm:ml-auto sm:w-72" />
            </div>

            <div class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($certificats as $c)
                    <button type="button" wire:click="ouvrir({{ $c['id'] }})" wire:key="c-{{ $c['id'] }}" data-test="ligne-certificat" class="flex w-full flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3 text-left first:border-t-0 hover:bg-cloud/50 sm:px-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] {{ $c['statut'] === 'valide' ? 'bg-brand text-white' : 'bg-accent/10 text-accent' }}"><x-ui.icon :name="$c['type'] === 'formation' ? 'award' : ($c['type'] === 'evenement' ? 'calendar' : 'graduation-cap')" class="size-[18px]" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2 text-[13.5px] font-bold text-brand">{{ $c['nom'] }}
                                @if ($c['statut'] !== 'valide')<span class="rounded-full bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent">Révoqué</span>@endif
                                @if ($c['correction_demandee'] && $c['statut'] === 'valide')<span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[10px] font-bold text-[#B27007]">Correction demandée</span>@endif
                                @if ($c['invite'])<span class="rounded-full bg-cloud px-2 py-0.5 text-[10px] font-bold text-[#5B677A]">Invité</span>@endif
                            </span>
                            <span class="block truncate text-[12px] text-[#5B677A]">{{ $c['intitule'] }} · {{ $c['titre'] }}</span>
                        </span>
                        <span class="text-right text-[11px] text-[#9AA6B8]">
                            <span class="block font-semibold tracking-wide text-brand">{{ $c['reference'] }}</span>
                            {{ $date($c['delivre_le']) }} · {{ $c['verifications'] }} vérif.
                        </span>
                    </button>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-[#5B677A]">Aucun certificat ne correspond.</p>
                @endforelse
            </div>
            @if (($meta['last_page'] ?? 1) > 1)
                <div class="mt-4 flex items-center justify-center gap-2 text-xs">
                    <button wire:click="$set('page', {{ max(1, $page - 1) }})" @disabled($page <= 1) class="btn-tap rounded-full border border-brand/15 bg-white px-3 py-1.5 font-bold text-brand disabled:opacity-40">Précédent</button>
                    <span class="text-[#5B677A]">Page {{ $meta['current_page'] }} / {{ $meta['last_page'] }}</span>
                    <button wire:click="$set('page', {{ $page + 1 }})" @disabled($page >= $meta['last_page']) class="btn-tap rounded-full border border-brand/15 bg-white px-3 py-1.5 font-bold text-brand disabled:opacity-40">Suivant</button>
                </div>
            @endif
        @endif
    </div>

    {{-- ══════════ Fiche d'un certificat ══════════ --}}
    @if ($ouvert && $detail)
        @php $c = $detail['certificate']; $valide = $c['statut'] === 'valide'; @endphp
        <div class="fixed inset-0 z-[90] flex justify-end bg-brand/40" wire:click.self="fermer" x-on:keydown.escape.window="$wire.fermer()">
            <aside data-test="fiche-certificat" class="panel-enter flex h-full w-full max-w-[560px] flex-col overflow-y-auto bg-white shadow-2xl">
                <div class="flex items-start gap-3 border-b border-[#EDF0F5] px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $c['type_label'] }}</p>
                        <p class="text-[16px] font-bold text-brand">{{ $c['nom'] }}</p>
                        <p class="text-[12px] text-[#5B677A]">{{ $c['email'] ?: 'sans email' }}</p>
                    </div>
                    <button type="button" wire:click="fermer" aria-label="Fermer" class="flex size-9 items-center justify-center rounded-full text-brand hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                </div>
                <div class="space-y-5 px-5 py-5">
                    @if ($message)<p class="rounded-[12px] bg-[#22A85A]/10 px-3 py-2.5 text-xs font-semibold text-[#1C8F4C]">{{ $message }}</p>@endif
                    @if (! $valide)
                        <p class="rounded-[12px] bg-accent/10 px-3 py-2.5 text-[12.5px] text-accent"><span class="font-bold">Révoqué le {{ $date($c['revoque_at']) }}</span> — {{ $c['motif_revocation'] }}</p>
                    @endif
                    @if ($c['correction_demandee'] && $valide)
                        <div class="rounded-[12px] border border-[#F5A623]/40 bg-[#F5A623]/10 px-3.5 py-3">
                            <p class="text-[12.5px] font-bold text-[#8A5A00]">Correction demandée le {{ $date($c['correction_demandee_at']) }}</p>
                            <p class="mt-0.5 text-[12.5px] text-ink">« {{ $c['correction_demandee'] }} »</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button wire:click="preparer('corriger', @js($c['nom']), @js($c['titre']))" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[11.5px] font-bold text-white">Corriger et délivrer à nouveau</button>
                                <button wire:click="preparer('refuser')" class="btn-tap rounded-full border border-brand/15 px-3.5 py-1.5 text-[11.5px] font-bold text-brand">Répondre sans corriger</button>
                            </div>
                        </div>
                    @endif

                    <x-certificats.fiche :c="$c" />

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.certificats.pdf', $c['id']) }}" target="_blank" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white"><x-ui.icon name="file-text" class="size-3.5" /> PDF officiel</a>
                        <a href="{{ route('verifier', ['code' => $c['code']]) }}" target="_blank" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 px-4 py-2 text-[12px] font-bold text-brand"><x-ui.icon name="shield-check" class="size-3.5" /> Page de vérification</a>
                        @if ($valide)
                            <button wire:click="preparer('corriger', @js($c['nom']), @js($c['titre']))" data-test="btn-corriger" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12px] font-bold text-brand">Corriger</button>
                            <button wire:click="preparer('revoquer')" data-test="btn-revoquer" class="btn-tap rounded-full border border-accent/30 px-4 py-2 text-[12px] font-bold text-accent">Révoquer</button>
                        @endif
                    </div>

                    @if ($action)
                        <div wire:key="action-{{ $action }}" class="panel-enter rounded-[14px] border border-brand/10 bg-cloud/40 p-4">
                            @if ($action === 'corriger')
                                <p class="mb-2 text-[13px] font-bold text-brand">Corriger et délivrer à nouveau</p>
                                <p class="mb-3 text-[11.5px] text-[#5B677A]">Un nouveau certificat (nouveau code, référence suffixée -R1…) remplace l'actuel, qui reste au registre comme révoqué et renvoie vers le nouveau. Le membre est prévenu.</p>
                                <label class="{{ $lab }}">Nom sur le certificat</label>
                                <input wire:model="nom" type="text" data-test="correction-nom" class="{{ $input }} mb-2" />
                                <label class="{{ $lab }}">Titre</label>
                                <input wire:model="titre" type="text" class="{{ $input }} mb-2" />
                            @elseif ($action === 'revoquer')
                                <p class="mb-2 text-[13px] font-bold text-accent">Révoquer ce certificat</p>
                                <p class="mb-3 text-[11.5px] text-[#5B677A]">Il restera au registre mais s'affichera « Révoqué » à chaque vérification, avec ce motif. Le membre est prévenu.</p>
                            @else
                                <p class="mb-2 text-[13px] font-bold text-brand">Répondre au membre</p>
                            @endif
                            <label class="{{ $lab }}">{{ $action === 'refuser' ? 'Réponse (facultative)' : 'Motif' }}</label>
                            <textarea wire:model="motif" rows="2" data-test="action-motif" class="{{ $input }}"></textarea>
                            @if ($erreur)<p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $erreur }}</p>@endif
                            <div class="mt-3 flex gap-2">
                                <button wire:click="{{ ['corriger' => 'corriger', 'revoquer' => 'revoquer', 'refuser' => 'refuserCorrection'][$action] }}" data-test="confirmer-action" class="btn-tap rounded-full px-4 py-2 text-[12px] font-bold text-white {{ $action === 'revoquer' ? 'bg-accent' : 'bg-brand' }}">{{ ['corriger' => 'Délivrer le certificat corrigé', 'revoquer' => 'Révoquer', 'refuser' => 'Envoyer la réponse'][$action] }}</button>
                                <button wire:click="$set('action', '')" class="btn-tap rounded-full px-3 py-2 text-[12px] font-bold text-[#5B677A]">Annuler</button>
                            </div>
                        </div>
                    @endif

                    <div>
                        <p class="mb-2 text-[11.5px] font-bold uppercase tracking-[0.08em] text-[#5B677A]">Vérifications récentes</p>
                        @forelse ($detail['journal'] as $v)
                            <p class="flex justify-between border-t border-[#EDF0F5] py-1.5 text-[12px] first:border-t-0">
                                <span class="text-brand">{{ ['qr' => 'QR code', 'code' => 'Code saisi', 'fichier' => 'Fichier déposé'][$v['methode']] ?? $v['methode'] }} · <span class="{{ in_array($v['resultat'], ['valide', 'intact'], true) ? 'text-[#1C8F4C]' : 'text-accent' }}">{{ ['valide' => 'valide', 'intact' => 'fichier intact', 'revoque' => 'révoqué', 'modifie' => 'fichier modifié', 'inconnu' => 'inconnu'][$v['resultat']] ?? $v['resultat'] }}</span></span>
                                <span class="text-[#9AA6B8]">{{ \Illuminate\Support\Carbon::parse($v['le'])->locale('fr')->isoFormat('D MMM YYYY, HH:mm') }}</span>
                            </p>
                        @empty
                            <p class="text-[12px] text-[#9AA6B8]">Ce certificat n'a pas encore été vérifié.</p>
                        @endforelse
                    </div>
                    @if (! empty($c['empreinte']))
                        <p class="break-all text-[10.5px] text-[#9AA6B8]">Empreinte SHA-256 du PDF délivré : <span class="font-mono">{{ $c['empreinte'] }}</span>{{ $c['signe_electroniquement'] ? ' · signé électroniquement' : '' }}</p>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
