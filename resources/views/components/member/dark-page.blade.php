@props(['title', 'subtitle' => null, 'icon' => null])

<div class="px-8 pb-[60px] pt-8">
    <a href="{{ route('espace-membre.dashboard') }}" wire:navigate class="mb-[22px] inline-flex items-center gap-1.5 text-[12.5px] text-[var(--ms-dim)] transition-colors hover:text-[var(--ms-muted)]">
        <x-ui.icon name="arrow-left" class="size-[13px]" /> Tableau de bord
    </a>

    <div class="mb-8 flex items-start gap-3.5 border-b border-[var(--ms-bc)] pb-6">
        @if ($icon)
            <span class="mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-xl border border-[var(--ms-bc)] bg-[var(--ms-surf)] text-[var(--ms-muted)]">
                <x-ui.icon :name="$icon" class="size-5" />
            </span>
        @endif
        <div>
            <h1 class="m-0 font-serif text-[28px] italic leading-tight text-[var(--ms-text)]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="m-0 mt-1.5 text-sm text-[var(--ms-muted)]">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{ $slot }}
</div>
