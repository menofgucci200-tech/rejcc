@php
    $statutBadge = fn ($s) => match ($s) {
        'approuve' => ['#22A85A', '#EAF6EE', 'En ligne'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusée'],
        default => ['#F5A623', '#FCF1DD', 'En attente de validation'],
    };
@endphp

<div>
    <x-member-light.topbar title="Marketplace" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Marketplace du réseau</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Services et produits proposés par les membres. Vendez vos compétences, trouvez un prestataire de confiance au sein du réseau.</p>
            </div>
            <button wire:click="openForm" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Proposer un service / produit</button>
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        {{-- Formulaire de soumission --}}
        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <div>
                        <p class="text-sm font-bold text-brand">Proposer un service ou un produit</p>
                        <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">Votre annonce sera examinée par l'administration avant d'apparaître sur la Marketplace.</p>
                    </div>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type d'annonce</label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('type', 'service')" class="btn-tap flex-1 rounded-[10px] border px-3 py-2 text-xs font-bold transition-colors duration-200 {{ $type === 'service' ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-[#5B677A]' }}">Service</button>
                        <button type="button" wire:click="$set('type', 'produit')" class="btn-tap flex-1 rounded-[10px] border px-3 py-2 text-xs font-bold transition-colors duration-200 {{ $type === 'produit' ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-[#5B677A]' }}">Produit</button>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie</label>
                    <select wire:model="category" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre de l'annonce</label>
                    <input wire:model="title" type="text" placeholder="Ex : Plomberie & dépannage à domicile — Abidjan" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description — ce que vous proposez, votre expérience, votre zone d'intervention</label>
                    <textarea wire:model="description" rows="4" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prix (optionnel — ex : 15 000 FCFA, Sur devis…)</label>
                    <input wire:model="price" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone / WhatsApp de contact</label>
                    <input wire:model="contact" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Photo de votre service / produit (optionnel)" hint="Une belle photo augmente vos chances d'être contacté." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="soumettre" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Soumettre à la validation</button>
            </div>
        @endif

        {{-- Onglets --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            <button wire:click="setOnglet('catalogue')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $onglet === 'catalogue' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">Catalogue</button>
            <button wire:click="setOnglet('mes-annonces')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $onglet === 'mes-annonces' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">Mes annonces</button>
        </div>

        @if ($onglet === 'catalogue')
            {{-- Recherche & filtres --}}
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <div class="relative max-w-[320px] flex-1">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
                    <input wire:model.live.debounce.300ms="recherche" type="text" placeholder="Rechercher (service, membre, ville)…" class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13px] text-ink outline-none focus:border-azure" />
                </div>
                @foreach (['tous' => 'Tout', 'service' => 'Services', 'produit' => 'Produits'] as $value => $label)
                    <button wire:click="setFiltreType('{{ $value }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtreType === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
                @endforeach
                @if ($categoriesActives->isNotEmpty())
                    <select wire:model.live="filtreCategorie" class="rounded-full border border-brand/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#5B677A] outline-none">
                        <option value="toutes">Toutes les catégories</option>
                        @foreach ($categoriesActives as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Grille des annonces --}}
            @if ($listings->isEmpty())
                <div class="flex flex-col items-center rounded-[18px] border border-brand/10 bg-white px-8 py-16 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                        <x-ui.icon name="nav-briefcase" class="size-7" />
                    </span>
                    <h2 class="mb-2 text-[16px] font-bold text-brand">Aucune annonce pour le moment</h2>
                    <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">Soyez le premier à proposer un service ou un produit au réseau ! Cliquez sur « Proposer un service / produit » pour commencer.</p>
                </div>
            @else
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
                    @foreach ($listings as $l)
                        <article class="card-hover flex flex-col overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            @if ($l['photo'])
                                <img src="{{ $l['photo'] }}" alt="{{ $l['title'] }}" class="h-36 w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-24 items-center justify-center" style="background: linear-gradient(135deg,#0B2E7A,#4F6FBF)">
                                    <x-ui.icon :name="$l['type'] === 'produit' ? 'nav-library' : 'nav-briefcase'" class="size-8 text-white/75" />
                                </div>
                            @endif
                            <div class="flex flex-1 flex-col p-4">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold {{ $l['type'] === 'service' ? 'bg-azure/10 text-azure' : 'bg-[#F5A623]/10 text-[#B87A0D]' }}">{{ ucfirst($l['type']) }}</span>
                                    <span class="truncate text-[10.5px] font-semibold text-[#9AA6B8]">{{ $l['category'] }}</span>
                                </div>
                                <h3 class="mb-1.5 text-[14px] font-bold leading-snug text-brand">{{ $l['title'] }}</h3>
                                <p class="mb-3 line-clamp-3 text-xs leading-relaxed text-[#5B677A]">{{ $l['description'] }}</p>
                                <div class="mt-auto">
                                    @if ($l['price'])
                                        <p class="mb-2.5 text-[13px] font-extrabold text-brand">{{ $l['price'] }}</p>
                                    @endif
                                    <div class="mb-3 flex items-center gap-2">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                            {{ mb_substr($l['seller']['prenom'] ?? '?', 0, 1) }}{{ mb_substr($l['seller']['nom'] ?? '', 0, 1) }}
                                        </span>
                                        <span class="min-w-0 truncate text-[11.5px] text-[#5B677A]">
                                            {{ $l['seller']['prenom'] ?? '' }} {{ $l['seller']['nom'] ?? '' }}@if ($l['seller']['ville'] ?? null) · {{ $l['seller']['ville'] }}@endif
                                        </span>
                                    </div>
                                    @if (($l['seller']['id'] ?? null) !== $me)
                                        <div class="flex gap-2">
                                            <a href="{{ route('espace-membre.messaging', ['to' => $l['seller']['id']]) }}" wire:navigate class="btn-tap flex flex-1 items-center justify-center gap-1.5 rounded-[9px] bg-brand py-2 text-[12px] font-bold text-white hover:bg-brand/90">
                                                <x-ui.icon name="message-circle" class="size-3.5" /> Message
                                            </a>
                                            @if ($l['contact'])
                                                <a href="https://wa.me/225{{ preg_replace('/\D/', '', $l['contact']) }}" target="_blank" rel="noopener" class="btn-tap flex flex-1 items-center justify-center gap-1.5 rounded-[9px] border border-[#22A85A]/30 bg-[#22A85A]/10 py-2 text-[12px] font-bold text-[#1C8F4C] hover:bg-[#22A85A]/20">
                                                    <x-ui.icon name="phone" class="size-3.5" /> WhatsApp
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <p class="rounded-[9px] bg-cloud px-3 py-2 text-center text-[11px] font-semibold text-[#9AA6B8]">Votre annonce</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Mes annonces --}}
            @if ($mesAnnonces->isEmpty())
                <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Vous n'avez pas encore d'annonce. Cliquez sur « Proposer un service / produit » pour vendre sur la Marketplace.</p>
            @else
                <div class="space-y-3">
                    @foreach ($mesAnnonces as $l)
                        @php $b = $statutBadge($l['statut']); @endphp
                        <article class="card-hover flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            @if ($l['photo'])
                                <img src="{{ $l['photo'] }}" alt="" class="size-14 shrink-0 rounded-xl object-cover">
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                                    <x-ui.icon :name="$l['type'] === 'produit' ? 'nav-library' : 'nav-briefcase'" class="size-5" />
                                </span>
                            @endif
                            <div class="min-w-[200px] flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $l['title'] }}</p>
                                <p class="mt-0.5 text-xs text-[#5B677A]">{{ ucfirst($l['type']) }} · {{ $l['category'] }}@if ($l['price']) · {{ $l['price'] }}@endif</p>
                                @if ($l['statut'] === 'refuse' && $l['reject_reason'])
                                    <p class="mt-1 text-[11.5px] text-accent">Motif du refus : {{ $l['reject_reason'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                            <button wire:click="retirer({{ $l['id'] }})" wire:confirm="Retirer définitivement « {{ $l['title'] }} » de la Marketplace ?" class="icon-btn shrink-0 rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Retirer l'annonce">
                                <x-ui.icon name="trash-2" class="size-4" />
                            </button>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
