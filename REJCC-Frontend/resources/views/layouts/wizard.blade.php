<!DOCTYPE html>
<html lang="fr" @if (config('app.debug')) data-debug @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Adhésion · REJCC — Rejoindre le réseau des jeunes entrepreneurs catholiques</title>
        <meta name="description" content="Rejoignez le REJCC : adhésion en ligne au réseau des jeunes entrepreneurs et porteurs de projets catholiques de Côte d'Ivoire. Formations, mentorat, réseautage.">
        <link rel="canonical" href="{{ rtrim((string) config('app.url'), '/').'/'.request()->path() }}">
        <meta name="robots" content="index, follow, max-image-preview:large">
        <meta property="og:site_name" content="REJCC">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Adhésion · REJCC — Rejoindre le réseau des jeunes entrepreneurs catholiques">
        <meta property="og:description" content="Rejoignez le REJCC : adhésion en ligne au réseau des jeunes entrepreneurs et porteurs de projets catholiques de Côte d'Ivoire.">
        <meta property="og:url" content="{{ rtrim((string) config('app.url'), '/').'/'.request()->path() }}">
        <meta property="og:image" content="{{ asset('brand/rejcc-partage.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        @include('partials.favicon')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-cloud font-sans text-ink antialiased">
        @include('partials.splash')
        {{ $slot }}

        @livewireScripts
    </body>
</html>
