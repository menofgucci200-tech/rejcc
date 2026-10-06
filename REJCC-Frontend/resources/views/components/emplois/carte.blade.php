@props(['o', 'avecStatut' => false])

{{-- Carte d'une offre (liste du réseau ou « Mes offres »). --}}
@php
    $tc = \App\Support\OffreStatus::type($o['type']);
    $sc = \App\Support\OffreStatus::statut($o['statut']);
    $limite = $o['deadline'] ?? null;
@endphp
<article wire:key="offre-{{ $o['id'] }}" data-test="carte-offre" class="card-hover rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
    <button type="button" wire:click="voir({{ $o['id'] }})" class="flex w-full flex-wrap items-start gap-4 p-4 text-left sm:p-5">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl text-white" style="background: linear-gradient(135deg, {{ $o['groupe']['couleur'] ?? '#4F6FBF' }}, #031D59)">
            <x-ui.icon :name="$o['groupe']['icone'] ?? 'nav-briefcase'" class="size-5" />
        </span>
        <div class="min-w-[200px] flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                @if ($avecStatut)
                    <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold" style="background: {{ $sc }}1A; color: {{ $sc }}">{{ $o['statut_label'] }}</span>
                @endif
                <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold" style="background: {{ $tc }}14; color: {{ $tc }}">{{ $o['type_label'] }}{{ $o['contrat_label'] ? ' · '.$o['contrat_label'] : '' }}</span>
                @if (($o['teletravail'] ?? 'sur_site') !== 'sur_site')
                    <span class="rounded-full bg-azure/10 px-2 py-0.5 text-[10.5px] font-bold text-azure">{{ $o['teletravail_label'] }}</span>
                @endif
            </div>
            <p class="mt-1.5 text-[14.5px] font-bold text-brand">{{ $o['title'] }}</p>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] font-semibold text-[#5B677A]">
                <span class="inline-flex items-center gap-1"><x-ui.icon name="store" class="size-3 text-azure" /> {{ $o['entreprise'] ?: 'Structure du réseau' }}</span>
                @if ($o['lieu']) <span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3 text-azure" /> {{ $o['lieu'] }}</span> @endif
                @if ($o['remuneration']) <span class="inline-flex items-center gap-1 text-[#1C8F4C]">{{ $o['remuneration'] }}</span> @endif
            </p>
            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-[#5B677A]">{{ $o['description'] }}</p>
            @if ($avecStatut && in_array($o['statut'], ['a_corriger', 'refusee'], true) && ($o['motif'] ?? null))
                <p class="mt-2 rounded-lg px-2.5 py-1.5 text-[11.5px] font-semibold" style="background: {{ $sc }}12; color: {{ $sc }}">{{ $o['statut'] === 'refusee' ? 'Motif' : "Demande de l'équipe" }} : {{ $o['motif'] }}</p>
            @endif
            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[#9AA6B8]">
                @if ($o['groupe']) <span style="color: {{ $o['groupe']['couleur'] }}">{{ $o['groupe']['nom'] }}</span> @endif
                @if ($o['publie_at']) <span>Publiée {{ \Illuminate\Support\Carbon::parse($o['publie_at'])->locale('fr')->diffForHumans() }}</span> @endif
                @if ($limite)
                    <span class="inline-flex items-center gap-1 font-semibold text-accent"><x-ui.icon name="clock" class="size-3" /> Candidature avant le {{ \Illuminate\Support\Carbon::parse($limite)->locale('fr')->isoFormat('D MMMM') }}</span>
                @elseif ($avecStatut && $o['statut'] === 'publiee' && $o['expire_le'])
                    <span>En ligne jusqu'au {{ \Illuminate\Support\Carbon::parse($o['expire_le'])->locale('fr')->isoFormat('D MMMM') }}</span>
                @endif
            </p>
        </div>
    </button>
</article>
