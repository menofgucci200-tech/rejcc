<div>
    <x-member-light.topbar title="Mentorat" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Mentorat</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <button class="rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Devenir mentor</button>
        </div>

        <h2 class="mb-4 text-sm font-bold text-brand">Mentors disponibles</h2>
        <div class="mb-8 grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
            @foreach ($mentors as $m)
                <article class="rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="relative flex size-12 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ collect(explode(' ', $m['nom']))->map(fn ($w) => mb_substr($w, 0, 1))->slice(-2)->implode('') }}
                            @if ($m['online'])
                                <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white bg-[#22A85A]"></span>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-brand">{{ $m['nom'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-azure">{{ $m['specialite'] }}</p>
                        </div>
                    </div>
                    <p class="mb-3 text-xs leading-relaxed text-[#5B677A]">{{ $m['bio'] }}</p>
                    <div class="mb-4 flex items-center gap-4 text-xs text-[#9AA6B8]">
                        <span class="inline-flex items-center gap-1"><x-ui.icon name="star" class="size-3.5 text-[#F5A623]" /> {{ $m['note'] }}</span>
                        <span class="inline-flex items-center gap-1"><x-ui.icon name="users" class="size-3.5" /> {{ $m['mentores'] }} mentorés</span>
                    </div>
                    <button class="w-full rounded-[9px] border border-azure/25 bg-azure/10 py-2 text-[12.5px] font-semibold text-azure">Demander un mentorat</button>
                </article>
            @endforeach
        </div>

        <h2 class="mb-4 text-sm font-bold text-brand">Historique de mes sessions</h2>
        <div class="space-y-3">
            @foreach ($historique as $h)
                <article class="flex flex-wrap items-start gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-cloud text-brand">
                        <span class="text-sm font-bold leading-none">{{ $h['jour'] }}</span>
                        <span class="text-[10px] font-semibold uppercase leading-none">{{ $h['mois'] }}</span>
                    </div>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13px] font-bold text-brand">{{ $h['sujet'] }}</p>
                        <p class="mt-0.5 text-xs text-[#9AA6B8]">Avec {{ $h['mentor'] }}</p>
                        <p class="mt-2 text-xs text-[#5B677A]"><span class="font-semibold text-brand">Prochaine étape :</span> {{ $h['prochaineEtape'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
