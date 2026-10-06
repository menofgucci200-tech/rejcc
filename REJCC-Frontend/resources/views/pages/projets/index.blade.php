<x-site-layout title="Projets du réseau" description="Les projets portés par les jeunes entrepreneurs du REJCC : agriculture, BTP, numérique, commerce… Découvrez-les et rejoignez le réseau pour y contribuer.">
    <x-page-header eyebrow="Projets" crumb="Projets" subtitle="Des projets portés par les membres du réseau, accompagnés par la communauté : partenaires, compétences, mentors.">
        Les projets du <span class="font-serif italic normal-case text-azure">réseau</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24" x-data="{ secteur: '' }">
        <x-ui.container>
            @if ($projets->isEmpty())
                <div class="rounded-3xl border border-dashed border-brand/15 bg-white p-10 text-center text-ink/60">
                    Les premiers projets du réseau seront bientôt présentés ici.
                </div>
            @else
                @if ($secteurs->count() > 1)
                    <div class="mb-8 flex flex-wrap gap-2">
                        <button type="button" x-on:click="secteur = ''" :class="secteur === '' ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:text-brand'" class="rounded-full px-4 py-2 text-sm font-semibold">Tous</button>
                        @foreach ($secteurs as $s)
                            <button type="button" x-on:click="secteur = @js($s)" :class="secteur === @js($s) ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:text-brand'" class="rounded-full px-4 py-2 text-sm font-semibold">{{ $s }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projets as $p)
                        <div x-show="secteur === '' || secteur === @js($p['groupe']['nom'] ?? '')">
                            <x-projets.carte-publique :p="$p" />
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
