@php
    $statutBadge = fn ($s) => match ($s) {
        'approuve' => ['#22A85A', '#EAF6EE', 'En ligne'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusée'],
        'indisponible' => ['#5B677A', '#EEF1F6', 'Vendu / indisponible'],
        'expiree' => ['#5B677A', '#EEF1F6', 'Expirée'],
        'retiree' => ['#AC0100', '#F9E9E9', "Retirée par l'administration"],
        default => ['#F5A623', '#FCF1DD', 'En attente de validation'],
    };
    // Prix saisi en chiffres seuls (« 5000 ») : affiché « 5 000 F ».
    $prix = fn (?string $p) => $p !== null && preg_match('/^\s*\d[\d\s.]*$/', $p)
        ? number_format((int) preg_replace('/\D/', '', $p), 0, ',', ' ').' F'
        : $p;
@endphp

<div>
    <x-member-light.topbar title="Marketplace" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Marketplace du réseau</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Services et produits proposés par les membres. Trouvez un prestataire de confiance au sein du réseau.</p>
            </div>
            @if ($abonnementActif)
                <button wire:click="openForm" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Proposer un service / produit</button>
            @else
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-[#F5A623]/40 bg-[#F5A623]/10 px-4 py-2.5 text-xs font-bold text-[#B27007] hover:bg-[#F5A623]/20">
                    <x-ui.icon name="shield" class="size-3.5" /> Abonnement requis pour vendre
                </a>
            @endif
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        {{-- Formulaire de soumission --}}
        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <div>
                        <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier mon annonce' : 'Proposer un service ou un produit' }}</p>
                        <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $editingId ? 'Prix et téléphone : modifiés immédiatement. Titre, description, visuel, type ou catégorie : l\'annonce repasse en validation.' : 'Votre annonce sera examinée par l\'administration avant d\'apparaître sur la Marketplace.' }}</p>
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
                    <select wire:model="groupId" data-test="categorie-annonce" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        <option value="">— Choisir —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['nom'] }}</option>
                        @endforeach
                    </select>
                    @error('groupId') <span class="text-xs text-accent">{{ $message }}</span> @enderror
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
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone <span class="font-normal text-[#9AA6B8]">(facultatif — affiché aux membres abonnés ; ils vous écriront d'abord par la messagerie)</span></label>
                    <input wire:model="contact" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Image ou vidéo de votre service / produit (optionnel)" hint="Ajoutez une photo, une courte vidéo de présentation (20 Mo max), ou collez un lien YouTube/TikTok — un visuel augmente vos chances d'être contacté." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="soumettre" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit" data-test="enregistrer-annonce">{{ $editingId ? 'Enregistrer les modifications' : 'Soumettre à la validation' }}</button>
            </div>
        @endif

        {{-- Onglets --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            <button wire:click="setOnglet('catalogue')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $onglet === 'catalogue' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">Catalogue</button>
            <button wire:click="setOnglet('mes-annonces')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $onglet === 'mes-annonces' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">Mes annonces</button>
        </div>

        @if ($onglet === 'catalogue')
            {{-- Recherche & filtres --}}
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1 sm:max-w-[380px]">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
                    <input wire:model.live.debounce.300ms="recherche" type="search" data-test="recherche-marketplace" aria-label="Rechercher une annonce" placeholder="Ex : traiteur Cocody, couture, attiéké…" class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13px] text-ink outline-none focus:border-azure" />
                </div>
                @foreach (['tous' => 'Tout', 'service' => 'Services', 'produit' => 'Produits'] as $value => $label)
                    <button wire:click="setFiltreType('{{ $value }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtreType === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
                @endforeach
                <button wire:click="$toggle('favoris')" data-test="filtre-favoris" class="btn-tap inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $favoris ? 'border-accent bg-accent text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">
                    <span aria-hidden="true">{{ $favoris ? '♥' : '♡' }}</span> Mes favoris{{ $nbFavoris ? ' ('.$nbFavoris.')' : '' }}
                </button>
            </div>
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <select wire:model.live="filtreGroupe" data-test="filtre-groupe" aria-label="Catégorie" class="rounded-full border border-brand/15 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-[#5B677A] outline-none">
                    <option value="0">Toutes les catégories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['nom'] }}</option>
                    @endforeach
                </select>
                @if (count($villes))
                    <select wire:model.live="ville" data-test="filtre-ville" aria-label="Ville" class="rounded-full border border-brand/15 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-[#5B677A] outline-none">
                        <option value="">Toutes les villes</option>
                        @foreach ($villes as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                @endif
                <select wire:model.live="tri" data-test="tri-marketplace" aria-label="Trier" class="rounded-full border border-brand/15 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-[#5B677A] outline-none">
                    <option value="recent">Plus récentes</option>
                    <option value="prix_asc">Prix croissant</option>
                    <option value="prix_desc">Prix décroissant</option>
                </select>
                <span data-test="nb-annonces" class="ml-auto text-[12px] font-semibold text-[#5B677A]">{{ $meta['total'] ?? 0 }} annonce{{ ($meta['total'] ?? 0) > 1 ? 's' : '' }}</span>
            </div>

            {{-- Grille des annonces --}}
            @if ($listings->isEmpty())
                <div data-test="marketplace-vide" class="flex flex-col items-center rounded-[18px] border border-brand/10 bg-white px-8 py-16 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                        <x-ui.icon :name="$favoris ? 'star' : ($totalCatalogue ? 'search' : 'store')" class="size-7" />
                    </span>
                    @if ($favoris && ! trim($recherche))
                        <h2 class="mb-2 text-[16px] font-bold text-brand">Aucun favori pour l'instant</h2>
                        <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">Touchez ♡ sur une annonce pour la retrouver ici.</p>
                        <button type="button" wire:click="$set('favoris', false)" class="mt-4 text-[12.5px] font-semibold text-azure hover:underline">Voir tout le catalogue</button>
                    @elseif ($totalCatalogue)
                        <h2 class="mb-2 text-[16px] font-bold text-brand">Aucune annonce ne correspond</h2>
                        <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">Essayez un autre mot ou retirez un filtre.</p>
                        <button type="button" wire:click="effacerFiltres" class="mt-4 text-[12.5px] font-semibold text-azure hover:underline">Effacer la recherche et les filtres</button>
                    @else
                        <h2 class="mb-2 text-[16px] font-bold text-brand">Aucune annonce pour le moment</h2>
                        <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">Soyez le premier à proposer un service ou un produit au réseau !</p>
                    @endif
                </div>
            @else
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
                    @foreach ($listings as $l)
                        <article wire:key="annonce-{{ $l['id'] }}" wire:click="voir({{ $l['id'] }})" data-test="carte-annonce" class="card-hover flex cursor-pointer flex-col overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="relative">
                                <x-ui.media-thumb :url="$l['photo']" :alt="$l['title']" mode="card" :fallback-icon="$l['type'] === 'produit' ? 'shopping-bag' : 'nav-briefcase'" />
                                <button type="button" wire:click.stop="basculerFavori({{ $l['id'] }})" data-test="favori" aria-label="{{ $l['favori'] ?? false ? 'Retirer des favoris' : 'Ajouter aux favoris' }}" aria-pressed="{{ $l['favori'] ?? false ? 'true' : 'false' }}"
                                    class="absolute right-2.5 top-2.5 flex size-8 items-center justify-center rounded-full bg-white/90 text-[16px] shadow transition-transform hover:scale-110 {{ $l['favori'] ?? false ? 'text-accent' : 'text-[#5B677A]' }}">{{ $l['favori'] ?? false ? '♥' : '♡' }}</button>
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold {{ $l['type'] === 'service' ? 'bg-azure/10 text-azure' : 'bg-[#F5A623]/10 text-[#B87A0D]' }}">{{ ucfirst($l['type']) }}</span>
                                    @if ($l['groupe'] ?? null)
                                        <span class="inline-flex min-w-0 items-center gap-1 truncate text-[10.5px] font-semibold" style="color: {{ $l['groupe']['couleur'] }}"><x-ui.icon :name="$l['groupe']['icone']" class="size-3 shrink-0" /> {{ $l['groupe']['nom'] }}</span>
                                    @else
                                        <span class="truncate text-[10.5px] font-semibold text-[#9AA6B8]">{{ $l['category'] }}</span>
                                    @endif
                                </div>
                                <h3 class="mb-1.5 text-[14px] font-bold leading-snug text-brand">{{ $l['title'] }}</h3>
                                <p class="mb-3 line-clamp-3 text-xs leading-relaxed text-[#5B677A]">{{ $l['description'] }}</p>
                                <div class="mt-auto">
                                    @if ($l['price'])
                                        <p data-test="prix" class="mb-2.5 text-[13px] font-extrabold text-brand">{{ $prix($l['price']) }}</p>
                                    @endif
                                    <div class="mb-3 flex items-center gap-2">
                                        <x-messagerie.avatar :personne="$l['seller'] ?? []" taille="size-7" texte="text-[10px]" />
                                        <span class="min-w-0 truncate text-[11.5px] text-[#5B677A]">
                                            {{ $l['seller']['prenom'] ?? '' }} {{ $l['seller']['nom'] ?? '' }}@if ($l['seller']['ville'] ?? null) · {{ $l['seller']['ville'] }}@endif
                                        </span>
                                        @if (($l['seller']['role'] ?? '') === 'mentor')<span class="shrink-0 rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Mentor</span>@endif
                                    </div>
                                    @if (($l['seller']['id'] ?? null) !== $me)
                                        @if ($abonnementActif)
                                            <a href="{{ route('espace-membre.messaging', ['to' => $l['seller']['id'], 'annonce' => $l['id']]) }}" wire:navigate onclick="event.stopPropagation()" data-test="contacter-vendeur" class="btn-tap flex items-center justify-center gap-1.5 rounded-[9px] bg-brand py-2 text-[12px] font-bold text-white hover:bg-brand/90">
                                                <x-ui.icon name="message-circle" class="size-3.5" /> Contacter le vendeur
                                            </a>
                                        @else
                                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate onclick="event.stopPropagation()" data-test="abonner-pour-contacter" class="btn-tap flex items-center justify-center gap-1.5 rounded-[9px] border border-[#F5A623]/40 bg-[#F5A623]/10 py-2 text-[12px] font-bold text-[#B27007] hover:bg-[#F5A623]/20">
                                                <x-ui.icon name="lock" class="size-3.5" /> S'abonner pour contacter
                                            </a>
                                        @endif
                                    @else
                                        <p class="rounded-[9px] bg-cloud px-3 py-2 text-center text-[11px] font-semibold text-[#9AA6B8]">Votre annonce</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <x-ui.pager :meta="$meta" />
            @endif
        @else
            {{-- Mes annonces --}}
            @if (! $abonnementActif && $mesAnnonces->isEmpty())
                <x-member-light.paywall description="Pour publier vos propres services ou produits sur la Marketplace, un abonnement annuel actif (10 000 F) est nécessaire. La consultation du catalogue reste libre pour tous les membres." />
            @elseif ($mesAnnonces->isEmpty())
                <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Vous n'avez pas encore d'annonce. Cliquez sur « Proposer un service / produit » pour vendre sur la Marketplace.</p>
            @else
                @if (! $abonnementActif)
                    <div data-test="annonces-suspendues" class="mb-4 flex flex-wrap items-center gap-3 rounded-[16px] border border-[#F5A623]/40 bg-[#FFF8EC] p-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B27007]"><x-ui.icon name="lock" class="size-5" /></span>
                        <p class="min-w-[220px] flex-1 text-[13px] leading-relaxed text-ink"><span class="font-bold text-brand">Votre abonnement n'est plus à jour : vos annonces sont suspendues.</span> Elles ne sont plus visibles sur la Marketplace et réapparaîtront automatiquement dès le renouvellement.</p>
                        <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap shrink-0 rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Renouveler mon abonnement</a>
                    </div>
                @endif
                <div class="space-y-3">
                    @foreach ($mesAnnonces as $l)
                        @php
                            $b = ($l['suspendue'] ?? false) ? ['#B27007', '#FCF1DD', 'Suspendue (abonnement)'] : $statutBadge($l['statut']);
                            $expire = $l['expire_le'] ? \Illuminate\Support\Carbon::parse($l['expire_le']) : null;
                            $joursRestants = $expire && $expire->isFuture() ? (int) ceil(now()->diffInHours($expire) / 24) : null;
                            $renouvelable = in_array($l['statut'], ['approuve', 'indisponible', 'expiree'], true) && ($l['statut'] === 'expiree' || ($joursRestants !== null && $joursRestants <= 7));
                        @endphp
                        <article wire:key="mes-{{ $l['id'] }}" data-test="mon-annonce" class="flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <x-ui.media-thumb :url="$l['photo']" mode="thumb" :fallback-icon="$l['type'] === 'produit' ? 'shopping-bag' : 'nav-briefcase'" />
                            <div class="min-w-[220px] flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $l['title'] }}</p>
                                <p class="mt-0.5 text-xs text-[#5B677A]">{{ ucfirst($l['type']) }} · {{ $l['category'] }}@if ($l['price']) · {{ $prix($l['price']) }}@endif</p>
                                @if (in_array($l['statut'], ['approuve', 'indisponible', 'expiree'], true))
                                    <p data-test="mon-annonce-stats" class="mt-1 flex flex-wrap gap-x-3 text-[11.5px] text-[#9AA6B8]">
                                        <span class="inline-flex items-center gap-1"><x-ui.icon name="eye" class="size-3.5" /> {{ $l['vues'] }} vue{{ $l['vues'] > 1 ? 's' : '' }}</span>
                                        <span class="inline-flex items-center gap-1"><x-ui.icon name="message-circle" class="size-3.5" /> {{ $l['contacts'] }} contact{{ $l['contacts'] > 1 ? 's' : '' }}</span>
                                        @if ($l['statut'] === 'expiree')
                                            <span class="font-semibold text-accent">Expirée : plus visible</span>
                                        @elseif ($joursRestants !== null)
                                            <span class="{{ $joursRestants <= 7 ? 'font-semibold text-[#B27007]' : '' }}">Expire dans {{ $joursRestants }} jour{{ $joursRestants > 1 ? 's' : '' }}</span>
                                        @endif
                                    </p>
                                @endif
                                @if (in_array($l['statut'], ['refuse', 'retiree'], true) && $l['reject_reason'])
                                    <p class="mt-1 text-[11.5px] text-accent">{{ $l['statut'] === 'retiree' ? 'Motif du retrait' : 'Motif du refus' }} : {{ $l['reject_reason'] }} — corrigez l'annonce pour la soumettre à nouveau.</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                            <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                @if ($renouvelable && $abonnementActif)
                                    <button wire:click="renouveler({{ $l['id'] }})" data-test="renouveler" class="btn-tap rounded-full bg-accent px-3 py-1.5 text-[11.5px] font-bold text-white hover:bg-accent-600">Renouveler 90 jours</button>
                                @endif
                                @if ($l['statut'] === 'approuve' && $abonnementActif)
                                    <button wire:click="basculerDisponibilite({{ $l['id'] }}, false)" data-test="marquer-vendu" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">Vendu / indisponible</button>
                                @elseif ($l['statut'] === 'indisponible' && $abonnementActif)
                                    <button wire:click="basculerDisponibilite({{ $l['id'] }}, true)" data-test="remettre-en-ligne" class="btn-tap rounded-full border border-[#22A85A]/30 bg-[#22A85A]/10 px-3 py-1.5 text-[11.5px] font-bold text-[#1C8F4C] hover:bg-[#22A85A]/20">Remettre en ligne</button>
                                @endif
                                @if ($l['statut'] === 'approuve' && ! ($l['suspendue'] ?? false))
                                    <button wire:click="voir({{ $l['id'] }})" title="Voir l'annonce" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="eye" class="size-4" /></button>
                                @endif
                                @if ($l['statut'] !== 'expiree' && $abonnementActif)
                                    <button wire:click="modifier({{ $l['id'] }})" data-test="modifier-annonce" title="{{ in_array($l['statut'], ['refuse', 'retiree'], true) ? 'Corriger et resoumettre' : 'Modifier' }}" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="pencil" class="size-4" /></button>
                                @endif
                                <button wire:click="retirer({{ $l['id'] }})" wire:confirm="Retirer définitivement « {{ $l['title'] }} » de la Marketplace ?" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Retirer l'annonce">
                                    <x-ui.icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <x-marketplace.fiche :fiche="$fiche" :abonnement-actif="$abonnementActif" :info="$ficheInfo" />
</div>
