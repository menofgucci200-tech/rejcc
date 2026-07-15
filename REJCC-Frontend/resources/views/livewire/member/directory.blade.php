<div>
    <x-member-light.topbar title="Annuaire des membres" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Annuaire des membres</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('tous')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                @foreach ($profiles as $p)
                    <button wire:click="setFiltre('{{ $p['id'] }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $p['id'] ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $p['label'] }}</button>
                @endforeach
            </div>
        </div>

        <div class="relative mb-6 max-w-[420px]">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Rechercher (nom, domaine, ville)…"
                class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure"
            />
        </div>

        @if ($members->isEmpty())
            <p class="py-10 text-center text-sm text-[#5B677A]">Aucun membre trouvé.</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))" wire:key="dir-page-{{ $meta['current_page'] ?? 1 }}">
                @foreach ($members as $m)
                    <article class="card-hover rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-brand">{{ $m->prenom }} {{ $m->nom }}</p>
                                @if ($m->secteur)
                                    <p class="mt-0.5 truncate text-xs text-[#5B677A]">{{ $m->secteur }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($m->ville || $m->organisation)
                            <p class="mt-3 flex items-center gap-1.5 text-xs text-[#9AA6B8]">
                                <x-ui.icon name="map-pin" class="size-3" />
                                {{ collect([$m->ville, $m->organisation])->filter()->join(' · ') }}
                            </p>
                        @endif

                        <a
                            href="{{ route('espace-membre.messaging', ['to' => $m->id]) }}"
                            wire:navigate
                            class="btn-tap mt-3.5 flex items-center justify-center gap-1.5 rounded-[9px] border border-azure/25 bg-azure/10 py-2 text-[12.5px] font-semibold text-azure hover:bg-azure/20"
                        >
                            <x-ui.icon name="message-circle" class="size-[13px]" /> Envoyer un message
                        </a>
                    </article>
                @endforeach
            </div>

            <x-ui.pager :meta="$meta" />
        @endif
    </div>
</div>
