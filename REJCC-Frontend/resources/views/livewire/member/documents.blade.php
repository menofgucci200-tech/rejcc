<div>
    <x-member-light.topbar title="Documents & ressources" />

    @php
        $icones = ['PDF' => 'file-text', 'Word' => 'file-text', 'Excel' => 'list-checks', 'PowerPoint' => 'layout-dashboard', 'Image' => 'image', 'Vidéo' => 'video', 'Audio' => 'play', 'Lien' => 'external-link'];
        $couleurs = ['PDF' => '#AC0100', 'Word' => '#2B579A', 'Excel' => '#1C7C45', 'PowerPoint' => '#C2410C', 'Image' => '#7C3AED', 'Vidéo' => '#031D59', 'Audio' => '#B27007', 'Lien' => '#4F6FBF'];
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Documents &amp; ressources</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-xl text-xs text-[#5B677A]">Guides, modèles et ressources du réseau, à consulter directement sur la plateforme ou à télécharger.</p>
            </div>
        </div>

        @if ($message)
            <p wire:key="flash-ok" data-test="flash-doc" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur)
            <p wire:key="flash-ko" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-accent/10 px-3.5 py-2.5 text-xs font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-3.5 shrink-0" /> {{ $erreur }}</p>
        @endif

        {{-- ══════════ Bibliothèque ══════════ --}}
        <div class="mb-4 flex flex-col gap-3 rounded-[16px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:flex-row sm:items-center">
            <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Rechercher un document…" data-test="recherche-docs" class="w-full rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure sm:max-w-sm" />
            <select wire:model.live="tri" class="rounded-full border border-brand/15 bg-white px-3 py-2 text-xs text-brand outline-none sm:ml-auto">
                <option value="recents">Plus récents</option>
                <option value="titre">Par titre (A → Z)</option>
                <option value="populaires">Plus téléchargés</option>
            </select>
        </div>
        <div class="mb-5 flex flex-wrap gap-1.5">
            <button wire:click="setCategorie('')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === '' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">Toutes</button>
            @foreach ($categories->where('nombre', '>', 0) as $c)
                <button wire:click="setCategorie('{{ $c['id'] }}')" wire:key="cat-{{ $c['id'] }}" data-test="categorie-doc" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === (string) $c['id'] ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                    {{ $c['nom'] }} <span class="{{ $categorie === (string) $c['id'] ? 'text-white/70' : 'text-[#9AA6B8]' }}">{{ $c['nombre'] }}</span>
                </button>
            @endforeach
        </div>

        @if ($docs->isEmpty())
            <div class="rounded-[16px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                <x-ui.icon name="folder-open" class="mx-auto mb-3 size-9 text-[#9AA6B8]" />
                <p class="text-sm font-bold text-brand">{{ $filtresActifs ? 'Aucun document ne correspond à votre recherche' : 'Aucun document disponible pour le moment' }}</p>
            </div>
        @else
            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))">
                @foreach ($docs as $d)
                    @php $tc = $couleurs[$d['type']] ?? '#4F6FBF'; @endphp
                    <button type="button" wire:click="ouvrir({{ $d['id'] }})" wire:key="doc-{{ $d['id'] }}" data-test="carte-doc"
                        class="card-hover flex items-start gap-3.5 rounded-2xl border border-brand/10 bg-white px-[18px] py-4 text-left shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $d['verrouille'] ? 'opacity-80' : '' }}">
                        <span class="relative flex size-[44px] shrink-0 items-center justify-center rounded-[11px]" style="background: {{ $tc }}14; color: {{ $tc }}">
                            <x-ui.icon :name="$icones[$d['type']] ?? 'file-text'" class="size-[19px]" />
                            @if ($d['verrouille'])<span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-[#F5A623] text-white ring-2 ring-white"><x-ui.icon name="lock" class="size-2.5" /></span>@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-semibold text-brand">
                                <span class="min-w-0">{{ $d['title'] }}</span>
                                @if ($d['nouveau'])<span class="rounded-full bg-accent px-1.5 text-[9.5px] font-bold uppercase leading-4 text-white">Nouveau</span>@endif
                            </p>
                            @if ($d['description'])<p class="mt-0.5 line-clamp-2 text-xs text-[#5B677A]">{{ $d['description'] }}</p>@endif
                            <p class="mt-1.5 text-[11px] text-[#9AA6B8]">{{ collect([$d['category'], ($d['disponible'] ?? true) ? $d['type'] : 'Bientôt disponible', $d['taille'], $d['verrouille'] ? $d['acces_label'] : null])->filter()->join(' · ') }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <x-documents.visionneuse :doc="$doc" />
</div>
