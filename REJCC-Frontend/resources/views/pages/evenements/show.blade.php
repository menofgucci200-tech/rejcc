@php $cta = \App\Support\Content\SiteConfig::ctaPrimary(); @endphp

<x-site-layout :title="$event->title">
    <x-page-header :eyebrow="$event->category" crumb="Événements" :subtitle="$event->excerpt">
        {{ $event->title }}
    </x-page-header>

    <section class="bg-white py-20 sm:py-28">
        <x-ui.container class="grid gap-12 lg:grid-cols-[1fr_320px] lg:gap-16">
            <div class="prose max-w-none">
                @foreach ($event->body ?? [] as $paragraph)
                    <p class="mb-5 text-pretty leading-relaxed text-ink/80">{{ $paragraph }}</p>
                @endforeach

                <div class="mt-10">
                    <x-ui.button :href="url($cta['href'])" variant="primary" :with-arrow="true">Adhérer pour participer</x-ui.button>
                </div>
            </div>

            <aside class="h-fit rounded-3xl border border-brand/10 bg-cloud p-7">
                <div class="flex items-center gap-4">
                    <div class="flex w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand py-3 text-white">
                        <span class="font-display text-2xl leading-none">{{ $event->starts_at->format('d') }}</span>
                        <span class="mt-1 text-[0.65rem] uppercase tracking-wider text-white/70">{{ $event->starts_at->locale('fr')->translatedFormat('M') }}</span>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $event->category }}</span>
                </div>
                <ul class="mt-6 space-y-3 text-sm text-ink/70">
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-accent"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        {{ $event->time_label ?? $event->starts_at->format('H:i') }}
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-accent"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $event->location }}
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-accent"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                        {{ $event->starts_at->format('Y') }}
                    </li>
                </ul>
            </aside>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
