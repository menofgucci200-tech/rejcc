<div>
    <x-admin-light.topbar title="Actualités" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Rédiger un article</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="flex flex-col gap-3 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                        <input wire:model="titre" type="text" placeholder="Titre de l'article" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Catégorie</label>
                        <select wire:model="categorie" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                            <option>Vie du réseau</option>
                            <option>Témoignage</option>
                            <option>Formation</option>
                            <option>Événement</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Contenu</label>
                        <textarea wire:model="contenu" rows="6" placeholder="Rédigez le contenu de l'article…" class="w-full resize-y rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    </div>
                    <div class="flex gap-2.5">
                        <button wire:click="publier" class="flex-1 rounded-[9px] bg-brand py-2.5 text-sm font-bold text-white hover:bg-accent">Publier</button>
                        <button class="flex-1 rounded-[9px] border border-[#C9D3E6] py-2.5 text-sm font-bold text-brand hover:bg-cloud">Enregistrer en brouillon</button>
                    </div>
                    @if ($messagePublication)
                        <p class="text-xs font-semibold text-[#22A85A]">{{ $messagePublication }}</p>
                    @endif
                </div>
            </section>

            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Articles</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-4.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @foreach ($articles as $a)
                        <div class="flex flex-wrap items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                            <div class="min-w-[180px] flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $a['titre'] }}</p>
                                <p class="mt-0.5 text-xs text-[#5B677A]">{{ $a['categorie'] }} · publié {{ $a['date'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $a['publie'] ? '#22A85A' : '#9AA6B8' }}; background: {{ $a['publie'] ? '#EAF6EE' : '#EEF1F5' }}">{{ $a['publie'] ? 'Publié' : 'Brouillon' }}</span>
                            <button wire:click="toggle({{ $a['index'] }})" class="shrink-0 rounded-lg border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $a['publie'] ? 'Dépublier' : 'Publier' }}</button>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
