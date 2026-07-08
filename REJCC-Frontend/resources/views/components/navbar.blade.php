@php
    $nav = \App\Support\Content\SiteConfig::nav();
    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
    $isHome = request()->is('/');
@endphp

<div x-data="{ open: false, scrolled: window.scrollY > 24 }"
     x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 24, { passive: true })"
     @keydown.escape.window="open = false">

    <header
        :class="(scrolled || {{ $isHome ? 'false' : 'true' }} || open) ? 'glass border-b border-brand/10 py-2.5 shadow-[0_8px_30px_-18px_rgba(3,29,89,0.35)]' : 'border-b border-transparent py-4'"
        class="fixed inset-x-0 top-0 z-[80] transition-all duration-500"
    >
        <nav class="mx-auto flex w-full max-w-7xl items-center justify-between container-px">
            <a href="{{ url('/') }}" aria-label="REJCC — Accueil" class="relative z-10 flex items-center">
                <template x-if="scrolled || {{ $isHome ? 'false' : 'true' }} || open">
                    <x-ui.logo-mark kind="lockup-color" class="h-9 sm:h-10" />
                </template>
                <template x-if="!(scrolled || {{ $isHome ? 'false' : 'true' }} || open)">
                    <x-ui.logo-mark kind="lockup-white" class="h-9 sm:h-10" />
                </template>
            </a>

            <ul class="hidden items-center gap-1 lg:flex">
                @foreach ($nav as $item)
                    @php $active = $item['href'] === '/' ? request()->is('/') : request()->is(ltrim($item['href'], '/').'*'); @endphp
                    <li>
                        <a href="{{ url($item['href']) }}"
                           :class="(scrolled || {{ $isHome ? 'false' : 'true' }} || open) ? '{{ $active ? 'text-brand' : 'text-ink/70 hover:text-brand' }}' : '{{ $active ? 'text-white' : 'text-white/80 hover:text-white' }}'"
                           class="relative rounded-full px-3.5 py-2 text-sm font-semibold transition-colors">
                            {{ $item['label'] }}
                            @if ($active)
                                <span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-accent"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="flex items-center gap-2">
                <x-ui.button :href="url($cta['href'])" size="sm" :with-arrow="true"
                    x-bind:class="(scrolled || {{ $isHome ? 'false' : 'true' }} || open) ? '' : '!bg-white !text-brand hover:!bg-cloud'"
                    class="hidden sm:inline-flex">
                    {{ $cta['label'] }}
                </x-ui.button>

                <button
                    @click="open = !open"
                    :aria-label="open ? 'Fermer le menu' : 'Ouvrir le menu'"
                    :aria-expanded="open"
                    :class="(scrolled || {{ $isHome ? 'false' : 'true' }} || open) ? 'border-brand/15 text-brand' : 'border-white/25 text-white'"
                    class="relative z-10 inline-flex size-11 items-center justify-center rounded-full border transition-colors lg:hidden"
                >
                    <svg x-show="!open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg x-show="open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </nav>
    </header>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex flex-col bg-brand lg:hidden"
        style="display: none;"
    >
        <div class="mt-24 flex flex-1 flex-col gap-1 container-px">
            @foreach ($nav as $item)
                <a href="{{ url($item['href']) }}" @click="open = false" class="block border-b border-white/10 py-4 font-display text-3xl uppercase tracking-tight text-white">
                    {{ $item['label'] }}
                </a>
            @endforeach
            <div class="mt-8">
                <x-ui.button :href="url($cta['href'])" variant="white" size="lg" :with-arrow="true" class="w-full">
                    {{ $cta['label'] }}
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
