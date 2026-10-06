@props(['p'])
@php $c = $p['groupe']['couleur'] ?? '#031D59'; @endphp
<a href="/projets/{{ $p['id'] }}" wire:navigate class="group flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-white transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]">
    @if ($p['image'])
        <img src="{{ $p['image'] }}" alt="" loading="lazy" class="h-44 w-full object-cover">
    @else
        <div class="flex h-32 items-center justify-center" style="background: linear-gradient(135deg, {{ $c }}, #031D59)"><x-ui.icon :name="$p['groupe']['icone'] ?? 'nav-projects'" class="size-10 text-white/70" /></div>
    @endif
    <div class="flex flex-1 flex-col p-6">
        <div class="flex flex-wrap items-center gap-2">
            @if ($p['a_la_une'])<span class="rounded-full bg-accent px-2.5 py-0.5 text-xs font-semibold text-white">À la une</span>@endif
            <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $p['stade'] }}</span>
            @if ($p['groupe'])<span class="text-xs font-semibold" style="color: {{ $c }}">{{ $p['groupe']['nom'] }}</span>@endif
        </div>
        <h3 class="mt-3 text-lg font-bold text-brand">{{ $p['title'] }}</h3>
        <p class="mt-1.5 line-clamp-3 text-sm text-ink/65">{{ $p['accroche'] ?: $p['description'] }}</p>
        <p class="mt-auto pt-4 text-xs text-ink/55">Porté par {{ $p['porteur'] }}{{ $p['ville'] ? ' · '.$p['ville'] : '' }}{{ $p['equipe_taille'] > 1 ? ' · équipe de '.$p['equipe_taille'] : '' }}</p>
    </div>
</a>
