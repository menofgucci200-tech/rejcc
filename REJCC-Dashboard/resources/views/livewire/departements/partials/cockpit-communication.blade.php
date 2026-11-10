{{-- Cockpit de l'espace Communication : chiffres du mois, « À faire aujourd'hui » (publications,
     retards, demandes, CathoImpact) et accès rapides aux outils de la Com. Source : /admin/communication/cockpit. --}}
@php
    use App\Support\AdminSections;
    use App\Support\DemandesCom;

    $user = session('api_user');
    $lien = fn (string $route) => AdminSections::allowedRoute($user, $route) ? route($route) : null;
    $ch = $cockpit['chiffres'];
    $v = $cockpit['visites'];
    $evolution = $v['evolution']['visiteurs'] ?? null;
    $tuiles = [
        ['nouveaux-membres', 'Nouveaux membres', $ch['nouveaux_membres'], $ch['adhesions_recues'].' adhésion'.($ch['adhesions_recues'] > 1 ? 's' : '').' reçue'.($ch['adhesions_recues'] > 1 ? 's' : ''), 'equipe', '#EFFAF4', $lien('admin.members'), null],
        ['communaute', 'Messages dans la Communauté', $ch['messages_communaute'], $ch['membres_actifs_communaute'].' membre'.($ch['membres_actifs_communaute'] > 1 ? 's' : '').' actif'.($ch['membres_actifs_communaute'] > 1 ? 's' : ''), 'mains', '#FBF1DC', $lien('admin.communaute'), null],
        ['cathoimpact', 'CathoImpact reçues', $ch['cathoimpact']['recues'], $ch['cathoimpact']['publiees'].' publiée'.($ch['cathoimpact']['publiees'] > 1 ? 's' : ''), 'etoile', '#FFF3E8', $lien('admin.cathoimpact'), null],
        ['visites', 'Visiteurs du site', $v ? number_format($v['visiteurs'], 0, ',', ' ') : '—', $v ? number_format($v['pages_vues'], 0, ',', ' ').' pages vues' : 'Mesure indisponible', 'globe', '#EEF6FD', $lien('admin.statistiques-site'), $evolution],
        ['demandes', 'Demandes en attente', $ch['demandes_en_attente'], 'dont '.$ch['demandes_nouvelles'].' nouvelle'.($ch['demandes_nouvelles'] > 1 ? 's' : ''), 'bulle', '#FDEEEE', $lien('admin.demandes-communication'), null],
        ['inscriptions', 'Inscriptions aux événements', $ch['inscriptions_evenements'], 'ce mois-ci', 'agenda', '#EDF1FA', $lien('admin.evenements'), null],
        ['publications', 'Publications', $ch['publications_publiees'].'/'.$ch['publications_prevues'], 'publiées / prévues', 'megaphone', '#F1F3F7', $lien('admin.calendrier-editorial'), null],
    ];
    $pastilles = \App\Support\AdminNav::pastilles();
    $fonds = ['#F6EEE3', '#EAF5EF', '#EDF1FA', '#FBF1DC', '#F1F3F7'];
    $aTraiter = $cockpit['a_traiter'];
@endphp

