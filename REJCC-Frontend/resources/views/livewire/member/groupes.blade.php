<div>
    <x-member-light.topbar title="Groupes sectoriels" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Groupes sectoriels</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Chaque groupe réunit les membres d'un même domaine pour les retrouver facilement : un plombier, une comptable, un traiteur… Consultez leur fiche (services, zone, disponibilités, avis des membres) et contactez-les. Rejoignez les groupes de vos métiers pour être trouvé à votre tour.</p>
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
                <div data-test="form-groupe" role="dialog" aria-modal="true" class="panel-enter max-h-[92vh] w-full max-w-[560px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
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

                    <label for="groupe-services" class="mb-1 mt-3 block text-xs font-semibold text-[#5B677A]">Services proposés <span class="font-normal text-[#9AA6B8]">(séparés par des virgules)</span></label>
                    <input id="groupe-services" wire:model="services" type="text" placeholder="Ex : Dépannage urgent, Pose de chauffe-eau, Devis gratuit" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="groupe-zone" class="mb-1 block text-xs font-semibold text-[#5B677A]">Zone d'intervention</label>
                            <input id="groupe-zone" wire:model="zone" type="text" placeholder="Ex : Cocody, Bingerville" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                        <div>
                            <label for="groupe-dispo" class="mb-1 block text-xs font-semibold text-[#5B677A]">Disponibilités</label>
                            <input id="groupe-dispo" wire:model="disponibilites" type="text" placeholder="Ex : du lundi au samedi, 8h-18h" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-2.5 rounded-[10px] bg-cloud/60 p-3 text-[12.5px] text-ink">
                        <input type="checkbox" wire:model="telephoneVisible" class="mt-0.5 size-4 shrink-0 rounded border-brand/30 text-brand">
                        <span><span class="font-semibold">Afficher mon téléphone aux membres de ce groupe</span><span class="block text-[11.5px] text-[#5B677A]">Pour être joint directement par les membres abonnés qui consultent votre fiche dans ce groupe.</span></span>
                    </label>
                    <div class="mt-4 flex gap-2">
                        <button wire:click="confirmerAdhesion" wire:loading.attr="disabled" data-test="valider-groupe" class="btn-tap rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">{{ ($groupeForm['joined'] ?? false) ? 'Enregistrer' : 'Rejoindre le groupe' }}</button>
                        <button wire:click="fermerFormulaire" class="btn-tap rounded-full border border-brand/15 px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Annuler</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- « Je cherche… » : trouver un professionnel dans tous les groupes à la fois --}}
        <div class="mb-6 overflow-hidden rounded-[18px] bg-gradient-to-br from-brand to-[#0A2C6E] p-5 text-white shadow-[0_12px_32px_-16px_rgba(3,29,89,.6)] sm:p-6">
            <label for="je-cherche" class="block text-[15px] font-extrabold">Je cherche…</label>
            <p class="mt-0.5 text-[12.5px] text-white/70">Un métier, un service, un quartier : trouvez le bon professionnel parmi les membres de tous les groupes.</p>
            <div class="relative mt-3.5">
                <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-[17px] -translate-y-1/2 text-[#9AA6B8]" />
                <input id="je-cherche" type="search" wire:model.live.debounce.400ms="cherche" data-test="je-cherche"
                    placeholder="Ex : un plombier à Cocody, une comptable, un traiteur…"
                    class="w-full rounded-[14px] border-0 bg-white py-3.5 pl-11 pr-4 text-[14px] text-ink shadow-inner outline-none ring-2 ring-transparent focus:ring-[#8FA3D9]" />
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <span class="text-[11.5px] font-semibold text-white/60">Exemples :</span>
                @foreach (['Plombier', 'Électricien', 'Comptable', 'Traiteur', 'Informaticien', 'Couturière'] as $exemple)
                    <button type="button" wire:click="$set('cherche', '{{ $exemple }}')" class="rounded-full bg-white/10 px-2.5 py-1 text-[11.5px] font-semibold text-white transition-colors hover:bg-white/20">{{ $exemple }}</button>
                @endforeach
            </div>
        </div>

        @if ($resultats)
            <section data-test="resultats-je-cherche" class="mb-8" wire:key="resultats-{{ md5($cherche) }}">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p data-test="nb-resultats" class="text-[14px] font-bold text-brand">
                        @if ($resultats['total'] ?? 0)
                            {{ $resultats['total'] }} professionnel{{ $resultats['total'] > 1 ? 's' : '' }} pour « {{ trim($cherche) }} »
                        @else
                            Aucun professionnel pour « {{ trim($cherche) }} »
                        @endif
                    </p>
                    <button type="button" wire:click="$set('cherche', '')" class="inline-flex items-center gap-1 text-[12px] font-semibold text-[#5B677A] hover:text-brand"><x-ui.icon name="x" class="size-3.5" /> Effacer la recherche</button>
                </div>

                @if (! empty($resultats['par_groupe']))
                    <div class="mb-4 flex flex-wrap gap-1.5">
                        @foreach ($resultats['par_groupe'] as $pg)
                            <a href="{{ route('espace-membre.groupes.membres', ['groupId' => $pg['id'], 'q' => trim($cherche)]) }}" wire:navigate data-test="resultat-groupe"
                                class="inline-flex items-center gap-1.5 rounded-full border border-brand/10 bg-white px-3 py-1.5 text-[12px] font-semibold text-brand hover:border-brand hover:bg-brand hover:text-white">
                                {{ $pg['nom'] }} <span class="rounded-full bg-brand/[.08] px-1.5 text-[11px]">{{ $pg['nombre'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($resultats['verrouille'] ?? false)
                    @if ($resultats['total'] ?? 0)
                        <div data-test="je-cherche-verrou" class="flex flex-wrap items-center gap-4 rounded-[16px] border border-dashed border-brand/20 bg-white p-5">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent"><x-ui.icon name="lock" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[14px] font-bold text-brand">Les fiches des professionnels sont réservées aux membres abonnés</p>
                                <p class="text-[12.5px] text-[#5B677A]">Abonnez-vous (10 000 F / an) pour voir leurs services, zones d'intervention, avis et les contacter.</p>
                            </div>
                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">M'abonner</a>
                        </div>
                    @endif
                @elseif (! empty($resultats['members']))
                    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
                        @foreach ($resultats['members'] as $m)
                            <x-groupes.carte-membre :m="$m" :groupe="$m['groupe']['nom']" wire:key="jc-{{ $m['groupe']['id'] }}-{{ $m['id'] }}" wire:click="voirFiche({{ $m['groupe']['id'] }}, {{ $m['id'] }})" />
                        @endforeach
                    </div>
                    @if (($resultats['meta']['total'] ?? 0) > count($resultats['members']))
                        <p class="mt-3 text-center text-[12px] text-[#9AA6B8]">{{ count($resultats['members']) }} premiers résultats affichés, les mieux notés d'abord. Précisez votre recherche ou ouvrez un groupe ci-dessus.</p>
                    @endif
                @else
                    <p class="rounded-[14px] bg-white p-5 text-center text-[13px] text-[#5B677A]">Essayez un autre mot (métier, service, quartier) ou parcourez les groupes ci-dessous.</p>
                @endif
            </section>
        @endif

        @if ($mesGroupes->isNotEmpty())
            <div class="mb-6 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-2.5 text-[12px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Mes groupes ({{ $mesGroupes->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($mesGroupes as $g)
                        <a href="{{ route('espace-membre.groupes.membres', $g['id']) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-brand/[.06] px-3 py-1.5 text-[12px] font-semibold text-brand transition-colors hover:bg-brand hover:text-white">
                            <x-ui.icon :name="$g['icone']" class="size-3.5" /> {{ $g['name'] }}@if (($g['discussion']['non_lus'] ?? 0) > 0) <span class="rounded-full bg-accent px-1.5 text-[10px] font-bold leading-4 text-white">{{ $g['discussion']['non_lus'] }}</span>@endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @php $suggeres = $groups->where('suggere', true)->values(); @endphp
        @if ($suggeres->isNotEmpty())
            <div data-test="groupes-suggeres" class="mb-6 rounded-[16px] border border-dashed border-azure/40 bg-azure/[.04] p-4">
                <p class="mb-2.5 flex items-center gap-1.5 text-[12px] font-bold uppercase tracking-[0.1em] text-azure"><x-ui.icon name="sparkles" class="size-3.5" /> Suggérés pour votre profil</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($suggeres as $g)
                        <span class="inline-flex items-center gap-2 rounded-full border border-brand/10 bg-white py-1 pl-1 pr-1 text-[12px] font-semibold text-brand">
                            <span class="flex size-6 items-center justify-center rounded-full" style="background: {{ $g['couleur'] }}1A; color: {{ $g['couleur'] }}"><x-ui.icon :name="$g['icone']" class="size-3.5" /></span>
                            {{ $g['name'] }}
                            <button type="button" wire:click="ouvrirFormulaire({{ $g['id'] }})" class="rounded-full bg-brand px-2.5 py-0.5 text-[11px] font-bold text-white hover:bg-brand/90">Rejoindre</button>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $g)
                <div data-test="carte-secteur" class="card-hover relative flex flex-col overflow-hidden rounded-[16px] border bg-white p-5 pt-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $g['joined'] ? 'border-azure/40' : 'border-brand/10' }}" wire:key="groupe-{{ $g['id'] }}">
                    <span class="absolute inset-x-0 top-0 h-1" style="background: {{ $g['couleur'] }}"></span>
                    <div class="mb-2.5 flex items-start justify-between gap-2">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px]" style="background: {{ $g['couleur'] }}1A; color: {{ $g['couleur'] }}">
                            <x-ui.icon :name="$g['icone']" class="size-[22px]" />
                        </span>
                        <div class="flex flex-wrap justify-end gap-1">
                            @if ($g['joined'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[10.5px] font-bold text-[#1C8F4C]">
                                    <x-ui.icon name="check" class="size-3" /> Membre
                                </span>
                            @elseif ($g['suggere'] ?? false)
                                <span class="inline-flex items-center gap-1 rounded-full bg-azure/10 px-2.5 py-1 text-[10.5px] font-bold text-azure">
                                    <x-ui.icon name="sparkles" class="size-3" /> Pour vous
                                </span>
                            @endif
                        </div>
                    </div>

                    <p class="text-[14px] font-bold leading-snug text-brand">{{ $g['name'] }}</p>
                    <p class="mt-1.5 flex-1 text-[12px] leading-relaxed text-[#5B677A]">{{ $g['description'] }}</p>

                    @if ($g['annonce'] ?? null)
                        <p data-test="annonce-groupe" class="mt-2.5 flex items-start gap-1.5 rounded-[10px] bg-[#F5A623]/10 px-3 py-2 text-[11.5px] leading-relaxed text-[#7A4E05]">
                            <x-ui.icon name="pin" class="mt-px size-3.5 shrink-0" /> <span class="line-clamp-2">{{ $g['annonce'] }}</span>
                        </p>
                    @endif

                    @if ($g['joined'] && $g['ma_specialite'])
                        <p class="mt-2 rounded-[10px] bg-cloud/60 px-3 py-2 text-[11.5px] italic leading-relaxed text-[#5B677A]">« {{ $g['ma_specialite'] }} »</p>
                    @endif

                    @if ($g['joined'] && ($g['discussion']['non_lus'] ?? 0) > 0)
                        <a href="{{ route('espace-membre.groupes.membres', ['groupId' => $g['id'], 'vue' => 'discussion']) }}" wire:navigate data-test="carte-discussion-non-lus" class="mt-2.5 inline-flex items-center gap-1.5 self-start rounded-full bg-accent/10 px-2.5 py-1 text-[11.5px] font-bold text-accent hover:bg-accent/15">
                            <x-ui.icon name="message-circle" class="size-3.5" /> {{ $g['discussion']['non_lus'] }} nouveau{{ $g['discussion']['non_lus'] > 1 ? 'x' : '' }} message{{ $g['discussion']['non_lus'] > 1 ? 's' : '' }} dans la discussion
                        </a>
                    @endif

                    @if ($g['referent'] ?? null)
                        <p data-test="referent-groupe" class="mt-2.5 flex items-center gap-1.5 text-[11.5px] text-[#5B677A]">
                            <x-ui.icon name="award" class="size-3.5 shrink-0" style="color: {{ $g['couleur'] }}" /> Référent : <span class="font-semibold text-brand">{{ $g['referent']['prenom'] }} {{ $g['referent']['nom'] }}</span>
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-cloud-200 pt-3.5">
                        @if ($g['members'] > 0)
                            <a href="{{ route('espace-membre.groupes.membres', $g['id']) }}" wire:navigate class="group/lien inline-flex items-center gap-2 text-[11.5px] font-semibold text-azure">
                                @if (! empty($g['derniers']))
                                    <span class="flex -space-x-2" aria-hidden="true">
                                        @foreach ($g['derniers'] as $d)
                                            <span x-data="{ erreur: false }" class="relative">
                                                @if ($d['photo'])
                                                    <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $d['photo'] }}" alt="" class="size-6 rounded-full object-cover ring-2 ring-white">
                                                @endif
                                                <span @if ($d['photo']) x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $d['mentor'] ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})" @else style="background: linear-gradient(135deg, {{ $d['mentor'] ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})" @endif class="flex size-6 items-center justify-center rounded-full text-[8.5px] font-bold text-white ring-2 ring-white">{{ $d['initiales'] }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                @endif
                                <span class="group-hover/lien:underline">{{ $g['members'] }} membre{{ $g['members'] > 1 ? 's' : '' }} · Voir</span>
                            </a>
                        @else
                            <span class="text-[11.5px] text-[#9AA6B8]">Aucun membre pour l'instant</span>
                        @endif
                        <div class="flex items-center gap-2">
                            @if ($g['joined'])
                                <button
                                    type="button"
                                    wire:click="ouvrirFormulaire({{ $g['id'] }})"
                                    class="btn-tap rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-[11.5px] font-bold text-azure hover:bg-azure/20"
                                >Ma fiche</button>
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

    <x-groupes.fiche-pro :fiche="$detail" :erreur="$avisErreur" />
</div>
