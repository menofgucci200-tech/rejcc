@php
    $site = \App\Support\Content\SiteConfig::get();
    $nav = \App\Support\Content\SiteConfig::nav();
    $socials = \App\Support\Content\SiteConfig::socials();
    $legal = \App\Support\Content\LegalPages::all();
    $exploreLinks = [
        ['label' => 'Adhérer', 'href' => '/adhesion'],
        ['label' => 'Devenir partenaire', 'href' => '/partenaires'],
        ['label' => 'Nos domaines', 'href' => '/domaines'],
        ['label' => 'Espace membre', 'href' => '/connexion'],
        ['label' => 'Vérifier un certificat', 'href' => '/verifier'],
    ];
@endphp

<footer class="relative overflow-hidden bg-brand text-white">
    <div class="pointer-events-none absolute inset-0 bg-dots opacity-[0.06]"></div>
    <div class="pointer-events-none absolute -left-20 top-0 size-[40vmin] rounded-full bg-azure/20 blur-[100px]"></div>

    <div class="relative mx-auto w-full max-w-7xl container-px">
        <div class="grid gap-12 border-b border-white/10 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
            <div>
                <x-ui.logo-mark kind="lockup-white" class="h-16" />
                <p data-test="nom-officiel" class="mt-4 max-w-xs text-[13px] font-semibold uppercase leading-snug tracking-[0.08em] text-white/85">{{ $site['fullName'] }}</p>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/65">{{ $site['about'] }}</p>
                <div class="mt-6 flex gap-2.5">
                    @foreach ($socials as $s)
                        <a href="{{ $s['href'] }}" aria-label="{{ $s['label'] }}"
                           class="inline-flex size-10 items-center justify-center rounded-full border border-white/15 text-white/80 transition-all hover:border-white/40 hover:bg-white/10 hover:text-white">
                            <x-ui.social-icon :type="$s['icon']" class="size-4.5" />
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">Navigation</h3>
                <ul class="mt-5 space-y-3">
                    @foreach ($nav as $item)
                        <li><a href="{{ url($item['href']) }}" wire:navigate class="text-sm text-white/70 transition-colors hover:text-white">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">Explorer</h3>
                <ul class="mt-5 space-y-3">
                    @foreach ($exploreLinks as $item)
                        <li><a href="{{ url($item['href']) }}" wire:navigate class="text-sm text-white/70 transition-colors hover:text-white">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
                <ul class="mt-7 space-y-3 text-sm text-white/70">
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-azure"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $site['contact']['address'] }}
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-azure"><path d="m4 6 8 6 8-6"/><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                        {{ $site['contact']['email'] }}
                    </li>
                    @if ($site['contact']['phone'])
                    <li class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-azure"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                        {{ $site['contact']['phone'] }}
                    </li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">Restez informé</h3>
                <p class="mt-5 text-sm text-white/65">Recevez les actualités, événements et opportunités du réseau.</p>
                <livewire:newsletter-form />
            </div>
        </div>

        {{-- Bas de page : copyright + informations légales. Marge à droite pour le
             bouton vidéo flottant, qui ne doit pas recouvrir les liens. --}}
        <div class="flex flex-col gap-5 py-7 text-sm text-white/55 lg:flex-row lg:items-center lg:justify-between lg:pr-28">
            <p>
                © {{ now()->year }} {{ $site['name'] }} —
                <span class="font-serif italic text-white/75">{{ $site['slogan'] }}</span>
            </p>
            <nav aria-label="Informations légales" data-test="liens-legaux" class="flex flex-wrap gap-x-5 gap-y-2 pr-24 lg:pr-0">
                @foreach ($legal as $l)
                    <a href="{{ $l['url'] }}" wire:navigate class="transition-colors hover:text-white">{{ $l['court'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
