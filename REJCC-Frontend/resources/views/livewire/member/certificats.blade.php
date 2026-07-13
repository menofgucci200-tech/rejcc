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
                    <article class="card-hover overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="flex h-28 items-center justify-center" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">
                            <x-ui.icon name="award" class="size-10 text-white/90" />
                        </div>
                        <div class="p-4">
                            <p class="mb-1 text-[13.5px] font-bold leading-snug text-brand">{{ $c['titre'] }}</p>
                            <p class="text-xs text-[#9AA6B8]">Obtenu le {{ $c['date'] }}</p>
                            <p class="mb-4 mt-0.5 text-[10.5px] font-semibold tracking-wide text-[#9AA6B8]">{{ $c['reference'] }}</p>
                            <p class="rounded-[9px] bg-cloud px-3 py-2 text-center text-[11px] text-[#5B677A]">Présentez cette référence pour toute vérification</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
