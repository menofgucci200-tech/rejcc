@php
    // Projets du réseau dont le porteur a accepté la vitrine (à la une d'abord).
    $projets = collect(\App\Support\Api::get('/public-projects')['projects'] ?? [])->take(3);
@endphp

@if ($projets->isNotEmpty())
<section class="bg-white py-24 sm:py-32">
    <x-ui.container>
        <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <x-ui.section-heading align="left" eyebrow="Projets" title="Ils entreprennent ensemble" subtitle="Des projets portés par les membres et soutenus par tout le réseau." class="max-w-2xl" />
            <x-ui.button href="/projets" variant="outline" :with-arrow="true" class="shrink-0">Voir tous les projets</x-ui.button>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projets as $i => $p)
                <x-ui.reveal :delay="$i * 0.08"><x-projets.carte-publique :p="$p" /></x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
@endif
