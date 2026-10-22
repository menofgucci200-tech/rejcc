<div>
    <x-admin-light.topbar title="Formateurs" />

    <div class="mx-auto max-w-[1100px] px-4 py-8 sm:px-8">
        <section class="mb-6 flex flex-wrap items-center gap-5 rounded-[22px] border border-[#E7E1D6] bg-gradient-to-br from-[#FAF6EF] via-white to-[#FFF8F1] p-6">
            <x-ui.illustration name="equipe" size="size-14" />
            <div class="min-w-[220px] flex-1">
                <h1 class="text-[22px] font-bold text-ink">Formateurs</h1>
                <p class="mt-1 max-w-2xl text-[13.5px] leading-relaxed text-[#5B677A]">Les personnes qui animent les sessions live : un membre du réseau, ou un intervenant extérieur. Pour chaque session, chacun reçoit par e-mail un lien personnel qui ouvre la visio et la liste des inscrits.</p>
            </div>
            <button type="button" wire:click="nouveau" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-[#8F0100]">
                <x-ui.icon name="plus" class="size-4" /> Ajouter un formateur
            </button>
        </section>

        @if ($message)
            <p class="mb-5 rounded-[14px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-3 text-[13px] font-semibold text-[#2E7D5B]">{{ $message }}</p>
        @endif

        @if ($formulaire)
            <section class="panel-enter mb-6 rounded-[22px] border border-[#E7E1D6] bg-white p-6 shadow-[0_8px_30px_-18px_rgba(3,29,89,.35)]">
                <h2 class="mb-4 flex items-center gap-2.5 text-[17px] font-bold text-ink"><x-ui.illustration name="crayon" size="size-8" /> {{ $editionId ? 'Modifier le formateur' : 'Nouveau formateur' }}</h2>

                @unless ($editionId)
                    <div class="mb-4 grid gap-2 sm:grid-cols-2">
                        @foreach (['membre' => ['Membre du réseau', 'Il retrouve ses sessions dans son espace membre.'], 'exterieur' => ['Intervenant extérieur', 'Sans compte : un lien personnel par session, par e-mail.']] as $val => [$titre, $aide])
                            <label class="flex cursor-pointer items-start gap-3 rounded-[14px] border p-3.5 {{ $type === $val ? 'border-azure/50 bg-[#FAF6EF]' : 'border-[#E7E1D6] bg-white' }}">
                                <input type="radio" wire:model.live="type" value="{{ $val }}" class="mt-1">
                                <span><span class="block text-[13px] font-bold text-ink">{{ $titre }}</span><span class="text-[12px] text-[#5B677A]">{{ $aide }}</span></span>
                            </label>
                        @endforeach
                    </div>
                @endunless

                <div class="grid gap-4 sm:grid-cols-2">
                    @if ($type === 'membre')
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Membre</label>
                            @if ($membreChoisi)
                                <p class="flex items-center justify-between rounded-[11px] border border-azure/50 bg-[#FAF6EF] px-3.5 py-2.5 text-sm font-semibold text-ink">{{ $membreChoisi }}
                                    @unless ($editionId)<button type="button" wire:click="$set('membreChoisi', '')" class="text-xs font-bold text-azure">Changer</button>@endunless
                                </p>
                            @else
                                <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Rechercher par nom ou e-mail" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                                @if ($resultats)
                                    <div class="mt-1.5 overflow-hidden rounded-[11px] border border-[#E7E1D6] bg-white">
                                        @foreach ($resultats as $m)
                                            <button type="button" wire:click="choisirMembre({{ $m['id'] }}, @js(trim($m['prenom'].' '.$m['nom'])))" class="flex w-full items-center justify-between gap-3 border-t border-[#EFEAE1] px-3.5 py-2 text-left text-[13px] first:border-t-0 hover:bg-[#FAF6EF]">
                                                <span class="font-semibold text-ink">{{ $m['prenom'] }} {{ $m['nom'] }}</span><span class="text-[12px] text-[#5B677A]">{{ $m['email'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif (mb_strlen(trim($recherche)) >= 2)
                                    <p class="mt-1.5 text-[12px] text-[#5B677A]">Aucun membre trouvé (ou déjà formateur).</p>
                                @endif
                            @endif
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prénom</label>
                            <input wire:model="prenom" type="text" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom</label>
                            <input wire:model="nom" type="text" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">E-mail (reçoit les liens d'animateur)</label>
                            <input wire:model="email" type="email" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone</label>
                            <input wire:model="telephone" type="text" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Spécialité</label>
                        <input wire:model="specialite" type="text" placeholder="Ex. Création d'entreprise, leadership, comptabilité…" class="w-full rounded-[11px] border border-[#E7E1D6] bg-[#FDFBF7] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                    </div>
                    @if ($editionId)
                        <label class="flex items-center gap-2 text-[13px] font-semibold text-ink"><input type="checkbox" wire:model="actif" class="size-4 rounded"> Actif (proposé dans les sessions, lien valable)</label>
                    @endif
                </div>
                @if ($erreur)
                    <p class="mt-4 rounded-[11px] bg-[#FDEEEE] px-3.5 py-2.5 text-[13px] font-semibold text-[#AC0100]">{{ $erreur }}</p>
                @endif
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="enregistrer" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white hover:bg-[#8F0100]">Enregistrer</button>
                    <button type="button" wire:click="$set('formulaire', false)" class="btn-tap rounded-full border border-[#E7E1D6] px-5 py-2.5 text-[13.5px] font-bold text-[#5B677A] hover:bg-[#FAF6EF]">Annuler</button>
                </div>
            </section>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            @forelse ($formateurs as $f)
                <article class="flex items-start gap-3.5 rounded-[18px] border border-[#EFEAE1] bg-white p-4 shadow-[0_2px_10px_-6px_rgba(3,29,89,.2)] {{ $f['actif'] ? '' : 'opacity-60' }}" wire:key="formateur-{{ $f['id'] }}">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-ink" style="background: {{ ['#F3DDB0', '#C6E6D5', '#C9D3EE', '#EAD9C4'][$loop->index % 4] }}">{{ mb_strtoupper(mb_substr($f['prenom'], 0, 1).mb_substr($f['nom'], 0, 1)) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[14.5px] font-bold text-ink">{{ $f['nom_complet'] }}</p>
                        <p class="text-[12px] text-[#5B677A]">{{ $f['specialite'] ?: 'Spécialité non précisée' }}</p>
                        <p class="mt-1.5 flex flex-wrap gap-1.5">
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $f['exterieur'] ? 'bg-[#FFF6E9] text-[#B7791F]' : 'bg-[#F4FBF7] text-[#2E7D5B]' }}">{{ $f['exterieur'] ? 'Intervenant extérieur' : 'Membre du réseau' }}</span>
                            <span class="rounded-full bg-[#FAF6EF] px-2.5 py-0.5 text-[11px] font-bold text-[#3D5AA6]">{{ $f['sessions_a_venir'] }} session{{ $f['sessions_a_venir'] > 1 ? 's' : '' }} à venir</span>
                            @unless ($f['actif'])<span class="rounded-full bg-[#F1F1F4] px-2.5 py-0.5 text-[11px] font-bold text-[#5B677A]">Inactif</span>@endunless
                        </p>
                    </div>
                    <button type="button" wire:click="modifier({{ $f['id'] }})" class="btn-tap shrink-0 rounded-full border border-[#E7E1D6] px-3 py-1.5 text-[12px] font-bold text-[#5B677A] hover:bg-[#FAF6EF]">Modifier</button>
                </article>
            @empty
                <div class="flex flex-col items-center rounded-[20px] border border-dashed border-[#E7E1D6] bg-white px-6 py-12 text-center sm:col-span-2">
                    <x-ui.illustration name="equipe" size="size-14" />
                    <p class="mt-3 text-[14px] font-bold text-ink">Aucun formateur pour l'instant</p>
                    <p class="mt-1 text-[12.5px] text-[#5B677A]">Ajoutez les personnes qui animeront les sessions live.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
