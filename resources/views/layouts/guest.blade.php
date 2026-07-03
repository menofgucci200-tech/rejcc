<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-cloud px-6 py-10">
            <div>
                <a href="{{ url('/') }}" wire:navigate>
                    <x-ui.logo-mark kind="mono-color" class="h-16" />
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-3xl border border-brand/10 bg-white px-6 py-8 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:max-w-md">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
