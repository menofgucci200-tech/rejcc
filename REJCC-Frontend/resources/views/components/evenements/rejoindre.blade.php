@props(['e', 'taille' => 'normal'])
{{-- Bouton « Rejoindre la visio » d'un événement en ligne : actif 15 min avant
     le début et pendant la session ; sinon, le délai avant l'ouverture. --}}
@if (($e['en_ligne'] ?? false) && ($e['statut'] ?? '') === 'publie' && ! ($e['passe'] ?? false))
    @if ($e['visio_ouverte'] ?? false)
        <a href="{{ route('espace-membre.evenements.rejoindre', $e['id']) }}" target="_blank" rel="noopener" data-test="rejoindre-visio"
           class="btn-tap inline-flex items-center justify-center gap-2 rounded-full bg-accent font-bold text-white shadow-[0_10px_24px_-10px_rgba(172,1,0,0.7)] hover:bg-accent-600 {{ $taille === 'grand' ? 'px-6 py-3.5 text-[15px]' : 'px-5 py-2.5 text-[13.5px]' }}">
            <x-ui.icon name="video" class="size-4" /> {{ ($e['registered'] ?? false) ? 'Rejoindre la visio' : 'Rejoindre maintenant' }}
        </a>
    @elseif ($e['registered'] ?? false)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-cloud px-4 py-2.5 text-[12.5px] font-semibold text-[#3D4A60]">
            <x-ui.icon name="clock" class="size-4" /> Visio {{ \App\Support\Quand::delai($e['starts_at']) }} · le bouton s'active 15 min avant
        </span>
    @endif
@endif
