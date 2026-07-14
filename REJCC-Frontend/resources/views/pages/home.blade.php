@php use App\Support\Content\SiteRemote; @endphp

<x-site-layout>
    <x-sections.hero />
    @if (SiteRemote::visible('home', 'about')) <x-sections.about /> @endif
    @if (SiteRemote::visible('home', 'video')) <x-sections.video-embed /> @endif
    @if (SiteRemote::visible('home', 'stats')) <x-sections.stats /> @endif
    @if (SiteRemote::visible('home', 'why-join')) <x-sections.why-join /> @endif
    @if (SiteRemote::visible('home', 'values')) <x-sections.values /> @endif
    @if (SiteRemote::visible('home', 'domains-preview')) <x-sections.domains-preview /> @endif
    @if (SiteRemote::visible('home', 'how-to-join')) <x-sections.how-to-join /> @endif
    @if (SiteRemote::visible('home', 'testimonials')) <x-sections.testimonials /> @endif
    @if (SiteRemote::visible('home', 'gallery')) <x-sections.gallery /> @endif
    @if (SiteRemote::visible('home', 'events')) <x-sections.events /> @endif
    @if (SiteRemote::visible('home', 'news')) <x-sections.news /> @endif
    @if (SiteRemote::visible('home', 'cta-band')) <x-sections.cta-band /> @endif
</x-site-layout>
