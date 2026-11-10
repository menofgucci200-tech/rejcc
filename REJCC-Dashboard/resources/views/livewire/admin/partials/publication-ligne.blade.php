{{-- Ligne d'une publication du calendrier éditorial, avec « Marquer publié » en un clic (lien collé facultatif).
     Paramètres : $p (ligne de l'API), $styleStatut, $compact (bool), $lienEdition (bool : lien vers le calendrier au lieu de l'édition sur place). --}}
@php
    $lienEdition ??= false;
    $styleStatut ??= [];
    $attente = in_array($p['statut'], ['prevu', 'pret', 'non_publie'], true);
    $quand = \Illuminate\Support\Carbon::parse($p['date'])->locale('fr');
@endphp
<div wire:key="ligne-pub-{{ $p['id'] }}" data-test="ligne-publication" x-data="{ ouvert: false, lien: '' }"
    class="{{ $compact ? 'mb-2 rounded-[12px] border px-3 py-2 last:mb-0' : '-mx-4 border-t px-4 py-3 first:border-t-0 sm:-mx-5 sm:px-5' }} {{ $p['en_retard'] ? 'border-accent/40 bg-accent/[0.04]' : 'border-[#EDF0F5]' }}">
    <div class="flex flex-wrap items-start gap-x-3 gap-y-1">
        @unless ($compact)
            <div class="w-[92px] shrink-0 text-[12px] {{ $p['en_retard'] ? 'font-bold text-accent' : 'text-[#5B677A]' }}">
                <span class="block font-bold {{ $p['en_retard'] ? '' : 'text-brand' }}">{{ ucfirst($quand->isoFormat('ddd D MMM')) }}</span>
                {{ $p['heure'] ?? '' }}
            </div>
        @endunless
        <div class="min-w-[170px] flex-1">
            @if ($lienEdition)
                <a href="{{ route('admin.calendrier-editorial', ['publication' => $p['id'], 'mois' => substr($p['date'], 0, 7)]) }}" wire:navigate class="text-[13px] font-bold text-ink hover:underline">{{ $compact && $p['heure'] ? $p['heure'].' · ' : '' }}{{ $p['titre'] }}</a>
            @else
                <button type="button" wire:click="modifier({{ $p['id'] }})" class="text-left text-[13px] font-bold text-ink hover:underline">{{ $compact && $p['heure'] ? $p['heure'].' · ' : '' }}{{ $p['titre'] }}</button>
            @endif
            <p class="mt-0.5 text-[11.5px] text-[#5B677A]">
                {{ $p['type_label'] }}@if ($p['format']) · {{ $p['format'] }}@endif
                @if ($p['canaux_labels']) · <span class="text-[#3A57A3]">{{ implode(', ', $p['canaux_labels']) }}</span>@endif
            </p>
            <p class="mt-0.5 text-[11.5px] {{ $p['en_retard'] ? 'font-semibold text-accent' : 'text-[#5B677A]' }}">
                {{ $p['responsable_effectif']['nom'] ?? 'Sans responsable' }}@if ($p['responsable_effectif']['du_tour'] ?? false) <span class="opacity-75">(tour)</span>@endif
                @if ($p['en_retard']) · en retard @endif
            </p>
            @foreach ($p['liens'] as $l)
                <a href="{{ $l }}" target="_blank" rel="noopener" class="mt-0.5 block truncate text-[11.5px] font-semibold text-[#4F6FBF] hover:underline">{{ $l }}</a>
            @endforeach
        </div>
        <div class="flex shrink-0 items-center gap-1.5">
            <span class="rounded-full px-2 py-0.5 text-[10.5px] font-bold {{ $styleStatut[$p['statut']] ?? 'bg-cloud text-[#5B677A]' }}">{{ $p['statut_label'] }}</span>
            @if ($attente)
                <button type="button" x-on:click="ouvert = ! ouvert; $nextTick(() => $refs.lien?.focus())" data-test="marquer-publie" class="btn-tap rounded-full bg-[#1C8F4C] px-3 py-1 text-[11.5px] font-bold text-white hover:bg-[#16703C]">Marquer publié</button>
            @endif
        </div>
    </div>
    @if ($attente)
        <form x-show="ouvert" x-cloak x-on:submit.prevent="$wire.marquerPublie({{ $p['id'] }}, lien); ouvert = false" class="mt-2 flex flex-wrap gap-2" data-test="form-marquer-publie">
            <input x-ref="lien" x-model="lien" type="url" placeholder="Collez le lien de la publication (facultatif)" class="min-w-0 flex-1 rounded-[10px] border border-brand/15 px-3 py-1.5 text-[12.5px] focus:border-brand focus:outline-none">
            <button type="submit" data-test="confirmer-publie" class="btn-tap rounded-full bg-[#1C8F4C] px-3.5 py-1.5 text-[12px] font-bold text-white">C'est publié</button>
        </form>
    @endif
</div>
