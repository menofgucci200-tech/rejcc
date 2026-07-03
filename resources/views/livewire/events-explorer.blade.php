<div>
    <div class="flex flex-wrap gap-2">
        @foreach ($types as $t)
            <button
                wire:click="selectType('{{ $t }}')"
                class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $type === $t ? 'bg-brand text-white' : 'border border-brand/15 text-ink/70 hover:border-brand/30 hover:text-brand' }}"
            >
                {{ $t }}
            </button>
        @endforeach
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,360px)_1fr]">
        <div class="rounded-3xl border border-brand/10 bg-white p-6">
            <div class="flex items-center justify-between">
                <span class="font-display text-lg uppercase tracking-tight text-brand">{{ $monthLabel }}</span>
                <div class="flex gap-1">
                    <button wire:click="shiftMonth(-1)" aria-label="Mois précédent" class="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button wire:click="shiftMonth(1)" aria-label="Mois suivant" class="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>

            <div class="mt-5 grid gap-1 text-center text-xs font-semibold text-ink/40" style="grid-template-columns: repeat(7, minmax(0, 1fr))">
                @foreach ($weekdays as $w)
                    <span class="py-1">{{ $w }}</span>
                @endforeach
            </div>
            <div class="mt-1 grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr))">
                @foreach ($cells as $iso)
                    @if (!$iso)
                        <span></span>
                    @else
                        @php
                            $has = $eventDays->has($iso);
                            $day = (int) substr($iso, -2);
                            $active = $selectedDay === $iso;
                        @endphp
                        <button
                            @if ($has) wire:click="selectDay('{{ $iso }}')" @endif
                            @disabled(!$has)
                            class="relative flex aspect-square items-center justify-center rounded-xl text-sm transition-colors {{ $active ? 'bg-accent text-white' : ($has ? 'bg-brand/5 font-semibold text-brand hover:bg-brand hover:text-white' : 'text-ink/40') }}"
                        >
                            {{ $day }}
                            @if ($has && !$active)
                                <span class="absolute bottom-1 size-1 rounded-full bg-accent"></span>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>

            @if ($selectedDay)
                <button wire:click="resetDay" class="mt-4 text-xs font-semibold text-accent hover:underline">Réinitialiser le jour sélectionné</button>
            @endif
        </div>

        <div>
            @if ($agenda->isEmpty())
                <div class="flex h-full min-h-48 items-center justify-center rounded-3xl border border-dashed border-brand/15 text-center text-ink/55">
                    Aucun événement {{ $selectedDay ? 'ce jour' : 'ce mois-ci' }}. Naviguez entre les mois pour en découvrir d'autres.
                </div>
            @else
                <div class="flex flex-col gap-4">
                    @foreach ($agenda as $e)
                        <a href="/evenements/{{ $e->slug }}" wire:navigate class="group flex items-stretch gap-5 rounded-3xl border border-brand/10 bg-white p-5 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]">
                            <div class="flex w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand py-4 text-white">
                                <span class="font-display text-3xl leading-none">{{ $e->starts_at->format('d') }}</span>
                                <span class="mt-1 text-xs uppercase tracking-wider text-white/70">{{ $e->starts_at->locale('fr')->translatedFormat('M') }}</span>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col justify-center">
                                <span class="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">{{ $e->category }}</span>
                                <h3 class="mt-2 text-lg font-bold text-brand">{{ $e->title }}</h3>
                                <p class="mt-1 line-clamp-1 text-sm text-ink/65">{{ $e->excerpt }}</p>
                                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink/55">
                                    <span class="flex items-center gap-1.5">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $e->location }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                        {{ $e->time_label ?? $e->starts_at->format('H:i') }}
                                    </span>
                                </p>
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 self-center text-brand/20 transition-all duration-500 group-hover:translate-x-1 group-hover:text-accent"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
