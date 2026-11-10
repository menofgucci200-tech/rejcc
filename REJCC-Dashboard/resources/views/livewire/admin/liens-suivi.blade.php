<div>
    <x-admin-light.topbar title="Liens de suivi" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <x-admin-light.onglets groupe="statistiques" />
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Liens de suivi</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Créez un lien court par canal de diffusion (un pour WhatsApp, un pour Facebook…) vers la même page : vous saurez quel canal ramène du monde. Les clics sont comptés par jour, sans rien enregistrer sur les personnes qui cliquent.</p>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex max-w-full items-center gap-1.5 break-all rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5 shrink-0" /> {{ $message }}</p>
        @endif

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Liens indisponibles (accès à la rubrique « Espace Communication » requis).</p>
        @else
            {{-- Formulaire : création ou modification --}}
            <form wire:submit="enregistrer" data-test="formulaire-lien" class="mb-6 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-3 text-[13px] font-bold text-brand">{{ $editId ? 'Modifier le lien' : 'Nouveau lien' }}</p>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Libellé</span>
                        <input type="text" wire:model="libelle" maxlength="150" required placeholder="Ex. : Affiche du gala – groupe WhatsApp" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] focus:border-brand focus:outline-none">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Canal</span>
                        <select wire:model="canal" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] focus:border-brand focus:outline-none">
                            @foreach ($canaux as $cle => $label)
                                <option value="{{ $cle }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Adresse de destination</span>
                        <input type="url" wire:model="cible" maxlength="2000" required placeholder="https://rejcc.site/evenements/…" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] focus:border-brand focus:outline-none">
                        <span class="mt-1 block text-[11px] text-[#5B677A]">Adresse complète en http:// ou https://. Vers une page du site, la provenance s'affiche aussi dans « Statistiques du site ».</span>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Lien court</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[13px] text-[#5B677A]">{{ $base }}</span>
                            <input type="text" wire:model="code" maxlength="40" required pattern="[A-Za-z0-9\-]{3,40}" data-test="code-lien" class="w-44 rounded-[10px] border border-brand/15 px-3 py-2 font-mono text-[13px] focus:border-brand focus:outline-none">
                            <button type="button" wire:click="proposerCode" class="btn-tap inline-flex items-center gap-1 rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="refresh-cw" class="size-3" /> Autre code</button>
                        </div>
                        <span class="mt-1 block text-[11px] text-[#5B677A]">Lettres, chiffres et tirets. Vous pouvez écrire un code parlant (ex. : gala-wa).</span>
                    </label>
                </div>
                @if ($erreur)
                    <p data-test="erreur-lien" class="mt-3 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="submit" data-test="enregistrer-lien" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">{{ $editId ? 'Enregistrer' : 'Créer le lien' }}</button>
                    @if ($editId)
                        <button type="button" wire:click="annuler" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Annuler</button>
                    @endif
                </div>
            </form>

            {{-- Clics par canal --}}
            @if ($parCanal)
                <div class="mb-5 flex flex-wrap gap-2" data-test="clics-par-canal">
                    @foreach ($parCanal as $c)
                        <div class="rounded-[14px] border border-brand/10 bg-white px-4 py-2.5 shadow-[0_2px_8px_rgba(3,29,89,.04)]">
                            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#5B677A]">{{ $c['label'] }}</p>
                            <p class="text-[17px] font-bold text-brand">{{ number_format($c['clics_total'], 0, ',', ' ') }} <span class="text-[11.5px] font-semibold text-[#5B677A]">clic{{ $c['clics_total'] > 1 ? 's' : '' }} · {{ $c['clics_30j'] }} en 30 j</span></p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($liens as $l)
                    @php $url = $base.$l['code']; @endphp
                    <div data-test="ligne-lien" wire:key="lien-{{ $l['id'] }}" class="-mx-5 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0 {{ $l['actif'] ? '' : 'opacity-60' }}">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-brand/5 text-brand"><x-ui.icon name="link" class="size-4" /></span>
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $l['libelle'] }}
                                <span class="ml-1 rounded-full bg-cloud px-2 py-0.5 text-[10.5px] font-bold text-[#5B677A]">{{ $l['canal_label'] }}</span>
                                @unless ($l['actif'])<span class="ml-1 rounded-full bg-accent/10 px-2 py-0.5 text-[10.5px] font-bold text-accent">Désactivé</span>@endunless
                            </p>
                            <p class="mt-0.5 font-mono text-[12px] text-brand">{{ $url }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-[#5B677A]" title="{{ $l['cible'] }}">→ {{ $l['cible'] }}</p>
                            @if ($l['cree_par'])<p class="text-[10.5px] text-[#5B677A]">Créé par {{ $l['cree_par'] }} · {{ \Illuminate\Support\Carbon::parse($l['created_at'])->locale('fr')->translatedFormat('j M Y') }}</p>@endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[17px] font-bold text-brand" data-test="clics-total">{{ number_format($l['clics_total'], 0, ',', ' ') }}</p>
                            <p class="text-[10.5px] text-[#5B677A]">clic{{ $l['clics_total'] > 1 ? 's' : '' }} · {{ $l['clics_30j'] }} en 30 j</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                            <button type="button" x-data="{ copie: false }" x-on:click="navigator.clipboard.writeText(@js($url)); copie = true; setTimeout(() => copie = false, 1800)" data-test="copier-lien"
                                class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud" x-text="copie ? 'Copié !' : 'Copier le lien'">Copier le lien</button>
                            <button type="button" wire:click="basculer({{ $l['id'] }}, {{ $l['actif'] ? 'false' : 'true' }})" data-test="basculer-lien" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">{{ $l['actif'] ? 'Désactiver' : 'Réactiver' }}</button>
                            <button type="button" wire:click="modifier({{ $l['id'] }})" aria-label="Modifier" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-cloud hover:text-brand"><x-ui.icon name="pencil" class="size-4" /></button>
                            <button type="button" wire:click="supprimer({{ $l['id'] }})" wire:confirm="Supprimer ce lien et ses compteurs de clics ? Le lien court ne fonctionnera plus (il renverra vers l'accueil du site). Pour l'arrêter sans perdre les chiffres, désactivez-le plutôt." aria-label="Supprimer" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-4" /></button>
                        </div>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-[#5B677A]">Aucun lien de suivi pour l'instant. Créez le premier ci-dessus, par exemple pour la prochaine affiche partagée sur WhatsApp.</p>
                @endforelse
            </div>
        @endif
    </div>
</div>
