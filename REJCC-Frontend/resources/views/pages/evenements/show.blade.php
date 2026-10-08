@php
    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
    $d = $event->starts_at;
    $lienMembre = url('/espace-membre/evenements?evenement='.$event->id);
    $annule = $event->statut === 'annule';
    $horaire = $event->time_label ?: $d->format('H\hi').($event->ends_at && $event->ends_at->isSameDay($d) ? ' – '.$event->ends_at->format('H\hi') : '');

    // Référencement : description de repli et données structurées « Event »
    // (affichage enrichi de la date et du lieu dans les résultats Google).
    $seoDescription = $event->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) ($event->description ?: $event->body))), 155);
    $seoUrl = rtrim((string) config('app.url'), '/').'/evenements/'.$event->slug;
    $schema = array_filter([
        '@type' => 'Event',
        'name' => $event->title,
        'description' => $seoDescription ?: null,
        'startDate' => $d->toIso8601String(),
        'endDate' => $event->ends_at?->toIso8601String(),
        'eventStatus' => $annule ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => $event->en_ligne ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
        'location' => $event->en_ligne
            ? ['@type' => 'VirtualLocation', 'url' => $seoUrl]
            : ['@type' => 'Place', 'name' => $event->location ?: 'Abidjan', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $event->location ?: 'Abidjan', 'addressCountry' => 'CI']],
        'image' => $event->image ? [$event->image] : [asset('brand/rejcc-partage.jpg')],
        'organizer' => ['@type' => 'Organization', 'name' => 'REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques', 'url' => rtrim((string) config('app.url'), '/').'/'],
        'url' => $seoUrl,
    ]);
@endphp

<x-site-layout :title="$event->title" :description="$seoDescription" :image="$event->image ?: null" :schema="$schema">
    <x-page-header :eyebrow="$event->category" crumb="Événements" :subtitle="$event->excerpt">
        {{ $event->title }}
    </x-page-header>

    <section class="bg-white py-16 sm:py-24">
        <x-ui.container class="grid gap-12 lg:grid-cols-[1fr_340px] lg:gap-16">
            <div class="min-w-0">
                @if ($annule)
                    <div class="mb-8 rounded-2xl border border-accent/25 bg-accent/5 p-5 text-accent">
                        <p class="font-bold">Cet événement est annulé.</p>
                        @if ($event->motif_annulation) <p class="mt-1 text-sm">{{ $event->motif_annulation }}</p> @endif
                    </div>
                @endif

                @if ($event->image)
                    <img src="{{ $event->image }}" alt="Visuel — {{ $event->title }}" class="mb-8 max-h-[420px] w-full rounded-3xl object-cover">
                @endif

                @if ($event->description)
                    <div class="whitespace-pre-line text-pretty leading-relaxed text-ink/80">{!! \App\Support\Texte::liens($event->description) !!}</div>
                @endif
                @foreach ($event->body ?? [] as $paragraph)
                    <p class="mt-5 text-pretty leading-relaxed text-ink/80">{{ $paragraph }}</p>
                @endforeach
                @if (! $event->description && empty($event->body))
                    <p class="text-ink/60">Le programme détaillé sera bientôt disponible.</p>
                @endif
            </div>

            <aside class="h-fit rounded-3xl border border-brand/10 bg-cloud p-7 lg:sticky lg:top-28">
                <div class="flex items-center gap-4">
                    <div class="flex w-16 shrink-0 flex-col items-center justify-center rounded-2xl py-3 text-white {{ $annule || $event->passe ? 'bg-ink/40' : 'bg-brand' }}">
                        <span class="font-display text-2xl leading-none">{{ $d->format('d') }}</span>
                        <span class="mt-1 text-[0.65rem] uppercase tracking-wider text-white/70">{{ $d->translatedFormat('M') }}</span>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $event->category }}</span>
                </div>
                <ul class="mt-6 space-y-3 text-sm text-ink/70">
                    <li class="flex items-start gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 size-4 shrink-0 text-accent"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                        {{ ucfirst($d->isoFormat('dddd D MMMM YYYY')) }}
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 size-4 shrink-0 text-accent"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        {{ $horaire }}
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 size-4 shrink-0 text-accent"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        @if ($event->en_ligne)
                            En ligne — le lien de connexion est envoyé aux inscrits
                        @elseif ($event->location)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($event->location) }}" target="_blank" rel="noopener" class="hover:text-brand hover:underline">{{ $event->location }}</a>
                        @else
                            Lieu communiqué prochainement
                        @endif
                    </li>
                    @if ($event->capacity && ! $event->passe && ! $annule)
                        <li class="flex items-start gap-2.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 size-4 shrink-0 text-accent"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            {{ $event->complet ? 'Complet' : $event->places_restantes.' place'.($event->places_restantes > 1 ? 's' : '').' restante'.($event->places_restantes > 1 ? 's' : '') }}
                        </li>
                    @endif
                </ul>

                <div class="mt-7 flex flex-col gap-2.5">
                    @if ($annule)
                        <a href="/evenements" class="text-center text-sm font-semibold text-brand hover:underline">Voir les autres événements</a>
                    @elseif ($event->passe)
                        <p class="text-sm font-semibold text-ink/60">Cet événement a eu lieu.</p>
                        <a href="/evenements" class="text-sm font-semibold text-brand hover:underline">Voir les prochains événements →</a>
                    @elseif ($membreConnecte)
                        <x-ui.button :href="$lienMembre" variant="primary" :with-arrow="true">{{ $event->inscriptions_membres ? "Je m'inscris" : "Voir dans mon espace" }}</x-ui.button>
                        <p class="text-xs text-ink/55">Votre billet et le rappel la veille sont dans votre espace membre.</p>
                    @elseif ($event->inscription_visiteur)
                        <x-ui.button :href="url('/participer/'.$event->slug)" variant="primary" :with-arrow="true">S'inscrire</x-ui.button>
                        <p class="text-xs text-ink/55">Ouvert à tous : un billet avec QR code vous est remis après l'inscription.</p>
                        <a href="{{ $lienMembre }}" class="text-xs font-semibold text-brand hover:underline">Déjà membre ? Connectez-vous pour vous inscrire</a>
                    @else
                        <x-ui.button :href="url($cta['href'])" variant="primary" :with-arrow="true">Adhérer pour participer</x-ui.button>
                        <p class="text-xs text-ink/55">{{ $event->reserve_abonnes ? 'Événement réservé aux membres à jour de leur abonnement.' : 'Événement réservé aux membres du réseau.' }}</p>
                        <a href="{{ $lienMembre }}" class="text-xs font-semibold text-brand hover:underline">Déjà membre ? Connectez-vous pour vous inscrire</a>
                    @endif
                </div>
            </aside>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
