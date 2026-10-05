@props(['variant' => 'membre'])

{{-- Pied de page compact des espaces connectés (membre et administration) :
     nom officiel, liens légaux, aide ; réseaux sociaux côté membre seulement. --}}
@php
    $site = \App\Support\Content\SiteConfig::get();
    $legal = collect(\App\Support\Content\LegalPages::all())->keyBy('slug');
    $liens = collect(['cgu', 'politique-de-confidentialite', 'mentions-legales', 'charte-du-membre'])
        ->map(fn ($s) => $legal->get($s))->filter();
    $socials = $variant === 'membre' ? \App\Support\Content\SiteConfig::socials() : [];
@endphp

<footer data-test="pied-app" class="mt-10 border-t border-brand/10 bg-white {{ $variant === 'membre' ? 'pb-20 lg:pb-0' : '' }}">
    <div class="mx-auto flex max-w-[1280px] flex-col gap-4 px-5 py-6 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="" class="size-8 shrink-0 object-contain">
            <div class="min-w-0">
                <p class="text-[12.5px] font-bold text-brand">© {{ now()->year }} REJCC · {{ $site['fullName'] }}</p>
                <p class="font-serif text-[12px] italic text-[#5B677A]">{{ $site['slogan'] }}</p>
            </div>
        </div>

        <nav aria-label="Informations légales" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12.5px] font-semibold text-[#5B677A]">
            @foreach ($liens as $l)
                <a href="{{ $l['url'] }}" class="transition-colors hover:text-accent">{{ $l['court'] }}</a>
            @endforeach
            <a href="{{ url('/contact') }}" class="inline-flex items-center gap-1 transition-colors hover:text-accent"><x-ui.icon name="message-circle" class="size-3.5" /> Aide &amp; contact</a>
            @if ($variant === 'admin')
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 transition-colors hover:text-accent"><x-ui.icon name="external-link" class="size-3.5" /> Voir le site</a>
            @endif
        </nav>

        @if ($socials)
            <div class="flex gap-2">
                @foreach ($socials as $s)
                    <a href="{{ $s['href'] }}" target="_blank" rel="noopener" aria-label="{{ $s['label'] }}" class="flex size-8 items-center justify-center rounded-full border border-brand/10 text-brand transition-colors hover:border-accent hover:bg-accent hover:text-white">
                        <x-ui.social-icon :type="$s['icon']" class="size-3.5" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</footer>
