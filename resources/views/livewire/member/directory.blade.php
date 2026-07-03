<x-member.dark-page title="Annuaire des membres" subtitle="Retrouvez et contactez les membres du réseau." icon="users">
    <div class="relative mb-7 max-w-[420px]">
        <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[var(--ms-dim)]" />
        <input
            wire:model.live.debounce.300ms="query"
            type="text"
            placeholder="Rechercher (nom, domaine, ville)…"
            class="w-full rounded-xl border border-[var(--ms-bc2)] bg-[var(--ms-surf)] py-2.5 pl-10 pr-4 text-[13.5px] text-[var(--ms-text)] outline-none"
        />
    </div>

    @if ($members->isEmpty())
        <p class="py-10 text-center text-sm text-[var(--ms-muted)]">Aucun membre trouvé.</p>
    @else
        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($members as $m)
                <article class="rounded-[18px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-[22px] py-5">
                    <div class="flex items-center gap-3.5">
                        <span class="flex size-[46px] shrink-0 items-center justify-center rounded-full text-sm font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <p class="m-0 truncate text-sm font-bold text-[var(--ms-text)]">{{ $m->prenom }} {{ $m->nom }}</p>
                            @if ($m->secteur)
                                <p class="m-0 mt-0.5 truncate text-[12.5px] text-[var(--ms-muted)]">{{ $m->secteur }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($m->ville || $m->organisation)
                        <p class="mt-3.5 flex items-center gap-1.5 text-xs text-[var(--ms-dim)]">
                            <x-ui.icon name="map-pin" class="size-3" />
                            {{ collect([$m->ville, $m->organisation])->filter()->join(' · ') }}
                        </p>
                    @endif

                    <a
                        href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}"
                        wire:navigate
                        class="mt-4 flex items-center justify-center gap-1.5 rounded-[10px] py-2.5 text-[13px] font-semibold transition-all"
                        style="background: rgba(79,111,191,0.14); border: 1px solid rgba(79,111,191,0.24); color: #9DB2EE;"
                    >
                        <x-ui.icon name="message-circle" class="size-[13px]" /> Envoyer un message
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</x-member.dark-page>
