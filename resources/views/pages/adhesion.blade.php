<x-site-layout title="Adhésion">
    <x-page-header eyebrow="Rejoindre le réseau" crumb="Adhésion" subtitle="Renseignez votre profil, choisissez votre moyen de paiement et finalisez votre adhésion en quelques minutes.">
        Devenez <span class="font-serif italic normal-case text-azure">membre</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-4xl">
            <livewire:adhesion-form />
        </x-ui.container>
    </section>
</x-site-layout>
