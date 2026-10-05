<x-site-layout :title="$page['titre']" :description="$page['resume'] ?: $page['titre'].' — REJCC, Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.'">
    <x-page-header eyebrow="Informations légales" :crumb="$page['titre']" :subtitle="$page['resume']">
        {{ $page['titre'] }}
    </x-page-header>

    <section class="bg-white py-16 sm:py-20">
        <x-ui.container>
            <div class="grid gap-10 lg:grid-cols-[1fr_280px]">
                <article data-test="page-legale" class="min-w-0">
                    @if ($rendu)
                        <p data-test="legal-version" class="mb-8 inline-flex flex-wrap items-center gap-2 rounded-full bg-cloud px-4 py-1.5 text-[12.5px] font-semibold text-[#5B677A]">
                            Version {{ $page['version'] }} · mise à jour le {{ \Carbon\Carbon::parse($page['mise_a_jour'])->translatedFormat('j F Y') }}
                        </p>
                        @if (count($rendu['sommaire']) > 2)
                            <nav aria-label="Sommaire" class="mb-10 rounded-2xl border border-brand/10 bg-cloud/50 p-5 lg:hidden">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#9AA6B8]">Sommaire</p>
                                <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-[14px]">
                                    @foreach ($rendu['sommaire'] as $s)
                                        <li><a href="#{{ $s['id'] }}" class="text-brand hover:text-accent">{{ $s['titre'] }}</a></li>
                                    @endforeach
                                </ol>
                            </nav>
                        @endif
                        <div class="lecon legal-texte max-w-3xl">{!! $rendu['html'] !!}</div>
                    @else
                        <div data-test="legal-en-redaction" class="flex max-w-2xl items-start gap-4 rounded-3xl border border-brand/10 bg-cloud/60 p-7">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white text-accent shadow-sm"><x-ui.icon name="file-text" class="size-6" /></span>
                            <div>
                                <p class="text-[17px] font-bold text-brand">Page en cours de rédaction</p>
                                <p class="mt-1.5 text-[14.5px] leading-relaxed text-[#5B677A]">Ce document sera publié prochainement. Pour toute question en attendant, écrivez-nous depuis la page <a href="{{ url('/contact') }}" wire:navigate class="font-semibold text-azure underline">Contact</a>.</p>
                            </div>
                        </div>
                    @endif
                </article>

                <aside class="flex flex-col gap-6 lg:sticky lg:top-28 lg:self-start">
                    @if ($rendu && count($rendu['sommaire']) > 2)
                        <nav aria-label="Sommaire" class="hidden rounded-2xl border border-brand/10 p-5 lg:block">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#9AA6B8]">Sommaire</p>
                            <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-[13.5px]">
                                @foreach ($rendu['sommaire'] as $s)
                                    <li><a href="#{{ $s['id'] }}" class="text-brand hover:text-accent">{{ $s['titre'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    <nav aria-label="Autres informations légales" class="rounded-2xl bg-brand p-5 text-white">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-white/50">Informations légales</p>
                        <ul class="mt-3 space-y-2 text-[13.5px]">
                            @foreach ($autres as $p)
                                <li><a href="{{ $p['url'] }}" wire:navigate class="text-white/80 transition-colors hover:text-white">{{ $p['titre'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>
            </div>
        </x-ui.container>
    </section>
</x-site-layout>
