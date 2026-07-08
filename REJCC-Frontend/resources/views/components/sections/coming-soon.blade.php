@props(['intro', 'features' => []])

<section class="bg-white py-24 sm:py-28">
    <x-ui.container>
        <x-ui.reveal class="mx-auto max-w-3xl">
            <div class="relative overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-8 sm:p-12">
                <div class="pointer-events-none absolute -right-12 -top-12 size-48 rounded-full bg-azure/10 blur-3xl"></div>
                <span class="inline-flex items-center gap-2 rounded-full bg-brand px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><path d="m12 3-1.9 4.6-4.6 1.9 4.6 1.9L12 16l1.9-4.6 4.6-1.9-4.6-1.9L12 3Z"/></svg>
                    Module en cours de déploiement
                </span>
                <p class="mt-6 text-pretty text-lg leading-relaxed text-ink/75">{{ $intro }}</p>

                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach ($features as $feature)
                        <li class="flex items-start gap-3 text-ink/80">
                            <span class="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-3.5"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-10 flex flex-wrap gap-3">
                    <x-ui.button href="/adhesion" variant="primary" :with-arrow="true">Adhérer au réseau</x-ui.button>
                    <x-ui.button href="/contact" variant="outline">Nous contacter</x-ui.button>
                </div>
            </div>
        </x-ui.reveal>
    </x-ui.container>
</section>
