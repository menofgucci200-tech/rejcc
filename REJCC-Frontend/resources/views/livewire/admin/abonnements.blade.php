<div>
    <x-admin-light.topbar title="Abonnements" />

    @php
        $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->locale('fr')->isoFormat('D MMM YYYY') : '—';
        $f = fn ($n) => number_format((int) $n, 0, ',', ' ').' F';
        $etats = [
            'actif' => ['Actif', '#1C8F4C', '#EAF6EE'],
            'echeance' => ['Échéance proche', '#0F6FC6', '#E8F2FC'],
            'grace' => ['Délai de grâce', '#B27007', '#FCF1DD'],
            'expire' => ['Expiré', '#AC0100', '#F9E9E9'],
            'jamais' => ['Jamais abonné', '#7A8699', '#EEF1F5'],
        ];
        $statutsPaiement = [
            'success' => ['Payé', '#1C8F4C', '#EAF6EE'],
            'pending' => ['En attente', '#B27007', '#FCF1DD'],
            'failed' => ['Échoué', '#AC0100', '#F9E9E9'],
            'abandonne' => ['Abandonné', '#7A8699', '#EEF1F5'],
        ];
        $rappels = ['j30' => 'Rappel J-30 envoyé', 'j7' => 'Rappel J-7 envoyé', 'j0' => 'Avis d\'échéance envoyé', 'fin' => 'Fin de grâce notifiée'];
        $meta = $onglet === 'membres' ? $metaMembres : $metaPaiements;
    @endphp

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Abonnements</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">L'abonnement s'active uniquement par paiement en ligne (CinetPay). Après l'échéance, l'accès est maintenu {{ $graceJours }} jours ; les rappels partent automatiquement à J-30, J-7, à l'échéance et à la fin du délai de grâce.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.export', ['dataset' => 'abonnements']) }}" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Exporter les abonnés</a>
                <a href="{{ route('admin.export', ['dataset' => 'paiements']) }}" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Exporter les paiements</a>
            </div>
        </div>

        @if (! $enforced)
            <div class="mb-5 flex items-start gap-3 rounded-[14px] border border-[#22A85A]/30 bg-[#F2FBF5] px-4 py-3 text-[12.5px] text-[#1C5F38]">
                <x-ui.icon name="info" class="mt-0.5 size-4 shrink-0" />
                <p>Les abonnements ne sont pas encore obligatoires : tous les membres ont accès librement. Vous pouvez les rendre obligatoires depuis le <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-bold underline">tableau de bord</a>.</p>
            </div>
        @endif

        <div class="mb-5 grid gap-3 lg:grid-cols-[1fr_300px]">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ([
                    ['actifs', 'Abonnés actifs', $stats['actifs'] ?? 0, 'dont '.($stats['echeance'] ?? 0).' échéance(s) sous 30 j', 'text-[#1C8F4C]'],
                    ['grace', 'En délai de grâce', $stats['grace'] ?? 0, 'à relancer en priorité', ($stats['grace'] ?? 0) ? 'text-[#B27007]' : 'text-brand'],
                    ['expires', 'Expirés', $stats['expires'] ?? 0, ($stats['jamais'] ?? 0).' jamais abonné(s)', 'text-brand'],
                    [null, 'Recettes '.now()->year, $f($stats['recettes_annee'] ?? 0), ($stats['paiements_annee'] ?? 0).' paiement(s) · '.($stats['offerts_annee'] ?? 0).' offert(s)', 'text-brand'],
                ] as [$cle, $l, $n, $sous, $c])
                    @if ($cle)
                        <button wire:click="setStatut('{{ $cle }}')" data-test="stat-{{ $cle }}" class="btn-tap rounded-[16px] border bg-white px-4 py-3.5 text-left shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $onglet === 'membres' && $statut === $cle ? 'border-brand ring-1 ring-brand/30' : 'border-brand/10 hover:border-brand/25' }}">
                    @else
                        <div class="rounded-[16px] border border-brand/10 bg-white px-4 py-3.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @endif
                        <p class="text-[11.5px] font-semibold text-[#5B677A]">{{ $l }}</p>
                        <p class="mt-1 text-[24px] font-extrabold leading-none {{ $c }}">{{ $n }}</p>
                        <p class="mt-1 text-[10.5px] text-[#9AA6B8]">{{ $sous }}</p>
                    @if ($cle) </button> @else </div> @endif
                @endforeach
            </div>

            <div data-test="tarif" class="rounded-[16px] border border-brand/10 bg-white px-4 py-3.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="text-[11.5px] font-semibold text-[#5B677A]">Tarif annuel</p>
                @if ($editionTarif)
                    <form wire:submit="enregistrerTarif" wire:confirm="Appliquer ce nouveau tarif aux prochains paiements ? Les abonnements en cours ne sont pas modifiés." class="mt-1.5">
                        <div class="flex items-center gap-2">
                            <input wire:model="montant" type="text" inputmode="numeric" data-test="champ-tarif" class="w-full rounded-[10px] border border-brand/15 px-3 py-1.5 text-[15px] font-bold text-brand" />
                            <span class="text-[12px] font-bold text-[#5B677A]">F</span>
                        </div>
                        @if ($erreur)
                            <p class="mt-1.5 text-[11px] font-semibold text-accent">{{ $erreur }}</p>
                        @endif
                        <div class="mt-2 flex gap-2">
                            <button type="submit" data-test="enregistrer-tarif" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90">Enregistrer</button>
                            <button type="button" wire:click="$set('editionTarif', false)" class="btn-tap rounded-full border border-brand/15 px-3.5 py-1.5 text-[11.5px] font-bold text-[#5B677A] hover:bg-cloud">Annuler</button>
                        </div>
                    </form>
                @else
                    <div class="mt-1 flex items-end justify-between gap-2">
                        <p class="text-[24px] font-extrabold leading-none text-brand" data-test="tarif-actuel">{{ $f($tarif) }}</p>
                        <button wire:click="modifierTarif({{ $tarif }})" data-test="modifier-tarif" class="text-[11.5px] font-bold text-azure hover:underline">Modifier</button>
                    </div>
                    <p class="mt-1 text-[10.5px] text-[#9AA6B8]">{{ $f($stats['recettes_mois'] ?? 0) }} encaissés ce mois · {{ $stats['en_attente'] ?? 0 }} paiement(s) en attente</p>
                @endif
            </div>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <button wire:click="setOnglet('membres')" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'membres' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Membres</button>
            <button wire:click="setOnglet('paiements')" data-test="onglet-paiements" class="btn-tap rounded-full px-4 py-1.5 text-xs font-bold {{ $onglet === 'paiements' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Paiements</button>
            <div class="relative ml-auto w-full sm:w-72">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[#9AA6B8]" />
                <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="{{ $onglet === 'membres' ? 'Nom, email, téléphone…' : 'Référence, reçu, membre…' }}" class="rj-search-input w-full rounded-full border border-brand/15 bg-white py-2 pl-9 pr-4 text-[12.5px]" />
            </div>
        </div>

        @if ($onglet === 'membres')
            <div class="mb-3 flex flex-wrap gap-1.5">
                @foreach (['' => 'Tous', 'actifs' => 'Actifs', 'echeance' => 'Échéance sous 30 j', 'grace' => 'Délai de grâce', 'expires' => 'Expirés', 'jamais' => 'Jamais abonnés'] as $k => $l)
                    <button wire:click="$set('statut', '{{ $k }}')" class="rounded-full px-3 py-1 text-[11.5px] font-semibold {{ $statut === $k ? 'bg-brand/10 text-brand' : 'text-[#5B677A] hover:bg-cloud' }}">{{ $l }}</button>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-[12.5px]">
                        <thead>
                            <tr class="border-b border-cloud-200 bg-cloud/50 text-[10.5px] font-bold uppercase tracking-[0.05em] text-[#9AA6B8]">
                                <th class="px-4 py-2.5">Membre</th>
                                <th class="px-4 py-2.5">Statut</th>
                                <th class="px-4 py-2.5">Échéance</th>
                                <th class="px-4 py-2.5">Dernier paiement</th>
                                <th class="px-4 py-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($membres as $m)
                                @php [$el, $ec, $ebg] = $etats[$m['statut']] ?? $etats['jamais']; @endphp
                                <tr wire:key="m-{{ $m['id'] }}" data-test="ligne-membre" data-statut="{{ $m['statut'] }}" class="border-b border-cloud-200 last:border-0">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-ink">{{ $m['nom'] }}</p>
                                        <p class="text-[11px] text-[#9AA6B8]">{{ $m['numero'] }} · {{ $m['email'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" style="background: {{ $ebg }}; color: {{ $ec }}">{{ $el }}</span>
                                        @if (! empty($m['relance_le']) && isset($rappels[$m['relance_le']]))
                                            <p class="mt-1 text-[10.5px] text-[#9AA6B8]">{{ $rappels[$m['relance_le']] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[#5B677A]">{{ $date($m['expire_le']) }}</td>
                                    <td class="px-4 py-3 text-[#5B677A]">
                                        @if ($m['dernier_paiement'])
                                            {{ $date($m['dernier_paiement']['le']) }}
                                            <span class="text-[11px] text-[#9AA6B8]">{{ $m['dernier_paiement']['moyen'] ? '· '.$m['dernier_paiement']['moyen'] : '' }}{{ $m['dernier_paiement']['offert'] ? ' · offert' : '' }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($m['statut'] !== 'actif')
                                            <button wire:click="relancer({{ $m['id'] }})" wire:confirm="Envoyer à {{ $m['nom'] }} un rappel (notification et email) pour son abonnement ?" data-test="relancer"
                                                class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">Relancer</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-[12.5px] text-[#5B677A]">Aucun membre ne correspond à ces critères.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="mb-3 flex flex-wrap gap-1.5">
                @foreach (['' => 'Tous'] + collect($statutsPaiement)->map(fn ($s) => $s[0])->all() as $k => $l)
                    <button wire:click="$set('statutPaiement', '{{ $k }}')" class="rounded-full px-3 py-1 text-[11.5px] font-semibold {{ $statutPaiement === $k ? 'bg-brand/10 text-brand' : 'text-[#5B677A] hover:bg-cloud' }}">{{ $l }}</button>
                @endforeach
            </div>
            <div class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-[12.5px]">
                        <thead>
                            <tr class="border-b border-cloud-200 bg-cloud/50 text-[10.5px] font-bold uppercase tracking-[0.05em] text-[#9AA6B8]">
                                <th class="px-4 py-2.5">Date</th>
                                <th class="px-4 py-2.5">Payé par</th>
                                <th class="px-4 py-2.5">Moyen</th>
                                <th class="px-4 py-2.5">Montant</th>
                                <th class="px-4 py-2.5">Statut</th>
                                <th class="px-4 py-2.5 text-right">Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paiements as $p)
                                @php [$pl, $pc, $pbg] = $statutsPaiement[$p['statut']] ?? $statutsPaiement['pending']; @endphp
                                <tr wire:key="p-{{ $p['reference'] }}" data-test="ligne-paiement" class="border-b border-cloud-200 last:border-0">
                                    <td class="px-4 py-3 text-[#5B677A]">{{ $date($p['paye_at'] ?? $p['created_at']) }}<p class="text-[10.5px] text-[#9AA6B8]">{{ $p['reference'] }}</p></td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-ink">{{ $p['payeur'] }}</p>
                                        @if ($p['beneficiaire'])
                                            <p class="flex items-center gap-1 text-[11px] text-accent"><x-ui.icon name="gift" class="size-3" /> offert à {{ $p['beneficiaire'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[#5B677A]">{{ $p['moyen'] ?: '—' }}</td>
                                    <td class="px-4 py-3 font-semibold text-brand">{{ $f($p['montant']) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" style="background: {{ $pbg }}; color: {{ $pc }}">{{ $pl }}</span></td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($p['recu'])
                                            <a href="{{ route('admin.abonnements.recu', $p['reference']) }}" target="_blank" data-test="recu-admin" class="inline-flex items-center gap-1 text-[11.5px] font-bold text-azure hover:underline"><x-ui.icon name="receipt" class="size-3.5" /> {{ $p['recu'] }}</a>
                                        @else
                                            <span class="text-[#9AA6B8]">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-[12.5px] text-[#5B677A]">Aucun paiement pour le moment.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (($meta['last_page'] ?? 1) > 1)
            <div class="mt-4 flex items-center justify-center gap-3 text-[12px]">
                <button wire:click="allerPage({{ $page - 1 }})" @disabled($page <= 1) class="btn-tap rounded-full border border-brand/15 bg-white px-3 py-1.5 font-bold text-brand disabled:opacity-40">Précédent</button>
                <span class="text-[#5B677A]">Page {{ $meta['current_page'] }} / {{ $meta['last_page'] }} · {{ $meta['total'] }} au total</span>
                <button wire:click="allerPage({{ $page + 1 }})" @disabled($page >= $meta['last_page']) class="btn-tap rounded-full border border-brand/15 bg-white px-3 py-1.5 font-bold text-brand disabled:opacity-40">Suivant</button>
            </div>
        @endif
    </div>
</div>
