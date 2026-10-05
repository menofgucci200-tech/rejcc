<div>
    <x-member-light.topbar :title="$groupe['name'] ?? 'Membres du groupe'" />

    @if ($locked ?? false)
        <x-member-light.paywall description="La liste des membres d'un groupe sectoriel (fiche complète, spécialité, contact) est réservée aux membres à jour de leur abonnement annuel (10 000 F)." />
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

        @if (($groupe['annonce'] ?? null) || ($groupe['referent'] ?? null) || ($groupe['whatsapp'] ?? null))
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
                    @if (($groupe['whatsapp'] ?? null) === 'verrouille')
                        <div data-test="whatsapp-verrouille" class="flex max-w-[280px] items-center gap-2.5 rounded-[14px] border border-dashed border-brand/15 bg-white px-3.5 py-2.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-cloud text-[#9AA6B8]"><x-ui.icon name="lock" class="size-4" /></span>
                            <span class="text-[12px] leading-snug text-[#5B677A]"><span class="font-bold text-brand">Groupe WhatsApp</span><br>Réservé aux membres abonnés de ce groupe.</span>
                        </div>
                    @elseif ($groupe['whatsapp'] ?? null)
                        <a href="{{ $groupe['whatsapp'] }}" target="_blank" rel="noopener" data-test="whatsapp" class="btn-tap flex items-center gap-2.5 rounded-[14px] bg-[#25D366] px-4 py-2.5 text-white hover:bg-[#1EBE5A]">
                            <x-ui.icon name="message-circle" class="size-5" />
                            <span class="text-[13px] font-bold leading-tight">Rejoindre le<br>groupe WhatsApp</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

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
    </div>

    <x-groupes.fiche-pro :fiche="$detail" :erreur="$avisErreur" />
    @endif
</div>
