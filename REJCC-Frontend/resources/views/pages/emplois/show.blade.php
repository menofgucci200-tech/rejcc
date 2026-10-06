@php
    $o = $offre;
    $infos = array_filter([
        'Contrat' => $o['type_label'].($o['contrat_label'] ? ' · '.$o['contrat_label'] : ''),
        'Lieu' => $o['lieu'].(($o['teletravail'] ?? 'sur_site') !== 'sur_site' ? ' · '.$o['teletravail_label'] : ''),
        'Rémunération' => $o['remuneration'],
        'Début' => $o['debut'] ? \Illuminate\Support\Carbon::parse($o['debut'])->locale('fr')->isoFormat('D MMMM YYYY') : null,
        'Durée' => $o['duree'],
        'Secteur' => $o['groupe']['nom'] ?? null,
        'Date limite' => $o['deadline'] ? \Illuminate\Support\Carbon::parse($o['deadline'])->locale('fr')->isoFormat('D MMMM YYYY') : null,
    ]);
@endphp

<x-site-layout :title="$o['title'].' — '.$o['entreprise']" :description="\Illuminate\Support\Str::limit($o['description'], 150)">
    <x-page-header :eyebrow="$o['type_label']" crumb="Emplois" :subtitle="$o['entreprise'].' · '.$o['lieu']">
        {{ $o['title'] }}
    </x-page-header>

    <section class="bg-white py-16 sm:py-24">
        <x-ui.container class="grid gap-12 lg:grid-cols-[1fr_340px] lg:gap-16">
            <div class="min-w-0">
                <div class="whitespace-pre-line text-pretty leading-relaxed text-ink/80">{!! \App\Support\Texte::liens($o['description']) !!}</div>
                @foreach (['missions' => 'Missions', 'profil' => 'Profil recherché'] as $k => $t)
                    @if ($o[$k] ?? null)
                        <h2 class="mt-10 text-xs font-semibold uppercase tracking-[0.16em] text-accent">{{ $t }}</h2>
                        <p class="mt-3 whitespace-pre-line leading-relaxed text-ink/80">{{ $o[$k] }}</p>
                    @endif
                @endforeach
                @if (! empty($o['competences']))
                    <div class="mt-8 flex flex-wrap gap-2">
                        @foreach ($o['competences'] as $c)<span class="rounded-full bg-cloud px-3 py-1 text-sm font-semibold text-brand">{{ $c }}</span>@endforeach
                    </div>
                @endif
            </div>

            <aside class="h-fit rounded-3xl border border-brand/10 bg-cloud p-7 lg:sticky lg:top-28">
                <dl class="space-y-3 text-sm">
                    @foreach ($infos as $k => $v)
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/45">{{ $k }}</dt><dd class="mt-0.5 font-semibold text-brand">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-7 flex flex-col gap-2.5">
                    @if ($membreConnecte)
                        <x-ui.button :href="url('/espace-membre/emplois?offre='.$o['id'])" variant="primary" :with-arrow="true">Postuler depuis mon espace</x-ui.button>
                    @else
                        <x-ui.button href="/adhesion" variant="primary" :with-arrow="true">Adhérer pour postuler</x-ui.button>
                        <p class="text-xs text-ink/55">Les candidatures se font sur la plateforme du REJCC : message, CV et suivi de votre candidature.</p>
                        <a href="{{ url('/espace-membre/emplois?offre='.$o['id']) }}" class="text-xs font-semibold text-brand hover:underline">Déjà membre ? Connectez-vous pour postuler</a>
                    @endif
                </div>
            </aside>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
