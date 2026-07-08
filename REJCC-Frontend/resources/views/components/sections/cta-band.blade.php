@php
    $site = \App\Support\Content\SiteConfig::get();
    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
@endphp

<section class="relative overflow-hidden bg-brand py-24 sm:py-28">
    <div class="pointer-events-none absolute inset-0 bg-grid opacity-[0.2] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
    <div class="pointer-events-none absolute -left-[10%] top-0 size-[40vmax] rounded-full bg-azure/20 blur-[120px]"></div>
    <div class="pointer-events-none absolute -right-[10%] bottom-0 size-[35vmax] rounded-full bg-accent/20 blur-[120px]"></div>

    <x-ui.container class="relative">
        <x-ui.reveal class="mx-auto flex max-w-3xl flex-col items-center text-center">
            <span class="font-serif text-lg italic text-azure">{{ $site['slogan'] }}</span>
            <h2 class="mt-4 font-display uppercase leading-[0.95] tracking-tight text-white text-[clamp(2.25rem,6vw,4.25rem)]">
                Prêt à écrire votre réussite&nbsp;?
            </h2>
            <p class="mt-6 max-w-xl text-pretty text-lg leading-relaxed text-white/75">
                Rejoignez une communauté de jeunes entrepreneurs catholiques déterminés à grandir, collaborer et réussir — ensemble.
            </p>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                <x-ui.button :href="url($cta['href'])" size="lg" variant="primary" :with-arrow="true">Adhérer maintenant</x-ui.button>
                <x-ui.button href="/partenaires" size="lg" variant="ghost" class="text-white hover:bg-white/10">Devenir partenaire</x-ui.button>
            </div>
        </x-ui.reveal>
    </x-ui.container>
</section>
