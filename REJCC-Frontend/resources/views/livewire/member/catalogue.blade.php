<div>
    <x-member-light.topbar title="Catalogue des formations" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Catalogue des formations</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('toutes')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'toutes' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Toutes</button>
                <button wire:click="setFiltre('gratuit')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'gratuit' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Gratuites</button>
                <button wire:click="setFiltre('certifiante')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'certifiante' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Certifiantes</button>
            </div>
        </div>

        @if ($erreur)
            <div data-test="erreur-inscription" class="mb-4 flex flex-wrap items-center gap-3 rounded-[14px] border border-[#F5A623]/40 bg-[#FFF8EC] px-4 py-3 text-[13px] text-brand">
                <x-ui.icon name="shield" class="size-4 text-[#B97400]" /> <span class="flex-1">{{ $erreur }}</span>
                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="font-bold text-accent hover:underline">Activer mon abonnement</a>
            </div>
        @endif

        {{-- Recherche, catégorie et tri --}}
        <div class="mb-5 flex flex-wrap items-center gap-2.5" data-test="outils-catalogue">
            <label class="flex min-w-[220px] flex-1 items-center gap-2 rounded-[10px] border border-brand/10 bg-white px-3 py-2 focus-within:border-azure/50">
                <x-ui.icon name="search" class="size-4 shrink-0 text-[#9AA6B8]" />
                <input wire:model.live.debounce.300ms="q" type="search" data-test="recherche-catalogue" placeholder="Rechercher une formation (titre, thème, mot-clé)…" class="rj-search-input w-full min-w-0 border-none bg-transparent text-[13px] outline-none placeholder:text-[#9AA6B8]" />
            </label>
            <select wire:model.live="categorie" data-test="categorie" class="rounded-[10px] border border-brand/10 bg-white py-2 pl-3 pr-9 text-[13px] text-ink outline-none focus:border-azure">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            <select wire:model.live="tri" data-test="tri" class="rounded-[10px] border border-brand/10 bg-white py-2 pl-3 pr-9 text-[13px] text-ink outline-none focus:border-azure">
                <option value="recentes">Plus récentes</option>
                <option value="populaires">Plus suivies</option>
                <option value="az">De A à Z</option>
            </select>
        </div>

        @if ($filtresActifs && $cours->isNotEmpty())
            <p class="mb-3 text-[12.5px] text-[#5B677A]" data-test="nb-resultats">{{ $cours->count() }} formation{{ $cours->count() > 1 ? 's' : '' }} trouvée{{ $cours->count() > 1 ? 's' : '' }}</p>
        @endif

        @if ($aucuneFormation)
            <p class="rounded-[16px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]" data-test="catalogue-vide">Aucune formation n'est encore publiée. Revenez bientôt : le REJCC prépare son catalogue.</p>
        @elseif ($cours->isEmpty())
            <div class="flex flex-col items-center gap-2 rounded-[16px] border border-brand/10 bg-white py-12 text-center" data-test="aucun-resultat">
                <x-ui.icon name="search" class="size-7 text-[#9AA6B8]" />
                <p class="text-[14px] font-bold text-brand">Aucune formation ne correspond{{ trim($q) !== '' ? ' à « '.$q.' »' : '' }}</p>
                <p class="text-[12.5px] text-[#5B677A]">Essayez un autre mot-clé ou une autre catégorie.</p>
                <button type="button" wire:click="reinitialiser" class="mt-1 rounded-full border border-brand/15 px-4 py-1.5 text-[12.5px] font-bold text-brand hover:bg-cloud">Voir toutes les formations</button>
            </div>
        @endif

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($cours as $c)
                <article class="card-hover overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <a href="{{ route('espace-membre.catalogue.fiche', $c['id']) }}" wire:navigate class="relative flex h-28 items-center justify-center overflow-hidden" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">
                        @if ($c['image'])
                            <img src="{{ $c['image'] }}" alt="" loading="lazy" data-test="couverture-carte" class="absolute inset-0 size-full object-cover">
                        @else
                            <x-ui.icon name="graduation-cap" class="size-9 text-white/85" />
                        @endif
                        @if ($c['termine'])
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[10.5px] font-bold text-[#1C8F4C] shadow-sm"><x-ui.icon name="check-circle" class="size-3" /> Terminée</span>
                        @endif
                        @if ($c['inscrits'] > 0)
                            <span class="absolute bottom-2.5 right-3 inline-flex items-center gap-1 rounded-full bg-black/35 px-2 py-0.5 text-[10.5px] font-semibold text-white backdrop-blur-sm" data-test="inscrits"><x-ui.icon name="users" class="size-3" /> {{ $c['inscrits'] }} inscrit{{ $c['inscrits'] > 1 ? 's' : '' }}</span>
                        @endif
                    </a>
                    <div class="p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $c['tagColor'] }}1A; color: {{ $c['tagColor'] }}">{{ $c['tag'] }}</span>
                            @if ($c['certifiante'])
                                <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#F5A623]">
                                    <x-ui.icon name="award" class="size-3" /> Certifiante
                                </span>
                            @endif
                        </div>
                        <a href="{{ route('espace-membre.catalogue.fiche', $c['id']) }}" wire:navigate class="mb-2 block text-[13.5px] font-bold leading-snug text-brand hover:underline">{{ $c['titre'] }}</a>
                        @unless ($c['has_modules'])
                            <p class="mb-2 inline-flex items-center gap-1 rounded-full bg-cloud px-2 py-0.5 text-[10.5px] font-semibold text-[#5B677A]"><x-ui.icon name="clock" class="size-3" /> Contenu en préparation</p>
                        @endunless
                        <div class="mb-3 flex items-center gap-3 text-xs text-[#9AA6B8]">
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="size-3" /> {{ $c['duree'] }}</span>
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="target" class="size-3" /> {{ $c['niveau'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold {{ $c['gratuit'] || $accesLibre ? 'text-[#22A85A]' : 'text-brand' }}">{{ $c['gratuit'] ? 'Gratuit' : ($accesLibre ? 'Accès libre' : 'Inclus dans l\'abonnement') }}</span>
                            @if ($c['termine'] && $c['certifiante'])
                                <a href="{{ route('espace-membre.certificats') }}" wire:navigate data-test="certificat-carte" class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C] hover:bg-[#22A85A]/20">
                                    <x-ui.icon name="award" class="size-3.5" /> Mon certificat
                                </a>
                            @elseif ($c['inscrit'] && $c['has_modules'])
                                <a href="{{ route('espace-membre.formations.detail', $c['id']) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A] hover:bg-[#22A85A]/20">
                                    <x-ui.icon name="arrow-right" class="size-3.5" /> Continuer
                                </a>
                            @elseif ($c['inscrit'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                                    <x-ui.icon name="check" class="size-3.5" /> Inscrit
                                </span>
                            @else
                                <button wire:click="inscrire({{ $c['id'] }})" wire:loading.attr="disabled" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand/90 disabled:opacity-60">S'inscrire</button>
                            @endif
                        </div>
                        <a href="{{ route('espace-membre.catalogue.fiche', $c['id']) }}" wire:navigate data-test="voir-fiche" class="mt-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-azure hover:underline">
                            Voir le programme <x-ui.icon name="arrow-right" class="size-3.5" />
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
