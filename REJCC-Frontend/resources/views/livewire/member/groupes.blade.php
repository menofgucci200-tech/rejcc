<div>
    <x-member-light.topbar title="Groupes sectoriels" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Groupes sectoriels</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Ces pôles regroupent les membres par domaine d'activité pour des échanges ciblés et des synergies sectorielles. Rejoignez autant de groupes que vous voulez — par exemple pour suivre plusieurs formations en parallèle.</p>
            </div>
            @if ($message)
                <span class="panel-enter inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                    <x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}
                </span>
            @endif
        </div>

        {{-- Formulaire d'adhésion / modification : fenêtre centrée, visible où que l'on soit dans la page --}}
        @if ($formGroupId)
            @php $groupeForm = $groups->firstWhere('id', $formGroupId); @endphp
            <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFormulaire" @keydown.escape.window="$wire.fermerFormulaire()">
                <div data-test="form-groupe" role="dialog" aria-modal="true" class="panel-enter w-full max-w-[520px] rounded-[20px] bg-white p-6 shadow-2xl">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ ($groupeForm['joined'] ?? false) ? 'Ma fiche dans le groupe' : 'Rejoindre le groupe' }}</p>
                            <p class="text-[16px] font-bold text-brand">{{ $groupeForm['name'] ?? '' }}</p>
                        </div>
                        <button type="button" wire:click="fermerFormulaire" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                    </div>
                    <label for="groupe-specialite" class="mb-1 block text-xs font-semibold text-[#5B677A]">Votre spécialité dans ce domaine <span class="font-normal text-[#9AA6B8]">(visible des membres abonnés)</span></label>
                    <textarea id="groupe-specialite" wire:model="specialite" rows="3" autofocus placeholder="Ex : Plombier spécialisé en dépannage sanitaire et chauffage central, intervention rapide à domicile." class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure"></textarea>
                    @error('specialite') <p data-test="erreur-specialite" class="mt-1 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                    <div class="mt-4 flex gap-2">
                        <button wire:click="confirmerAdhesion" wire:loading.attr="disabled" data-test="valider-groupe" class="btn-tap rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">{{ ($groupeForm['joined'] ?? false) ? 'Enregistrer' : 'Rejoindre le groupe' }}</button>
                        <button wire:click="fermerFormulaire" class="btn-tap rounded-full border border-brand/15 px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Annuler</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($mesGroupes->isNotEmpty())
            <div class="mb-6 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-2.5 text-[12px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Mes groupes ({{ $mesGroupes->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($mesGroupes as $g)
                        <a href="{{ route('espace-membre.groupes.membres', $g['id']) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-brand/[.06] px-3 py-1.5 text-[12px] font-semibold text-brand transition-colors hover:bg-brand hover:text-white">
                            <x-ui.icon name="network" class="size-3.5" /> {{ $g['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $g)
                <div class="card-hover flex flex-col rounded-[16px] border bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $g['joined'] ? 'border-azure/40' : 'border-brand/10' }}" wire:key="groupe-{{ $g['id'] }}">
                    <div class="mb-2.5 flex items-start justify-between gap-2">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-brand/[.06] text-brand">
                            <x-ui.icon name="network" class="size-5" />
                        </span>
                        @if ($g['joined'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[10.5px] font-bold text-[#1C8F4C]">
                                <x-ui.icon name="check" class="size-3" /> Membre
                            </span>
                        @endif
                    </div>

                    <p class="text-[14px] font-bold leading-snug text-brand">{{ $g['name'] }}</p>
                    <p class="mt-1.5 flex-1 text-[12px] leading-relaxed text-[#5B677A]">{{ $g['description'] }}</p>

                    @if ($g['joined'] && $g['ma_specialite'])
                        <p class="mt-2 rounded-[10px] bg-cloud/60 px-3 py-2 text-[11.5px] italic leading-relaxed text-[#5B677A]">« {{ $g['ma_specialite'] }} »</p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        @if ($g['members'] > 0)
                            <a
                                href="{{ route('espace-membre.groupes.membres', $g['id']) }}"
                                wire:navigate
                                class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-azure hover:underline"
                            >
                                <x-ui.icon name="users" class="size-3.5" /> {{ $g['members'] }} membre{{ $g['members'] > 1 ? 's' : '' }} · Voir les membres
                            </a>
                        @else
                            <span class="text-[11.5px] text-[#9AA6B8]">Aucun membre pour l'instant</span>
                        @endif
                        <div class="flex items-center gap-2">
                            @if ($g['joined'])
                                <button
                                    type="button"
                                    wire:click="ouvrirFormulaire({{ $g['id'] }}, {{ \Illuminate\Support\Js::from($g['ma_specialite']) }})"
                                    class="btn-tap rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-[11.5px] font-bold text-azure hover:bg-azure/20"
                                >Ma spécialité</button>
                                <button
                                    type="button"
                                    wire:click="quitter({{ $g['id'] }})"
                                    wire:confirm="Quitter le groupe « {{ $g['name'] }} » ? Votre fiche n'y apparaîtra plus."
                                    wire:loading.attr="disabled"
                                    wire:target="quitter({{ $g['id'] }})"
                                    class="btn-tap rounded-full border border-brand/15 px-3.5 py-1.5 text-[11.5px] font-bold text-[#5B677A] hover:border-accent/40 hover:text-accent disabled:opacity-60"
                                >Quitter</button>
                            @else
                                <button
                                    type="button"
                                    wire:click="ouvrirFormulaire({{ $g['id'] }})"
                                    class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90"
                                >Rejoindre</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
