@php
    $steps = \App\Models\MembershipStep::orderBy('ordre')->get();
    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
@endphp

<section class="bg-cloud py-24 sm:py-32">
    <x-ui.container>
        <x-ui.section-heading
            eyebrow="Comment adhérer"
            title="Rejoignez-nous en 4 étapes"
            subtitle="Un parcours simple et 100 % en ligne pour intégrer le réseau et accéder à tous ses avantages."
        />

        <div class="relative mt-16">
            <div class="absolute left-0 right-0 top-9 hidden h-px bg-linear-to-r from-transparent via-brand/20 to-transparent lg:block"></div>

            <ol class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                @foreach ($steps as $i => $s)
                    <x-ui.reveal :delay="$i * 0.1" class="h-full">
                        <li class="relative flex h-full flex-col">
                            <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                                <span class="relative z-10 inline-flex size-18 shrink-0 items-center justify-center rounded-2xl bg-white text-brand shadow-[0_18px_40px_-20px_rgba(3,29,89,0.5)] ring-1 ring-brand/10">
                                    <x-ui.icon :name="$s->icon" class="size-7" />
                                    <span class="absolute -right-2 -top-2 inline-flex size-7 items-center justify-center rounded-full bg-accent text-xs font-bold text-white">{{ $i + 1 }}</span>
                                </span>
                            </div>
                            <h3 class="mt-6 text-lg font-bold text-brand">{{ $s->title }}</h3>
                            <p class="mt-2 text-pretty text-sm leading-relaxed text-ink/70">{{ $s->text }}</p>
                        </li>
                    </x-ui.reveal>
                @endforeach
            </ol>
        </div>

        <div class="mt-14 flex justify-center">
            <x-ui.button :href="url($cta['href'])" size="lg" variant="primary" :with-arrow="true">Commencer mon adhésion</x-ui.button>
        </div>
    </x-ui.container>
</section>
