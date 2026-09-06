<div>
    <x-member-light.topbar title="Trombinoscope" />

    @if ($locked ?? false)
        <x-member-light.paywall description="La liste des membres d'un groupe sectoriel (fiche complète, spécialité, contact) est réservée aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.groupes') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Tous les groupes
            </a>
        </div>
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $groupe['name'] ?? 'Groupe' }}</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            @if ($groupe['description'] ?? null)
                <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $groupe['description'] }}</p>
            @endif
        </div>

        <div class="relative mb-6 max-w-[420px]">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Rechercher (nom, ville, spécialité)…"
                class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
            />
        </div>

        @if ($members->isEmpty())
            <p class="py-10 text-center text-sm text-[#5B677A]">Aucun membre dans ce groupe pour le moment.</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))" wire:key="roster-page-{{ $meta['current_page'] ?? 1 }}">
                @foreach ($members as $m)
                    <article
                        wire:click="voirProfil({{ $m['id'] }}, {{ \Illuminate\Support\Js::from($m['specialite']) }})"
                        class="card-hover cursor-pointer rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                {{ mb_substr($m['prenom'], 0, 1) }}{{ mb_substr($m['nom'], 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-brand">{{ $m['prenom'] }} {{ $m['nom'] }}</p>
                                @if ($m['ville'] || $m['organisation'])
                                    <p class="mt-0.5 truncate text-xs text-[#5B677A]">{{ collect([$m['ville'], $m['organisation']])->filter()->join(' · ') }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($m['specialite'])
                            <p class="mt-3 line-clamp-2 rounded-[10px] bg-cloud/60 px-3 py-2 text-[12px] italic leading-relaxed text-ink">« {{ $m['specialite'] }} »</p>
                        @endif
                    </article>
                @endforeach
            </div>

            <x-ui.pager :meta="$meta" />
        @endif
    </div>

    <x-member-light.profile-modal :member="$detail" />
    @endif
</div>
