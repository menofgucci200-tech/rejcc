<x-site-layout title="Offres d'emploi et de stage" description="Emplois, stages, alternances et missions proposés par les entreprises du réseau REJCC en Côte d'Ivoire. Adhérez pour postuler.">
    <x-page-header eyebrow="Emploi & Stage" crumb="Emplois" subtitle="Des offres proposées par les entrepreneurs du réseau et vérifiées par l'équipe REJCC. Les membres postulent directement sur la plateforme.">
        Emplois &amp; <span class="font-serif italic normal-case text-azure">stages</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24" x-data="{ type: '' }">
        <x-ui.container>
            @if ($offres->isEmpty())
                <div class="rounded-3xl border border-dashed border-brand/15 bg-white p-10 text-center text-ink/60">Aucune offre en ligne pour le moment : revenez bientôt !</div>
            @else
                @if ($types->count() > 1)
                    <div class="mb-8 flex flex-wrap gap-2">
                        <button type="button" x-on:click="type = ''" :class="type === '' ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:text-brand'" class="rounded-full px-4 py-2 text-sm font-semibold">Toutes</button>
                        @foreach ($types as $k => $v)
                            <button type="button" x-on:click="type = @js($k)" :class="type === @js($k) ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:text-brand'" class="rounded-full px-4 py-2 text-sm font-semibold">{{ $v }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($offres as $o)
                        <div x-show="type === '' || type === @js($o['type'])"><x-emplois.carte-publique :o="$o" /></div>
                    @endforeach
                </div>
            @endif
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
