<div>
    <x-admin-light.topbar title="Journal d'audit" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Journal d'audit</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">Traçabilité des actions d'administration (création, modification, suppression…) — 300 dernières entrées.</p>
            </div>
            <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Rechercher (auteur, action, cible)…" class="w-64 rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure" />
        </div>

        <div class="overflow-x-auto rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="border-b border-[#EDF0F5] text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">
                        <th class="px-5 py-3">Action</th>
                        <th class="px-3 py-3">Cible</th>
                        <th class="px-3 py-3">Auteur</th>
                        <th class="px-3 py-3">Adresse IP</th>
                        <th class="px-5 py-3 text-right">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $l)
                        <tr class="border-b border-[#EDF0F5] transition-colors duration-200 hover:bg-[#FAFBFD]">
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $l['couleur'][0] }}; background: {{ $l['couleur'][1] }}">{{ $l['action'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-[12.5px] font-semibold text-brand">{{ $l['target'] }}</td>
                            <td class="px-3 py-3 text-[12px] text-[#5B677A]">{{ $l['actor'] }}</td>
                            <td class="px-3 py-3 text-[12px] text-[#9AA6B8]">{{ $l['ip'] }}</td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-[12px] text-[#5B677A]" title="{{ $l['depuis'] }}">{{ $l['quand'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-[#5B677A]">Aucune action enregistrée pour le moment. Les actions d'administration apparaîtront ici.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
