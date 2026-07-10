<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>REJCC · Espace membre</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-cloud font-sans text-ink antialiased">
        <div x-data="{ mobileOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Sidebar (desktop) -->
            <div class="hidden lg:block lg:shrink-0">
                <x-member-light.sidebar />
            </div>

            <!-- Sidebar (mobile overlay) : tiroir depuis la droite, au-dessus de la
                 barre d'onglets du bas (z-70) pour que « Paramètres » reste visible. -->
            <div x-show="mobileOpen" style="display: none;" class="fixed inset-0 z-[80] lg:hidden">
                <div x-show="mobileOpen" x-transition.opacity class="absolute inset-0 bg-brand/45" @click="mobileOpen = false"></div>
                <div
                    x-show="mobileOpen"
                    x-transition:enter="transition-transform duration-300 ease-out"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition-transform duration-200 ease-in"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="absolute inset-y-0 right-0 z-10 w-[82%] max-w-[320px] shadow-[-8px_0_30px_rgba(3,29,89,.3)]"
                >
                    <x-member-light.sidebar class="!w-full" :with-close="true" />
                </div>
            </div>

            <!-- Cette colonne defile en interne (overflow-y-auto), independamment du
                 scroll de la fenetre — Lenis (smooth-scroll global du site) intercepte
                 le scroll de window et ne recalcule pas ses mesures apres un
                 wire:navigate, ce qui bloquait le defilement si on laissait la page
                 defiler au niveau du document. -->
            <div class="flex min-w-0 flex-1 flex-col overflow-y-auto" data-lenis-prevent>
                <main class="flex-1 pb-20 lg:pb-0">
                    {{ $slot }}
                </main>

                <x-member-light.footer />
            </div>

            <x-member-light.mobile-nav />
        </div>

        @livewireScripts
    </body>
</html>
