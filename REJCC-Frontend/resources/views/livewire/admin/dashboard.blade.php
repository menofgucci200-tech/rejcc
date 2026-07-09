<div>
    <x-admin-light.topbar title="Vue d'ensemble" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-6">
            <h1 class="mb-1.5 text-[26px] font-extrabold tracking-tight text-brand">Bonjour, {{ \App\Support\Api::user()->prenom }} 👋</h1>
            <p class="text-sm text-[#5B677A]">Voici l'état du réseau aujourd'hui, {{ now()->translatedFormat('d F Y') }}.</p>
        </section>

        <section class="mb-7 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            @foreach ($cards as $c)
                <div class="rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="text-xs font-semibold text-[#5B677A]">{{ $c['label'] }}</p>
                    <p class="mt-1.5 text-[26px] font-extrabold text-brand">{{ $c['value'] }}</p>
                    <p class="mt-1 text-[11.5px] font-bold" style="color: {{ $c['subColor'] }}">{{ $c['sub'] }}</p>
                </div>
            @endforeach
        </section>

        <div class="mb-7 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <section>
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Évolution du réseau</h2>
                        <div class="h-[3px] w-9 rounded bg-accent"></div>
                    </div>
                    <div class="flex gap-1.5">
                        @foreach ([3, 6, 12] as $p)
                            <button wire:click="setPeriode({{ $p }})" class="rounded-full border px-3 py-1.5 text-[11.5px] font-semibold {{ $periode === $p ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $p }} mois</button>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex h-40 items-end gap-3">
                        @foreach ($mois as $m)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[11px] font-bold text-[#5B677A]">{{ $m['valeur'] }}</span>
                                <div class="w-full max-w-[34px] rounded-t-lg rounded-b-[3px]" style="height: {{ $m['h'] }}px; background: linear-gradient(180deg,#4F6FBF,#031D59)"></div>
                                <span class="text-[10.5px] text-[#5B677A]">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">En attente d'action</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-4.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($enAttente as $a)
                        <a href="{{ route($a['route']) }}" wire:navigate class="flex items-center gap-3 border-t border-[#EDF0F5] py-3 first:border-t-0">
                            <span class="size-2 shrink-0 rounded-full" style="background: {{ $a['dot'] }}"></span>
                            <span class="min-w-0 flex-1 text-[13px] font-semibold text-ink">{{ $a['texte'] }}</span>
                            <span class="shrink-0 text-[11.5px] text-[#9AA6B8]">→</span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm text-[#5B677A]">Rien à traiter pour le moment.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Répartition des membres par parcours</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($parcoursRepartition as $p)
                    <div class="flex items-center gap-3.5 py-2">
                        <span class="w-[170px] shrink-0 text-[12.5px] text-ink">{{ $p['nom'] }}</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-[#EDF0F5]">
                            <div class="h-full rounded-full" style="width: {{ $p['pct'] }}%; background: {{ $p['color'] }}"></div>
                        </div>
                        <span class="w-[70px] shrink-0 text-right text-xs font-bold text-brand">{{ $p['membres'] }} membres</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
