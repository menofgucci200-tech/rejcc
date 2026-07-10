@php
    $sectors = collect(\App\Support\Api::get('/sectors')['sectors'] ?? [])->map(fn ($s) => (object) $s);
    $total = $sectors->sum(fn ($s) => count($s->items));
@endphp

<x-site-layout title="Domaines d'activité" description="Agriculture, numérique, commerce, artisanat, finance… les secteurs d'activité accompagnés par le REJCC en Côte d'Ivoire.">
    <x-page-header :eyebrow="$total.' domaines · 9 pôles'" crumb="Domaines" subtitle="Le réseau rassemble des entrepreneurs de tous les secteurs. Trouvez le vôtre et connectez-vous aux bonnes personnes.">
        Domaines d'<span class="font-serif italic normal-case text-azure">activité</span>
    </x-page-header>

    <section class="bg-white py-24 sm:py-28">
        <x-ui.container>
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($sectors as $i => $s)
                    <x-ui.reveal :delay="($i % 2) * 0.08" class="h-full">
                        <article class="group flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-brand text-white transition-colors duration-500 group-hover:bg-accent">
                                    <x-ui.icon :name="$s->icon" class="size-6" />
                                </span>
                                <div>
                                    <h2 class="text-xl font-bold text-brand">{{ $s->title }}</h2>
                                    <p class="text-sm text-ink/60">{{ $s->blurb }}</p>
                                </div>
                            </div>
                            <div class="mt-6 flex flex-wrap gap-2">
                                @foreach ($s->items as $item)
                                    <span class="rounded-full border border-brand/10 bg-white px-3.5 py-1.5 text-sm font-medium text-brand transition-colors hover:border-accent/40 hover:text-accent">{{ $item }}</span>
                                @endforeach
                            </div>
                        </article>
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
