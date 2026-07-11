@php
    $sectors = collect(\App\Support\Api::get('/sectors')['sectors'] ?? [])->map(fn ($s) => (object) $s);
    $total = $sectors->sum(fn ($s) => count($s->items));
@endphp

<section class="relative overflow-hidden bg-brand py-24 sm:py-32">
    <div class="pointer-events-none absolute inset-0 bg-grid opacity-[0.25] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]"></div>
    <div class="pointer-events-none absolute -right-[5%] top-1/4 size-[40vmax] rounded-full bg-azure/15 blur-[130px]"></div>

    <x-ui.container class="relative">
        <div class="flex flex-col items-end justify-between gap-8 md:flex-row">
            <x-ui.section-heading align="left" tone="dark" eyebrow="Nos domaines d'activité" subtitle="De l'agriculture à l'intelligence artificielle, le REJCC fédère les talents de tous les secteurs de l'économie ivoirienne." class="max-w-2xl">
                <x-slot:title>{{ $total }} domaines,<br />un seul réseau</x-slot:title>
            </x-ui.section-heading>
            <x-ui.button href="/domaines" variant="white" :with-arrow="true" class="shrink-0">Explorer les domaines</x-ui.button>
        </div>

        <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($sectors as $i => $s)
                <x-ui.reveal :delay="($i % 3) * 0.07">
                    <a href="/domaines" wire:navigate class="group relative block h-full overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-7 transition-all duration-500 hover:border-white/25 hover:bg-white/[0.08]">
                        <div class="flex items-start justify-between">
                            <span class="inline-flex size-13 items-center justify-center rounded-2xl bg-white/10 text-white transition-all duration-500 group-hover:bg-accent">
                                <x-ui.icon :name="$s->icon" class="size-6" />
                            </span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 text-white/30 transition-all duration-500 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-white"><path d="M7 17 17 7M7 7h10v10"/></svg>
                        </div>

                        <h3 class="mt-5 text-lg font-bold text-white">{{ $s->title }}</h3>
                        <p class="mt-1.5 text-sm text-white/55">{{ $s->blurb }}</p>

                        <div class="grid grid-rows-[0fr] opacity-0 transition-all duration-500 group-hover:grid-rows-[1fr] group-hover:opacity-100">
                            <div class="overflow-hidden">
                                <div class="mt-4 flex flex-wrap gap-1.5 pt-3">
                                    @foreach ($s->items as $item)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-xs text-white/75">{{ $item }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <span class="mt-4 inline-block text-xs font-semibold uppercase tracking-[0.14em] text-white/40">{{ count($s->items) }} domaines</span>
                    </a>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
