@php
    use App\Support\Content\SiteRemote;

    $site = \App\Support\Content\SiteConfig::get();
    $infos = [
        ['icon' => 'map-pin', 'label' => 'Adresse', 'value' => $site['contact']['address']],
        ['icon' => 'mail', 'label' => 'E-mail', 'value' => $site['contact']['email']],
        ['icon' => 'phone', 'label' => 'Téléphone', 'value' => $site['contact']['phone']],
    ];
    $socials = \App\Support\Content\SiteConfig::socials();
    $headerTitle = SiteRemote::field('contact', 'header', 'title');
    $headerSubtitle = SiteRemote::field('contact', 'header', 'subtitle', "Une question, un projet, une envie de collaborer ? L'équipe du REJCC vous répond.");
@endphp

<x-site-layout title="Contact" description="Contactez le REJCC — Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire : questions, adhésions, partenariats.">
    <x-page-header eyebrow="Parlons-en" crumb="Contact" :subtitle="$headerSubtitle">
        @if ($headerTitle) {{ $headerTitle }} @else Nous <span class="font-serif italic normal-case text-azure">contacter</span> @endif
    </x-page-header>

    <section class="bg-white py-20 sm:py-28">
        <x-ui.container class="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
            <div>
                <h2 class="font-display text-2xl uppercase tracking-tight text-brand">Coordonnées</h2>
                <ul class="mt-6 space-y-4">
                    @foreach ($infos as $info)
                        <li class="flex items-start gap-4">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand text-white">
                                @if ($info['icon'] === 'map-pin')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                @elseif ($info['icon'] === 'mail')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path d="m4 6 8 6 8-6"/><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">{{ $info['label'] }}</p>
                                <p class="mt-0.5 font-medium text-brand">{{ $info['value'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-8 text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">Suivez-nous</p>
                <div class="mt-3 flex gap-2.5">
                    @foreach ($socials as $s)
                        <a href="{{ $s['href'] }}" aria-label="{{ $s['label'] }}" class="inline-flex size-11 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white">
                            <x-ui.social-icon :type="$s['icon']" class="size-4.5" />
                        </a>
                    @endforeach
                </div>
            </div>

            <livewire:contact-form />
        </x-ui.container>
    </section>
</x-site-layout>
