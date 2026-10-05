<div>
    <x-admin-light.topbar title="Pages légales" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Pages légales</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-3xl text-xs text-[#9AA6B8]">Ces pages sont liées dans les pieds de page du site, de l'espace membre et de l'administration. Tant qu'une page n'est pas publiée, le site affiche « Page en cours de rédaction ». Chaque publication crée une nouvelle version datée.</p>
        </div>

        <div class="grid items-start gap-5 lg:grid-cols-[260px_1fr]">
            <nav class="flex flex-col gap-1 rounded-[16px] border border-brand/10 bg-white p-2 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($pages as $p)
                    <button wire:click="charger('{{ $p['slug'] }}')" data-test="legal-{{ $p['slug'] }}" class="flex items-center justify-between gap-2 rounded-[10px] px-3 py-2.5 text-left text-[13px] font-semibold transition-colors {{ $slug === $p['slug'] ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                        <span class="min-w-0 truncate">{{ $p['titre'] }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $p['publie'] ? 'bg-[#22A85A]/15 '.($slug === $p['slug'] ? 'text-white' : 'text-[#1C8F4C]') : 'bg-[#F5A623]/20 '.($slug === $p['slug'] ? 'text-white' : 'text-[#8A5A00]') }}">{{ $p['publie'] ? 'v'.$p['version'] : 'À rédiger' }}</span>
                    </button>
                @endforeach
            </nav>

            <section class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @if ($courante)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[12px] text-[#5B677A]">
                            @if ($courante['publie'])
                                <span class="font-bold text-[#1C8F4C]">En ligne</span> · version {{ $courante['version'] }} publiée le {{ \Carbon\Carbon::parse($courante['publie_at'])->translatedFormat('j F Y \à H\hi') }}
                            @else
                                <span class="font-bold text-[#8A5A00]">Non publiée</span> · le site affiche « Page en cours de rédaction »
                            @endif
                        </p>
                        <a href="{{ url('/'.$slug) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-azure hover:underline"><x-ui.icon name="external-link" class="size-3.5" /> Voir sur le site</a>
                    </div>

                    <div class="grid gap-3">
                        <div>
                            <label for="legal-titre" class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                            <input id="legal-titre" wire:model="titre" type="text" maxlength="150" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                        <div>
                            <label for="legal-resume" class="mb-1 block text-xs font-semibold text-[#5B677A]">Résumé <span class="font-normal text-[#9AA6B8]">(sous le titre et pour les moteurs de recherche)</span></label>
                            <input id="legal-resume" wire:model="resume" type="text" maxlength="300" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        </div>
                        <div>
                            <div class="mb-1 flex flex-wrap items-end justify-between gap-2">
                                <label for="legal-contenu" class="block text-xs font-semibold text-[#5B677A]">Contenu</label>
                                <div class="flex rounded-full bg-cloud p-0.5 text-[11.5px] font-semibold">
                                    <button type="button" wire:click="$set('apercu', false)" class="rounded-full px-3 py-1 {{ ! $apercu ? 'bg-white text-brand shadow-sm' : 'text-[#5B677A]' }}">Rédaction</button>
                                    <button type="button" wire:click="$set('apercu', true)" data-test="legal-apercu" class="rounded-full px-3 py-1 {{ $apercu ? 'bg-white text-brand shadow-sm' : 'text-[#5B677A]' }}">Aperçu</button>
                                </div>
                            </div>
                            @if ($apercu)
                                <div class="lecon min-h-[320px] rounded-[9px] border border-brand/15 px-5 py-4">{!! $rendu['html'] ?? '<p class="text-[#9AA6B8]">Aucun contenu.</p>' !!}</div>
                            @else
                                <textarea id="legal-contenu" wire:model="contenu" rows="18" placeholder="## Article 1 — Objet&#10;Texte de l'article…&#10;&#10;## Article 2 — …" class="w-full rounded-[9px] border border-brand/15 px-3 py-2.5 font-mono text-[13px] leading-relaxed outline-none focus:border-azure"></textarea>
                                <p class="mt-1 text-[11px] text-[#9AA6B8]">Mise en forme : <code>## Titre de section</code> (crée le sommaire), <code>**gras**</code>, <code>- liste</code>, <code>[lien](https://…)</code>.</p>
                            @endif
                        </div>
                    </div>

                    @if ($message)
                        <p data-test="legal-message" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
                    @endif
                    @if ($erreur)
                        <p data-test="legal-erreur" role="alert" class="mt-4 flex items-start gap-2 rounded-[10px] bg-accent/5 px-3.5 py-2.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> {{ $erreur }}</p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-2">
                        <button wire:click="sauvegarder" wire:loading.attr="disabled" class="btn-tap rounded-[9px] border border-brand/15 px-4 py-2 text-[13px] font-bold text-brand hover:bg-cloud disabled:opacity-60">Enregistrer le brouillon</button>
                        <button wire:click="publier" wire:loading.attr="disabled" wire:confirm="Publier une nouvelle version de « {{ $titre }} » sur le site ?" data-test="legal-publier" class="btn-tap rounded-[9px] bg-accent px-4 py-2 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">{{ $courante['publie'] ? 'Publier une nouvelle version' : 'Publier' }}</button>
                        @if ($courante['publie'])
                            <button wire:click="depublier" wire:confirm="Retirer cette page du site ?" class="btn-tap rounded-[9px] px-4 py-2 text-[13px] font-bold text-[#5B677A] hover:bg-cloud">Retirer du site</button>
                        @endif
                    </div>
                @else
                    <p class="py-10 text-center text-sm text-[#5B677A]">Pages légales indisponibles.</p>
                @endif
            </section>
        </div>
    </div>
</div>
