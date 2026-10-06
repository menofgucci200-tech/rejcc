<div>
    <x-member-light.topbar title="Événements" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Événements</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        @if ($message)
            <p data-test="message-evenements" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $fiche)
            <p data-test="erreur-evenements" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3.5 py-1.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="size-3.5" /> {{ $erreur }}</p>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold text-brand">À venir</h2>
                <div class="space-y-3">
                    @forelse ($evenements as $e)
                        <article wire:key="ev-{{ $e['id'] }}" wire:click="voir({{ $e['id'] }})" data-test="carte-evenement"
                            class="card-hover flex cursor-pointer flex-wrap items-center gap-4 rounded-[16px] border bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $focus === $e['id'] ? 'border-azure ring-2 ring-azure/30' : 'border-brand/10' }}">
                            <div class="flex size-14 shrink-0 flex-col items-center justify-center rounded-xl text-white" style="background: linear-gradient(135deg, #031D59, {{ $e['couleur'] }})">
                                <span class="text-[17px] font-extrabold leading-none">{{ $e['debut']->day }}</span>
                                <span class="mt-0.5 text-[9.5px] font-bold uppercase tracking-[0.08em] text-white/80">{{ $e['debut']->isoFormat('MMM') }}</span>
                            </div>
                            <div class="min-w-[180px] flex-1">
                                <div class="mb-1 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $e['couleur'] }}1A; color: {{ $e['couleur'] }}">{{ $e['category'] }}</span>
                                    @if ($e['statut'] === 'annule')<span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-white">Annulé</span>@endif
                                    @if ($e['reserve_abonnes'])<span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[10px] font-bold text-[#B27007]">Abonnés</span>@endif
                                    @if ($e['en_ligne'])<span class="rounded-full bg-azure/10 px-2 py-0.5 text-[10px] font-bold text-azure">En ligne</span>@endif
                                </div>
                                <p class="text-[13.5px] font-bold text-brand">{{ $e['title'] }}</p>
                                <p class="mt-0.5 text-xs text-[#9AA6B8]">{{ ucfirst($e['debut']->isoFormat('dddd D MMMM')) }} · {{ $e['time_label'] ?: $e['debut']->format('H\hi') }}{{ $e['en_ligne'] ? ' · En ligne' : ($e['location'] ? ' · '.$e['location'] : '') }}</p>
                                @if ($e['capacity'] !== null && ! $e['registered'])
                                    <p class="mt-0.5 text-[11.5px] font-semibold {{ $e['complet'] ? 'text-accent' : 'text-[#5B677A]' }}">{{ $e['complet'] ? 'Complet' : $e['places_restantes'].' place'.($e['places_restantes'] > 1 ? 's' : '').' restante'.($e['places_restantes'] > 1 ? 's' : '') }}</p>
                                @endif
                            </div>
                            @if ($e['registered'])
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check" class="size-3.5" /> Inscrit</span>
                            @elseif ($e['refus'])
                                <span class="shrink-0 rounded-full bg-cloud px-3.5 py-1.5 text-xs font-semibold text-[#9AA6B8]">{{ $e['complet'] ? 'Complet' : ($e['statut'] === 'annule' ? 'Annulé' : 'Fermé') }}</span>
                            @else
                                <button wire:click.stop="inscrire({{ $e['id'] }})" wire:loading.attr="disabled" data-test="inscrire" class="btn-tap shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20 disabled:opacity-60">S'inscrire</button>
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

    <x-evenements.fiche :fiche="$fiche" :erreur="$erreur" />
</div>
