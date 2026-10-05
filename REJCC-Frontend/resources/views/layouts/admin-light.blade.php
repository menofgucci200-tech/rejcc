<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.favicon')
        <title>REJCC · Administration</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-cloud font-sans text-ink antialiased">
        @include('partials.splash')
        <div x-data="{ mobileOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Sidebar (desktop) -->
            <div class="hidden lg:block lg:shrink-0">
                <x-admin-light.sidebar />
            </div>

            <!-- Sidebar (mobile) : tiroir depuis la droite, comme dans l'espace membre -->
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
                    <x-admin-light.sidebar class="!w-full" :with-close="true" />
                </div>
            </div>

            <div class="flex min-w-0 flex-1 flex-col overflow-y-auto" data-lenis-prevent>
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <x-member-light.footer variant="admin" />
            </div>
        </div>

        @livewireScripts
    </body>
</html>
