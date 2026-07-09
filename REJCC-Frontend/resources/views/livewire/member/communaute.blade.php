<div>
    <x-member-light.topbar title="Communauté" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Communauté</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex -space-x-2">
                    @foreach (['4F6FBF','AC0100','22A85A','F5A623'] as $color)
                        <span class="flex size-8 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold text-white" style="background: #{{ $color }}"></span>
                    @endforeach
                </div>
                <p class="text-xs text-[#5B677A]">32 membres en ligne</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-brand">Discussions récentes</h2>
                    <button class="rounded-full bg-brand px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand/90">Nouveau sujet</button>
                </div>
                <div class="space-y-3">
                    @foreach ($discussions as $d)
                        <article class="rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <p class="mb-1.5 text-[13.5px] font-bold leading-snug text-brand">{{ $d['titre'] }}</p>
                            <div class="flex items-center justify-between text-xs text-[#9AA6B8]">
                                <span>Par {{ $d['auteur'] }}</span>
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="message-circle" class="size-3.5" /> {{ $d['reponses'] }} réponses</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="mb-4 text-sm font-bold text-brand">Projets partagés</h2>
                <div class="space-y-3">
                    @foreach ($projets as $p)
                        <article class="rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <p class="mb-2 text-[13px] font-bold text-brand">{{ $p['titre'] }}</p>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-cloud px-2.5 py-1 text-[11px] font-semibold text-[#5B677A]">
                                <x-ui.icon name="network" class="size-3" /> {{ $p['statut'] }}
                            </span>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
