{{-- Accueil de l'espace Communication (composant Departements\CockpitCommunication) : un écran qui dit quoi faire.
     1. Votre priorité du jour · 2. Actions rapides · 3. Résultats CathoImpact · 4. Quatre chiffres
     · 5. Publications d'aujourd'hui et de demain, demandes à traiter. --}}
@php
    use App\Support\DemandesCom;

    $nb = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $pluriel = fn (int $n, string $mot) => $n.' '.$mot.($n > 1 ? 's' : '');
    $champ = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] focus:border-brand focus:outline-none';
    $teinteCi = fn (?string $s) => match ($s) {
        'recu' => 'bg-[#FDF2E3] text-[#B26A00]',
        'retenu', 'questionnaire_envoye', 'questionnaire_rempli' => 'bg-[#EEF6FD] text-[#2D5C8F]',
        'attribue', 'en_creation' => 'bg-[#F1EDFB] text-[#5B3FA8]',
        'publie' => 'bg-[#EAF6EE] text-[#1C8A49]',
        default => 'bg-[#F1F3F7] text-[#5B677A]',
    };
    $nDemandes = $pastilles['admin.demandes-communication'] ?? 0;
    $nCatho = $pastilles['admin.cathoimpact'] ?? 0;
@endphp

<div data-test="cockpit-communication">
    @if (! $cockpit)
        <p class="mb-8 rounded-[14px] bg-[#FDF2E3] px-4 py-3 text-[13px] font-semibold text-[#8A5A12]">Le tableau de bord de la Communication est momentanément indisponible. Toutes vos rubriques restent dans le menu.</p>
    @else
        @php
            $ch = $cockpit['chiffres'];
            $ci = $cockpit['cathoimpact'] ?? null;
            $demandesListe = collect($cockpit['a_traiter']['demandes'])
                ->reject(fn ($d) => in_array($priorite['type'], ['demande', 'demande_urgente'], true) && $d['id'] === $priorite['demande']['id'])->values();
        @endphp

        {{-- ── 1. Votre priorité du jour ─────────────────────────────── --}}
        <section aria-labelledby="priorite-titre" data-test="priorite-du-jour" data-priorite="{{ $priorite['type'] }}"
            class="mb-5 overflow-hidden rounded-[22px] border p-5 shadow-[0_14px_40px_-24px_rgba(3,29,89,.45)] sm:p-6 {{ $priorite['type'] === 'ok' ? 'border-[#CDEBDD] bg-gradient-to-br from-[#F2FAF5] to-white' : ($priorite['type'] === 'retard' ? 'border-accent/30 bg-gradient-to-br from-[#FFF5F4] to-white' : 'border-[#F3E3D3] bg-gradient-to-br from-[#FFF8F1] to-white') }}">
            <p id="priorite-titre" class="text-[11px] font-bold uppercase tracking-[0.12em] {{ $priorite['type'] === 'retard' ? 'text-accent' : 'text-azure' }}">Votre priorité du jour</p>

            @switch($priorite['type'])
                @case('retard')
                @case('jour')
                    @php $p = $priorite['publication']; $autres = $priorite['nombre'] - 1; @endphp
                    <div x-data="{ ouvert: false, lien: '' }">
                        <h2 class="mt-1.5 text-[19px] font-bold leading-snug text-ink sm:text-[22px]">
                            {{ $priorite['type'] === 'retard' ? 'Publier ce qui est en retard' : 'Publier aujourd\'hui' }} : « {{ $p['titre'] }} »
                        </h2>
                        <p class="mt-1 text-[13px] text-[#5B677A]">
                            @if ($priorite['type'] === 'retard')
                                Prévue le {{ \Illuminate\Support\Carbon::parse($p['date'])->locale('fr')->isoFormat('dddd D MMMM') }}
                            @else
                                Aujourd'hui{{ $p['heure'] ? ' à '.$p['heure'] : '' }}
                            @endif
                            · {{ $p['type_label'] }}@if ($p['canaux_labels']) · {{ implode(', ', $p['canaux_labels']) }}@endif
                            · {{ $p['responsable_effectif']['nom'] ?? 'Sans responsable' }}
                            @if ($autres > 0)<span class="font-semibold {{ $priorite['type'] === 'retard' ? 'text-accent' : 'text-ink' }}"> · et {{ $pluriel($autres, 'autre') }} {{ $priorite['type'] === 'retard' ? 'en retard' : 'aujourd\'hui' }}</span>@endif
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <button type="button" x-on:click="ouvert = true; $nextTick(() => $refs.lien.focus())" x-show="! ouvert" data-test="priorite-action"
                                class="btn-tap inline-flex items-center gap-2 rounded-full bg-[#1C8F4C] px-5 py-3 text-[14px] font-bold text-white shadow-sm hover:bg-[#16703C]">
                                <x-ui.icon name="check-circle" class="size-5" /> Marquer publié
                            </button>
                            <a href="{{ route('admin.calendrier-editorial', ['publication' => $p['id'], 'mois' => substr($p['date'], 0, 7)]) }}" wire:navigate x-show="! ouvert" class="btn-tap rounded-full px-4 py-3 text-[13px] font-bold text-[#5B677A] hover:text-brand">Ouvrir dans le calendrier</a>
                        </div>
                        <form x-show="ouvert" x-cloak x-on:submit.prevent="$wire.marquerPublie({{ $p['id'] }}, lien); ouvert = false; lien = ''" class="mt-4 flex flex-col gap-2 sm:flex-row" data-test="priorite-form-publie">
                            <input x-ref="lien" x-model="lien" type="url" placeholder="Collez le lien de la publication (facultatif)" class="{{ $champ }} min-w-0 flex-1 py-2.5">
                            <div class="flex gap-2">
                                <button type="submit" data-test="priorite-confirmer-publie" class="btn-tap flex-1 rounded-full bg-[#1C8F4C] px-5 py-2.5 text-[13.5px] font-bold text-white hover:bg-[#16703C] sm:flex-none">C'est publié</button>
                                <button type="button" x-on:click="ouvert = false" class="btn-tap rounded-full px-3 py-2.5 text-[13px] font-bold text-[#5B677A] hover:text-brand">Annuler</button>
                            </div>
                        </form>
                    </div>
                    @break

                @case('demande_urgente')
                @case('demande')
                    @php $d = $priorite['demande']; $autres = $priorite['nombre'] - 1; @endphp
                    <h2 class="mt-1.5 text-[19px] font-bold leading-snug text-ink sm:text-[22px]">
                        {{ $priorite['type'] === 'demande_urgente' ? 'Demande urgente' : 'Nouvelle demande' }} : « {{ $d['titre'] }} »
                    </h2>
                    <p class="mt-1 text-[13px] text-[#5B677A]">
                        {{ $d['departement_label'] }} · souhaitée le {{ DemandesCom::date($d['date_souhaitee'], 'l j F') }}
                        <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ DemandesCom::classesDelai($d['niveau_delai']) }}">{{ DemandesCom::echeance($d['jours_avant']) }}</span>
                        @if ($autres > 0)<span class="font-semibold text-ink"> · et {{ $pluriel($autres, 'autre demande') }}</span>@endif
                    </p>
                    <div class="mt-4">
                        @include('livewire.departements.partials.demande-actions', ['d' => $d, 'grand' => true])
                    </div>
                    @break

                @case('cathoimpact')
                    @php $c = $priorite['reponse']; $autres = $priorite['nombre'] - 1; @endphp
                    <h2 class="mt-1.5 text-[19px] font-bold leading-snug text-ink sm:text-[22px]">Examiner la candidature CathoImpact de {{ $c['nom_complet'] }}</h2>
                    <p class="mt-1 text-[13px] text-[#5B677A]">
                        {{ collect([$c['fonction'], $c['entreprise']])->filter()->implode(' · ') ?: $c['profil_label'] }} · reçue le {{ DemandesCom::date($c['created_at'], 'j F') }}
                        @if ($autres > 0)<span class="font-semibold text-ink"> · et {{ $pluriel($autres, 'autre réponse') }} à examiner</span>@endif
                    </p>
                    <a href="{{ route('admin.cathoimpact', ['fiche' => $c['id']]) }}" wire:navigate data-test="priorite-action"
                        class="btn-tap mt-4 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-3 text-[14px] font-bold text-white shadow-sm hover:bg-brand/90">
                        <x-ui.icon name="sparkles" class="size-5" /> Examiner la candidature
                    </a>
                    @break

                @default
                    <h2 class="mt-1.5 text-[20px] font-bold text-ink sm:text-[22px]">Tout est à jour 🎉</h2>
                    @if ($priorite['suggestion'] === 'planifier')
                        <p class="mt-1 text-[13px] text-[#5B677A]">Rien n'est encore prévu la semaine prochaine : prenez un peu d'avance sur le calendrier.</p>
                        <button type="button" wire:click="ouvrirPlanification('{{ $cockpit['semaine_prochaine'] }}')" data-test="priorite-action"
                            class="btn-tap mt-4 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-3 text-[14px] font-bold text-white shadow-sm hover:bg-brand/90">
                            <x-ui.icon name="plus" class="size-5" /> Planifier la semaine prochaine
                        </button>
                    @else
                        <p class="mt-1 text-[13px] text-[#5B677A]">Aucune publication en retard, aucune demande en attente. Le moment de préparer un visuel d'avance ?</p>
                        <a href="{{ route('admin.visuels') }}" wire:navigate data-test="priorite-action"
                            class="btn-tap mt-4 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-3 text-[14px] font-bold text-white shadow-sm hover:bg-brand/90">
                            <x-ui.icon name="image" class="size-5" /> Créer un visuel
                        </a>
                    @endif
            @endswitch
        </section>

        {{-- ── 2. Actions rapides ─────────────────────────────────────── --}}
        <section aria-label="Actions rapides" class="mb-8 grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4" data-test="actions-rapides">
            @php $bouton = 'btn-tap relative flex min-h-[64px] items-center gap-3 rounded-[16px] border border-[#EFEAE1] bg-white px-3.5 py-3 text-left transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(3,29,89,.35)]'; @endphp
            <button type="button" wire:click="ouvrirPlanification" data-test="action-planifier-publication" class="{{ $bouton }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-[12px] bg-[#FFF3E8]"><x-ui.illustration name="agenda" size="size-7" /></span>
                <span class="min-w-0 text-[13px] font-bold leading-tight text-ink">+ Planifier une publication</span>
            </button>
            <a href="{{ route('admin.visuels') }}" wire:navigate data-test="action-creer-visuel" class="{{ $bouton }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-[12px] bg-[#EDF1FA]"><x-ui.illustration name="affiche" size="size-7" /></span>
                <span class="min-w-0 text-[13px] font-bold leading-tight text-ink">Créer un visuel</span>
            </a>
            <a href="{{ route('admin.demandes-communication') }}" wire:navigate data-test="action-repondre-demandes" class="{{ $bouton }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-[12px] bg-[#FDEEEE]"><x-ui.illustration name="bulle" size="size-7" /></span>
                <span class="min-w-0 text-[13px] font-bold leading-tight text-ink">Répondre aux demandes</span>
                @if ($nDemandes > 0)<span class="absolute -right-1.5 -top-1.5 rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white" data-test="compteur-demandes">{{ $nDemandes }}</span>@endif
            </a>
            <a href="{{ route('admin.cathoimpact', $nCatho > 0 ? ['statut' => 'recu'] : []) }}" wire:navigate data-test="action-examiner-cathoimpact" class="{{ $bouton }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-[12px] bg-[#FBF1DC]"><x-ui.illustration name="etoile" size="size-7" /></span>
                <span class="min-w-0 text-[13px] font-bold leading-tight text-ink">Examiner CathoImpact</span>
                @if ($nCatho > 0)<span class="absolute -right-1.5 -top-1.5 rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white" data-test="compteur-cathoimpact">{{ $nCatho }}</span>@endif
            </a>
        </section>

        {{-- ── 3. Résultats CathoImpact ──────────────────────────────── --}}
        @if ($ci)
            <section aria-labelledby="ci-titre" class="mb-8 rounded-[20px] border border-[#EFEAE1] bg-white p-4 sm:p-5" data-test="resultats-cathoimpact">
                <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h2 id="ci-titre" class="flex flex-1 items-center gap-2 text-[16px] font-bold text-ink"><x-ui.illustration name="etoile" size="size-7" /> CathoImpact</h2>
                    <a href="{{ \App\Support\Site::url('/cathoimpact') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[12.5px] font-bold text-azure hover:text-brand">Voir la galerie du site <x-ui.icon name="external-link" class="size-3.5" /></a>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        ['Reçues', $ci['recues'], $ci['recues_mois'].' ce mois-ci', null],
                        ['À examiner', $ci['a_examiner'], 'en attente de votre avis', 'recu'],
                        ['En cours', $ci['en_cours'], 'de « retenu » à « en création »', null],
                        ['Publiées', $ci['publiees'], 'sur les réseaux', 'publie'],
                    ] as [$libelle, $valeur, $detail, $filtre])
                        <a href="{{ route('admin.cathoimpact', $filtre ? ['statut' => $filtre] : []) }}" wire:navigate class="rounded-[14px] bg-[#FAF8F4] px-3 py-2.5 transition hover:bg-[#F4EFE6]">
                            <span class="block font-display text-[24px] leading-none text-brand">{{ $nb($valeur) }}</span>
                            <span class="mt-1 block text-[12px] font-bold text-ink">{{ $libelle }}</span>
                            <span class="block text-[11px] leading-tight text-[#5B677A]">{{ $detail }}</span>
                        </a>
                    @endforeach
                </div>
                @if ($ci['dernieres'])
                    <p class="mb-1 mt-4 text-[12px] font-bold text-[#5B677A]">Dernières candidatures</p>
                    @foreach ($ci['dernieres'] as $r)
                        <a href="{{ route('admin.cathoimpact', ['fiche' => $r['id']]) }}" wire:navigate data-test="derniere-candidature"
                            class="flex flex-wrap items-center gap-x-2.5 gap-y-1 border-t border-[#EFEAE1] px-1 py-2 text-[12.5px] first-of-type:border-t-0 hover:bg-[#FAF6EF]">
                            <span class="min-w-0 font-bold text-ink">{{ $r['nom_complet'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-[#5B677A]">{{ $r['entreprise'] }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $teinteCi($r['statut']) }}">{{ $r['statut_label'] }}</span>
                            <span class="text-[11px] text-[#5B677A]">{{ DemandesCom::date($r['created_at'], 'j M') }}</span>
                        </a>
                    @endforeach
                @else
                    <p class="mt-3 text-[12.5px] text-[#5B677A]">Aucune candidature pour l'instant : partagez le formulaire CathoImpact du site sur vos réseaux.</p>
                @endif
            </section>
        @endif

        {{-- ── 4. Quatre chiffres du mois ────────────────────────────── --}}
        @php
            $evolution = $visites['evolution']['visiteurs'] ?? null;
            $chiffres = [
                ['regularite', 'Publications du mois', $ch['publications_publiees'].'/'.$ch['publications_prevues'], 'publiées / prévues', 'megaphone', '#F1F3F7', route('admin.calendrier-editorial'), null],
                ['visites', 'Visiteurs du site', $visites ? $nb($visites['visiteurs']) : '—', $visites ? 'ce mois-ci' : 'mesure indisponible', 'globe', '#EEF6FD', route('admin.statistiques-site'), $evolution],
                ['nouveaux-membres', 'Nouveaux membres', $ch['nouveaux_membres'], 'ce mois-ci', 'equipe', '#EFFAF4', null, null],
                ['demandes', 'Demandes en attente', $ch['demandes_en_attente'], $ch['demandes_nouvelles'] ? 'dont '.$ch['demandes_nouvelles'].' nouvelle'.($ch['demandes_nouvelles'] > 1 ? 's' : '') : 'toutes prises en charge', 'bulle', '#FDEEEE', route('admin.demandes-communication'), null],
            ];
        @endphp
        <section aria-labelledby="chiffres-titre" class="mb-8" data-test="chiffres-du-mois">
            <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                <h2 id="chiffres-titre" class="flex-1 text-[16px] font-bold text-ink">{{ $cockpit['libelle'] }} <span class="text-[13px] font-semibold text-[#5B677A]">· en chiffres</span></h2>
                <a href="{{ route('admin.bilan-communication') }}" wire:navigate class="inline-flex items-center gap-1 text-[12.5px] font-bold text-azure hover:text-brand">Voir le bilan complet <x-ui.icon name="chevron-right" class="size-3.5" /></a>
            </div>
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4">
                @foreach ($chiffres as [$cle, $libelle, $valeur, $detail, $icone, $fond, $url, $evo])
                    <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" wire:navigate @endif data-test="chiffre-{{ $cle }}"
                        class="flex min-w-0 items-center gap-3 rounded-[16px] border border-[#EFEAE1] bg-white p-3 {{ $url ? 'transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(3,29,89,.35)]' : '' }}">
                        <span class="hidden size-10 shrink-0 items-center justify-center rounded-[12px] sm:flex" style="background: {{ $fond }}"><x-ui.illustration :name="$icone" size="size-6" /></span>
                        <span class="min-w-0">
                            <span class="block font-display text-[24px] leading-none text-brand">{{ $valeur }}</span>
                            <span class="mt-1 block text-[12px] font-bold leading-tight text-ink">{{ $libelle }}</span>
                            <span class="block text-[11px] leading-tight text-[#5B677A]">{{ $detail }}@if ($evo !== null) <span class="whitespace-nowrap font-bold {{ $evo >= 0 ? 'text-[#1C8F4C]' : 'text-accent' }}">· {{ $evo > 0 ? '+' : '' }}{{ $evo }} %</span>@endif</span>
                        </span>
                    </{{ $url ? 'a' : 'div' }}>
                @endforeach
            </div>
        </section>

        {{-- ── 5. Publications d'aujourd'hui et de demain, demandes à traiter ── --}}
        <section aria-labelledby="jour-titre" class="mb-4">
            <h2 id="jour-titre" class="mb-3 text-[16px] font-bold text-ink">Au programme</h2>
            <div class="grid gap-4 lg:grid-cols-5">
                <div class="min-w-0 lg:col-span-3">
                    <livewire:departements.bloc-calendrier :key="'bloc-calendrier'" />
                </div>
                <div class="min-w-0 rounded-[18px] border border-[#EFEAE1] bg-white p-4 sm:p-5 lg:col-span-2" data-test="bloc-demandes">
                    <div class="mb-2.5 flex flex-wrap items-center gap-2">
                        <h3 class="flex flex-1 items-center gap-2 text-[15px] font-bold text-ink"><x-ui.illustration name="bulle" size="size-7" /> Demandes à traiter</h3>
                        <a href="{{ route('admin.demandes-communication') }}" wire:navigate data-test="lien-demandes" class="text-[12.5px] font-bold text-azure hover:text-brand">Tout voir</a>
                    </div>
                    @forelse ($demandesListe as $d)
                        <div wire:key="dem-{{ $d['id'] }}" class="border-t border-[#EFEAE1] py-3 first-of-type:border-t-0" data-test="bloc-demande-{{ $d['id'] }}">
                            <p class="text-[13px] font-bold text-ink">{{ $d['titre'] }}</p>
                            <p class="mb-2 mt-0.5 flex flex-wrap items-center gap-1.5 text-[11.5px] text-[#5B677A]">
                                {{ $d['departement_label'] }}
                                @if ($d['statut'] === 'nouvelle')<span class="rounded-full bg-[#FFF3E8] px-2 py-0.5 text-[11px] font-bold text-[#8A5A12]">Nouvelle</span>@endif
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ DemandesCom::classesDelai($d['niveau_delai']) }}">{{ DemandesCom::date($d['date_souhaitee'], 'j M') }} · {{ DemandesCom::echeance($d['jours_avant']) }}</span>
                            </p>
                            @include('livewire.departements.partials.demande-actions', ['d' => $d])
                        </div>
                    @empty
                        <p class="text-[12.5px] text-[#5B677A]">
                            {{ in_array($priorite['type'], ['demande', 'demande_urgente'], true) ? 'Pas d\'autre demande en attente.' : 'Aucune demande nouvelle ou urgente : tout est à jour.' }}
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    {{-- ── Fenêtre « Planifier une publication » ──────────────────────── --}}
    @if ($modale)
        <div class="fixed inset-0 z-[90] flex items-end justify-center bg-brand/40 p-0 sm:items-center sm:p-4" wire:click.self="fermerPlanification" @keydown.escape.window="$wire.fermerPlanification()">
            <form wire:submit="planifierPublication" role="dialog" aria-modal="true" aria-labelledby="modale-pub-titre" data-test="modale-planifier"
                class="panel-enter max-h-[92vh] w-full max-w-[560px] overflow-y-auto rounded-t-[22px] bg-white p-5 shadow-2xl sm:rounded-[22px] sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p id="modale-pub-titre" class="text-[16px] font-bold text-brand">Planifier une publication</p>
                    <button type="button" wire:click="fermerPlanification" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block sm:col-span-2"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Sujet</span>
                        <input type="text" wire:model="pubTitre" required maxlength="200" autofocus data-test="modale-titre" placeholder="Ex. : Annonce du gala de fin d'année" class="{{ $champ }}">
                        @error('pubTitre') <span class="text-xs text-accent">{{ $message }}</span> @enderror</label>
                    <label class="block"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Date</span>
                        <input type="date" wire:model="pubDate" required data-test="modale-date" class="{{ $champ }}">
                        @error('pubDate') <span class="text-xs text-accent">{{ $message }}</span> @enderror</label>
                    <label class="block"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Type d'information</span>
                        <select wire:model.live="pubType" data-test="modale-type" class="{{ $champ }}">
                            @foreach ($options['types'] ?? [] as $cle => $l)<option value="{{ $cle }}">{{ $l }}</option>@endforeach
                        </select></label>
                    <div class="sm:col-span-2">
                        <span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Canaux <span class="font-normal">(pré-cochés selon le plan de diffusion)</span></span>
                        <div class="flex flex-wrap gap-1.5" data-test="modale-canaux">
                            @foreach ($options['canaux'] ?? [] as $cle => $l)
                                <label class="cursor-pointer" wire:key="canal-{{ $cle }}">
                                    <input type="checkbox" wire:model="pubCanaux" value="{{ $cle }}" class="peer sr-only">
                                    <span class="inline-block rounded-full border border-brand/15 px-3 py-1 text-[12px] font-semibold text-[#5B677A] peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#4F6FBF]">{{ $l }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <label class="block sm:col-span-2"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Responsable</span>
                        <select wire:model="pubResponsable" data-test="modale-responsable" class="{{ $champ }}">
                            <option value="">Celui du tour de rôle à cette date</option>
                            @foreach ($options['equipe'] ?? [] as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                        </select></label>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <button type="submit" wire:loading.attr="disabled" data-test="modale-enregistrer" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Ajouter au calendrier</button>
                    <button type="button" wire:click="fermerPlanification" class="btn-tap rounded-full px-4 py-2.5 text-[13px] font-bold text-[#5B677A] hover:text-brand">Annuler</button>
                    <a href="{{ route('admin.calendrier-editorial', ['nouvelle' => 1]) }}" wire:navigate class="ml-auto text-[12px] font-semibold text-azure hover:text-brand">Plus d'options (heure, format, notes)</a>
                </div>
            </form>
        </div>
    @endif
</div>
