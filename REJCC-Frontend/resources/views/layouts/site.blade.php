<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ ($title ?? null) ? $title.' · '.\App\Support\Content\SiteConfig::get()['name'] : \App\Support\Content\SiteConfig::get()['name'].' — '.\App\Support\Content\SiteConfig::get()['fullName'] }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div id="page-loader" class="fixed inset-0 z-[200] flex items-center justify-center bg-brand transition-opacity duration-500">
            <x-ui.logo-mark kind="mono-white" class="h-14 animate-pulse" />
        </div>

        <div id="scroll-progress-bar" class="fixed inset-x-0 top-0 z-[90] h-[3px] bg-accent" style="width: 0%"></div>

        <x-navbar />

        <main>
            {{ $slot }}
        </main>

        <x-footer />

        @livewireScripts
    </body>
</html>
