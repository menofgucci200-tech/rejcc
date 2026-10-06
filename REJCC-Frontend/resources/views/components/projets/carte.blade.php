@props(['p', 'besoinsLabels' => [], 'avecStatut' => false])

{{-- Carte d'un projet (liste du réseau ou « Mes projets »). --}}
@php
    $couleur = $p['groupe']['couleur'] ?? '#031D59';
    $stadeC = \App\Support\ProjectStatus::stade($p['stade']);
    $statutC = \App\Support\ProjectStatus::color($p['statut']);
@endphp
<button type="button" wire:click="voir({{ $p['id'] }})" wire:key="projet-{{ $p['id'] }}" data-test="carte-projet"
    class="card-hover group flex flex-col overflow-hidden rounded-[16px] border border-brand/10 bg-white text-left shadow-[0_2px_8px_rgba(3,29,89,.05)]">
    @if ($p['image'] ?? null)
        <img src="{{ $p['image'] }}" alt="" loading="lazy" class="h-32 w-full object-cover">
    @else
        <div class="flex h-20 items-center justify-center" style="background: linear-gradient(135deg, {{ $couleur }}, {{ $couleur }}B3)">
            <x-ui.icon :name="$p['groupe']['icone'] ?? 'nav-projects'" class="size-8 text-white/80" />
        </div>
    @endif
    <div class="flex flex-1 flex-col p-[16px]">
        <div class="mb-2 flex flex-wrap items-center gap-1.5">
            @if ($avecStatut)
                <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold" style="background: {{ $statutC }}1A; color: {{ $statutC }}">{{ $p['statut_label'] }}</span>
            @endif
            <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold" style="background: {{ $stadeC }}14; color: {{ $stadeC }}">{{ $p['stade_label'] }}</span>
            @if ($p['groupe'] ?? null)
                <span class="truncate text-[11px] font-semibold" style="color: {{ $couleur }}">{{ $p['groupe']['nom'] }}</span>
            @endif
        </div>
        <p class="text-[14px] font-bold leading-snug text-brand group-hover:underline">{{ $p['title'] }}</p>
        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-[#5B677A]">{{ $p['accroche'] ?: $p['description'] }}</p>

        @if ($avecStatut && in_array($p['statut'], ['a_completer', 'refuse'], true) && ($p['motif'] ?? null))
            <p class="mt-2 line-clamp-2 rounded-lg px-2.5 py-1.5 text-[11.5px] font-semibold" style="background: {{ $statutC }}12; color: {{ $statutC }}">{{ $p['statut'] === 'refuse' ? 'Motif' : "Demande de l'équipe" }} : {{ $p['motif'] }}</p>
        @endif

        @if (! empty($p['besoins']))
            <div class="mt-2.5 flex flex-wrap gap-1">
                @foreach (array_slice($p['besoins'], 0, 3) as $b)
                    <span class="rounded-full bg-accent/[.07] px-2 py-0.5 text-[10.5px] font-semibold text-accent">Cherche : {{ mb_strtolower($besoinsLabels[$b] ?? $b) }}</span>
                @endforeach
            </div>
        @endif

        <div class="mt-auto flex items-center justify-between gap-2 pt-3">
            <span class="flex min-w-0 items-center gap-1.5 text-[11.5px] text-[#5B677A]">
                @if ($p['mine'])
                    <span class="font-semibold text-brand">Votre projet</span>
                @elseif ($p['porteur'] ?? null)
                    <x-messagerie.avatar :personne="$p['porteur']" taille="size-5" texte="text-[8px]" />
                    <span class="truncate">{{ $p['porteur']['prenom'] }} {{ $p['porteur']['nom'] }}</span>
                @endif
            </span>
            @if ($p['ville'] ?? null)
                <span class="inline-flex shrink-0 items-center gap-1 text-[11px] text-[#9AA6B8]"><x-ui.icon name="map-pin" class="size-3" /> {{ $p['ville'] }}</span>
            @endif
        </div>
    </div>
</button>
