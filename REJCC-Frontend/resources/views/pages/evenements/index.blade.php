<x-site-layout title="Événements">
    <x-page-header eyebrow="Agenda" crumb="Événements" subtitle="Parcourez le calendrier, filtrez par type et inscrivez-vous aux rendez-vous du réseau.">
        Nos <span class="font-serif italic normal-case text-azure">événements</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container>
            <livewire:events-explorer />
        </x-ui.container>
    </section>
</x-site-layout>
