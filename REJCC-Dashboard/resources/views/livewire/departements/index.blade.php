<div>
    <x-admin-light.topbar title="Départements du Bureau" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <p class="mb-6 max-w-2xl text-[13.5px] text-[#5B677A]">Chaque département a son espace dans le dashboard, avec les rubriques liées à ses missions. Ouvrez un département pour gérer ses accès : donner l'accès à un membre existant ou créer un compte.</p>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($departements as $d)
                <a href="{{ route('departements.show', $d['slug']) }}" wire:navigate data-test="departement-{{ $d['slug'] }}"
                    class="group flex flex-col rounded-[20px] border border-[#EDE9F3] bg-white p-5 shadow-[0_2px_10px_-6px_rgba(59,63,92,.2)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-16px_rgba(59,63,92,.4)]">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex items-center gap-3">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px]" style="background: {{ ['#FFF3E8', '#EFFAF4', '#F3EFFC', '#FFF8E1', '#EEF6FD'][$loop->index % 5] }}"><x-ui.illustration :name="\App\Support\Espace::ILLUSTRATIONS[$d['slug']] ?? 'etoile'" size="size-8" /></span>
                            <h2 class="text-[16px] font-bold text-[#3B3F5C]">{{ $d['label'] }}</h2>
                        </span>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ count($d['membres']) ? 'bg-[#EAF6EE] text-[#22A85A]' : 'bg-cloud text-[#5B677A]' }}">{{ count($d['membres']) ? count($d['membres']).' compte'.(count($d['membres']) > 1 ? 's' : '') : 'Aucun compte' }}</span>
                    </div>
                    <p class="mt-1.5 flex-1 text-[12.5px] leading-relaxed text-[#5B677A]">{{ $d['mission'] }}</p>
                    <p class="mt-3 text-[11.5px] font-semibold text-[#9AA6B8]">{{ count($d['sections']) }} rubrique{{ count($d['sections']) > 1 ? 's' : '' }} : {{ collect($d['sections'])->map(fn ($s) => \App\Support\AdminSections::SECTIONS[$s] ?? $s)->implode(', ') }}</p>
                </a>
            @endforeach
        </div>
    </div>
</div>
