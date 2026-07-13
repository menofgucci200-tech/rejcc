<div>
    <x-admin-light.topbar title="Contacts" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Messages non traités</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($pending as $m)
                    <div class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                        <div class="flex flex-wrap items-center gap-3.5">
                            <div class="min-w-[180px] flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $m->nom }}</p>
                                <p class="text-xs text-[#5B677A]">{{ $m->email }} · {{ $m->sujet }} · {{ $m->created_at->diffForHumans() }}</p>
                            </div>
                            <button wire:click="toggle({{ $m->id }})" class="btn-tap shrink-0 rounded-lg border border-[#C9D6F0] px-3.5 py-1.5 text-xs font-bold text-azure hover:bg-[#E8EDF8]">{{ $expanded === $m->id ? 'Masquer' : 'Lire' }}</button>
                            <button wire:click="markTraite({{ $m->id }})" class="btn-tap shrink-0 rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">Marquer traité</button>
                        </div>
                        @if ($expanded === $m->id)
                            <div class="panel-enter mt-2.5 rounded-xl bg-[#F8FAFC] p-4 text-[12.5px] leading-relaxed text-ink">{{ $m->message }}</div>
                        @endif
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun message en attente.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Messages traités</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($done as $m)
                    <div class="row-hover -mx-5 flex items-center gap-3.5 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                        <p class="min-w-0 flex-1 truncate text-[13px] font-semibold text-[#5B677A]">{{ $m->nom }} — {{ $m->sujet }}</p>
                        <span class="shrink-0 rounded-full bg-[#EAF6EE] px-2.5 py-1 text-[11px] font-bold text-[#22A85A]">Traité</span>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun message traité.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
