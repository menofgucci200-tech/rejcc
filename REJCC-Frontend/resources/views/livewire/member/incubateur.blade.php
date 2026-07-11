<div>
    <x-member-light.topbar title="Incubateur" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Incubateur REJCC</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Un accompagnement complet — mentorat, financement et mise en réseau — pour faire grandir votre projet.</p>
            </div>
            <a href="{{ route('espace-membre.projets') }}" wire:navigate class="rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">Soumettre un projet</a>
        </div>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))">
            @foreach ($projets as $p)
                @php $pct = $p['objectif'] > 0 ? min(100, round($p['leve'] / $p['objectif'] * 100)) : 0; @endphp
                <article class="rounded-[16px] border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                            <x-ui.icon name="nav-incubator" class="size-5" />
                        </span>
                        <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $p['statutColor'] }}1A; color: {{ $p['statutColor'] }}">{{ $p['statut'] }}</span>
                    </div>
                    <p class="mb-3 text-[14px] font-bold text-brand">{{ $p['titre'] }}</p>

                    <div class="mb-1 h-1.5 w-full rounded-full bg-cloud">
                        <div class="h-1.5 rounded-full bg-[#22A85A]" style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="mb-4 text-[11px] font-semibold text-[#5B677A]">
                        {{ number_format($p['leve'], 0, ',', ' ') }} FCFA levés sur {{ number_format($p['objectif'], 0, ',', ' ') }} FCFA ({{ $pct }}%)
                    </p>

                    <p class="mb-2 text-[11.5px] font-bold uppercase tracking-[0.04em] text-brand">Jalons</p>
                    <ul class="space-y-1.5">
                        @foreach ($p['jalons'] as $j)
                            <li class="flex items-center gap-2 text-xs {{ $j['fait'] ? 'text-brand' : 'text-[#9AA6B8]' }}">
                                <x-ui.icon :name="$j['fait'] ? 'check-circle' : 'circle-dot'" class="size-3.5 {{ $j['fait'] ? 'text-[#22A85A]' : 'text-[#9AA6B8]' }}" />
                                {{ $j['label'] }}
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
        @if (empty($projets))
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucun projet incubé pour le moment. Proposez le vôtre depuis la page Projets !</p>
        @endif
    </div>
</div>
