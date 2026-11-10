<div>
    <x-admin-light.topbar title="Textes prêts à copier" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <x-admin-light.onglets groupe="ressources" />
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Bibliothèque de textes</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Des textes justes et cohérents, à copier en un clic. Remplacez les repères entre crochets, comme [date] ou [lieu], avant de publier. Nom officiel : Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.</p>
            </div>
            <div class="flex gap-2">
                @if ($defautsManquants)
                    <button wire:click="restaurer" wire:confirm="Remettre les {{ $defautsManquants }} texte(s) par défaut supprimé(s) ? Vos textes modifiés ne changent pas." class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Restaurer les textes par défaut ({{ $defautsManquants }})</button>
                @endif
                <button wire:click="openCreate" data-test="ajouter-texte" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Ajouter un texte</button>
            </div>
        </div>

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm)
            <p wire:key="flash-ko" class="panel-enter mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if ($showForm)
            <form wire:key="form-texte" wire:submit="save" data-test="form-texte" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-[1fr_1fr_160px]">
                <div class="flex items-center justify-between sm:col-span-3">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le texte' : 'Nouveau texte' }}</p>
                    <button type="button" wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div><label class="{{ $lab }}">Titre</label><input wire:model="titre" type="text" data-test="texte-titre" maxlength="150" class="{{ $input }}" /></div>
                <div>
                    <label class="{{ $lab }}">Catégorie</label>
                    <select wire:model="categorieForm" class="{{ $input }}">
                        @foreach ($categories as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                </div>
                <div><label class="{{ $lab }}">Limite conseillée</label><input wire:model.live.debounce.400ms="limite" type="number" min="10" max="10000" placeholder="caractères" class="{{ $input }}" /></div>
                <div class="sm:col-span-3" x-data="{ n: 0 }" x-init="n = $refs.zone.value.length">
                    <label class="{{ $lab }}">Texte</label>
                    <textarea x-ref="zone" wire:model="contenu" x-on:input="n = $el.value.length" data-test="texte-contenu" rows="9" maxlength="10000" class="{{ $input }} font-[inherit] leading-relaxed"></textarea>
                    <p class="mt-1 text-right text-[11px] font-semibold" :class="{{ $limite !== '' ? (int) $limite : 0 }} && n > {{ $limite !== '' ? (int) $limite : 0 }} ? 'text-accent' : 'text-[#5B677A]'">
                        <span x-text="n">0</span> caractère<span x-show="n > 1">s</span>@if ($limite !== '') / {{ (int) $limite }}@endif
                    </p>
                </div>
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-3">{{ $erreur }}</p> @endif
                <button type="submit" wire:loading.attr="disabled" data-test="enregistrer-texte" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:w-fit">Enregistrer</button>
            </form>
        @endif

        <div class="mb-5 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Tous'] + $categories as $cle => $libelle)
                <button wire:click="setCategorie('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $categorie === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }} <span class="{{ $categorie === $cle ? 'text-white/70' : 'text-[#5B677A]' }}">{{ $compteurs[$cle] ?? 0 }}</span></button>
            @endforeach
            <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Rechercher un texte…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-56" />
        </div>

        @forelse ($categories as $cle => $libelle)
            @continue(! isset($textes[$cle]))
            <section wire:key="cat-{{ $cle }}" class="mb-7">
                <h3 class="mb-2.5 text-[14px] font-bold text-ink">{{ $libelle }}</h3>
                <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                    @foreach ($textes[$cle] as $t)
                        @php $n = mb_strlen($t['contenu']); $trop = $t['limite'] && $n > $t['limite']; @endphp
                        <article wire:key="t-{{ $t['id'] }}" data-test="carte-texte" x-data="{ copie: false }" class="flex flex-col rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="flex items-center gap-2 border-b border-[#EDF0F5] px-4 py-2.5">
                                <p class="min-w-0 flex-1 truncate text-[13px] font-bold text-brand">{{ $t['titre'] }}</p>
                                <span class="shrink-0 text-[11px] font-semibold {{ $trop ? 'text-accent' : 'text-[#5B677A]' }}" title="Nombre de caractères">{{ $n }}{{ $t['limite'] ? ' / '.$t['limite'] : '' }} car.</span>
                                @if ($t['modifiable'])
                                    <button wire:click="openEdit({{ $t['id'] }})" class="icon-btn rounded-lg p-1 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                                    <button wire:click="delete({{ $t['id'] }})" wire:confirm="Supprimer le texte « {{ $t['titre'] }} » ?{{ $t['par_defaut'] ? ' (Texte par défaut : vous pourrez le restaurer.)' : '' }}" class="icon-btn rounded-lg p-1 text-[#5B677A] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                @endif
                            </div>
                            <pre x-ref="texte" class="max-h-56 flex-1 overflow-y-auto whitespace-pre-wrap break-words px-4 py-3 font-sans text-[12.5px] leading-relaxed text-ink">{{ $t['contenu'] }}</pre>
                            <div class="flex items-center justify-end border-t border-[#EDF0F5] px-4 py-2">
                                <button type="button" data-test="copier-texte"
                                    @click="navigator.clipboard.writeText($refs.texte.textContent); copie = true; setTimeout(() => copie = false, 1600)"
                                    class="btn-tap inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-[11.5px] font-bold transition"
                                    :class="copie ? 'bg-[#1C8F4C] text-white' : 'bg-brand text-white hover:bg-brand/90'">
                                    <x-ui.icon name="check" class="size-3" x-show="copie" /><span x-text="copie ? 'Copié !' : 'Copier'">Copier</span>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
        @endforelse
        @if ($textes->isEmpty())
            <p class="rounded-[18px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucun texte ne correspond.</p>
        @endif
    </div>
</div>
