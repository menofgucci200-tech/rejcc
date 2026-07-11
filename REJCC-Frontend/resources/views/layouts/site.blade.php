<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $site = \App\Support\Content\SiteConfig::get();
            $seoTitle = ($title ?? null) ? $title.' · '.$site['name'] : $site['name'].' — '.$site['fullName'];
            $seoDescription = $description ?? "Le REJCC accompagne les jeunes entrepreneurs catholiques de Côte d'Ivoire : formations en entrepreneuriat, mentorat, réseautage, incubateur de projets et opportunités professionnelles à Abidjan et dans tous les diocèses.";
            $seoImage = $image ?? asset('brand/rejcc-logo-color.png');
        @endphp

        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:site_name" content="{{ $site['name'] }}">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:type" content="{{ $type ?? 'website' }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">

        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site['name'],
            'legalName' => $site['fullName'],
            'description' => $site['positioning'],
            'url' => url('/'),
            'logo' => asset('brand/rejcc-logo-color.png'),
            'email' => $site['contact']['email'],
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $site['contact']['city'],
                'addressCountry' => 'CI',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white antialiased" data-smooth-scroll>
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
