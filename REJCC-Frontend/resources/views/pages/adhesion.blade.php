<x-site-layout title="Adhésion">
    <x-page-header eyebrow="Rejoindre le réseau" crumb="Adhésion" subtitle="Renseignez votre profil et vos attentes en quelques minutes pour rejoindre le REJCC.">
        Devenez <span class="font-serif italic normal-case text-azure">membre</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-2xl">
            <livewire:adhesion-application-form />
        </x-ui.container>
    </section>
</x-site-layout>
