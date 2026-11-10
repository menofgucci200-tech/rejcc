@php
    $nb = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $taux = fn ($t) => $t === null ? '—' : $t.' %';
    $couleurTaux = fn ($t) => $t === null ? 'text-[#5B677A]' : ($t >= 80 ? 'text-[#1C8F4C]' : ($t >= 50 ? 'text-[#B7791F]' : 'text-accent'));
    $th = 'px-3 py-2 text-left text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#5B677A]';
    $thn = 'px-3 py-2 text-right text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#5B677A]';
    $td = 'px-3 py-2 text-[12.5px]';
    $tdn = 'px-3 py-2 text-right text-[12.5px] tabular-nums';
    $carte = 'bilan-carte rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]';
@endphp

<div>
    {{-- Impression / PDF : seule la feuille du bilan, sans menu ni barre du haut. --}}
    <style>
        @media print {
            @page { size: A4; margin: 12mm; }
            html, body { background: #fff !important; height: auto !important; overflow: visible !important; }
            body > div.flex.h-screen { display: block !important; height: auto !important; overflow: visible !important; }
            body > div.flex.h-screen > div:not(:last-child) { display: none !important; }
            body > div.flex.h-screen > div:last-child { display: block !important; overflow: visible !important; }
            header, footer, .no-print, #rj-toasts, [data-splash], [data-mode-test] { display: none !important; }
            #bilan-imprimable { max-width: none !important; padding: 0 !important; }
            .bilan-carte { box-shadow: none !important; break-inside: avoid; page-break-inside: avoid; border-color: #D5DCE8 !important; }
            .bilan-entete-print { display: flex !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

    <div class="no-print"><x-admin-light.topbar title="Bilan mensuel" /></div>

    <div id="bilan-imprimable" class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8" data-test="bilan-communication">
        <x-admin-light.onglets groupe="statistiques" />
        {{-- En-tête imprimé --}}
        <div class="bilan-entete-print mb-5 hidden items-center justify-between border-b-[3px] border-accent pb-3">
            <div>
                <p class="font-display text-[22px] tracking-wide text-brand">REJCC · Communication</p>
                <p class="text-[11px] text-[#5B677A]">Réseau Entrepreneurial des Jeunes Chrétiens Catholiques</p>
            </div>
            <p class="text-right text-[13px] font-bold text-brand">Bilan de {{ $bilan['libelle'] ?? $mois }}<br><span class="text-[10.5px] font-normal text-[#5B677A]">Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}</span></p>
        </div>

        <div class="no-print mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-[240px] flex-1">
                <h2 class="mb-1 text-[17px] font-bold text-brand">Bilan mensuel de la Communication</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Régularité des publications, CathoImpact, chiffres du réseau et du site pour le mois choisi. Pour un PDF : « Imprimer », puis « Enregistrer au format PDF ».</p>
            </div>
            <select wire:model.live="mois" aria-label="Mois du bilan" data-test="choix-mois" class="rounded-full border border-brand/15 bg-white px-4 py-2 text-[13px] font-bold text-brand focus:border-brand focus:outline-none">
                @foreach ($moisListe as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach
            </select>
            <button type="button" x-data x-on:click="window.print()" data-test="imprimer-bilan" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-3.5" /> Imprimer / PDF</button>
        </div>

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Bilan indisponible (accès à la rubrique « Espace Communication » requis).</p>
        @else
            @php $p = $bilan['publications']; $r = $bilan['reseau']; @endphp
            @if ($enCours)
                <p class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#4F6FBF]/10 px-3.5 py-1.5 text-[12px] font-semibold text-[#3A57A3]"><x-ui.icon name="info" class="size-3.5" /> Mois en cours : chiffres arrêtés à aujourd'hui.</p>
            @endif

            {{-- ═════ Publications ═════ --}}
            <section class="{{ $carte }} mb-5" data-test="bilan-publications">
                <h3 class="mb-3 flex items-center gap-2 text-[15px] font-bold text-brand"><x-ui.illustration name="agenda" size="size-7" /> Publications</h3>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                    @foreach ([['Prévues', $p['prevues'], 'text-brand'], ['Publiées', $p['publiees'], 'text-[#1C8F4C]'], ['Non publiées', $p['non_publiees'], $p['non_publiees'] ? 'text-accent' : 'text-brand'], ['À venir', $p['a_venir'], 'text-brand']] as [$l, $v, $c])
                        <div class="rounded-[14px] border border-[#EFEAE1] p-3"><p class="font-display text-[26px] leading-none {{ $c }}">{{ $nb($v) }}</p><p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">{{ $l }}</p></div>
                    @endforeach
                    <div class="col-span-2 rounded-[14px] border border-[#EFEAE1] bg-cloud/50 p-3 sm:col-span-1" data-test="taux-regularite">
                        <p class="font-display text-[26px] leading-none {{ $couleurTaux($p['taux_regularite']) }}">{{ $taux($p['taux_regularite']) }}</p>
                        <p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">Régularité <span class="font-normal">(publiées le jour prévu)</span></p>
                    </div>
                </div>

                <h4 class="mb-1.5 mt-5 text-[12.5px] font-bold text-brand">Par responsable : qui publie quand c'est son tour</h4>
                @if ($p['par_responsable'])
                    <div class="overflow-x-auto rounded-[12px] border border-[#EDF0F5]">
                        <table class="w-full min-w-[520px]" data-test="tableau-responsables">
                            <thead class="bg-cloud/60"><tr><th class="{{ $th }}">Responsable</th><th class="{{ $thn }}">Prévues</th><th class="{{ $thn }}">Publiées</th><th class="{{ $thn }}">Le jour prévu</th><th class="{{ $thn }}">Non publiées</th><th class="{{ $thn }}">Régularité</th></tr></thead>
                            <tbody>
                                @foreach ($p['par_responsable'] as $l)
                                    <tr class="border-t border-[#EDF0F5]">
                                        <td class="{{ $td }} font-semibold text-ink">{{ $l['nom'] }}</td>
                                        <td class="{{ $tdn }}">{{ $l['prevues'] }}</td>
                                        <td class="{{ $tdn }}">{{ $l['publiees'] }}</td>
                                        <td class="{{ $tdn }}">{{ $l['a_temps'] }}</td>
                                        <td class="{{ $tdn }} {{ $l['non_publiees'] ? 'font-bold text-accent' : '' }}">{{ $l['non_publiees'] }}</td>
                                        <td class="{{ $tdn }} font-bold {{ $couleurTaux($l['taux_regularite']) }}">{{ $taux($l['taux_regularite']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-[12.5px] text-[#5B677A]">Aucune publication prévue ce mois-ci.</p>
                @endif

                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    @foreach ([['Par canal', $p['par_canal'], 'label'], ["Par type d'information", $p['par_type'], 'label']] as [$titre, $lignes, $cle])
                        <div class="min-w-0">
                            <h4 class="mb-1.5 text-[12.5px] font-bold text-brand">{{ $titre }}</h4>
                            @if ($lignes)
                                <div class="overflow-x-auto rounded-[12px] border border-[#EDF0F5]">
                                    <table class="w-full">
                                        <thead class="bg-cloud/60"><tr><th class="{{ $th }}">{{ $cle === 'label' && $titre === 'Par canal' ? 'Canal' : 'Type' }}</th><th class="{{ $thn }}">Prévues</th><th class="{{ $thn }}">Publiées</th><th class="{{ $thn }}">Non publiées</th></tr></thead>
                                        <tbody>
                                            @foreach ($lignes as $l)
                                                <tr class="border-t border-[#EDF0F5]"><td class="{{ $td }}">{{ $l['label'] }}</td><td class="{{ $tdn }}">{{ $l['prevues'] }}</td><td class="{{ $tdn }}">{{ $l['publiees'] }}</td><td class="{{ $tdn }} {{ $l['non_publiees'] ? 'text-accent' : '' }}">{{ $l['non_publiees'] }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-[12.5px] text-[#5B677A]">—</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="mb-5 grid gap-5 lg:grid-cols-2">
                {{-- ═════ CathoImpact ═════ --}}
                <section class="{{ $carte }}" data-test="bilan-cathoimpact">
                    <h3 class="mb-3 flex items-center gap-2 text-[15px] font-bold text-brand"><x-ui.illustration name="etoile" size="size-7" /> CathoImpact</h3>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="rounded-[14px] border border-[#EFEAE1] p-3"><p class="font-display text-[26px] leading-none text-brand">{{ $nb($bilan['cathoimpact']['recues']) }}</p><p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">Réponses reçues</p></div>
                        <div class="rounded-[14px] border border-[#EFEAE1] p-3"><p class="font-display text-[26px] leading-none text-[#1C8F4C]">{{ $nb($bilan['cathoimpact']['publiees']) }}</p><p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">Portraits publiés</p></div>
                    </div>
                </section>

                {{-- ═════ Demandes des départements ═════ --}}
                <section class="{{ $carte }}" data-test="bilan-demandes">
                    <h3 class="mb-3 flex items-center gap-2 text-[15px] font-bold text-brand"><x-ui.illustration name="bulle" size="size-7" /> Demandes des départements</h3>
                    @if ($d = $bilan['demandes'])
                        <div class="grid grid-cols-3 gap-2.5">
                            @foreach ([['Reçues', $d['recues'], 'text-brand'], ['Publiées', $d['publiees'], 'text-[#1C8F4C]'], ['En attente', $d['en_attente'], $d['en_attente'] ? 'text-[#B7791F]' : 'text-brand']] as [$l, $v, $c])
                                <div class="rounded-[14px] border border-[#EFEAE1] p-3"><p class="font-display text-[26px] leading-none {{ $c }}">{{ $nb($v) }}</p><p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">{{ $l }}</p></div>
                            @endforeach
                        </div>
                        @if ($d['par_departement'])
                            <p class="mt-3 text-[12px] text-[#5B677A]">Reçues ce mois : {{ collect($d['par_departement'])->map(fn ($x) => $x['label'].' ('.$x['nombre'].')')->implode(', ') }}</p>
                        @endif
                    @else
                        <p class="text-[12.5px] text-[#5B677A]">Bientôt disponible : le suivi des demandes des départements s'affichera ici.</p>
                    @endif
                </section>
            </div>

            {{-- ═════ Chiffres du réseau ═════ --}}
            <section class="{{ $carte }} mb-5" data-test="bilan-reseau">
                <h3 class="mb-3 flex items-center gap-2 text-[15px] font-bold text-brand"><x-ui.illustration name="equipe" size="size-7" /> Le réseau ce mois-ci</h3>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ([
                        ['Nouveaux membres', $r['nouveaux_membres']],
                        ["Demandes d'adhésion", $r['adhesions_recues']],
                        ['Adhésions acceptées', $r['adhesions_acceptees']],
                        ['Messages dans la Communauté', $r['messages_communaute']],
                        ['Membres actifs (Communauté)', $r['membres_actifs_communaute']],
                        ['Inscriptions aux événements', $r['inscriptions_evenements']],
                    ] as [$l, $v])
                        <div class="rounded-[14px] border border-[#EFEAE1] p-3"><p class="font-display text-[24px] leading-none text-brand">{{ $nb($v) }}</p><p class="mt-1 text-[11.5px] font-semibold leading-tight text-[#5B677A]">{{ $l }}</p></div>
                    @endforeach
                </div>
                <p class="mt-2 text-[11px] text-[#5B677A]">{{ $r['evenements'] }} événement{{ $r['evenements'] > 1 ? 's' : '' }} publié{{ $r['evenements'] > 1 ? 's' : '' }} ce mois-ci.</p>
            </section>

            {{-- ═════ Site ═════ --}}
            <section class="{{ $carte }}" data-test="bilan-site">
                <h3 class="mb-3 flex items-center gap-2 text-[15px] font-bold text-brand"><x-ui.illustration name="globe" size="size-7" /> Le site</h3>
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="grid grid-cols-2 gap-2.5 self-start">
                        @if ($site && $site['jours_mesures'] > 0)
                            @foreach ([['Pages vues', 'pages_vues'], ['Visiteurs', 'visiteurs']] as [$l, $cle])
                                <div class="rounded-[14px] border border-[#EFEAE1] p-3">
                                    <p class="font-display text-[26px] leading-none text-brand">{{ $nb($site[$cle]) }}</p>
                                    <p class="mt-1 text-[11.5px] font-semibold text-[#5B677A]">{{ $l }}
                                        @if (($e = $site['evolution'][$cle] ?? null) !== null)<span class="{{ $e >= 0 ? 'text-[#1C8F4C]' : 'text-accent' }}">{{ $e >= 0 ? '+' : '' }}{{ $e }} %</span>@endif
                                    </p>
                                </div>
                            @endforeach
                            <p class="col-span-2 text-[11px] text-[#5B677A]">Mesuré sur {{ $site['jours_mesures'] }} jour{{ $site['jours_mesures'] > 1 ? 's' : '' }} ; évolution par rapport au mois précédent.</p>
                        @else
                            <p class="col-span-2 text-[12.5px] text-[#5B677A]">Pas de mesure d'audience pour ce mois.</p>
                        @endif
                    </div>
                    <div>
                        <h4 class="mb-1.5 text-[12.5px] font-bold text-brand">Clics des liens de suivi, par canal</h4>
                        @if ($clics && $clics['par_canal'])
                            <div class="overflow-x-auto rounded-[12px] border border-[#EDF0F5]">
                                <table class="w-full">
                                    <tbody>
                                        @foreach ($clics['par_canal'] as $c)
                                            <tr class="border-t border-[#EDF0F5] first:border-t-0"><td class="{{ $td }}">{{ $c['label'] }}</td><td class="{{ $tdn }} font-bold">{{ $nb($c['clics']) }}</td></tr>
                                        @endforeach
                                        <tr class="border-t border-[#EDF0F5] bg-cloud/50"><td class="{{ $td }} font-bold">Total</td><td class="{{ $tdn }} font-bold">{{ $nb($clics['total']) }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-[12.5px] text-[#5B677A]">Aucun clic sur les liens de suivi ce mois-ci.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
