<div>
    <x-admin-light.topbar title="Certificats" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Registre des certificats émis</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Les certificats sont émis automatiquement lorsqu'un membre termine une formation certifiante. Ce registre permet de vérifier une référence présentée par un membre.</p>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($certificats as $c)
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">{{ $c['initiales'] }}</span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $c['membre'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $c['formation'] }}</p>
                        <p class="mt-0.5 text-[11px] text-[#9AA6B8]">{{ $c['email'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-[8px] bg-cloud px-2.5 py-1 text-[11px] font-bold tracking-wide text-brand">{{ $c['reference'] }}</span>
                    <span class="w-[120px] shrink-0 text-right text-xs text-[#5B677A]">{{ $c['date'] }}</span>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun certificat émis pour le moment — ils apparaîtront quand des membres termineront des formations certifiantes.</p>
            @endforelse
        </div>
    </div>
</div>
