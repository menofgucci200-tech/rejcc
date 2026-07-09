<div>
    <x-admin-light.topbar title="Communauté" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Signalements à traiter</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($signalements as $s)
                    <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="size-2 shrink-0 rounded-full bg-accent"></span>
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $s['titre'] }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">Motif : {{ $s['motif'] }} · signalé par {{ $s['auteur'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $s['statutColor'] }}; background: {{ $s['statutBg'] }}">{{ $s['statutLabel'] }}</span>
                        @if ($s['enAttente'])
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="masquer({{ $s['index'] }})" class="rounded-lg bg-accent px-3.5 py-1.5 text-xs font-bold text-white hover:bg-accent-600">Masquer</button>
                                <button wire:click="ignorer({{ $s['index'] }})" class="rounded-lg border border-[#C9D3E6] px-3.5 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Ignorer</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Discussions populaires</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($discussions as $d)
                    <div class="flex items-center justify-between gap-3 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <div>
                            <p class="text-[13.5px] font-semibold text-ink">{{ $d['titre'] }}</p>
                            <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">Par {{ $d['auteur'] }} · {{ $d['reponses'] }} réponses</p>
                        </div>
                        <button class="shrink-0 rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Épingler</button>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
