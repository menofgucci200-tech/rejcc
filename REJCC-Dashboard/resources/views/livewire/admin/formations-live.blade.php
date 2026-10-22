<div>
    <x-admin-light.topbar title="Formations live" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        {{-- En-tête --}}
        <section class="mb-6 flex flex-wrap items-center gap-5 rounded-[22px] border border-[#F3E3D3] bg-gradient-to-br from-[#FFF8F1] via-[#FFFDF8] to-[#F2FAF5] p-6">
            <x-ui.illustration name="visio" size="size-14" />
            <div class="min-w-[220px] flex-1">
                <h1 class="text-[22px] font-bold text-[#3B3F5C]">Formations live</h1>
                <p class="mt-1 max-w-2xl text-[13.5px] leading-relaxed text-[#6B6F86]">Programmez une session en visio : elle s'affiche dans le calendrier des membres, qui sont prévenus puis reçoivent un rappel la veille et le jour même.</p>
            </div>
            <button type="button" wire:click="nouvelle" data-test="programmer-session"
                class="btn-tap inline-flex items-center gap-2 rounded-full bg-[#F47C7C] px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-[#E86A6A]">
                <x-ui.icon name="plus" class="size-4" /> Programmer une session
            </button>
        </section>

        {{-- Compte Google du REJCC (réunions Meet) --}}
        <section class="mb-6 flex flex-wrap items-center gap-4 rounded-[18px] border border-[#E4E1EC] bg-white px-5 py-4" data-test="compte-google">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px] bg-[#EEF6FD]"><x-ui.illustration name="lien" size="size-8" /></span>
            <div class="min-w-[220px] flex-1">
                @if ($google['compte'])
                    <p class="text-[13.5px] font-bold text-[#3B3F5C]">Google Meet connecté : {{ $google['compte']['email'] }}</p>
                    <p class="text-[12px] text-[#6B6F86]">Les réunions sont créées automatiquement avec ce compte. Le jour de la session, une personne connectée à ce compte ouvre la réunion, admet les participants et gère la salle ; le formateur anime.</p>
                @elseif ($google['configure'])
                    <p class="text-[13.5px] font-bold text-[#3B3F5C]">Connecter le compte Google du REJCC</p>
                    <p class="text-[12px] text-[#6B6F86]">La personne qui a la main sur le compte (ex. contact.rejcc@gmail.com) clique ici et autorise l'accès au calendrier chez Google : aucun mot de passe n'est donné à la plateforme.</p>
                @else
                    <p class="text-[13.5px] font-bold text-[#3B3F5C]">Google Meet : lien à coller pour le moment</p>
                    <p class="text-[12px] text-[#6B6F86]">Créez la réunion sur meet.google.com avec le compte du REJCC, puis collez son lien. La création automatique arrive dès que la connexion Google est configurée sur le serveur.</p>
                @endif
            </div>
            @if ($google['compte'])
                <button type="button" wire:click="deconnecterGoogle" wire:confirm="Déconnecter le compte Google ? Les réunions déjà créées restent valables." class="btn-tap rounded-full border border-[#E4E1EC] px-4 py-2 text-[12.5px] font-bold text-[#6B6F86] hover:bg-[#F7F5FB]">Déconnecter</button>
            @elseif ($google['configure'])
                <button type="button" wire:click="connecterGoogle" class="btn-tap rounded-full bg-[#4285F4] px-4 py-2 text-[12.5px] font-bold text-white hover:bg-[#3574DE]">Connecter le compte Google</button>
            @endif
        </section>

        @if ($message)
            <p class="mb-5 rounded-[14px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-3 text-[13px] font-semibold text-[#2E7D5B]">{{ $message }}</p>
        @endif

        {{-- Formulaire --}}
        @if ($formulaire)
            <section class="panel-enter mb-6 rounded-[22px] border border-[#E8E1F7] bg-white p-6 shadow-[0_8px_30px_-18px_rgba(59,63,92,.35)]" data-test="formulaire-session">
                <div class="mb-5 flex items-center gap-3">
                    <x-ui.illustration name="crayon" size="size-9" />
                    <h2 class="text-[17px] font-bold text-[#3B3F5C]">{{ $editionId ? 'Modifier la session' : 'Nouvelle session live' }}</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Titre de la session</label>
                        <input wire:model="titre" type="text" placeholder="Ex. Atelier : rédiger son business plan" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-[#C3B5F0] focus:bg-white">
                        @error('titre') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Formation liée</label>
                        <select wire:model="formationId" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-[#C3B5F0]">
                            <option value="">Aucune (session indépendante)</option>
                            @foreach ($formations as $f)
                                <option value="{{ $f['id'] }}">{{ $f['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Formateur(s)</label>
                        @if ($listeFormateurs)
                            <div class="max-h-36 space-y-1 overflow-y-auto rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] p-2.5">
                                @foreach ($listeFormateurs as $fo)
                                    <label class="flex items-center gap-2 text-[13px] text-[#3B3F5C]">
                                        <input type="checkbox" wire:model="formateurs" value="{{ $fo['id'] }}" class="size-4 rounded">
                                        {{ $fo['nom'] }} <span class="text-[11.5px] text-[#9A9DB0]">{{ $fo['exterieur'] ? 'extérieur' : 'membre' }}{{ $fo['specialite'] ? ' · '.$fo['specialite'] : '' }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-[11.5px] text-[#9A9DB0]">Chacun reçoit par e-mail son lien personnel d'animateur.</p>
                        @else
                            <p class="rounded-[11px] border border-dashed border-[#E4E1EC] px-3 py-2.5 text-[12.5px] text-[#6B6F86]">Aucun formateur enregistré. <a href="{{ route('admin.formateurs') }}" wire:navigate class="font-semibold text-[#7A6BB8] hover:underline">Ajouter des formateurs</a></p>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Date</label>
                            <input wire:model="date" type="date" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3 py-2.5 text-sm outline-none focus:border-[#C3B5F0]">
                            @error('date') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Heure</label>
                            <input wire:model="heure" type="time" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3 py-2.5 text-sm outline-none focus:border-[#C3B5F0]">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Durée</label>
                            <select wire:model="duree" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3 py-2.5 text-sm outline-none focus:border-[#C3B5F0]">
                                @foreach ([30 => '30 min', 45 => '45 min', 60 => '1 h', 90 => '1 h 30', 120 => '2 h', 180 => '3 h'] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="rounded-[16px] border border-[#F0E6D8] bg-[#FFFBF5] p-4 sm:col-span-2" data-test="public-vise">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="flex items-center gap-2 text-[13.5px] font-bold text-[#3B3F5C]"><x-ui.illustration name="megaphone" size="size-6" /> Public visé</p>
                            @if ($publicTotal !== null)
                                <span class="rounded-full bg-white px-3 py-1 text-[12px] font-bold text-[#B7791F] shadow-sm" data-test="total-public">{{ $publicTotal === 0 ? 'Aucun membre concerné' : number_format($publicTotal, 0, ',', ' ').' membre'.($publicTotal > 1 ? 's' : '').' concerné'.($publicTotal > 1 ? 's' : '') }}</span>
                            @endif
                        </div>
                        <p class="mb-3 text-[12px] text-[#6B6F86]">Sans critère : tous les membres. Les critères se cumulent ; seules les personnes visées sont prévenues, voient la session et peuvent y entrer.</p>
                        <label class="mb-3 flex items-center gap-2 text-[13px] text-[#3B3F5C] {{ $formationId === '' ? 'opacity-50' : '' }}">
                            <input type="checkbox" wire:model.live="cibleFormation" @disabled($formationId === '') class="size-4 rounded"> Seulement les inscrits à la formation liée
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="mb-1.5 text-[11.5px] font-semibold uppercase tracking-wide text-[#9A8F7B]">Domaines (groupes sectoriels)</p>
                                <div class="max-h-36 space-y-1 overflow-y-auto rounded-[11px] bg-white p-2.5">
                                    @forelse ($groupes as $g)
                                        <label class="flex items-center gap-2 text-[12.5px] text-[#3B3F5C]"><input type="checkbox" wire:model.live="cibleGroupes" value="{{ $g['id'] }}" class="size-3.5 rounded"> {{ $g['name'] }}</label>
                                    @empty
                                        <p class="text-[12px] text-[#9A9DB0]">Aucun groupe sectoriel.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <p class="mb-1.5 text-[11.5px] font-semibold uppercase tracking-wide text-[#9A8F7B]">Profils</p>
                                <div class="space-y-1 rounded-[11px] bg-white p-2.5">
                                    @foreach ($profils as $cle => $libelle)
                                        <label class="flex items-center gap-2 text-[12.5px] text-[#3B3F5C]"><input type="checkbox" wire:model.live="cibleProfils" value="{{ $cle }}" class="size-3.5 rounded"> {{ $libelle }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-semibold text-[#6B6F86]">Visio</label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-[14px] border p-3.5 {{ ! $google['compte'] ? 'cursor-not-allowed opacity-55' : 'cursor-pointer' }} {{ $visio === 'meet' ? 'border-[#7FD1AE] bg-[#EFFAF4]' : 'border-[#E4E1EC] bg-white' }}">
                                <input wire:model.live="visio" type="radio" value="meet" class="mt-1" @disabled(! $google['compte'])>
                                <span><span class="block text-[13px] font-bold text-[#3B3F5C]">Google Meet du REJCC</span><span class="text-[12px] text-[#6B6F86]">{{ $google['compte'] ? 'Réunion créée automatiquement avec '.$google['compte']['email'].'.' : 'Disponible une fois le compte Google connecté.' }}</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-[14px] border p-3.5 {{ $visio === 'lien' ? 'border-[#9EC9F0] bg-[#F0F7FD]' : 'border-[#E4E1EC] bg-white' }}">
                                <input wire:model.live="visio" type="radio" value="lien" class="mt-1">
                                <span><span class="block text-[13px] font-bold text-[#3B3F5C]">Coller un lien</span><span class="text-[12px] text-[#6B6F86]">Réunion Google Meet créée avec le compte du REJCC (ou Zoom, Teams).</span></span>
                            </label>
                            @if ($visio === 'auto')
                                <p class="text-[12px] text-[#6B6F86] sm:col-span-2">Cette session utilise une salle Jitsi : elle est conservée tant que vous ne choisissez pas une autre option.</p>
                            @endif
                        </div>
                        @if ($visio === 'lien')
                            <input wire:model="lienVisio" type="url" placeholder="https://meet.google.com/…" class="mt-2 w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-[#9EC9F0]">
                            @error('lienVisio') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Description (facultatif)</label>
                        <textarea wire:model="description" rows="3" placeholder="Programme, prérequis, ce qu'il faut préparer…" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-[#C3B5F0] focus:bg-white"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#6B6F86]">Nombre de places (vide = sans limite)</label>
                        <input wire:model="places" type="number" min="1" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-[#C3B5F0]">
                    </div>
                    <label class="flex items-center gap-2.5 self-end pb-2 text-[13px] font-semibold text-[#3B3F5C]">
                        <input wire:model="annoncer" type="checkbox" class="size-4 rounded"> Prévenir tous les membres par notification
                    </label>
                </div>
                @if ($erreur)
                    <p class="mt-4 rounded-[11px] bg-[#FDEEEE] px-3.5 py-2.5 text-[13px] font-semibold text-[#C24E4E]">{{ $erreur }}</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2">
                    <button type="button" wire:click="enregistrer" wire:loading.attr="disabled" class="btn-tap rounded-full bg-[#3B3F5C] px-5 py-2.5 text-[13.5px] font-bold text-white hover:bg-[#2C2F47] disabled:opacity-60">{{ $editionId ? 'Enregistrer' : 'Programmer la session' }}</button>
                    <button type="button" wire:click="fermer" class="btn-tap rounded-full border border-[#E4E1EC] px-5 py-2.5 text-[13.5px] font-bold text-[#6B6F86] hover:bg-[#F7F5FB]">Annuler</button>
                </div>
            </section>
        @endif

        {{-- En direct / aujourd'hui --}}
        @if ($enDirect->isNotEmpty())
            <section class="mb-6 grid gap-4 md:grid-cols-2">
                @foreach ($enDirect as $s)
                    <div class="flex flex-wrap items-center gap-4 rounded-[20px] border border-[#CDEBDD] bg-[#EFFAF4] p-5" wire:key="direct-{{ $s['id'] }}">
                        <x-ui.illustration name="visio" size="size-12" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] {{ $s['en_cours'] ? 'text-[#2E7D5B]' : 'text-[#B7791F]' }}">{{ $s['en_cours'] ? '● En ce moment' : "Aujourd'hui à ".$s['debut']->format('H\hi') }}</p>
                            <p class="truncate text-[15px] font-bold text-[#3B3F5C]">{{ $s['title'] }}</p>
                            <p class="text-[12px] text-[#6B6F86]">{{ $s['inscrits'] }} inscrit{{ $s['inscrits'] > 1 ? 's' : '' }}@if ($s['animateur']) · {{ $s['animateur'] }}@endif</p>
                        </div>
                        <a href="{{ $s['lien_visio'] }}" target="_blank" rel="noopener" data-test="lancer-visio" class="btn-tap inline-flex items-center gap-2 rounded-full bg-[#2E9E6E] px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-[#268A5F]">
                            <x-ui.icon name="external-link" class="size-4" /> Lancer la visio
                        </a>
                    </div>
                @endforeach
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            {{-- Sessions à venir --}}
            <section>
                <div class="mb-3 flex items-center gap-2.5">
                    <x-ui.illustration name="cahier" size="size-8" />
                    <h2 class="text-[17px] font-bold text-[#3B3F5C]">Sessions à venir</h2>
                    @if ($jour)
                        <button type="button" wire:click="choisirJour('{{ $jour }}')" class="ml-auto rounded-full bg-[#F7F5FB] px-3 py-1 text-[12px] font-semibold text-[#6B6F86] hover:bg-[#EEE9FB]">{{ \Carbon\Carbon::parse($jour)->locale('fr')->isoFormat('dddd D MMMM') }} ✕</button>
                    @endif
                </div>
                <div class="space-y-3">
                    @forelse ($aVenir as $s)
                        @php $teinte = ['#FFF3E8', '#EEF6FD', '#F3EFFC', '#FFF8E1'][$loop->index % 4]; @endphp
                        <article class="rounded-[18px] border border-[#EDE9F3] bg-white p-4 shadow-[0_2px_10px_-6px_rgba(59,63,92,.2)]" wire:key="session-{{ $s['id'] }}">
                            <div class="flex flex-wrap items-start gap-4">
                                <div class="flex w-[62px] shrink-0 flex-col items-center rounded-[14px] py-2" style="background: {{ $teinte }}">
                                    <span class="text-[10.5px] font-bold uppercase text-[#8A7F6B]">{{ $s['debut']->isoFormat('MMM') }}</span>
                                    <span class="text-[22px] font-extrabold leading-none text-[#3B3F5C]">{{ $s['debut']->format('d') }}</span>
                                    <span class="mt-0.5 text-[11px] font-semibold text-[#6B6F86]">{{ $s['debut']->format('H\hi') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[15px] font-bold text-[#3B3F5C]">{{ $s['title'] }}
                                        @if ($s['statut'] === 'annule') <span class="ml-1 rounded-full bg-[#FDEEEE] px-2 py-0.5 align-middle text-[10.5px] font-bold text-[#C24E4E]">Annulée</span> @endif
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#6B6F86]">
                                        {{ ucfirst($s['debut']->isoFormat('dddd')) }} · {{ $s['duree'] ? ($s['duree'] >= 60 ? intdiv($s['duree'], 60).' h'.($s['duree'] % 60 ? ' '.($s['duree'] % 60) : '') : $s['duree'].' min') : '' }}
                                        @if ($s['formation']) · <span class="font-semibold text-[#7A6BB8]">{{ $s['formation'] }}</span>@endif
                                        @if ($s['animateur']) · {{ $s['animateur'] }}@endif
                                    </p>
                                    <p class="mt-1.5 flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F4FBF7] px-2.5 py-1 text-[11.5px] font-semibold text-[#2E7D5B]"><x-ui.icon name="users" class="size-3.5" /> {{ $s['inscrits'] }} inscrit{{ $s['inscrits'] > 1 ? 's' : '' }}{{ $s['capacity'] ? ' / '.$s['capacity'] : '' }}</span>
                                        <span class="inline-flex items-center rounded-full bg-[#FFF6E9] px-2.5 py-1 text-[11.5px] font-semibold text-[#B7791F]">{{ $s['public'] }}</span>
                                    </p>
                                    @if ($s['formateurs'])
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($s['formateurs'] as $fo)
                                                <button type="button" x-data x-on:click="navigator.clipboard.writeText(@js($fo['lien'])); $dispatch('rj-toast', { message: 'Lien d\'animateur de {{ $fo['nom'] }} copié.', type: 'succes' })"
                                                    title="Copier le lien personnel d'animateur" class="inline-flex items-center gap-1.5 rounded-full border border-[#E8E1F7] bg-[#F7F4FD] px-2.5 py-1 text-[11.5px] font-semibold text-[#6A5AA8] hover:bg-[#EEE9FB]">
                                                    <x-ui.icon name="external-link" class="size-3" /> {{ $fo['nom'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @if ($s['statut'] === 'publie')
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <a href="{{ $s['lien_visio'] }}" target="_blank" rel="noopener" title="Ouvrir la salle de visio" class="btn-tap rounded-full border border-[#CDEBDD] bg-[#EFFAF4] px-3 py-1.5 text-[12px] font-bold text-[#2E7D5B] hover:bg-[#E2F5EB]">Salle de visio</a>
                                        <button type="button" wire:click="voirInscrits({{ $s['id'] }})" class="btn-tap rounded-full border border-[#E4E1EC] px-3 py-1.5 text-[12px] font-bold text-[#6B6F86] hover:bg-[#F7F5FB]">Inscrits</button>
                                        <button type="button" wire:click="modifier({{ $s['id'] }})" class="btn-tap rounded-full border border-[#E4E1EC] px-3 py-1.5 text-[12px] font-bold text-[#6B6F86] hover:bg-[#F7F5FB]">Modifier</button>
                                        <button type="button" wire:click="annuler({{ $s['id'] }})" wire:confirm="Annuler « {{ $s['title'] }} » ? Les inscrits seront prévenus." class="btn-tap rounded-full px-3 py-1.5 text-[12px] font-bold text-[#C24E4E] hover:bg-[#FDEEEE]">Annuler</button>
                                    </div>
                                @endif
                            </div>
                            @if ($inscritsDe === $s['id'])
                                <div class="panel-enter mt-3 rounded-[14px] bg-[#FBFAFD] p-3">
                                    @forelse ($inscrits as $i)
                                        <p class="flex flex-wrap justify-between gap-2 border-t border-[#EEEAF4] py-1.5 text-[12.5px] first:border-t-0"><span class="font-semibold text-[#3B3F5C]">{{ $i['nom'] }}</span><span class="text-[#6B6F86]">{{ $i['email'] }}{{ $i['telephone'] ? ' · '.$i['telephone'] : '' }}</span></p>
                                    @empty
                                        <p class="py-2 text-center text-[12.5px] text-[#6B6F86]">Personne n'est encore inscrit.</p>
                                    @endforelse
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="flex flex-col items-center rounded-[20px] border border-dashed border-[#E4E1EC] bg-white px-6 py-12 text-center">
                            <x-ui.illustration name="agenda" size="size-14" />
                            <p class="mt-3 text-[14px] font-bold text-[#3B3F5C]">{{ $jour ? 'Aucune session ce jour-là' : 'Aucune session programmée' }}</p>
                            <p class="mt-1 text-[12.5px] text-[#6B6F86]">Programmez la première : les membres la verront dans leur calendrier.</p>
                            <button type="button" wire:click="nouvelle('{{ $jour }}')" class="btn-tap mt-4 rounded-full bg-[#F47C7C] px-5 py-2 text-[13px] font-bold text-white hover:bg-[#E86A6A]">Programmer une session</button>
                        </div>
                    @endforelse
                </div>

                @if ($passees->isNotEmpty())
                    <details class="mt-6 rounded-[18px] border border-[#EDE9F3] bg-white p-4">
                        <summary class="flex cursor-pointer items-center gap-2.5 text-[14px] font-bold text-[#3B3F5C]"><x-ui.illustration name="horloge" size="size-7" /> Sessions passées</summary>
                        @foreach ($passees as $s)
                            <p class="flex flex-wrap justify-between gap-2 border-t border-[#F1EEF6] py-2 text-[12.5px] first:mt-3"><span class="font-semibold text-[#3B3F5C]">{{ $s['title'] }}</span><span class="text-[#6B6F86]">{{ $s['debut']->isoFormat('D MMM YYYY') }} · {{ $s['inscrits'] }} inscrit{{ $s['inscrits'] > 1 ? 's' : '' }}</span></p>
                        @endforeach
                    </details>
                @endif
            </section>

            {{-- Calendrier --}}
            <aside class="h-fit rounded-[20px] border border-[#EDE9F3] bg-white p-5 shadow-[0_2px_10px_-6px_rgba(59,63,92,.2)]">
                <div class="mb-3 flex items-center gap-2.5">
                    <x-ui.illustration name="agenda" size="size-8" />
                    <h2 class="text-[15px] font-bold text-[#3B3F5C]">Calendrier</h2>
                </div>
                <div class="mb-3 flex items-center justify-between">
                    <button type="button" wire:click="moisPrecedent" aria-label="Mois précédent" class="flex size-8 items-center justify-center rounded-full bg-[#F7F5FB] text-[#3B3F5C] hover:bg-[#EEE9FB]"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /></button>
                    <p class="text-[13px] font-bold text-[#3B3F5C]">{{ $moisLabel }}</p>
                    <button type="button" wire:click="moisSuivant" aria-label="Mois suivant" class="flex size-8 items-center justify-center rounded-full bg-[#F7F5FB] text-[#3B3F5C] hover:bg-[#EEE9FB]"><x-ui.icon name="chevron-right" class="size-4" /></button>
                </div>
                <div class="mb-2 grid grid-cols-7 gap-1 text-center text-[10px] font-semibold text-[#A3A6B8]">
                    <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    @foreach ($cells as $date)
                        @if ($date === null)
                            <span></span>
                        @else
                            @php $nb = $jours[$date] ?? 0; $choisi = $jour === $date; @endphp
                            <button type="button" wire:click="{{ $nb ? "choisirJour('{$date}')" : "nouvelle('{$date}')" }}" title="{{ $nb ? $nb.' session(s)' : 'Programmer une session ce jour' }}"
                                class="relative mx-auto flex size-8 items-center justify-center rounded-full text-[11.5px] font-semibold transition-colors
                                    {{ $choisi ? 'bg-[#F47C7C] text-white' : ($nb ? 'bg-[#FFF0E6] text-[#3B3F5C] hover:bg-[#FFE3D3]' : ($date === $aujourdhui ? 'ring-2 ring-[#C3B5F0] text-[#3B3F5C]' : 'text-[#9A9DB0] hover:bg-[#F7F5FB]')) }}">
                                {{ (int) substr($date, 8, 2) }}
                                @if ($nb && ! $choisi)<span class="absolute bottom-0.5 size-1.5 rounded-full bg-[#F4A261]"></span>@endif
                            </button>
                        @endif
                    @endforeach
                </div>
                <p class="mt-3 text-center text-[11px] text-[#A3A6B8]">Touchez un jour libre pour y programmer une session.</p>
            </aside>
        </div>
    </div>
</div>
