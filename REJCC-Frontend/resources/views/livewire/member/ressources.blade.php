<div>
    <x-member-light.topbar title="Ressources" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Ressources</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        <div class="mb-9 grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))">
            @foreach ($categories as $c)
                <article class="flex items-center gap-3 rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)] transition-transform hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(3,29,89,.12)]">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl" style="background: {{ $c['color'] }}1A; color: {{ $c['color'] }}">
                        <x-ui.icon :name="$c['icon']" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13.5px] font-bold leading-snug text-brand">{{ $c['titre'] }}</p>
                        <p class="mt-0.5 text-xs text-[#9AA6B8]">{{ $c['nombre'] }} ressources</p>
                    </div>
                </article>
            @endforeach
        </div>

        <h2 class="mb-4 text-sm font-bold text-brand">Ils l'ont fait</h2>
        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($temoignages as $t)
                <article class="rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <x-ui.icon name="star" class="mb-3 size-5 text-[#F5A623]" />
                    <p class="mb-4 text-[13px] italic leading-relaxed text-[#5B677A]">« {{ $t['citation'] }} »</p>
                    <p class="text-[13px] font-bold text-brand">{{ $t['nom'] }}</p>
                    <p class="text-xs text-[#9AA6B8]">{{ $t['role'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</div>
