@php
    $badge = fn ($s) => match ($s) {
        'approuve' => ['#22A85A', '#EAF6EE', 'En ligne'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusée'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
    $filtres = [
        'en_attente' => 'En attente',
        'approuve' => 'En ligne',
        'refuse' => 'Refusées',
        'tous' => 'Toutes',
    ];
@endphp

<div>
    <x-admin-light.topbar title="Marketplace" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Marketplace des membres</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">Validez les services et produits proposés par les membres avant leur publication. Le membre est notifié à chaque décision.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($filtres as $value => $label)
                    <button wire:click="setFiltre('{{ $value }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">
                        {{ $label }} ({{ $compteurs[$value] }})
                    </button>
                @endforeach
            </div>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($listings as $l)
                @php $b = $badge($l['statut']); @endphp
                <div class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-4 first:border-t-0">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.media-thumb :url="$l['photo']" mode="thumb" :fallback-icon="$l['type'] === 'produit' ? 'nav-library' : 'nav-briefcase'" />
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $l['title'] }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">
                                {{ ucfirst($l['type']) }} · {{ $l['category'] }}@if ($l['price']) · {{ $l['price'] }}@endif
                            </p>
                            <p class="mt-0.5 text-[11px] text-[#9AA6B8]">
                                Par {{ $l['seller']['prenom'] ?? '' }} {{ $l['seller']['nom'] ?? '' }} ({{ $l['seller']['email'] ?? '' }})@if ($l['seller']['ville'] ?? null) · {{ $l['seller']['ville'] }}@endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                        <div class="flex shrink-0 items-center gap-2">
                            <button wire:click="toggleDetail({{ $l['id'] }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-azure/25 px-3 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                                <x-ui.icon name="eye" class="size-3.5" /> {{ $expandedId === $l['id'] ? 'Masquer' : 'Voir' }}
                            </button>
                            @if ($l['statut'] === 'en_attente')
                                <button wire:click="approve({{ $l['id'] }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">
                                    <x-ui.icon name="check" class="size-3.5" /> Publier
                                </button>
                                <button wire:click="openReject({{ $l['id'] }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">
                                    <x-ui.icon name="x" class="size-3.5" /> Refuser
                                </button>
                            @elseif ($l['statut'] === 'approuve')
                                <button wire:click="openReject({{ $l['id'] }})" class="btn-tap rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Retirer</button>
                            @endif
                            <button wire:click="delete({{ $l['id'] }})" wire:confirm="Supprimer définitivement « {{ $l['title'] }} » ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer">
                                <x-ui.icon name="trash-2" class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    @if ($expandedId === $l['id'])
                        <div class="panel-enter mt-3 rounded-xl bg-[#F8FAFC] p-4">
                            @if ($l['photo'])
                                <div class="mb-3 max-w-[420px] overflow-hidden rounded-xl border border-brand/10">
                                    <x-ui.media-thumb :url="$l['photo']" :alt="$l['title']" mode="card" />
                                </div>
                            @endif
                            <p class="text-[12.5px] leading-relaxed text-ink">{{ $l['description'] }}</p>
                            <div class="mt-2.5 flex flex-wrap gap-4 text-[11.5px] text-[#5B677A]">
                                @if ($l['contact']) <span>📞 {{ $l['contact'] }}</span> @endif
                                @if ($l['seller']['telephone'] ?? null) <span>Tél. membre : {{ $l['seller']['telephone'] }}</span> @endif
                                <span>Soumise le {{ \Illuminate\Support\Carbon::parse($l['created_at'])->translatedFormat('d F Y à H:i') }}</span>
                            </div>
                            @if ($l['statut'] === 'refuse' && $l['reject_reason'])
                                <p class="mt-2 text-[11.5px] text-accent">Motif du refus : {{ $l['reject_reason'] }}</p>
                            @endif
                        </div>
                    @endif

                    @if ($rejectingId === $l['id'])
                        <div class="panel-enter mt-3 rounded-[12px] border border-[#F0C9C9] bg-[#FDF7F7] p-4">
                            <p class="mb-2 text-[13px] font-bold text-accent">{{ $l['statut'] === 'approuve' ? 'Retirer' : 'Refuser' }} « {{ $l['title'] }} »</p>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Motif (communiqué au membre — optionnel)</label>
                            <textarea wire:model="motif" rows="2" placeholder="Ex : description trop vague, photo inappropriée, activité hors du cadre du réseau…" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                            @error('motif') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                            <div class="mt-3 flex gap-2">
                                <button wire:click="confirmReject" wire:loading.attr="disabled" class="btn-tap rounded-lg bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600 disabled:opacity-60">Confirmer</button>
                                <button wire:click="cancelReject" class="btn-tap rounded-lg border border-[#C9D3E6] px-4 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Annuler</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">
                    @if ($filtre === 'en_attente')
                        Aucune annonce en attente — tout est à jour ! 🎉
                    @else
                        Aucune annonce dans cette catégorie.
                    @endif
                </p>
            @endforelse
        </div>
    </div>
</div>
