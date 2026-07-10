<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Adhésion · REJCC — Rejoindre le réseau des jeunes entrepreneurs catholiques</title>
        <meta name="description" content="Rejoignez le REJCC : adhésion en ligne au réseau des jeunes entrepreneurs et porteurs de projets catholiques de Côte d'Ivoire. Formations, mentorat, réseautage.">
        <link rel="canonical" href="{{ url()->current() }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-cloud font-sans text-ink antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
