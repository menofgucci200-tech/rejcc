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
        <div x-data="{ mobileOpen: false }" class="flex min-h-screen">
            <!-- Sidebar (desktop) -->
            <div class="fixed inset-y-0 left-0 z-40 hidden lg:block">
                <x-member-light.sidebar />
            </div>

            <!-- Sidebar (mobile overlay) -->
            <div x-show="mobileOpen" style="display: none;" class="fixed inset-0 z-[60] flex lg:hidden">
                <div class="absolute inset-0 bg-black/50" @click="mobileOpen = false"></div>
                <div class="relative z-10 h-full">
                    <x-member-light.sidebar />
                </div>
                <button @click="mobileOpen = false" class="absolute right-4 top-4 flex size-9 items-center justify-center rounded-lg bg-white/10 text-white">
                    <x-ui.icon name="x" class="size-4" />
                </button>
            </div>

            <div class="flex min-w-0 flex-1 flex-col lg:pl-[236px]">
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <x-member-light.footer />
            </div>
        </div>

        @livewireScripts
    </body>
</html>
