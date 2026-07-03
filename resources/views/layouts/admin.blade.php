<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Administration · REJCC</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="antialiased">
        @php
            $user = auth()->user();
            $navItems = [
                ['icon' => 'layout-dashboard', 'label' => 'Tableau de bord', 'route' => 'admin.dashboard'],
                ['icon' => 'users', 'label' => 'Membres', 'route' => 'admin.members'],
                ['icon' => 'file-text', 'label' => 'Adhésions', 'route' => 'admin.adhesions'],
                ['icon' => 'message-circle', 'label' => 'Contacts', 'route' => 'admin.contacts'],
                ['icon' => 'folder-open', 'label' => 'Documents', 'route' => 'admin.documents'],
                ['icon' => 'bell', 'label' => 'Notifications', 'route' => 'admin.notifications'],
            ];
        @endphp

        <div class="flex h-screen overflow-hidden bg-cloud" x-data="{ sidebarOpen: false }">
            <!-- Desktop sidebar -->
            <div class="hidden lg:flex lg:shrink-0">
                <x-admin.sidebar :nav-items="$navItems" :user="$user" />
            </div>

            <!-- Mobile sidebar overlay -->
            <div x-show="sidebarOpen" class="fixed inset-0 z-50 flex lg:hidden" style="display: none;">
                <div class="absolute inset-0 bg-black/30" @click="sidebarOpen = false"></div>
                <div class="relative z-10 h-full" @click="sidebarOpen = false">
                    <x-admin.sidebar :nav-items="$navItems" :user="$user" />
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <header class="flex h-16 items-center gap-4 border-b border-brand/10 bg-white px-4 lg:hidden">
                    <button @click="sidebarOpen = true" aria-label="Menu">
                        <x-ui.icon name="menu" class="size-5 text-brand" />
                    </button>
                    <p class="font-bold text-brand">Administration</p>
                </header>

                <main class="flex-1 overflow-y-auto p-6 sm:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
