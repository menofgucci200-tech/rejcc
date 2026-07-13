<div>
    <x-member-light.topbar title="Mes parcours" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="flex flex-col items-center justify-center rounded-[18px] border border-brand/10 bg-white px-8 py-20 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                <x-ui.icon name="nav-route" class="size-7" />
            </span>
            <h2 class="mb-2 text-[17px] font-bold text-brand">Les parcours guidés arrivent bientôt</h2>
            <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">
                Les parcours thématiques (enchaînements de formations avec objectifs et badges)
                sont en cours de préparation. En attendant, progressez librement dans le catalogue
                de formations.
            </p>
            <a href="{{ route('espace-membre.catalogue') }}" wire:navigate class="btn-tap mt-5 rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Parcourir le catalogue</a>
        </div>
    </div>
</div>
