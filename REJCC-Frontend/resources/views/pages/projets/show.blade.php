@php
    $p = $projet;
    $c = $p['groupe']['couleur'] ?? '#031D59';
    $sections = array_filter(['Le problème' => $p['probleme'], 'La solution' => $p['solution'], 'Pour qui ?' => $p['cible'], 'Impact attendu' => $p['impact']]);
@endphp

<x-site-layout :title="$p['title']" :description="$p['accroche'] ?: \Illuminate\Support\Str::limit($p['description'], 150)">
    <x-page-header :eyebrow="$p['groupe']['nom'] ?? 'Projet'" crumb="Projets" :subtitle="$p['accroche']">
        {{ $p['title'] }}
    </x-page-header>

    <section class="bg-white py-16 sm:py-24">
        <x-ui.container class="grid gap-12 lg:grid-cols-[1fr_340px] lg:gap-16">
            <div class="min-w-0">
                @if ($p['image'])
                    <img src="{{ $p['image'] }}" alt="Visuel — {{ $p['title'] }}" class="mb-8 max-h-[420px] w-full rounded-3xl object-cover">
                @endif
                <div class="whitespace-pre-line text-pretty leading-relaxed text-ink/80">{!! \App\Support\Texte::liens($p['description']) !!}</div>
                @if ($sections)
                    <div class="mt-10 grid gap-4 sm:grid-cols-2">
                        @foreach ($sections as $titre => $texte)
                            <div class="rounded-3xl bg-cloud p-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent">{{ $titre }}</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink/80">{{ $texte }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="h-fit rounded-3xl border border-brand/10 bg-cloud p-7 lg:sticky lg:top-28">
                <div class="flex flex-wrap gap-2">
                    @if ($p['a_la_une'])<span class="rounded-full bg-accent px-2.5 py-0.5 text-xs font-semibold text-white">À la une</span>@endif
                    <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $p['stade'] }}</span>
                </div>
                <ul class="mt-5 space-y-2.5 text-sm text-ink/70">
                    <li>Porté par <strong class="text-brand">{{ $p['porteur'] }}</strong>{{ $p['equipe_taille'] > 1 ? ', avec une équipe de '.$p['equipe_taille'].' membres' : '' }}</li>
                    @if ($p['groupe'])<li>Secteur : <span class="font-semibold" style="color: {{ $c }}">{{ $p['groupe']['nom'] }}</span></li>@endif
                    @if ($p['ville'])<li>Ville : {{ $p['ville'] }}</li>@endif
                    @if ($p['lien'])<li><a href="{{ $p['lien'] }}" target="_blank" rel="noopener" class="font-semibold text-azure hover:underline">{{ parse_url($p['lien'], PHP_URL_HOST) ?: 'Site du projet' }}</a></li>@endif
                </ul>
                @if (! empty($p['besoins']))
                    <p class="mt-6 text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">Le projet recherche</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($p['besoins'] as $b)<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-accent">{{ $b }}</span>@endforeach
                    </div>
                @endif
                <div class="mt-7 flex flex-col gap-2.5">
                    @if ($membreConnecte)
                        <x-ui.button :href="url('/espace-membre/projets?projet='.$p['id'])" variant="primary" :with-arrow="true">Contribuer depuis mon espace</x-ui.button>
                    @else
                        <x-ui.button href="/adhesion" variant="primary" :with-arrow="true">Adhérer pour contribuer</x-ui.button>
                        <p class="text-xs text-ink/55">Les membres du REJCC peuvent contacter l'équipe, rejoindre le projet et suivre ses avancées.</p>
                        <a href="{{ url('/espace-membre/projets?projet='.$p['id']) }}" class="text-xs font-semibold text-brand hover:underline">Déjà membre ? Connectez-vous</a>
                    @endif
                </div>
            </aside>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
