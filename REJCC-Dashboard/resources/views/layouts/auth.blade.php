<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        @include('partials.favicon')
        <title>REJCC · Dashboard</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-cloud font-sans text-ink antialiased">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-12">
            <div aria-hidden="true" class="pointer-events-none absolute -left-40 -top-40 size-[520px] rounded-full bg-azure/15 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-48 -right-32 size-[520px] rounded-full bg-accent/10 blur-3xl"></div>
            <main class="relative z-10 w-full max-w-md">
                {{ $slot }}
            </main>
        </div>
        @livewireScripts
    </body>
</html>
