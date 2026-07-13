<div>
    <x-member-light.topbar title="Ressources" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Bibliothèque de ressources</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('tous')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Toutes</button>
                @foreach ($categories as $type => $nombre)
                    <button wire:click="setFiltre('{{ $type }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $type ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $type }}s ({{ $nombre }})</button>
                @endforeach
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($ressources as $r)
                <article class="card-hover flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl" style="background: {{ $r['color'] }}1A; color: {{ $r['color'] }}">
                        <x-ui.icon :name="$r['icon']" class="size-5" />
                    </span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $r['titre'] }}</p>
                        @if ($r['description'])
                            <p class="mt-0.5 line-clamp-1 text-xs text-[#5B677A]">{{ $r['description'] }}</p>
                        @endif
                        <p class="mt-1 text-[11px] text-[#9AA6B8]">{{ $r['type'] }}@if ($r['taille']) · {{ $r['taille'] }}@endif · {{ $r['telechargements'] }} téléchargement{{ $r['telechargements'] > 1 ? 's' : '' }}</p>
                    </div>
                    <a href="{{ $r['url'] }}" target="_blank" rel="noopener" wire:click="compter({{ $r['id'] }})" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20">
                        <x-ui.icon name="download" class="size-3.5" /> Consulter
                    </a>
                </article>
            @empty
                <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucune ressource{{ $filtre !== 'tous' ? ' dans cette catégorie' : '' }} pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
