<div>
    <x-member-light.topbar title="Communauté" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="flex flex-col items-center justify-center rounded-[18px] border border-brand/10 bg-white px-8 py-20 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                <x-ui.icon name="network" class="size-7" />
            </span>
            <h2 class="mb-2 text-[17px] font-bold text-brand">Le fil communautaire arrive bientôt</h2>
            <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">
                Publications, discussions par groupe et entraide entre membres sont en cours de
                préparation. En attendant, retrouvez les membres dans l'annuaire et échangez en
                messagerie privée.
            </p>
            <div class="mt-5 flex gap-2">
                <a href="{{ route('espace-membre.directory') }}" wire:navigate class="btn-tap rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Voir l'annuaire</a>
                <a href="{{ route('espace-membre.messaging') }}" wire:navigate class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-xs font-semibold text-brand hover:bg-cloud">Ouvrir la messagerie</a>
            </div>
        </div>
    </div>
</div>
