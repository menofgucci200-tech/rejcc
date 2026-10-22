<div>
    <x-admin-light.topbar :title="$departement['label']" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        @if ($adminGeneral)
            <a href="{{ route('departements.index') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#5B677A] hover:text-brand"><x-ui.icon name="chevron-right" class="size-3.5 rotate-180" /> Tous les départements</a>
        @endif

        <section class="mb-8 rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-accent">Département</p>
            <h1 class="mt-1 text-[24px] font-bold text-brand">{{ $departement['label'] }}</h1>
            <p class="mt-2 max-w-3xl text-[14px] leading-relaxed text-[#5B677A]">{{ $departement['mission'] }}</p>
        </section>

        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Vos rubriques</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            @if ($rubriques->isEmpty())
                <p class="rounded-[18px] border border-brand/10 bg-white px-5 py-10 text-center text-sm text-[#5B677A]">Aucune rubrique n'est encore ouverte à ce compte. L'administrateur général peut les ajouter.</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rubriques as $r)
                        @php $n = $pastilles[$r['route']] ?? 0; @endphp
                        <a href="{{ route($r['route']) }}" wire:navigate data-test="rubrique-{{ \Illuminate\Support\Str::slug($r['label']) }}"
                            class="group flex items-center gap-3 rounded-[14px] border border-brand/10 bg-white px-4 py-3.5 transition-colors hover:border-azure/40">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-cloud text-brand group-hover:text-azure"><x-ui.icon :name="$r['icon']" class="size-[18px]" /></span>
                            <span class="min-w-0 flex-1 truncate text-[14px] font-bold text-brand">{{ $r['label'] }}</span>
                            @if ($n > 0)
                                <span class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $n }} à traiter</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">L'équipe du département</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($departement['membres'] as $m)
                    <div class="flex items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand text-[12px] font-bold text-white">{{ mb_strtoupper(mb_substr($m['prenom'] ?? '', 0, 1).mb_substr($m['nom'] ?? '', 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-bold text-brand">{{ $m['prenom'] }} {{ $m['nom'] }}</p>
                            <p class="truncate text-xs text-[#5B677A]">{{ $m['email'] }}</p>
                        </div>
                        @unless ($m['is_active'])
                            <span class="shrink-0 rounded-full bg-cloud px-2.5 py-1 text-[11px] font-bold text-[#5B677A]">Suspendu</span>
                        @endunless
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun compte pour ce département.@if ($adminGeneral) Créez-en un depuis <a href="{{ route('admin.members') }}" wire:navigate class="font-semibold text-azure hover:underline">Comptes membres</a>.@endif</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
