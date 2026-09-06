<div>
    <x-member-light.topbar title="Parcours guidés" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Parcours guidés</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">Des séquences de formations vers un objectif précis. Terminez chaque formation dans l'ordre pour débloquer la suivante et décrocher le badge du parcours.</p>
        </div>

        @if ($paths->isEmpty())
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucun parcours disponible pour le moment.</p>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
                @foreach ($paths as $p)
                    <a href="{{ route('espace-membre.parcours.detail', $p['id']) }}" wire:navigate class="card-hover flex flex-col rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px]" style="background: {{ $p['badge_couleur'] ?? '#4F6FBF' }}1A; color: {{ $p['badge_couleur'] ?? '#4F6FBF' }}">
                                <x-ui.icon :name="$p['badge_icon'] ?: 'rocket'" class="size-5" />
                            </span>
                            @if ($p['badge_obtenu'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[10.5px] font-bold text-[#1C8F4C]">
                                    <x-ui.icon name="award" class="size-3" /> Badge obtenu
                                </span>
                            @endif
                        </div>
                        <p class="text-[14px] font-bold leading-snug text-brand">{{ $p['title'] }}</p>
                        @if ($p['objectif'])
                            <p class="mt-1.5 flex-1 text-[12px] leading-relaxed text-[#5B677A]">{{ $p['objectif'] }}</p>
                        @endif
                        <div class="mt-4">
                            <div class="h-1.5 w-full rounded-full bg-cloud">
                                <div class="h-1.5 rounded-full {{ $p['badge_obtenu'] ? 'bg-[#22A85A]' : 'bg-azure' }}" style="width: {{ $p['pct'] }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[11.5px] font-semibold text-[#5B677A]">{{ $p['formations_terminees'] }}/{{ $p['total_formations'] }} formations terminées</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
