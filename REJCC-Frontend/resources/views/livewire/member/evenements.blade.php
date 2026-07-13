<div>
    <x-member-light.topbar title="Événements" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Événements</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold text-brand">À venir</h2>
                <div class="space-y-3">
                    @forelse ($evenements as $e)
                        <article class="card-hover flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-cloud text-brand">
                                <span class="text-sm font-bold leading-none">{{ $e['jour'] }}</span>
                            </div>
                            <div class="min-w-[180px] flex-1">
                                <span class="mb-1 inline-block rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $e['tagColor'] }}1A; color: {{ $e['tagColor'] }}">{{ $e['tag'] }}</span>
                                <p class="text-[13.5px] font-bold text-brand">{{ $e['titre'] }}</p>
                                <p class="mt-0.5 text-xs text-[#9AA6B8]">{{ $e['date'] }} · {{ $e['detail'] }}</p>
                            </div>
                            @if ($e['inscrit'])
                                <button wire:click="toggleInscription({{ $e['id'] }})" wire:confirm="Annuler votre inscription à « {{ $e['titre'] }} » ?" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                                    <x-ui.icon name="check" class="size-3.5" /> Inscrit
                                </button>
                            @else
                                <button wire:click="toggleInscription({{ $e['id'] }})" wire:loading.attr="disabled" class="btn-tap shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20 disabled:opacity-60">S'inscrire</button>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucun événement à venir pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="mb-3 text-center text-[13px] font-bold text-brand">{{ $moisLabel }}</p>
                    <div class="mb-2 grid grid-cols-7 gap-1 text-center text-[10px] font-semibold text-[#9AA6B8]">
                        <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        @foreach ($cells as $day)
                            @if ($day === null)
                                <span></span>
                            @else
                                <span
                                    class="relative flex size-8 items-center justify-center rounded-full text-[11.5px] font-semibold
                                        {{ $day === $today ? 'bg-brand text-white' : 'text-[#5B677A]' }}"
                                >
                                    {{ $day }}
                                    @if (in_array($day, $eventDays, true) && $day !== $today)
                                        <span class="absolute bottom-0.5 size-1 rounded-full bg-accent"></span>
                                    @endif
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
