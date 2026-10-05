@php
    $badge = fn ($s) => match ($s) {
        'approuve' => ['#22A85A', '#EAF6EE', 'En ligne'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusée'],
        'indisponible' => ['#5B677A', '#EEF1F6', 'Vendu / indisponible'],
        'expiree' => ['#5B677A', '#EEF1F6', 'Expirée'],
        'retiree' => ['#AC0100', '#F9E9E9', 'Retirée'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
    $filtres = [
        'en_attente' => 'En attente',
        'signalees' => 'Signalées',
        'approuve' => 'En ligne',
        'refuse' => 'Refusées',
        'indisponible' => 'Vendues',
        'expiree' => 'Expirées',
        'retiree' => 'Retirées',
        'tous' => 'Toutes',
    ];
    $prix = fn (?string $p) => $p !== null && preg_match('/^\s*\d[\d\s.]*$/', $p) ? number_format((int) preg_replace('/\D/', '', $p), 0, ',', ' ').' F' : $p;
@endphp

<div>
    <x-admin-light.topbar title="Marketplace" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-4">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Marketplace des membres</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-3xl text-xs text-[#9AA6B8]">Validez les annonces avant publication, corrigez-les ou retirez-les si besoin, et traitez les signalements des membres. Le vendeur est notifié à chaque décision, avec le motif.</p>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach ($filtres as $value => $label)
                <button wire:click="setFiltre('{{ $value }}')" data-test="filtre-{{ $value }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[10.5px] font-bold {{ in_array($value, ['en_attente', 'signalees'], true) && ($compteurs[$value] ?? 0) ? 'bg-accent text-white' : ($filtre === $value ? 'bg-white/20' : 'bg-cloud') }}">{{ $compteurs[$value] ?? 0 }}</span>
                </button>
            @endforeach
            <div class="relative ml-auto min-w-[220px]">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-[#9AA6B8]" />
                <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Titre, vendeur, e-mail…" class="w-full rounded-full border border-brand/15 bg-white py-1.5 pl-9 pr-3 text-xs outline-none focus:border-azure" />
            </div>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($listings as $l)
                @php $b = $badge($l['statut']); $nbSignal = count($l['signalements'] ?? []); @endphp
                <div data-test="ligne-annonce-admin" wire:key="adm-{{ $l['id'] }}" class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-4 first:border-t-0">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.media-thumb :url="$l['photo']" mode="thumb" :fallback-icon="$l['type'] === 'produit' ? 'shopping-bag' : 'nav-briefcase'" />
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $l['title'] }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">
                                {{ ucfirst($l['type']) }} · {{ $l['groupe']['nom'] ?? $l['category'] }}@if ($l['price']) · {{ $prix($l['price']) }}@endif
                            </p>
                            <p class="mt-0.5 text-[11px] text-[#9AA6B8]">
                                Par {{ $l['seller']['prenom'] ?? '' }} {{ $l['seller']['nom'] ?? '' }} ({{ $l['seller']['email'] ?? '' }})@if ($l['seller']['ville'] ?? null) · {{ $l['seller']['ville'] }}@endif
                                · {{ \Illuminate\Support\Carbon::parse($l['updated_at'])->locale('fr')->diffForHumans() }}
                            </p>
                        </div>
                        @if ($nbSignal)
                            <span class="shrink-0 rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-white">{{ $nbSignal }} signalement{{ $nbSignal > 1 ? 's' : '' }}</span>
                        @endif
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                            <button wire:click="toggleDetail({{ $l['id'] }})" data-test="voir-annonce-admin" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-azure/25 px-3 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                                <x-ui.icon name="eye" class="size-3.5" /> {{ $expandedId === $l['id'] ? 'Masquer' : 'Voir' }}
                            </button>
                            @if ($l['statut'] === 'en_attente')
                                <button wire:click="approve({{ $l['id'] }})" data-test="publier" class="btn-tap inline-flex items-center gap-1.5 rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]"><x-ui.icon name="check" class="size-3.5" /> Publier</button>
                                <button wire:click="ouvrir('refuser', {{ $l['id'] }})" data-test="refuser" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]"><x-ui.icon name="x" class="size-3.5" /> Refuser</button>
                            @elseif (in_array($l['statut'], ['approuve', 'indisponible'], true))
                                <button wire:click="ouvrir('retirer', {{ $l['id'] }})" data-test="retirer-admin" class="btn-tap rounded-lg border border-[#F0C9C9] px-3 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Retirer</button>
                            @endif
                            @if ($nbSignal)
                                <button wire:click="classerSignalements({{ $l['id'] }})" data-test="classer-signalements" class="btn-tap rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Classer les signalements</button>
                            @endif
                            <button wire:click="ouvrir('corriger', {{ $l['id'] }})" data-test="corriger" title="Corriger" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $l['id'] }})" wire:confirm="Supprimer définitivement « {{ $l['title'] }} » ? Le vendeur sera prévenu." class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer définitivement">
                                <x-ui.icon name="trash-2" class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    @if ($expandedId === $l['id'])
                        <div class="panel-enter mt-3 grid gap-4 rounded-xl bg-[#F8FAFC] p-4 md:grid-cols-[minmax(0,1fr)_260px]">
                            <div>
                                @if ($l['photo'])
                                    <div class="mb-3 max-w-[420px] overflow-hidden rounded-xl border border-brand/10">
                                        <x-ui.media-thumb :url="$l['photo']" :alt="$l['title']" mode="card" />
                                    </div>
                                @endif
                                <p class="whitespace-pre-line text-[12.5px] leading-relaxed text-ink">{{ $l['description'] }}</p>
                                @if (in_array($l['statut'], ['refuse', 'retiree'], true) && $l['reject_reason'])
                                    <p class="mt-2 text-[11.5px] text-accent">Motif communiqué : {{ $l['reject_reason'] }}</p>
                                @endif
                                @if ($nbSignal)
                                    <div class="mt-3 rounded-[10px] border border-accent/20 bg-white p-3">
                                        <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.08em] text-accent">Signalements des membres</p>
                                        @foreach ($l['signalements'] as $sg)
                                            <p class="text-[12px] text-ink">• {{ $sg['motif'] ?: 'Sans motif précisé' }} <span class="text-[#9AA6B8]">— {{ \Illuminate\Support\Carbon::parse($sg['date'])->locale('fr')->diffForHumans() }}</span></p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1.5 text-[11.5px] text-[#5B677A]">
                                <p class="font-bold text-brand">Vendeur</p>
                                <p>{{ $l['seller']['prenom'] ?? '' }} {{ $l['seller']['nom'] ?? '' }}</p>
                                <p>{{ $l['seller']['email'] ?? '' }}</p>
                                @if ($l['seller']['telephone'] ?? null)<p>Tél. du compte : {{ $l['seller']['telephone'] }}</p>@endif
                                @if ($l['contact'])<p>Tél. de l'annonce : {{ $l['contact'] }}</p>@endif
                                <p class="pt-2 font-bold text-brand">Statistiques</p>
                                <p>{{ $l['vues'] }} vue{{ $l['vues'] > 1 ? 's' : '' }} · {{ $l['contacts'] }} contact{{ $l['contacts'] > 1 ? 's' : '' }}</p>
                                @if ($l['expire_le'])<p>Expire le {{ \Illuminate\Support\Carbon::parse($l['expire_le'])->locale('fr')->isoFormat('D MMMM YYYY') }}</p>@endif
                                <p>Soumise le {{ \Illuminate\Support\Carbon::parse($l['created_at'])->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">
                    @if (trim($recherche) !== '')
                        Aucune annonce ne correspond à « {{ trim($recherche) }} ».
                    @elseif ($filtre === 'en_attente')
                        Aucune annonce en attente : tout est à jour.
                    @elseif ($filtre === 'signalees')
                        Aucune annonce signalée.
                    @else
                        Aucune annonce dans cette catégorie.
                    @endif
                </p>
            @endforelse
        </div>
        @if (($meta['last_page'] ?? 1) > 1)
            <x-ui.pager :meta="$meta" />
        @endif
    </div>

    {{-- Refuser / retirer / corriger --}}
    @if ($action)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermer" x-on:keydown.escape.window="$wire.fermer()">
            <div data-test="fenetre-decision" role="dialog" aria-modal="true" class="panel-enter max-h-[92vh] w-full max-w-[600px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-[15px] font-bold text-brand">{{ ['refuser' => "Refuser l'annonce", 'retirer' => "Retirer l'annonce du catalogue", 'corriger' => "Corriger l'annonce"][$action] }}</p>
                    <button wire:click="fermer" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>

                @if ($action === 'corriger')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type</label>
                            <select wire:model="type" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"><option value="service">Service</option><option value="produit">Produit</option></select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie</label>
                            <select wire:model="groupId" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                                <option value="">— Choisir —</option>
                                @foreach ($categories as $cat)<option value="{{ $cat['id'] }}">{{ $cat['nom'] }}</option>@endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                            <input wire:model="title" type="text" data-test="corriger-titre" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description</label>
                            <textarea wire:model="description" rows="4" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prix</label>
                            <input wire:model="price" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Message au vendeur <span class="font-normal text-[#9AA6B8]">(facultatif)</span></label>
                            <input wire:model="note" type="text" placeholder="Ex : catégorie ajustée, faute corrigée dans le titre." class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                    </div>
                @else
                    @if ($action === 'refuser')
                        <p class="mb-2 text-[12px] font-semibold text-[#5B677A]">Motifs fréquents :</p>
                        <div class="mb-3 flex flex-col gap-1.5">
                            @foreach ($motifsRefus as $m)
                                <button type="button" wire:click="$set('motif', @js($m))" class="rounded-[10px] border border-brand/10 px-3 py-2 text-left text-[12px] text-ink hover:border-brand/30 hover:bg-cloud">{{ $m }}</button>
                            @endforeach
                        </div>
                    @endif
                    <label for="motif-decision" class="mb-1 block text-xs font-semibold text-[#5B677A]">Motif communiqué au vendeur <span class="text-accent">*</span></label>
                    <textarea id="motif-decision" wire:model="motif" rows="3" maxlength="300" data-test="motif-decision" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                @endif

                @if ($erreur)<p data-test="erreur-decision" class="mt-2 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif

                <div class="mt-5 flex gap-2">
                    <button wire:click="confirmer" wire:loading.attr="disabled" data-test="confirmer-decision" class="btn-tap rounded-full {{ $action === 'corriger' ? 'bg-brand hover:bg-brand/90' : 'bg-accent hover:bg-accent-600' }} px-5 py-2.5 text-[13px] font-bold text-white disabled:opacity-60">{{ ['refuser' => 'Refuser', 'retirer' => 'Retirer', 'corriger' => 'Enregistrer la correction'][$action] }}</button>
                    <button wire:click="fermer" class="btn-tap rounded-full border border-brand/15 px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Annuler</button>
                </div>
            </div>
        </div>
    @endif
</div>
