@props([
    'eyebrow' => null,
    'crumb',
    'subtitle' => null,
])

<header class="relative overflow-hidden bg-brand pb-16 pt-36 sm:pb-20 sm:pt-44">
    <div class="pointer-events-none absolute inset-0 bg-grid opacity-[0.25] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]"></div>
    <div class="pointer-events-none absolute -right-[8%] -top-[10%] size-[40vmax] rounded-full bg-azure/20 blur-[120px]"></div>
    <div class="pointer-events-none absolute -left-[8%] bottom-[-20%] size-[30vmax] rounded-full bg-accent/15 blur-[120px]"></div>

    <x-ui.container class="relative">
        <nav class="flex items-center gap-1.5 text-sm text-white/55">
            <a href="{{ url('/') }}" class="transition-colors hover:text-white">Accueil</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><path d="m9 18 6-6-6-6"/></svg>
            <span class="text-white/80">{{ $crumb }}</span>
        </nav>

        <div class="mt-7 max-w-3xl">
            @if ($eyebrow)
                <x-ui.eyebrow tone="dark">{{ $eyebrow }}</x-ui.eyebrow>
            @endif
            <h1 class="mt-5 font-display uppercase leading-[0.92] tracking-[-0.01em] text-white text-[clamp(2.5rem,7vw,5rem)]">
                {{ $slot }}
            </h1>
            @if ($subtitle)
                <p class="mt-6 max-w-2xl text-pretty text-lg leading-relaxed text-white/75">{{ $subtitle }}</p>
            @endif
        </div>
    </x-ui.container>
</header>
