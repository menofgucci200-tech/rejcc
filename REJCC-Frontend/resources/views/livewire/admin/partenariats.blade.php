@php
    $badge = fn ($s) => match ($s) {
        'accepte' => ['#22A85A', '#EAF6EE', 'Accepté'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusé'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
@endphp
<div>
    <x-admin-light.topbar title="Partenariats" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes de partenariat</h2>
        <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($demandes as $d)
                @php $b = $badge($d->statut); @endphp
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-4 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#E8EDF8] text-sm font-extrabold text-brand">{{ $d->initiales }}</span>
                    <div class="min-w-[220px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $d->organisation }}</p>
                        <p class="mt-0.5 text-xs text-[#5B677A]">{{ $d->contact }} · {{ $d->type }} · soumise {{ $d->created_at->diffForHumans() }}</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-ink">{{ $d->message }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                    @if ($d->statut === 'nouveau')
                        <div class="flex shrink-0 gap-2">
                            <button wire:click="accepter({{ $d->id }})" class="rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">Accepter</button>
                            <button wire:click="refuser({{ $d->id }})" class="rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Refuser</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-10 text-center text-sm text-[#5B677A]">Aucune demande de partenariat.</p>
            @endforelse
        </div>
    </div>
</div>
