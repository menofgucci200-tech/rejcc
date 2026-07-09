<div>
    <x-admin-light.topbar title="Newsletter" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-7 grid grid-cols-1 gap-3.5 sm:grid-cols-3">
            <div class="rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="text-xs font-semibold text-[#5B677A]">Abonnés actifs</p>
                <p class="mt-1.5 text-[26px] font-extrabold text-brand">{{ $total }}</p>
            </div>
            <div class="rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="text-xs font-semibold text-[#5B677A]">Nouveaux ce mois</p>
                <p class="mt-1.5 text-[26px] font-extrabold text-brand">{{ $nouveauxCeMois }}</p>
            </div>
            <div class="rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="text-xs font-semibold text-[#5B677A]">Désabonnements</p>
                <p class="mt-1.5 text-[26px] font-extrabold text-brand">0</p>
            </div>
        </section>

        <section>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Derniers abonnés</h2>
                    <div class="h-[3px] w-9 rounded bg-accent"></div>
                </div>
                <button type="button" class="rounded-[10px] bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">Exporter en CSV</button>
            </div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($abonnes as $a)
                    <div class="flex items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <p class="min-w-0 flex-1 truncate text-[13px] text-ink">{{ $a->email }}</p>
                        <span class="shrink-0 text-xs text-[#5B677A]">Inscrit {{ $a->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun abonné pour le moment.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
