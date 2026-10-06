<div>
    <x-member-light.topbar :title="$groupe['name'] ?? 'Membres du groupe'" />

    @if ($locked ?? false)
        {{-- Aperçu pour les non-abonnés : chiffres réels, services proposés, cartes floutées --}}
        @php $couleur = $groupe['couleur'] ?? '#031D59'; @endphp
        <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8" data-test="apercu-groupe">
            <div class="mb-2">
                <a href="{{ route('espace-membre.groupes') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                    <x-ui.icon name="arrow-left" class="size-3.5" /> Tous les groupes
                </a>
            </div>
            <div class="mb-5 flex items-start gap-3.5">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px]" style="background: {{ $couleur }}1A; color: {{ $couleur }}">
                    <x-ui.icon :name="$groupe['icone'] ?? 'network'" class="size-6" />
                </span>
                <div class="min-w-0">
                    <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $groupe['name'] ?? 'Groupe' }}</h1>
                    <div class="h-[3px] w-9 rounded" style="background: {{ $couleur }}"></div>
                    @if ($groupe['description'] ?? null)
                        <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $groupe['description'] }}</p>
                    @endif
                </div>
            </div>

            @if ($apercu)
                <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                    @foreach ([
                        [$apercu['membres'] > 1 ? 'Professionnels' : 'Professionnel', $apercu['membres'], 'users'],
                        [$apercu['villes'] > 1 ? 'Villes' : 'Ville', $apercu['villes'], 'map-pin'],
                        ['Avis des membres', $apercu['avis'], 'star'],
                        ['Note moyenne', $apercu['note_moyenne'] !== null ? number_format($apercu['note_moyenne'], 1, ',', ' ').'/5' : '—', 'award'],
                    ] as [$label, $val, $icon])
                        <div class="flex items-center gap-3 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <span class="flex size-10 items-center justify-center rounded-xl" style="background: {{ $couleur }}1A; color: {{ $couleur }}"><x-ui.icon :name="$icon" class="size-5" /></span>
                            <div><p class="text-[20px] font-extrabold leading-none text-brand">{{ $val }}</p><p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $label }}</p></div>
                        </div>
                    @endforeach
                </div>
                @if (! empty($apercu['services']))
                    <div class="mb-5">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Services proposés dans ce groupe</p>
                        <div class="flex flex-wrap gap-1.5" data-test="apercu-services">
                            @foreach ($apercu['services'] as $service)
                                <span class="rounded-full border border-brand/10 bg-white px-3 py-1 text-[12px] font-semibold text-brand">{{ $service }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="relative overflow-hidden rounded-[20px]">
                <div aria-hidden="true" class="pointer-events-none grid select-none gap-4 blur-[6px]" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))">
                    @foreach (array_slice(array_merge($apercu['services'] ?? [], ['Conseil', 'Devis gratuit', 'Intervention rapide', 'Accompagnement', 'Formation', 'Suivi']), 0, 8) as $i => $deco)
                        <div class="rounded-[16px] border border-brand/10 bg-white p-[18px]">
                            <div class="flex items-center gap-3">
                                <span class="size-12 rounded-xl" style="background: linear-gradient(135deg, {{ $i % 3 === 0 ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})"></span>
                                <div class="flex-1 space-y-1.5">
                                    <span class="block h-3 w-3/4 rounded bg-brand/20"></span>
                                    <span class="block text-[10.5px] font-bold text-[#B7790F]">★ ★ ★ ★ ★</span>
                                </div>
                            </div>
                            <span class="mt-3 block h-12 rounded-[10px] bg-cloud"></span>
                            <div class="mt-2.5 flex gap-1.5"><span class="rounded-full bg-brand/[.05] px-2 py-0.5 text-[11px] font-semibold text-brand">{{ $deco }}</span><span class="h-5 w-14 rounded-full bg-brand/10"></span></div>
                            <span class="mt-4 block h-8 rounded-[9px] bg-azure/10"></span>
                        </div>
                    @endforeach
                </div>
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-b from-white/30 via-white/70 to-white/90 p-6">
                    <div class="max-w-[460px] rounded-[20px] border border-brand/10 bg-white p-7 text-center shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-[#F5A623]/10 text-[#B27007]"><x-ui.icon name="lock" class="size-6" /></span>
                        <h2 class="mt-4 text-[16px] font-extrabold text-brand">
                            {{ ($apercu['membres'] ?? 0) > 1 ? $apercu['membres'].' professionnels dans ce groupe' : (($apercu['membres'] ?? 0) === 1 ? '1 professionnel dans ce groupe' : 'Soyez parmi les premiers') }}
                        </h2>
                        <p class="mt-2 text-[13px] leading-relaxed text-[#5B677A]">Consultez leurs fiches (services, zone d'intervention, disponibilités), les avis des membres et contactez-les directement. Les fiches des groupes sont réservées aux membres à jour de leur abonnement annuel (10&nbsp;000&nbsp;F).</p>
                        <a href="{{ route('espace-membre.abonnement') }}" wire:navigate data-test="debloquer-groupe" class="btn-tap mt-5 inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">
                            <x-ui.icon name="shield-check" class="size-4" /> Débloquer les fiches
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.groupes') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Tous les groupes
            </a>
        </div>
        @php $couleur = $groupe['couleur'] ?? '#031D59'; @endphp
        <div class="mb-5 flex items-start gap-3.5">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px]" style="background: {{ $couleur }}1A; color: {{ $couleur }}">
                <x-ui.icon :name="$groupe['icone'] ?? 'network'" class="size-6" />
            </span>
            <div class="min-w-0">
                <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $groupe['name'] ?? 'Groupe' }}</h1>
                <div class="h-[3px] w-9 rounded" style="background: {{ $couleur }}"></div>
                @if ($groupe['description'] ?? null)
                    <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $groupe['description'] }}</p>
                @endif
            </div>
        </div>

        @if (($groupe['annonce'] ?? null) || ($groupe['referent'] ?? null))
            <div class="mb-6 grid gap-3 lg:grid-cols-[1fr_auto]">
                @if ($groupe['annonce'] ?? null)
                    <div data-test="annonce-epinglee" class="flex items-start gap-3 rounded-[14px] border border-[#F5A623]/30 bg-[#F5A623]/10 p-4">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/20 text-[#8A5A08]"><x-ui.icon name="pin" class="size-4" /></span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#8A5A08]">Annonce du groupe @if ($groupe['annonce_at'] ?? null)<span class="font-semibold normal-case tracking-normal text-[#8A5A08]/70">· {{ \Illuminate\Support\Carbon::parse($groupe['annonce_at'])->locale('fr')->isoFormat('D MMMM YYYY') }}</span>@endif</p>
                            <p class="mt-0.5 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $groupe['annonce'] }}</p>
                        </div>
                    </div>
                @else
                    <div class="hidden lg:block"></div>
                @endif
                <div class="flex flex-wrap items-stretch gap-3">
                    @if ($groupe['referent'] ?? null)
                        @php $r = $groupe['referent']; @endphp
                        <button type="button" wire:click="voirProfil({{ $r['id'] }})" data-test="referent" class="flex items-center gap-2.5 rounded-[14px] border border-brand/10 bg-white px-3.5 py-2.5 text-left hover:border-brand/30">
                            <span x-data="{ erreur: false }" class="relative shrink-0">
                                @if ($r['photo'])
                                    <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $r['photo'] }}" alt="" class="size-9 rounded-full object-cover">
                                @endif
                                <span @if ($r['photo']) x-show="erreur" style="display: none; background: linear-gradient(135deg, #4F6FBF, #AC0100)" @else style="background: linear-gradient(135deg, #4F6FBF, #AC0100)" @endif class="flex size-9 items-center justify-center rounded-full text-[11px] font-bold text-white">{{ mb_strtoupper(mb_substr($r['prenom'], 0, 1).mb_substr($r['nom'], 0, 1)) }}</span>
                            </span>
                            <span>
                                <span class="block text-[10.5px] font-bold uppercase tracking-[0.1em]" style="color: {{ $couleur }}">Référent du groupe</span>
                                <span class="block text-[13px] font-bold text-brand">{{ $r['prenom'] }} {{ $r['nom'] }}</span>
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Onglets : membres du groupe | discussion du groupe (sur la plateforme) --}}
        <div class="mb-5 flex gap-1 border-b border-brand/10" role="tablist">
            <button type="button" role="tab" wire:click="$set('vue', 'membres')" data-test="onglet-membres" class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-[13px] font-bold transition-colors {{ $vue === 'membres' ? 'border-accent text-brand' : 'border-transparent text-[#9AA6B8] hover:text-brand' }}">
                <x-ui.icon name="users" class="size-4" /> Membres
            </button>
            <button type="button" role="tab" wire:click="$set('vue', 'discussion')" data-test="onglet-discussion" class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-[13px] font-bold transition-colors {{ $vue === 'discussion' ? 'border-accent text-brand' : 'border-transparent text-[#9AA6B8] hover:text-brand' }}">
                <x-ui.icon name="message-circle" class="size-4" /> Discussion
                @if ($vue !== 'discussion' && ($groupe['discussion']['non_lus'] ?? 0) > 0)
                    <span data-test="discussion-non-lus" class="rounded-full bg-accent px-1.5 text-[10.5px] font-bold leading-4 text-white">{{ ($groupe['discussion']['non_lus'] ?? 0) }}</span>
                @endif
            </button>
            @if (($nbProjets ?? 0) > 0)
                <a href="{{ route('espace-membre.projets', ['groupe' => $groupId]) }}" wire:navigate data-test="projets-du-groupe" class="-mb-px inline-flex items-center gap-1.5 border-b-2 border-transparent px-4 py-2.5 text-[13px] font-bold text-[#9AA6B8] transition-colors hover:text-brand">
                    <x-ui.icon name="nav-projects" class="size-4" /> Projets <span class="rounded-full bg-brand/10 px-1.5 text-[10.5px] leading-4 text-brand">{{ $nbProjets }}</span>
                </a>
            @endif
            @if (($nbOffres ?? 0) > 0)
                <a href="{{ route('espace-membre.emplois', ['groupe' => $groupId]) }}" wire:navigate data-test="offres-du-groupe" class="-mb-px inline-flex items-center gap-1.5 border-b-2 border-transparent px-4 py-2.5 text-[13px] font-bold text-[#9AA6B8] transition-colors hover:text-brand">
                    <x-ui.icon name="nav-briefcase" class="size-4" /> Offres <span class="rounded-full bg-brand/10 px-1.5 text-[10.5px] leading-4 text-brand">{{ $nbOffres }}</span>
                </a>
            @endif
        </div>

        @if ($vue === 'discussion')
            @include('livewire.member.partials.discussion-groupe')
        @else
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1 basis-[280px] sm:max-w-[460px]">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
                    <input
                        wire:model.live.debounce.300ms="query"
                        type="search"
                        data-test="recherche-groupe"
                        aria-label="Rechercher dans le groupe"
                        placeholder="Métier, service, quartier, nom… ex : plombier Cocody"
                        class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
                    />
                </div>
                <label class="flex items-center gap-2 text-[12.5px] font-semibold text-[#5B677A]">
                    Trier
                    <select wire:model.live="tri" data-test="tri-groupe" class="rounded-xl border border-brand/10 bg-white py-2.5 pl-3 pr-8 text-[13px] font-semibold text-brand outline-none focus:border-azure">
                        <option value="nom">Par nom</option>
                        <option value="note">Les mieux notés</option>
                        <option value="recents">Arrivés récemment</option>
                    </select>
                </label>
            </div>

            <p data-test="nb-membres-groupe" class="mb-3 text-[12.5px] font-semibold text-[#5B677A]">{{ $meta['total'] ?? $members->count() }} membre{{ ($meta['total'] ?? $members->count()) > 1 ? 's' : '' }}{{ trim($query) !== '' ? ' pour « '.trim($query).' »' : '' }}</p>

            @if ($members->isEmpty())
                <p class="py-10 text-center text-sm text-[#5B677A]">{{ trim($query) !== '' ? 'Aucun membre ne correspond à votre recherche.' : 'Aucun membre dans ce groupe pour le moment.' }}</p>
            @else
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))" wire:key="roster-page-{{ $meta['current_page'] ?? 1 }}">
                    @foreach ($members as $m)
                        <x-groupes.carte-membre :m="$m" wire:key="gm-{{ $m['id'] }}" wire:click="voirProfil({{ $m['id'] }})" />
                    @endforeach
                </div>

                <x-ui.pager :meta="$meta" />
            @endif
        @endif
    </div>

    <x-groupes.fiche-pro :fiche="$detail" :erreur="$avisErreur" />
    @endif
</div>
