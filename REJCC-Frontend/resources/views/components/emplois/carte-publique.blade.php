@props(['o'])
@php $c = $o['groupe']['couleur'] ?? '#031D59'; @endphp
<a href="/emplois/{{ $o['id'] }}" wire:navigate class="group flex h-full items-start gap-4 rounded-3xl border border-brand/10 bg-white p-6 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]">
    <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl text-white" style="background: linear-gradient(135deg, {{ $c }}, #031D59)"><x-ui.icon :name="$o['groupe']['icone'] ?? 'nav-briefcase'" class="size-5" /></span>
    <div class="min-w-0 flex-1">
        <span class="inline-flex rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $o['type_label'] }}{{ $o['contrat_label'] ? ' · '.$o['contrat_label'] : '' }}</span>
        <h3 class="mt-2 text-lg font-bold text-brand">{{ $o['title'] }}</h3>
        <p class="mt-1 text-sm text-ink/65">{{ $o['entreprise'] }} · {{ $o['lieu'] }}{{ ($o['teletravail'] ?? 'sur_site') !== 'sur_site' ? ' · '.$o['teletravail_label'] : '' }}</p>
        @if ($o['deadline'])<p class="mt-2 text-xs font-semibold text-accent">Candidature avant le {{ \Illuminate\Support\Carbon::parse($o['deadline'])->locale('fr')->isoFormat('D MMMM') }}</p>@endif
    </div>
</a>
