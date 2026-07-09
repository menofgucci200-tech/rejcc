<div>
    <x-member-light.topbar title="Certificats" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Mes certificats</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        @if (empty($certificats))
            <p class="py-10 text-center text-sm text-[#5B677A]">Aucun certificat obtenu pour le moment.</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
                @foreach ($certificats as $c)
                    <article class="overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="flex h-28 items-center justify-center" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">
                            <x-ui.icon name="award" class="size-10 text-white/90" />
                        </div>
                        <div class="p-4">
                            <p class="mb-1 text-[13.5px] font-bold leading-snug text-brand">{{ $c['titre'] }}</p>
                            <p class="mb-4 text-xs text-[#9AA6B8]">Obtenu le {{ $c['date'] }}</p>
                            <button class="flex w-full items-center justify-center gap-1.5 rounded-[9px] border border-azure/25 bg-azure/10 py-2 text-[12.5px] font-semibold text-azure">
                                <x-ui.icon name="download" class="size-3.5" /> Télécharger
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
