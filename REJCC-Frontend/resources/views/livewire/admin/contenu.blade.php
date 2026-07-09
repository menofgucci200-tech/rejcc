<div>
    <x-admin-light.topbar title="Contenu du site" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ([['secteurs', 'Secteurs & activités'], ['temoignages', 'Témoignages'], ['partenaires', 'Partenaires'], ['accueil', "Page d'accueil"], ['adhesion', "Étapes d'adhésion"]] as [$key, $label])
                <button wire:click="setOnglet('{{ $key }}')" class="rounded-full border px-4 py-2 text-[12.5px] font-bold {{ $onglet === $key ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
            @endforeach
        </div>

        @if ($onglet === 'secteurs')
            <div class="mb-4 rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($secteurs as $s)
                    <div class="flex items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex-1 text-[13.5px] font-bold text-brand">{{ $s['nom'] }}</span>
                        <span class="text-xs text-[#5B677A]">{{ $s['activites'] }} activités liées</span>
                        <button class="rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Modifier</button>
                    </div>
                @endforeach
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Ajouter un secteur</button>
        @elseif ($onglet === 'temoignages')
            <div class="mb-4 rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($temoignages as $t)
                    <div class="flex flex-wrap items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <div class="min-w-[200px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $t['nom'] }}</p>
                            <p class="mt-0.5 text-xs italic text-[#5B677A]">« {{ $t['citation'] }} »</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $t['visible'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $t['visible'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $t['visible'] ? 'Visible' : 'Masqué' }}</span>
                        <button wire:click="toggleTemoignage({{ $t['index'] }})" class="shrink-0 rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $t['visible'] ? 'Masquer' : 'Afficher' }}</button>
                    </div>
                @endforeach
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Ajouter un témoignage</button>
        @elseif ($onglet === 'partenaires')
            <div class="mb-4 rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($partenaires as $p)
                    <div class="flex items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#E8EDF8] text-xs font-extrabold text-brand">{{ $p['initiales'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $p['nom'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $p['citation'] }}</p>
                        </div>
                        <button class="shrink-0 rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Modifier</button>
                    </div>
                @endforeach
            </div>
            <button class="rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">+ Ajouter un partenaire</button>
        @elseif ($onglet === 'accueil')
            <div class="flex max-w-xl flex-col gap-3.5 rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Accroche principale</label>
                    <input type="text" value="Entreprendre avec foi, exceller ensemble" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Membres actifs</label>
                        <input type="text" value="1 284" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Formations</label>
                        <input type="text" value="32" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Villes couvertes</label>
                        <input type="text" value="8" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Valeurs mises en avant</label>
                    <textarea rows="3" class="w-full resize-y rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">Excellence, Foi, Solidarité, Innovation</textarea>
                </div>
                <button class="w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-accent">Enregistrer</button>
            </div>
        @elseif ($onglet === 'adhesion')
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($etapes as $e)
                    <div class="flex items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex size-[26px] shrink-0 items-center justify-center rounded-full bg-[#E8EDF8] text-xs font-extrabold text-brand">{{ $e['numero'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $e['titre'] }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $e['description'] }}</p>
                        </div>
                        <button class="shrink-0 rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Modifier</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
