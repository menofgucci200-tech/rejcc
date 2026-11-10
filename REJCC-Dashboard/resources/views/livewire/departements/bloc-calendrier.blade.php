{{-- Bloc « Calendrier éditorial » de l'espace Communication : tour en cours, aujourd'hui, demain, retards. --}}
@php
    $styleStatut = [
        'prevu' => 'bg-[#EEF2F8] text-[#41506B]',
        'pret' => 'bg-[#4F6FBF]/10 text-[#3A57A3]',
        'publie' => 'bg-[#22A85A]/10 text-[#1C8F4C]',
        'non_publie' => 'bg-accent/10 text-accent',
    ];
@endphp
<section class="h-full rounded-[18px] border border-[#EFEAE1] bg-white p-4 sm:p-5" data-test="bloc-calendrier">
    <div class="mb-3.5 flex flex-wrap items-center gap-3">
        <h3 class="flex flex-1 items-center gap-2 text-[15px] font-bold text-ink"><x-ui.illustration name="agenda" size="size-7" /> Publications du jour</h3>
        <a href="{{ route('admin.calendrier-editorial') }}" wire:navigate data-test="lien-calendrier" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-[#E7E1D6] bg-white px-3.5 py-1.5 text-[12.5px] font-bold text-ink hover:bg-[#FAF6EF]">
            Ouvrir le calendrier
            @if ($jour && $jour['chiffres']['retards'] > 0)<span class="rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $jour['chiffres']['retards'] }} en retard</span>@endif
        </a>
    </div>

    @if (! $jour)
        <p class="text-[12.5px] text-[#5B677A]">Calendrier indisponible pour le moment.</p>
    @else
        <p class="mb-3 rounded-[12px] bg-cloud/60 px-3 py-2 text-[12.5px] text-ink" data-test="tour-en-cours-bloc">
            @if ($jour['tour_en_cours'])
                Cette semaine : <strong>{{ $jour['tour_en_cours']['responsable'] }}</strong>
                <span class="text-[#5B677A]">(du {{ \Illuminate\Support\Carbon::parse($jour['tour_en_cours']['du'])->locale('fr')->isoFormat('ddd D') }} au {{ \Illuminate\Support\Carbon::parse($jour['tour_en_cours']['au'])->locale('fr')->isoFormat('ddd D MMM') }})</span>
            @else
                <span class="text-[#5B677A]">Aucun tour de rôle en cours.</span>
            @endif
            <span class="text-[#5B677A]"> · {{ $jour['chiffres']['publiees_mois'] }}/{{ $jour['chiffres']['prevues_mois'] }} publiées ce mois-ci</span>
        </p>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ([["Aujourd'hui", $jour['aujourdhui'], 'Rien de prévu aujourd\'hui.'], ['Demain', $jour['demain'], 'Rien de prévu demain.']] as [$titre, $liste, $vide])
                <div>
                    <p class="mb-1.5 text-[12px] font-bold text-[#5B677A]">{{ $titre }}</p>
                    @forelse ($liste as $p)
                        @include('livewire.admin.partials.publication-ligne', ['p' => $p, 'styleStatut' => $styleStatut, 'compact' => true, 'lienEdition' => true])
                    @empty
                        <p class="text-[12.5px] text-[#5B677A]">{{ $vide }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        @if ($jour['retards'])
            <p class="mb-1.5 mt-4 flex items-center gap-1.5 text-[12px] font-bold text-accent"><x-ui.icon name="alert-circle" class="size-3.5" /> En retard</p>
            @foreach ($jour['retards'] as $p)
                @include('livewire.admin.partials.publication-ligne', ['p' => $p, 'styleStatut' => $styleStatut, 'compact' => false, 'lienEdition' => true])
            @endforeach
        @endif
    @endif
</section>
