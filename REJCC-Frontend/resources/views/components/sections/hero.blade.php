@php
    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
    $floatingCards = [
        ['icon' => 'network', 'label' => 'Networking', 'sub' => 'Connexions ciblées', 'x' => '-12%', 'y' => '8%'],
        ['icon' => 'graduation-cap', 'label' => 'Mentorat', 'sub' => 'Experts confirmés', 'x' => '76%', 'y' => '-4%'],
        ['icon' => 'rocket', 'label' => 'Accélération', 'sub' => 'Projets à impact', 'x' => '80%', 'y' => '70%'],
    ];
@endphp

<section class="relative flex min-h-[100svh] items-center overflow-hidden bg-brand pb-20 pt-32 lg:pt-28">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-grid opacity-50 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
        <div class="absolute -left-[10%] top-[-10%] size-[55vmax] animate-aurora rounded-full bg-azure/25 blur-[120px]"></div>
        <div class="absolute -right-[10%] bottom-[-15%] size-[50vmax] animate-aurora rounded-full bg-accent/20 blur-[120px]" style="animation-delay: -8s"></div>
        <div class="absolute inset-0 bg-linear-to-b from-brand-900/40 via-transparent to-brand"></div>
    </div>

    <div class="relative mx-auto grid w-full max-w-7xl items-center gap-16 container-px lg:grid-cols-[1.05fr_0.95fr]">
        <div>
            <x-ui.reveal>
                <x-ui.eyebrow tone="dark">Réseau entrepreneurial catholique · Côte d'Ivoire</x-ui.eyebrow>
            </x-ui.reveal>

            <h1 class="mt-6 font-display uppercase leading-[0.9] tracking-[-0.01em] text-white text-[clamp(2.75rem,8vw,5.75rem)]">
                <x-ui.reveal :delay="0.08" class="block">Ensemble</x-ui.reveal>
                <x-ui.reveal :delay="0.16" class="block">
                    pour <span class="font-serif italic normal-case text-azure">l'excellence</span>
                </x-ui.reveal>
            </h1>

            <x-ui.reveal :delay="0.28">
                <p class="mt-7 max-w-xl text-pretty text-lg leading-relaxed text-white/75">
                    Le réseau de référence des jeunes entrepreneurs et porteurs de projets catholiques. Collaborer, innover et bâtir des entreprises à impact durable — au service de l'Église et de la société.
                </p>
            </x-ui.reveal>

            <x-ui.reveal :delay="0.38" class="mt-9 flex flex-wrap items-center gap-3">
                <x-ui.button href="{{ url($cta['href']) }}" size="lg" variant="primary" :with-arrow="true">Rejoindre le réseau</x-ui.button>
                <x-ui.button href="/a-propos" size="lg" variant="ghost" class="text-white hover:bg-white/10">Découvrir le REJCC</x-ui.button>
            </x-ui.reveal>

            <x-ui.reveal :delay="0.5" class="mt-10 flex items-center gap-5 text-sm text-white/60">
                <div class="flex -space-x-2">
                    @foreach (['JK', 'AB', 'GA', 'MT'] as $initials)
                        <span class="inline-flex size-9 items-center justify-center rounded-full border-2 border-brand bg-azure/30 text-[0.7rem] font-bold text-white">{{ $initials }}</span>
                    @endforeach
                </div>
                <span><span class="font-semibold text-white">350+ membres</span> déjà engagés dans 33 domaines.</span>
            </x-ui.reveal>
        </div>

        <div class="relative mx-auto hidden aspect-square w-full max-w-md items-center justify-center lg:flex">
            <div aria-hidden class="absolute inset-0 rounded-full border border-white/10" style="animation: spin 60s linear infinite">
                <span class="absolute left-1/2 top-0 size-2 -translate-x-1/2 rounded-full bg-accent"></span>
            </div>
            <div aria-hidden class="absolute inset-[12%] rounded-full border border-dashed border-white/15" style="animation: spin 45s linear infinite reverse"></div>
            <div class="absolute inset-[22%] rounded-full bg-white/[0.03] backdrop-blur-sm"></div>
            <div class="absolute inset-0 rounded-full bg-azure/20 blur-3xl"></div>

            <x-ui.reveal direction="none" :delay="0.3" class="relative">
                <x-ui.logo-mark kind="mono-white" class="h-44 drop-shadow-[0_20px_40px_rgba(0,0,0,0.3)]" />
            </x-ui.reveal>

            @foreach ($floatingCards as $i => $card)
                <x-ui.reveal direction="none" :delay="0.6 + $i * 0.4" class="absolute" style="left: {{ $card['x'] }}; top: {{ $card['y'] }}">
                    <div class="glass-dark flex animate-float items-center gap-3 rounded-2xl px-4 py-3 shadow-xl" style="animation-delay: -{{ $i }}s">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-white/10 text-white">
                            <x-ui.icon :name="$card['icon']" class="size-4.5" />
                        </span>
                        <div class="leading-tight">
                            <p class="text-sm font-semibold text-white">{{ $card['label'] }}</p>
                            <p class="text-xs text-white/60">{{ $card['sub'] }}</p>
                        </div>
                    </div>
                </x-ui.reveal>
            @endforeach
        </div>
    </div>

    <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 animate-float flex-col items-center gap-2 text-white/50">
        <span class="text-[0.7rem] uppercase tracking-[0.2em]">Explorer</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
    </div>
</section>
