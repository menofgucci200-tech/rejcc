<div>
    <x-admin-light.topbar title="Groupes sectoriels" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Groupes sectoriels</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Les groupes permettent aux membres de trouver un professionnel par domaine. Gérez leur identité, le référent, l'annonce épinglée, les membres et les avis. Chaque groupe dispose de sa discussion sur la plateforme, réservée à ses membres abonnés.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.export', 'groupes') }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-1.5 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Exporter tous les membres</a>
                <button wire:click="openCreate" data-test="nouveau-groupe" class="btn-tap rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600">+ Nouveau groupe</button>
            </div>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        {{-- Onglets --}}
        <div class="mb-5 flex gap-1 border-b border-brand/10" role="tablist">
            @foreach (['groupes' => 'Groupes ('.$groups->count().')', 'avis' => 'Avis des membres'] as $cle => $libelle)
                <button type="button" role="tab" wire:click="$set('onglet', '{{ $cle }}')" :aria-selected="@js($onglet === $cle)" data-test="onglet-{{ $cle }}"
                    class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-[13px] font-bold transition-colors {{ $onglet === $cle ? 'border-accent text-brand' : 'border-transparent text-[#9AA6B8] hover:text-brand' }}">
                    {{ $libelle }}
                    @if ($cle === 'avis' && $avisSignales)
                        <span class="rounded-full bg-accent px-1.5 text-[10.5px] font-bold leading-4 text-white">{{ $avisSignales }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($onglet === 'groupes')
            <div class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($groups as $g)
                    <div data-test="ligne-groupe" wire:key="admin-groupe-{{ $g['id'] }}" class="row-hover flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3 first:border-t-0 sm:px-5">
                        <div class="flex shrink-0 flex-col">
                            <button wire:click="move({{ $g['id'] }}, 'up')" @disabled($loop->first) aria-label="Monter" class="rounded p-0.5 text-[#9AA6B8] hover:bg-cloud hover:text-brand disabled:opacity-25"><x-ui.icon name="chevron-down" class="size-3.5 rotate-180" /></button>
                            <button wire:click="move({{ $g['id'] }}, 'down')" @disabled($loop->last) aria-label="Descendre" class="rounded p-0.5 text-[#9AA6B8] hover:bg-cloud hover:text-brand disabled:opacity-25"><x-ui.icon name="chevron-down" class="size-3.5" /></button>
                        </div>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[11px]" style="background: {{ $g['couleur'] }}1A; color: {{ $g['couleur'] }}"><x-ui.icon :name="$g['icone']" class="size-5" /></span>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $g['name'] }}</p>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11.5px] text-[#5B677A]">
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="users" class="size-3.5" /> {{ $g['membres'] }} membre{{ $g['membres'] > 1 ? 's' : '' }}</span>
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="star" class="size-3.5" /> {{ $g['avis'] }} avis</span>
                                <span class="inline-flex items-center gap-1 {{ $g['referent'] ? '' : 'text-[#9AA6B8]' }}"><x-ui.icon name="award" class="size-3.5" /> {{ $g['referent']['nom'] ?? 'Pas de référent' }}</span>
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="message-circle" class="size-3.5" /> {{ $g['messages'] }} message{{ $g['messages'] > 1 ? 's' : '' }} dans la discussion</span>
                                @if ($g['annonce'])<span class="inline-flex items-center gap-1 text-[#8A5A08]"><x-ui.icon name="pin" class="size-3.5" /> Annonce épinglée</span>@endif
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <button wire:click="voirMembres({{ $g['id'] }})" data-test="voir-membres-admin" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">Membres</button>
                            <a href="{{ route('admin.export', ['dataset' => 'groupes', 'group' => $g['id']]) }}" title="Exporter les membres (CSV)" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="download" class="size-3.5" /></a>
                            <button wire:click="openEdit({{ $g['id'] }})" data-test="modifier-groupe" title="Modifier" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $g['id'] }})" wire:confirm="Supprimer le groupe « {{ $g['name'] }} » ?{{ $g['membres'] ? ' Les fiches de ses '.$g['membres'].' membres seront supprimées.' : '' }}" title="Supprimer" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Modération des avis --}}
            <div class="mb-4 flex flex-wrap gap-1.5">
                @foreach (['signales' => 'Signalés', 'masques' => 'Masqués', 'tous' => 'Tous les avis'] as $cle => $libelle)
                    <button type="button" wire:click="$set('filtreAvis', '{{ $cle }}')" data-test="filtre-avis-{{ $cle }}"
                        class="rounded-full px-3.5 py-1.5 text-[12px] font-bold {{ $filtreAvis === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }}</button>
                @endforeach
            </div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($avis['avis'] ?? [] as $a)
                    <div data-test="ligne-avis" wire:key="avis-admin-{{ $a['id'] }}" class="-mx-5 flex flex-wrap items-start gap-3 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                        <div class="min-w-[240px] flex-1">
                            <p class="text-[12.5px] text-[#5B677A]"><span class="font-bold text-brand">{{ $a['auteur'] }}</span> sur <span class="font-bold text-brand">{{ $a['professionnel'] }}</span>@if ($a['groupe']) · {{ $a['groupe'] }}@endif</p>
                            <p class="mt-0.5 text-[14px] tracking-wide text-[#F5A623]">{{ str_repeat('★', $a['note']) }}<span class="text-[#D5DCE8]">{{ str_repeat('★', 5 - $a['note']) }}</span></p>
                            @if ($a['commentaire'])<p class="mt-0.5 text-[13px] text-ink">« {{ $a['commentaire'] }} »</p>@endif
                            <div class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                                <span class="text-[#9AA6B8]">{{ \Illuminate\Support\Carbon::parse($a['date'])->locale('fr')->diffForHumans() }}</span>
                                @if ($a['signale'])<span class="rounded-full bg-accent/10 px-2 font-bold text-accent">Signalé{{ $a['motif'] ? ' : '.$a['motif'] : '' }}</span>@endif
                                @if ($a['masque'])<span class="rounded-full bg-[#5B677A]/10 px-2 font-bold text-[#5B677A]">Masqué</span>@endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                            @if ($a['masque'])
                                <button wire:click="moderer({{ $a['id'] }}, false)" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">Rétablir</button>
                            @else
                                @if ($a['signale'])
                                    <button wire:click="moderer({{ $a['id'] }}, false)" data-test="conserver-avis" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">Conserver</button>
                                @endif
                                <button wire:click="moderer({{ $a['id'] }}, true)" data-test="masquer-avis" class="btn-tap rounded-full bg-brand px-3 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90">Masquer</button>
                            @endif
                            <button wire:click="supprimerAvis({{ $a['id'] }})" wire:confirm="Supprimer définitivement cet avis ?" title="Supprimer" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-[#5B677A]">{{ $filtreAvis === 'signales' ? 'Aucun avis signalé : rien à modérer.' : ($filtreAvis === 'masques' ? 'Aucun avis masqué.' : 'Aucun avis pour le moment.') }}</p>
                @endforelse
            </div>
            @if (($avis['meta']['last_page'] ?? 1) > 1)
                <x-ui.pager :meta="$avis['meta']" />
            @endif
        @endif
    </div>

    {{-- Formulaire de groupe --}}
    @if ($showForm)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="closeForm" @keydown.escape.window="$wire.closeForm()">
            <div data-test="form-groupe-admin" role="dialog" aria-modal="true" class="panel-enter max-h-[92vh] w-full max-w-[640px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-[15px] font-bold text-brand">{{ $editingId ? 'Modifier le groupe' : 'Nouveau groupe' }}</p>
                    <button wire:click="closeForm" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>

                <div class="grid gap-3.5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="g-nom" class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom du groupe</label>
                        <input id="g-nom" wire:model="name" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="g-desc" class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                        <textarea id="g-desc" wire:model="description" rows="2" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    </div>
                    <div class="sm:col-span-2" x-data="{ icone: @entangle('icone'), couleur: @entangle('couleur') }">
                        <p class="mb-1 text-xs font-semibold text-[#5B677A]">Icône et couleur</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex size-12 items-center justify-center rounded-[12px]" :style="`background: ${couleur}1A; color: ${couleur}`">
                                @foreach ($icones as $i)
                                    <span x-show="icone === '{{ $i }}'" @if ($i !== $icone) style="display: none" @endif><x-ui.icon :name="$i" class="size-6" /></span>
                                @endforeach
                            </span>
                            <input type="color" x-model="couleur" aria-label="Couleur" class="h-10 w-14 cursor-pointer rounded-[9px] border border-brand/15 p-1" />
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (['#031D59', '#AC0100', '#4F6FBF', '#2E8B57', '#E07B24', '#7B5EA7', '#0E8A96', '#B7790F'] as $c)
                                    <button type="button" @click="couleur = '{{ $c }}'" aria-label="Couleur {{ $c }}" class="size-6 rounded-full ring-offset-2" :class="couleur.toLowerCase() === '{{ strtolower($c) }}' ? 'ring-2 ring-brand' : ''" style="background: {{ $c }}"></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-1">
                            @foreach ($icones as $i)
                                <button type="button" @click="icone = '{{ $i }}'" title="{{ $i }}" class="flex size-9 items-center justify-center rounded-[9px] border transition-colors"
                                    :class="icone === '{{ $i }}' ? 'border-brand bg-brand text-white' : 'border-brand/10 text-[#5B677A] hover:bg-cloud'"><x-ui.icon :name="$i" class="size-4" /></button>
                            @endforeach
                        </div>
                    </div>
                    @if ($editingId)
                        <div class="sm:col-span-2">
                            <label for="g-referent" class="mb-1 block text-xs font-semibold text-[#5B677A]">Référent du groupe <span class="font-normal text-[#9AA6B8]">(choisi parmi ses membres)</span></label>
                            <select id="g-referent" wire:model="referentId" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                                <option value="">— Aucun référent —</option>
                                @foreach ($membresForm as $m)
                                    <option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>
                                @endforeach
                            </select>
                            @if (empty($membresForm))<p class="mt-1 text-[11.5px] text-[#9AA6B8]">Le groupe n'a pas encore de membre.</p>@endif
                        </div>
                        <div class="sm:col-span-2">
                            <label for="g-annonce" class="mb-1 block text-xs font-semibold text-[#5B677A]">Annonce épinglée <span class="font-normal text-[#9AA6B8]">(laisser vide pour la retirer)</span></label>
                            <textarea id="g-annonce" wire:model="annonce" rows="3" maxlength="1000" placeholder="Ex : Rencontre des membres samedi à 10h…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                            <label class="mt-1.5 flex items-center gap-2 text-[12px] text-ink">
                                <input type="checkbox" wire:model="notifier" class="size-4 rounded border-brand/30 text-brand"> Prévenir les membres du groupe d'une nouvelle annonce (notification)
                            </label>
                        </div>
                    @endif
                </div>

                @if ($erreur)<p data-test="erreur-groupe-admin" class="mt-3 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif

                <div class="mt-5 flex gap-2">
                    <button wire:click="save" wire:loading.attr="disabled" data-test="enregistrer-groupe" class="btn-tap rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Enregistrer</button>
                    <button wire:click="closeForm" class="btn-tap rounded-full border border-brand/15 px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Annuler</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Membres d'un groupe --}}
    @if ($membresGroupId)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerMembres" @keydown.escape.window="$wire.fermerMembres()">
            <div data-test="membres-groupe-admin" role="dialog" aria-modal="true" class="panel-enter flex max-h-[90vh] w-full max-w-[760px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-cloud-200 px-6 py-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Membres du groupe</p>
                        <p class="text-[15px] font-bold text-brand">{{ $groupeMembres['name'] ?? '' }} <span class="font-semibold text-[#9AA6B8]">· {{ $membres->count() }}</span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.export', ['dataset' => 'groupes', 'group' => $membresGroupId]) }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> CSV</a>
                        <button wire:click="fermerMembres" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                    </div>
                </div>
                <div class="border-b border-cloud-200 px-6 py-3">
                    <input wire:model.live.debounce.250ms="rechercheMembre" type="search" placeholder="Filtrer (nom, spécialité, ville, e-mail)…" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="overflow-y-auto px-6">
                    @forelse ($membres as $m)
                        <div data-test="membre-admin" wire:key="ma-{{ $m['id'] }}" class="flex flex-wrap items-start gap-3 border-t border-[#EDF0F5] py-3 first:border-t-0">
                            <div class="min-w-[220px] flex-1">
                                <p class="text-[13px] font-bold text-brand">{{ $m['nom'] }}
                                    @unless ($m['actif'])<span class="ml-1 rounded-full bg-accent/10 px-2 text-[10.5px] font-bold text-accent">Suspendu</span>@endunless
                                    @unless ($m['dans_annuaire'])<span class="ml-1 rounded-full bg-[#5B677A]/10 px-2 text-[10.5px] font-bold text-[#5B677A]">Masqué de l'annuaire</span>@endunless
                                </p>
                                <p class="text-[11.5px] text-[#9AA6B8]">{{ collect([$m['email'], $m['telephone'], $m['ville']])->filter()->join(' · ') }}</p>
                                <p class="mt-1 line-clamp-2 text-[12.5px] italic text-ink">« {{ $m['specialite'] }} »</p>
                                <p class="mt-0.5 text-[11px] text-[#9AA6B8]">
                                    @if ($m['avis']['nombre'])<span class="font-semibold text-[#B7790F]">★ {{ number_format($m['avis']['moyenne'], 1, ',', ' ') }} ({{ $m['avis']['nombre'] }})</span> · @endif
                                    Rejoint {{ $m['rejoint_le'] ? \Illuminate\Support\Carbon::parse($m['rejoint_le'])->locale('fr')->diffForHumans() : '' }}
                                </p>
                            </div>
                            <button type="button" data-test="retirer-membre"
                                x-on:click="const motif = prompt('Retirer {{ addslashes($m['nom']) }} du groupe ? Indiquez le motif (il sera transmis au membre) :'); if (motif !== null) $wire.retirerMembre({{ $m['id'] }}, motif)"
                                class="btn-tap shrink-0 rounded-full border border-accent/30 px-3 py-1.5 text-[11.5px] font-bold text-accent hover:bg-accent/5">Retirer</button>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-[#5B677A]">{{ trim($rechercheMembre) !== '' ? 'Aucun membre ne correspond.' : 'Aucun membre dans ce groupe.' }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
