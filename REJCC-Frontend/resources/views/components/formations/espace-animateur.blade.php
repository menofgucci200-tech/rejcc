@props(['session'])
{{-- Fiche d'une session vue par son formateur : horaires, visio, inscrits. --}}
@php
    $debut = \Carbon\Carbon::parse($session['starts_at'])->locale('fr');
    $fin = $session['ends_at'] ? \Carbon\Carbon::parse($session['ends_at']) : null;
@endphp
<article class="rounded-[22px] border border-[#EDE9F3] bg-white p-6 shadow-[0_8px_30px_-18px_rgba(59,63,92,.35)]">
    <p class="text-[11px] font-bold uppercase tracking-[0.12em] {{ $session['en_cours'] ? 'text-[#2E7D5B]' : 'text-[#E07A5F]' }}">
        {{ $session['statut'] === 'annule' ? 'Session annulée' : ($session['en_cours'] ? '● En ce moment' : ($session['passe'] ? 'Session terminée' : 'Session live')) }}
    </p>
    <h2 class="mt-1 text-[22px] font-bold text-[#3B3F5C]">{{ $session['title'] }}</h2>
    <p class="mt-1 text-[14px] text-[#6B6F86]">{{ ucfirst($debut->isoFormat('dddd D MMMM YYYY [à] HH[h]mm')) }}{{ $fin ? ' – '.$fin->format('H\hi') : '' }}@if ($session['formation']) · {{ $session['formation'] }}@endif</p>
    @if ($session['co_animateurs'])
        <p class="mt-1 text-[13px] text-[#6B6F86]">Avec {{ implode(', ', $session['co_animateurs']) }}</p>
    @endif
    @if ($session['statut'] === 'annule')
        <p class="mt-4 rounded-[14px] bg-[#FDEEEE] px-4 py-3 text-[13.5px] font-semibold text-[#C24E4E]">Cette session est annulée.{{ $session['motif_annulation'] ? ' Motif : '.$session['motif_annulation'] : '' }}</p>
    @elseif (! $session['passe'])
        <div class="mt-5 rounded-[16px] border border-[#CDEBDD] bg-[#EFFAF4] p-4">
            @if ($session['lien_visio'])
                <a href="{{ $session['lien_visio'] }}" target="_blank" rel="noopener" data-test="ouvrir-visio" class="btn-tap inline-flex items-center gap-2 rounded-full bg-[#2E9E6E] px-6 py-3 text-[14px] font-bold text-white shadow-sm hover:bg-[#268A5F]">
                    <x-ui.icon name="external-link" class="size-4" /> Rejoindre la visio
                </a>
                @if ($session['visio_google'])
                    <p class="mt-3 text-[12.5px] leading-relaxed text-[#4F6B5F]">La réunion appartient au compte Google du REJCC{{ $session['compte_google'] ? ' ('.$session['compte_google'].')' : '' }}. Une personne connectée à ce compte admet les participants et gère la salle : vous, vous animez.</p>
                @endif
            @else
                <p class="text-[13.5px] font-semibold text-[#2E7D5B]">Le bouton « Rejoindre la visio » apparaîtra ici une heure avant le début.</p>
            @endif
        </div>
    @endif
    @if ($session['description'])
        <p class="mt-5 whitespace-pre-line text-[14px] leading-relaxed text-[#4A4E68]">{{ $session['description'] }}</p>
    @endif
    <h3 class="mt-6 text-[15px] font-bold text-[#3B3F5C]">Participants inscrits ({{ count($session['inscrits']) }})</h3>
    <div class="mt-2 flex flex-wrap gap-1.5">
        @forelse ($session['inscrits'] as $i)
            <span class="rounded-full bg-[#F7F4FD] px-3 py-1 text-[12.5px] font-semibold text-[#5A4E8C]">{{ $i['nom'] }}</span>
        @empty
            <p class="text-[13px] text-[#6B6F86]">Personne n'est encore inscrit.</p>
        @endforelse
    </div>
</article>