<div data-test="cockpit-communication">
    {{-- 1. Chiffres du mois --}}
    <section class="mb-8" aria-labelledby="cockpit-com-mois">
        <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1">
            <h2 id="cockpit-com-mois" class="flex-1 text-[17px] font-bold text-ink">{{ $cockpit['libelle'] }} <span class="hidden text-[13px] font-semibold text-[#5B677A] sm:inline">· les chiffres du mois</span></h2>
            @if ($l = $lien('admin.bilan-communication'))
                <a href="{{ $l }}" wire:navigate class="inline-flex items-center gap-1 text-[12.5px] font-bold text-azure hover:text-brand">Bilan mensuel <x-ui.icon name="chevron-right" class="size-3.5" /></a>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3 md:grid-cols-4 xl:grid-cols-7">
            @foreach ($tuiles as [$cle, $libelle, $valeur, $detail, $icone, $fond, $url, $evo])
                <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" wire:navigate @endif data-test="chiffre-{{ $cle }}"
                    class="flex min-w-0 flex-col gap-2 rounded-[16px] border border-[#EFEAE1] bg-white p-3 {{ $url ? 'transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(3,29,89,.35)]' : '' }}">
                    <span class="flex items-center gap-2">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-[11px]" style="background: {{ $fond }}"><x-ui.illustration :name="$icone" size="size-6" /></span>
                        <span class="min-w-0 font-display text-[24px] leading-none text-brand">{{ $valeur }}</span>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[12px] font-bold leading-tight text-ink">{{ $libelle }}</span>
                        <span class="mt-0.5 block text-[11.5px] leading-tight text-[#5B677A]">
                            {{ $detail }}
                            @if ($evo !== null)
                                <span class="whitespace-nowrap font-bold {{ $evo >= 0 ? 'text-[#1C8F4C]' : 'text-accent' }}">· {{ $evo > 0 ? '+' : '' }}{{ $evo }} %</span>
                            @endif
                        </span>
                    </span>
                </{{ $url ? 'a' : 'div' }}>
            @endforeach
        </div>
        <p class="mt-2 text-[11.5px] text-[#5B677A]">Visiteurs comparés à la même période du mois dernier. Pas d'abonnés newsletter ni d'abonnés par réseau, à la demande de la Communication.</p>
    </section>

    {{-- 2. À faire aujourd'hui : publications et tour (bloc calendrier), retards, demandes, CathoImpact --}}
    <section class="mb-8" aria-labelledby="cockpit-com-a-faire">
        <h2 id="cockpit-com-a-faire" class="mb-3 flex items-center gap-2 text-[17px] font-bold text-ink"><x-ui.illustration name="cahier" size="size-7" /> À faire aujourd'hui</h2>
        <div class="grid gap-4 lg:grid-cols-5">
            <div class="min-w-0 lg:col-span-3">
                @include('livewire.departements.partials.calendrier')
            </div>

            <div class="flex min-w-0 flex-col gap-4 lg:col-span-2">
                {{-- Demandes des départements : nouvelles ou urgentes --}}
                <div class="rounded-[18px] border border-[#EFEAE1] bg-white p-4 sm:p-5" data-test="bloc-demandes">
                    <div class="mb-2.5 flex flex-wrap items-center gap-2">
                        <h3 class="flex flex-1 items-center gap-2 text-[15px] font-bold text-ink"><x-ui.illustration name="bulle" size="size-7" /> Demandes à traiter</h3>
                        <a href="{{ route('admin.demandes-communication') }}" wire:navigate data-test="lien-demandes" class="inline-flex items-center gap-1 text-[12.5px] font-bold text-azure hover:text-brand">
                            Tout voir @if ($aTraiter['demandes_total'] > 0)<span class="rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $aTraiter['demandes_total'] }}</span>@endif
                        </a>
                    </div>
                    @forelse ($aTraiter['demandes'] as $d)
                        @php
                            // « Formation · Formation » : le type n'est répété que s'il apporte une information.
                            $sousTitre = mb_strtolower($d['departement_label']) === mb_strtolower($d['type_label']) ? $d['departement_label'] : $d['departement_label'].' · '.$d['type_label'];
                        @endphp
                        <a href="{{ route('admin.demandes-communication', ['fiche' => $d['id']]) }}" wire:navigate data-test="bloc-demande-{{ $d['id'] }}"
                            class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-[#EFEAE1] px-1 py-2 text-[12.5px] first-of-type:border-t-0 hover:bg-[#FAF6EF]">
                            <span class="min-w-0 basis-full truncate font-bold text-ink">{{ $d['titre'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-[#5B677A]">{{ $sousTitre }}</span>
                            @if ($d['statut'] === 'nouvelle')<span class="rounded-full bg-[#FFF3E8] px-2 py-0.5 text-[11px] font-bold text-[#8A5A12]">Nouvelle</span>@endif
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ DemandesCom::classesDelai($d['niveau_delai']) }}">{{ DemandesCom::date($d['date_souhaitee'], 'j M') }} · {{ DemandesCom::echeance($d['jours_avant']) }}</span>
                        </a>
                    @empty
                        <p class="text-[12.5px] text-[#5B677A]">Aucune demande nouvelle ou urgente : tout est à jour.</p>
                    @endforelse
                </div>

                {{-- CathoImpact : réponses reçues à examiner --}}
                <div class="rounded-[18px] border border-[#EFEAE1] bg-white p-4 sm:p-5" data-test="bloc-cathoimpact">
                    <div class="mb-2.5 flex flex-wrap items-center gap-2">
                        <h3 class="flex flex-1 items-center gap-2 text-[15px] font-bold text-ink"><x-ui.illustration name="etoile" size="size-7" /> CathoImpact à examiner</h3>
                        <a href="{{ route('admin.cathoimpact') }}" wire:navigate data-test="lien-cathoimpact" class="inline-flex items-center gap-1 text-[12.5px] font-bold text-azure hover:text-brand">
                            Tout voir @if ($aTraiter['cathoimpact_total'] > 0)<span class="rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $aTraiter['cathoimpact_total'] }}</span>@endif
                        </a>
                    </div>
                    @forelse ($aTraiter['cathoimpact'] as $r)
                        <a href="{{ route('admin.cathoimpact', ['fiche' => $r['id']]) }}" wire:navigate
                            class="flex flex-wrap items-center gap-x-2 gap-y-0.5 border-t border-[#EFEAE1] px-1 py-2 text-[12.5px] first-of-type:border-t-0 hover:bg-[#FAF6EF]">
                            <span class="min-w-0 truncate font-bold text-ink">{{ $r['nom_complet'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-[#5B677A]">{{ collect([$r['fonction'], $r['entreprise']])->filter()->implode(' · ') ?: $r['profil_label'] }}</span>
                            <span class="text-[11px] text-[#5B677A]">{{ DemandesCom::date($r['created_at'], 'j M') }}</span>
                        </a>
                    @empty
                        <p class="text-[12.5px] text-[#5B677A]">Aucune réponse en attente d'examen.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Accès rapides : les outils de la Communication, dans l'ordre du menu --}}
    <section class="mb-8" aria-labelledby="cockpit-com-acces" data-test="acces-rapides">
        <h2 id="cockpit-com-acces" class="mb-3 text-[17px] font-bold text-ink">Accès rapides</h2>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5">
            @foreach ($cockpit['acces'] as $a)
                @php $n = $pastilles[$a['route']] ?? 0; @endphp
                <a href="{{ route($a['route']) }}" wire:navigate data-test="acces-{{ \Illuminate\Support\Str::slug($a['label']) }}"
                    class="relative flex min-w-0 items-center gap-2.5 rounded-[16px] border border-[#EFEAE1] bg-white px-3 py-3 transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(3,29,89,.35)]">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-[11px]" style="background: {{ $fonds[$loop->index % 5] }}"><x-ui.illustration :name="\App\Support\Espace::ILLUSTRATIONS_RUBRIQUES[$a['route']] ?? 'etoile'" size="size-6" /></span>
                    <span class="min-w-0 flex-1 text-[12.5px] font-bold leading-tight text-ink">{{ $a['label'] }}</span>
                    @if ($n > 0)
                        <span class="absolute -right-1 -top-1 rounded-full bg-accent px-1.5 py-px text-[10.5px] font-bold text-white">{{ $n > 99 ? '99+' : $n }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
</div>
