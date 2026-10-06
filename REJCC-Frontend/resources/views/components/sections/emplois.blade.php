@php $offres = collect(\App\Support\Api::get('/public-opportunities')['opportunities'] ?? [])->take(4); @endphp

@if ($offres->isNotEmpty())
<section class="bg-cloud py-24 sm:py-32">
    <x-ui.container>
        <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <x-ui.section-heading align="left" eyebrow="Emploi & Stage" title="Le réseau recrute" subtitle="Emplois, stages et missions proposés par les entrepreneurs du REJCC." class="max-w-2xl" />
            <x-ui.button href="/emplois" variant="outline" :with-arrow="true" class="shrink-0">Voir toutes les offres</x-ui.button>
        </div>
        <div class="mt-14 grid gap-4 md:grid-cols-2">
            @foreach ($offres as $i => $o)
                <x-ui.reveal :delay="($i % 2) * 0.08"><x-emplois.carte-publique :o="$o" /></x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
@endif
