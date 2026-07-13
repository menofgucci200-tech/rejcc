<div>
    <x-member-light.topbar title="Projets" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Projets</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button wire:click="openForm" class="btn-tap rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Proposer un projet</button>
        </div>

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">Proposer un projet au réseau</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom du projet</label>
                    <input wire:model="title" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description — objectif, public visé, impact attendu</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nombre de membres impliqués</label>
                    <input wire:model="membersCount" type="number" min="1" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Besoin de financement en FCFA (optionnel)</label>
                    <input wire:model="fundingGoal" type="number" min="0" step="100000" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <button wire:click="proposer" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Soumettre à l'évaluation</button>
                    <p class="mt-2 text-[11px] text-[#9AA6B8]">Votre projet sera examiné par l'équipe avant d'apparaître comme validé.</p>
                </div>
            </div>
        @endif

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
            @foreach ($projets as $p)
                <article class="card-hover rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
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
                            <x-ui.icon name="users" class="size-3.5" /> {{ $p['membres'] }} membre{{ $p['membres'] > 1 ? 's' : '' }}
                        </span>
                        <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $p['mien'] ? 'Votre projet' : ($p['porteur'] ? 'Par '.$p['porteur'] : 'Projet du réseau') }}</span>
                    </div>
                </article>
            @endforeach
        </div>
        @if (empty($projets))
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucun projet pour le moment — proposez le vôtre !</p>
        @endif
    </div>
</div>
