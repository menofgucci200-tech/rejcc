<div>
    <x-member-light.topbar title="Mes parcours" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Mes parcours</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Des parcours de formation structurés, pensés pour vous accompagner étape par étape vers un objectif précis.</p>
        </div>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(270px, 1fr))">
            @foreach ($parcours as $p)
                <article class="rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl" style="background: {{ $p['tint'] }}1A; color: {{ $p['tint'] }}">
                            <x-ui.icon name="nav-route" class="size-5" />
                        </span>
                        @if ($p['cert'])
                            <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#F5A623]">
                                <x-ui.icon name="award" class="size-3" /> Certifiant
                            </span>
                        @endif
                    </div>
                    <p class="mb-1 text-[14px] font-bold text-brand">{{ $p['titre'] }}</p>
                    <p class="mb-3 text-xs text-[#9AA6B8]">{{ $p['difficulte'] }} · {{ $p['duree'] }} · Mentor : {{ $p['mentor'] }}</p>

                    <div class="mb-1 h-1.5 w-full rounded-full bg-cloud">
                        <div class="h-1.5 rounded-full" style="width: {{ $p['pct'] }}%; background: {{ $p['tint'] }}"></div>
                    </div>
                    <p class="mb-4 text-[11px] font-semibold text-[#5B677A]">{{ $p['pct'] }}% complété</p>

                    <button class="w-full rounded-[9px] py-2 text-[12.5px] font-semibold {{ $p['pct'] === 0 ? 'bg-brand text-white' : ($p['pct'] === 100 ? 'border border-[#22A85A]/25 bg-[#22A85A]/10 text-[#22A85A]' : 'border border-azure/25 bg-azure/10 text-azure') }}">
                        {{ $p['pct'] === 0 ? 'Commencer' : ($p['pct'] === 100 ? 'Parcours terminé' : 'Continuer') }}
                    </button>
                </article>
            @endforeach
        </div>
    </div>
</div>
