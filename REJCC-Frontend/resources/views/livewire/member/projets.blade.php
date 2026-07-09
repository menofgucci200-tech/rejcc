<div>
    <x-member-light.topbar title="Projets" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Projets</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Proposer un projet</button>
        </div>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
            @foreach ($projets as $p)
                <article class="rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                            <x-ui.icon name="nav-projects" class="size-5" />
                        </span>
                        <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $p['statutColor'] }}1A; color: {{ $p['statutColor'] }}">{{ $p['statut'] }}</span>
                    </div>
                    <p class="mb-2 text-[14px] font-bold text-brand">{{ $p['titre'] }}</p>
                    <p class="mb-4 text-xs leading-relaxed text-[#5B677A]">{{ $p['description'] }}</p>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 text-xs text-[#9AA6B8]">
                            <x-ui.icon name="users" class="size-3.5" /> {{ $p['membres'] }} membres
                        </span>
                        <button class="rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure">Rejoindre</button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
