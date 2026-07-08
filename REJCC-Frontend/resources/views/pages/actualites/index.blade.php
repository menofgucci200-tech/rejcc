<x-site-layout title="Actualités">
    <x-page-header eyebrow="Le journal du réseau" crumb="Actualités" subtitle="Suivez la vie du réseau, ses partenariats, ses événements et les réussites de ses membres.">
        Nos <span class="font-serif italic normal-case text-azure">actualités</span>
    </x-page-header>

    <section class="bg-white py-16 sm:py-24">
        <x-ui.container>
            <livewire:news-explorer />
        </x-ui.container>
    </section>
</x-site-layout>
