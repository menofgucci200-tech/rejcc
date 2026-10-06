@php
    $in = 'w-full rounded-[10px] border border-brand/12 bg-white px-3 py-2.5 text-[13.5px] text-ink placeholder:text-[#9AA6B8]';
    $lab = 'mb-1.5 block text-[12px] font-semibold text-[#5B677A]';
    $err = 'mt-1 block text-[11.5px] font-medium text-accent';
    $carte = 'rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-6';
    $titreCarte = 'text-[14px] font-bold text-brand';
    $btn = 'btn-tap inline-flex items-center justify-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60';
    $btn2 = 'btn-tap inline-flex items-center justify-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud disabled:opacity-60';
    $date = fn ($v, $f = 'D MMM YYYY [à] HH[h]mm') => $v ? \Illuminate\Support\Carbon::parse($v)->locale('fr')->isoFormat($f) : '';
    $interrupteur = fn (bool $on) => 'relative h-6 w-[42px] shrink-0 rounded-full transition-colors duration-200 '.($on ? 'bg-[#22A85A]' : 'bg-[#E1E6EE]');
@endphp

<div>
    <x-member-light.topbar title="Paramètres" />

    <div class="mx-auto max-w-[1100px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Paramètres</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        {{-- Onglets --}}
        <nav class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Rubriques des paramètres">
            <div class="flex w-max gap-1.5 rounded-full border border-brand/10 bg-white p-1 shadow-[0_1px_3px_rgba(3,29,89,.06)]">
                @foreach (\App\Livewire\Member\ProfileEditor::ONGLETS as $cle => [$libelle, $icone])
                    <button type="button" wire:click="setOnglet('{{ $cle }}')" data-test="onglet-{{ $cle }}" aria-current="{{ $onglet === $cle ? 'page' : 'false' }}"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-2 text-[12.5px] font-bold transition-colors {{ $onglet === $cle ? 'bg-brand text-white' : 'text-[#5B677A] hover:bg-cloud hover:text-brand' }}">
                        <x-ui.icon :name="$icone" class="size-3.5" /> {{ $libelle }}
                    </button>
                @endforeach
            </div>
        </nav>

        <div wire:loading.delay.class="opacity-60" wire:target="setOnglet" class="transition-opacity">

        {{-- ══════════════════ PROFIL ══════════════════ --}}
        @if ($onglet === 'profil')
            <div class="grid gap-5 lg:grid-cols-[300px_1fr]">
                <div class="flex flex-col gap-5">
                    <div class="{{ $carte }}" x-data="recadragePhoto()">
                        <p class="{{ $titreCarte }}">Photo de profil</p>
                        <div class="mt-4 flex flex-col items-center text-center">
                            <div x-data="{ casse: false }" wire:key="photo-{{ md5($photo) }}">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="Votre photo" data-test="photo-profil" x-show="!casse" x-on:error="casse = true" class="size-[112px] rounded-full object-cover ring-4 ring-cloud">
                                @endif
                                <div @if ($photo) x-show="casse" style="display:none" @endif class="flex size-[112px] items-center justify-center rounded-full text-3xl font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                    {{ mb_strtoupper(mb_substr($prenom ?: '?', 0, 1).mb_substr($nom ?: '', 0, 1)) }}
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap justify-center gap-2">
                                <label class="{{ $btn }} cursor-pointer !px-4 !py-2 !text-[12px]">
                                    <x-ui.icon name="image" class="size-3.5" /> {{ $photo ? 'Changer' : 'Ajouter une photo' }}
                                    <input type="file" accept="image/*" class="hidden" data-test="choisir-photo" x-on:change="choisir($event)">
                                </label>
                                @if ($photo)
                                    <button type="button" wire:click="removePhoto" wire:confirm="Retirer votre photo de profil ?" class="{{ $btn2 }} !py-2 !text-[12px]">Retirer</button>
                                @endif
                            </div>
                            <p class="mt-2 text-[11px] text-[#9AA6B8]">Vous pourrez la recadrer avant l'envoi. Elle apparaît sur votre carte membre et dans l'annuaire.</p>
                            @error('photoFile') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>

                        {{-- Recadrage --}}
                        <template x-teleport="body">
                            <div x-show="ouvert" x-transition.opacity style="display:none" class="fixed inset-0 z-[150] flex items-center justify-center bg-brand/60 p-4" x-on:keydown.escape.window="fermer()">
                                <div class="w-full max-w-[360px] rounded-[20px] bg-white p-5 shadow-2xl" data-test="recadrage">
                                    <p class="text-[15px] font-bold text-brand">Recadrer ma photo</p>
                                    <p class="mt-0.5 text-[12px] text-[#5B677A]">Faites glisser l'image et ajustez le zoom.</p>
                                    <div class="relative mx-auto mt-4 size-[280px] cursor-grab touch-none select-none overflow-hidden rounded-[14px] bg-cloud active:cursor-grabbing"
                                        x-on:mousedown.prevent="debut($event)" x-on:mousemove.window="deplacer($event)" x-on:mouseup.window="fin()"
                                        x-on:touchstart="debut($event)" x-on:touchmove.prevent="deplacer($event)" x-on:touchend="fin()">
                                        <img x-bind:src="src" alt="" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left" x-bind:style="style()">
                                        <div class="pointer-events-none absolute inset-0 rounded-[14px]" style="box-shadow: inset 0 0 0 999px rgba(3,29,89,.45); -webkit-mask: radial-gradient(circle at center, transparent 139px, #000 140px); mask: radial-gradient(circle at center, transparent 139px, #000 140px)"></div>
                                        <div class="pointer-events-none absolute inset-0 rounded-full border-2 border-white/80"></div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-3">
                                        <x-ui.icon name="image" class="size-3.5 text-[#9AA6B8]" />
                                        <input type="range" x-bind:min="min" x-bind:max="min * 4" step="0.001" x-bind:value="zoom" x-on:input="zoomer($event.target.value)" class="w-full accent-[#031D59]" aria-label="Zoom">
                                        <x-ui.icon name="image" class="size-5 text-[#9AA6B8]" />
                                    </div>
                                    <div class="mt-5 flex justify-end gap-2">
                                        <button type="button" x-on:click="fermer()" class="{{ $btn2 }}">Annuler</button>
                                        <button type="button" x-on:click="valider()" x-bind:disabled="envoi" data-test="valider-photo" class="{{ $btn }}">
                                            <span x-show="!envoi">Enregistrer</span><span x-show="envoi" style="display:none">Envoi…</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="{{ $carte }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-bold text-brand">Profil complété</span>
                            <span data-test="completion" class="text-[13px] font-extrabold text-azure">{{ $completion }} %</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-cloud">
                            <div class="h-full rounded-full transition-all" style="width: {{ $completion }}%; background: linear-gradient(90deg,#4F6FBF,#22A85A)"></div>
                        </div>
                        @if ($completion < 100)
                            <p class="mt-2 text-[11.5px] leading-relaxed text-[#5B677A]">Il reste : {{ implode(', ', $champsManquants) }}.</p>
                        @else
                            <p class="mt-2 text-[11.5px] text-[#1C8F4C]">Bravo, votre profil est complet !</p>
                        @endif
                    </div>
                </div>

                <form wire:submit="save" class="{{ $carte }}" x-data="formulaireSuivi()" x-on:input="sale = true" x-on:change="sale = true" data-test="form-profil">
                    <p class="{{ $titreCarte }}">Mes informations</p>
                    <p class="mb-5 mt-0.5 text-[12px] text-[#5B677A]">Elles figurent sur votre carte membre et aident les membres et l'équipe à mieux vous connaître.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="{{ $lab }}" for="p-prenom">Prénom</label><input id="p-prenom" wire:model="prenom" type="text" autocomplete="given-name" class="{{ $in }}" />@error('prenom') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div><label class="{{ $lab }}" for="p-nom">Nom</label><input id="p-nom" wire:model="nom" type="text" autocomplete="family-name" class="{{ $in }}" />@error('nom') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div>
                            <label class="{{ $lab }}">Adresse e-mail</label>
                            <input type="email" value="{{ $email }}" disabled class="{{ $in }} cursor-not-allowed bg-cloud text-[#7A8699]" />
                            <button type="button" wire:click="setOnglet('securite')" class="mt-1 text-[11.5px] font-bold text-azure hover:underline">Modifier mon adresse</button>
                        </div>
                        <div><label class="{{ $lab }}" for="p-tel">Téléphone</label><input id="p-tel" wire:model="telephone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="07 01 02 03 04" class="{{ $in }}" />@error('telephone') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div><label class="{{ $lab }}" for="p-naiss">Date de naissance</label><input id="p-naiss" wire:model="date_naissance" type="date" class="{{ $in }}" />@error('date_naissance') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div>
                            <label class="{{ $lab }}" for="p-genre">Genre</label>
                            <select id="p-genre" wire:model="genre" class="{{ $in }}"><option value="">—</option><option value="Homme">Homme</option><option value="Femme">Femme</option></select>
                        </div>
                        <div><label class="{{ $lab }}" for="p-ville">Ville</label><input id="p-ville" wire:model="ville" type="text" placeholder="Abidjan" class="{{ $in }}" /></div>
                        <div><label class="{{ $lab }}" for="p-paroisse">Paroisse</label><input id="p-paroisse" wire:model="paroisse" type="text" placeholder="Paroisse Saint-Jean, Cocody" class="{{ $in }}" /></div>
                        <div>
                            <label class="{{ $lab }}" for="p-secteur">Secteur d'activité</label>
                            <select id="p-secteur" wire:model="secteur" data-test="secteur" class="{{ $in }}">
                                <option value="">Choisir parmi les groupes sectoriels…</option>
                                @foreach ($secteurs as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $lab }}" for="p-profil">Profil</label>
                            <select id="p-profil" wire:model="profil" class="{{ $in }}">
                                <option value="">—</option>
                                @foreach ($profiles as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2"><label class="{{ $lab }}" for="p-orga">Entreprise / projet</label><input id="p-orga" wire:model="organisation" type="text" class="{{ $in }}" /></div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" data-test="enregistrer-profil" class="{{ $btn }}">
                            <span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">Enregistrement…</span>
                        </button>
                        <span x-show="sale" x-transition style="display:none" data-test="non-enregistre" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#B27007]"><x-ui.icon name="alert-circle" class="size-3.5" /> Modifications non enregistrées</span>
                    </div>
                </form>
            </div>
        @endif

        {{-- ══════════════════ PAGE PUBLIQUE ══════════════════ --}}
        @if ($onglet === 'page')
            <form wire:submit="saveBio" class="{{ $carte }}" x-data="formulaireSuivi()" x-on:input="sale = true" data-test="section-bio">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="{{ $titreCarte }}">Ma page biographique</p>
                        <p class="mt-0.5 max-w-2xl text-[12px] leading-relaxed text-[#5B677A]">La page que découvre toute personne qui scanne le QR code de votre carte membre : votre carte de visite et votre CV dans le réseau. Vos certificats, groupes, projets et offres s'y ajoutent automatiquement.</p>
                    </div>
                    @if ($pagePublique)
                        <a href="{{ $pagePublique }}" target="_blank" rel="noopener" data-test="voir-page-publique" class="{{ $btn2 }}"><x-ui.icon name="external-link" class="size-3.5" /> Voir ma page</a>
                    @endif
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="flex flex-col gap-4">
                        <div><label class="{{ $lab }}">Fonction / titre</label><input wire:model="titre" type="text" placeholder="Ex : Fondatrice d'AgroVert, consultante en agrobusiness" class="{{ $in }}" />@error('titre') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div><label class="{{ $lab }}">Diocèse</label><input wire:model="diocese" type="text" placeholder="Ex : Archidiocèse d'Abidjan" class="{{ $in }}" /></div>
                        <div x-data="{ n: {{ mb_strlen($bio) }} }">
                            <label class="{{ $lab }} flex justify-between">À propos de moi <span class="font-normal text-[#9AA6B8]" x-text="n + ' / 1 500'"></span></label>
                            <textarea wire:model="bio" x-on:input="n = $event.target.value.length" rows="7" maxlength="1500" placeholder="Votre histoire, votre vision, ce qui vous anime, ce que vous apportez au réseau…" class="{{ $in }} resize-y"></textarea>
                            @error('bio') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $lab }}">Compétences ({{ count($competences) }} / 15)</label>
                            <div class="mb-2 flex flex-wrap gap-1.5">
                                @foreach ($competences as $i => $c)
                                    <span wire:key="comp-{{ $i }}-{{ md5($c) }}" class="inline-flex items-center gap-1 rounded-full bg-azure/10 py-1 pl-3 pr-1.5 text-[12px] font-semibold text-azure">
                                        {{ $c }}
                                        <button type="button" wire:click="retirerCompetence({{ $i }})" aria-label="Retirer {{ $c }}" class="flex size-4 items-center justify-center rounded-full hover:bg-azure/20"><x-ui.icon name="x" class="size-3" /></button>
                                    </span>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input wire:model="nouvelleCompetence" wire:keydown.enter.prevent="ajouterCompetence" type="text" placeholder="Ex : Comptabilité, Marketing digital…" class="{{ $in }} min-w-0 flex-1" />
                                <button type="button" wire:click="ajouterCompetence" class="{{ $btn2 }}">Ajouter</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="{{ $lab }}">Parcours (postes, réalisations, formations)</label>
                            <div class="flex flex-col gap-2">
                                @foreach ($parcours as $i => $etape)
                                    <div wire:key="etape-{{ $i }}" class="rounded-[12px] border border-brand/10 p-2.5">
                                        <div class="grid gap-2 sm:grid-cols-[110px_1fr]">
                                            <input wire:model="parcours.{{ $i }}.periode" type="text" placeholder="2022 – auj." class="{{ $in }} !py-2 !text-[12.5px]" />
                                            <input wire:model="parcours.{{ $i }}.titre" type="text" placeholder="Poste ou réalisation" class="{{ $in }} !py-2 !text-[12.5px]" />
                                        </div>
                                        <div class="mt-2 flex gap-2">
                                            <input wire:model="parcours.{{ $i }}.structure" type="text" placeholder="Entreprise, organisation, école…" class="{{ $in }} !py-2 !text-[12.5px] min-w-0 flex-1" />
                                            <button type="button" wire:click="retirerEtape({{ $i }})" class="px-1 text-[11.5px] font-semibold text-accent hover:underline">Retirer</button>
                                        </div>
                                        @error("parcours.{$i}.titre") <span class="{{ $err }}">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                                @if (count($parcours) < 8)
                                    <button type="button" wire:click="ajouterEtape" class="btn-tap inline-flex w-fit items-center gap-1.5 rounded-[10px] border border-dashed border-brand/25 px-3 py-2 text-[12px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="plus" class="size-3.5" /> Ajouter une étape</button>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="{{ $lab }}">Liens</label>
                            <div class="flex flex-col gap-2">
                                @foreach (['site' => 'Site web', 'linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'instagram' => 'Instagram'] as $k => $label)
                                    <input wire:model="liens.{{ $k }}" type="url" placeholder="{{ $label }} — https://…" class="{{ $in }} !py-2 !text-[12.5px]" />
                                    @error("liens.{$k}") <span class="{{ $err }}">{{ $label }} : {{ $message }}</span> @enderror
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveBio" data-test="enregistrer-bio" class="{{ $btn }}">
                        <span wire:loading.remove wire:target="saveBio">Publier ma page</span><span wire:loading wire:target="saveBio">Publication…</span>
                    </button>
                    <span x-show="sale" x-transition style="display:none" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#B27007]"><x-ui.icon name="alert-circle" class="size-3.5" /> Modifications non publiées</span>
                </div>
            </form>
        @endif

        {{-- ══════════════════ NOTIFICATIONS ══════════════════ --}}
        @if ($onglet === 'notifications')
            <div class="flex flex-col gap-5">
                {{-- Application et notifications sur cet appareil --}}
                <div class="{{ $carte }}" x-data="notificationsAppareil(@js($clePush))" data-test="appareil-push">
                    <div class="flex flex-wrap items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand text-white"><x-ui.icon name="smartphone" class="size-5" /></span>
                        <div class="min-w-[220px] flex-1">
                            <p class="{{ $titreCarte }}">Notifications sur ce téléphone ou cet ordinateur</p>
                            <p class="mt-0.5 text-[12.5px] leading-relaxed text-[#5B677A]">Soyez prévenu(e) instantanément d'un nouveau message, d'une demande de mentorat ou d'un événement, même quand la plateforme est fermée.</p>
                            <p class="mt-2 text-[12px] font-semibold" x-show="etat !== 'chargement'" style="display:none">
                                <span x-show="etat === 'actif'" class="text-[#1C8F4C]">✓ Activées sur cet appareil</span>
                                <span x-show="etat === 'inactif'" class="text-[#5B677A]">Désactivées sur cet appareil</span>
                                <span x-show="etat === 'refuse'" class="text-accent">Bloquées par le navigateur : autorisez les notifications pour ce site dans ses réglages, puis revenez ici.</span>
                                <span x-show="etat === 'non-supporte'" class="text-[#5B677A]">Ce navigateur ne permet pas les notifications. Essayez Chrome, Edge, Firefox ou Safari récent.</span>
                                <span x-show="etat === 'ios-installer'" class="text-[#B27007]">Sur iPhone, ajoutez d'abord la plateforme à votre écran d'accueil (voir ci-dessous), puis ouvrez-la depuis l'icône REJCC.</span>
                            </p>
                            @if ($appareilsPush)
                                <p class="mt-1 text-[11.5px] text-[#9AA6B8]">{{ $appareilsPush }} appareil(s) reçoivent vos notifications.</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-show="etat === 'inactif'" style="display:none" x-on:click="activer()" x-bind:disabled="occupe" data-test="activer-push" class="{{ $btn }}"><x-ui.icon name="bell" class="size-4" /> Activer</button>
                            <button type="button" x-show="etat === 'actif'" style="display:none" wire:click="essaiPush" class="{{ $btn2 }}">Envoyer un essai</button>
                            <button type="button" x-show="etat === 'actif'" style="display:none" x-on:click="desactiver()" x-bind:disabled="occupe" class="{{ $btn2 }}">Désactiver</button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[14px] bg-cloud/70 p-4" x-show="!installee" style="display:none">
                        <p class="flex items-center gap-2 text-[13px] font-bold text-brand"><img src="{{ asset('app/icone-192.png') }}" alt="" class="size-6 rounded-md"> Installer l'application REJCC</p>
                        <p class="mt-1 text-[12px] leading-relaxed text-[#5B677A]">Une icône sur votre écran d'accueil, une ouverture en plein écran, comme une application — sans passer par un magasin d'applications.</p>
                        <button type="button" x-show="installable" style="display:none" x-on:click="installer()" data-test="installer-app" class="{{ $btn }} mt-3 !py-2 !text-[12.5px]"><x-ui.icon name="download" class="size-4" /> Installer</button>
                        <p x-show="!installable && ios" style="display:none" class="mt-2 text-[12px] text-brand">Sur iPhone : touchez <strong>Partager</strong> (le carré avec une flèche) dans Safari, puis <strong>« Sur l'écran d'accueil »</strong>.</p>
                        <p x-show="!installable && !ios" style="display:none" class="mt-2 text-[12px] text-brand">Ouvrez le menu de votre navigateur (⋮) puis choisissez <strong>« Installer l'application »</strong> ou <strong>« Ajouter à l'écran d'accueil »</strong>.</p>
                    </div>
                </div>

                {{-- Par catégorie --}}
                <form wire:submit="enregistrerNotifications" class="{{ $carte }}" data-test="reglages-notifications">
                    <p class="{{ $titreCarte }}">Que voulez-vous recevoir ?</p>
                    <p class="mt-0.5 text-[12px] text-[#5B677A]">Les notifications restent toujours visibles dans la cloche de votre espace. Un e-mail n'est envoyé que si vous ne les avez pas vues sur la plateforme.</p>

                    <div class="mt-4 divide-y divide-cloud-200">
                        <div class="hidden grid-cols-[1fr_180px_110px] gap-4 pb-2 text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8] sm:grid">
                            <span>Catégorie</span><span>Par e-mail</span><span class="text-center">Sur l'appareil</span>
                        </div>
                        @foreach ($categories as $c)
                            <div wire:key="cat-{{ $c['cle'] }}" data-test="categorie-{{ $c['cle'] }}" class="grid gap-3 py-3.5 sm:grid-cols-[1fr_180px_110px] sm:items-center sm:gap-4">
                                <div>
                                    <p class="text-[13px] font-semibold text-ink">{{ $c['libelle'] }}</p>
                                    <p class="text-[11.5px] text-[#9AA6B8]">{{ $c['detail'] }}</p>
                                </div>
                                <label class="flex items-center gap-2 sm:block">
                                    <span class="w-24 shrink-0 text-[11.5px] font-semibold text-[#5B677A] sm:hidden">Par e-mail</span>
                                    <select wire:model="emailCat.{{ $c['cle'] }}" class="{{ $in }} !py-2 !text-[12.5px]">
                                        @foreach ($frequences as $k => $l)
                                            <option value="{{ $k }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex items-center gap-2 sm:justify-center">
                                    <span class="w-24 shrink-0 text-[11.5px] font-semibold text-[#5B677A] sm:hidden">Sur l'appareil</span>
                                    <input type="checkbox" wire:model="pushCat.{{ $c['cle'] }}" class="size-[18px] rounded border-brand/25 text-brand" aria-label="Notifications sur l'appareil : {{ $c['libelle'] }}">
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-[14px] border border-brand/10 p-4" data-test="pause-emails">
                        <p class="text-[13px] font-bold text-brand">Faire une pause</p>
                        <p class="mt-0.5 text-[12px] text-[#5B677A]">Vacances, retraite spirituelle, examens… Plus aucun e-mail de notification jusqu'à la date choisie (les e-mails de sécurité et de paiement restent envoyés).</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <input type="date" wire:model="pauseEmails" min="{{ now()->toDateString() }}" max="{{ now()->addMonths(6)->subDay()->toDateString() }}" class="{{ $in }} !w-auto !py-2" />
                            @if ($pauseEmails)
                                <span class="rounded-full bg-[#FCF1DD] px-3 py-1 text-[11.5px] font-bold text-[#B27007]">En pause jusqu'au {{ $date($pauseEmails, 'D MMMM YYYY') }}</span>
                                <button type="button" wire:click="reprendreEmails" class="text-[12px] font-bold text-azure hover:underline">Reprendre maintenant</button>
                            @endif
                        </div>
                        @error('pauseEmails') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="enregistrerNotifications" data-test="enregistrer-notifications" class="{{ $btn }} mt-5">Enregistrer mes préférences</button>
                </form>

                <div class="{{ $carte }} flex items-center justify-between gap-4" data-test="pref-newsletter">
                    <div>
                        <p class="{{ $titreCarte }}">Lettre d'information du REJCC</p>
                        <p class="mt-0.5 text-[12px] text-[#5B677A]">Les actualités du réseau, une fois par mois.</p>
                    </div>
                    <button type="button" wire:click="togglePreference('newsletter')" role="switch" aria-checked="{{ ($preferences['newsletter'] ?? true) ? 'true' : 'false' }}" aria-label="Lettre d'information" class="{{ $interrupteur((bool) ($preferences['newsletter'] ?? true)) }}">
                        <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200" style="left: {{ ($preferences['newsletter'] ?? true) ? '21px' : '3px' }}"></span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ══════════════════ CONFIDENTIALITÉ ══════════════════ --}}
        @if ($onglet === 'confidentialite')
            @php
                $annuaire = (bool) ($preferences['apparaitre_annuaire'] ?? true);
                $coordMembres = (bool) ($preferences['visibilite_profil'] ?? false);
                $coordPublic = (bool) ($preferences['coordonnees_publiques'] ?? false);
                $vie = [
                    'apparaitre_annuaire' => ["Apparaître dans l'annuaire", 'Les membres abonnés peuvent vous trouver, voir votre fiche et vous écrire.', $annuaire],
                    'visibilite_profil' => ['Coordonnées visibles par les membres', 'Votre téléphone et votre e-mail sur votre fiche (annuaire, groupes sectoriels).', $coordMembres],
                    'coordonnees_publiques' => ['Coordonnées sur ma page publique', 'Votre téléphone et votre e-mail visibles par toute personne qui scanne votre carte.', $coordPublic],
                ];
            @endphp
            <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
                <div class="flex flex-col gap-5">
                    <div class="{{ $carte }}">
                        <p class="{{ $titreCarte }}">Ma visibilité</p>
                        <div class="mt-2 divide-y divide-cloud-200">
                            @foreach ($vie as $cle => [$l, $d, $on])
                                <div data-test="pref-{{ $cle }}" class="flex items-center justify-between gap-4 py-3.5">
                                    <div><p class="text-[13px] font-semibold text-ink">{{ $l }}</p><p class="text-[11.5px] text-[#9AA6B8]">{{ $d }}</p></div>
                                    <button type="button" wire:click="togglePreference('{{ $cle }}')" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $l }}" class="{{ $interrupteur($on) }}">
                                        <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200" style="left: {{ $on ? '21px' : '3px' }}"></span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="{{ $carte }} flex flex-wrap items-center gap-4" data-test="piece-coffre">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#22A85A]/12 text-[#1C8F4C]"><x-ui.icon name="id-card" class="size-5" /></span>
                        <div class="min-w-[220px] flex-1">
                            <p class="{{ $titreCarte }}">Pièce d'identité et documents officiels</p>
                            <p class="mt-0.5 text-[12px] leading-relaxed text-[#5B677A]">Ils sont rangés, chiffrés, dans « Mes documents personnels ». Vous choisissez pour chaque document s'il est partagé avec l'équipe du REJCC ; chaque consultation par l'équipe apparaît dans votre journal.</p>
                        </div>
                        <a href="{{ route('espace-membre.documents', ['onglet' => 'personnels']) }}" wire:navigate class="{{ $btn2 }}">Mes documents personnels</a>
                    </div>
                </div>

                {{-- Aperçu : ce que voient les autres --}}
                <div class="{{ $carte }}" data-test="apercu-visibilite">
                    <p class="{{ $titreCarte }}">Ce que voient les autres</p>
                    <div class="mt-4 space-y-4 text-[12.5px]">
                        <div>
                            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Les membres abonnés</p>
                            <ul class="space-y-1.5">
                                <li class="flex gap-2"><x-ui.icon :name="$annuaire ? 'check-circle' : 'x-circle'" class="mt-px size-4 shrink-0 {{ $annuaire ? 'text-[#1C8F4C]' : 'text-[#9AA6B8]' }}" /> {{ $annuaire ? 'Vous trouvent dans l\'annuaire (nom, photo, ville, secteur, compétences)' : 'Ne vous trouvent pas dans l\'annuaire' }}</li>
                                <li class="flex gap-2"><x-ui.icon :name="$coordMembres ? 'check-circle' : 'x-circle'" class="mt-px size-4 shrink-0 {{ $coordMembres ? 'text-[#1C8F4C]' : 'text-[#9AA6B8]' }}" /> {{ $coordMembres ? 'Voient votre téléphone et votre e-mail' : 'Ne voient pas vos coordonnées : ils vous écrivent par la messagerie' }}</li>
                            </ul>
                        </div>
                        <div>
                            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Le public (QR code de votre carte)</p>
                            <ul class="space-y-1.5">
                                <li class="flex gap-2"><x-ui.icon name="check-circle" class="mt-px size-4 shrink-0 text-[#1C8F4C]" /> Votre page : nom, photo, titre, présentation, parcours, certificats choisis</li>
                                <li class="flex gap-2"><x-ui.icon :name="$coordPublic ? 'check-circle' : 'x-circle'" class="mt-px size-4 shrink-0 {{ $coordPublic ? 'text-[#1C8F4C]' : 'text-[#9AA6B8]' }}" /> {{ $coordPublic ? 'Votre téléphone et votre e-mail' : 'Pas vos coordonnées' }}</li>
                            </ul>
                        </div>
                        <p class="flex gap-2 text-[11.5px] text-[#9AA6B8]"><x-ui.icon name="lock" class="mt-px size-3.5 shrink-0" /> Votre date de naissance, votre paroisse et vos documents personnels ne sont jamais montrés.</p>
                        @if ($pagePublique)
                            <a href="{{ $pagePublique }}" target="_blank" rel="noopener" class="{{ $btn2 }} w-full"><x-ui.icon name="external-link" class="size-3.5" /> Voir ma page publique</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════════════ SÉCURITÉ ══════════════════ --}}
        @if ($onglet === 'securite')
            <div class="grid gap-5 lg:grid-cols-2">
                <form wire:submit="updatePassword" class="{{ $carte }}" data-test="form-mot-de-passe"
                    x-data="{ mdp: '', get force() { let s = 0; const v = this.mdp; if (v.length >= 8) s++; if (v.length >= 12) s++; if (/[a-zà-ÿ]/i.test(v) && /\d/.test(v)) s++; if (/[^a-z0-9à-ÿ]/i.test(v)) s++; return v ? Math.max(1, s) : 0; } }">
                    <p class="{{ $titreCarte }}">Changer mon mot de passe</p>
                    <div class="mt-4 flex flex-col gap-3.5">
                        <div><label class="{{ $lab }}">Mot de passe actuel</label><x-ui.password-input wire:model="current_password" autocomplete="current-password" class="{{ $in }}" />@error('current_password') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        <div>
                            <label class="{{ $lab }}">Nouveau mot de passe</label>
                            <x-ui.password-input wire:model="password" x-on:input="mdp = $event.target.value" autocomplete="new-password" class="{{ $in }}" />
                            <div class="mt-2 flex gap-1" aria-hidden="true">
                                @foreach (range(1, 4) as $i)
                                    <span class="h-1.5 flex-1 rounded-full transition-colors" x-bind:class="force >= {{ $i }} ? ['bg-accent','bg-[#F5A623]','bg-[#4F6FBF]','bg-[#22A85A]'][force - 1] : 'bg-cloud-200'"></span>
                                @endforeach
                            </div>
                            <p class="mt-1 text-[11px] text-[#9AA6B8]" x-text="['8 caractères minimum, avec au moins une lettre et un chiffre.', 'Trop faible', 'Moyen', 'Bon', 'Excellent'][force]"></p>
                            @error('password') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div><label class="{{ $lab }}">Confirmer le nouveau mot de passe</label><x-ui.password-input wire:model="password_confirmation" autocomplete="new-password" class="{{ $in }}" /></div>
                        <label class="flex items-start gap-2.5 text-[12.5px] text-ink">
                            <input type="checkbox" wire:model="deconnecterAutres" class="mt-0.5 size-4 rounded border-brand/25 text-brand">
                            <span>Déconnecter mes autres appareils <span class="block text-[11.5px] text-[#9AA6B8]">Recommandé si vous pensez qu'une autre personne connaît votre mot de passe.</span></span>
                        </label>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" data-test="enregistrer-mot-de-passe" class="{{ $btn }} mt-5">Mettre à jour le mot de passe</button>
                </form>

                <div class="flex flex-col gap-5">
                    <form wire:submit="demanderEmail" class="{{ $carte }}" data-test="form-email">
                        <p class="{{ $titreCarte }}">Adresse e-mail de connexion</p>
                        <p class="mt-0.5 text-[12.5px] text-ink">{{ $email }}</p>
                        @if ($emailEnAttente)
                            <div class="mt-3 rounded-[12px] bg-[#FCF1DD] px-3.5 py-2.5 text-[12px] text-[#8A5A00]" data-test="email-en-attente">
                                En attente de confirmation : <strong>{{ $emailEnAttente }}</strong>. Ouvrez le lien reçu sur cette adresse (valable 48 h).
                                <button type="button" wire:click="annulerEmail" class="ml-1 font-bold underline">Annuler</button>
                            </div>
                        @endif
                        <div class="mt-4 flex flex-col gap-3">
                            <div><label class="{{ $lab }}">Nouvelle adresse</label><input wire:model="nouvelEmail" type="email" autocomplete="email" placeholder="prenom.nom@exemple.com" class="{{ $in }}" />@error('nouvelEmail') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                            <div><label class="{{ $lab }}">Mot de passe</label><x-ui.password-input wire:model="motDePasseEmail" autocomplete="current-password" class="{{ $in }}" />@error('motDePasseEmail') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                        </div>
                        <p class="mt-2 text-[11.5px] text-[#9AA6B8]">Nous enverrons un lien de confirmation à la nouvelle adresse ; l'actuelle reste valable en attendant.</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="demanderEmail" class="{{ $btn2 }} mt-4">Changer d'adresse</button>
                    </form>

                    <div class="{{ $carte }}" data-test="appareils">
                        <div class="flex items-center justify-between gap-3">
                            <p class="{{ $titreCarte }}">Appareils connectés</p>
                            @if (count($appareils) > 1)
                                <button type="button" wire:click="deconnecterTousLesAutres" wire:confirm="Déconnecter tous vos autres appareils ? Ils devront se reconnecter avec votre mot de passe." class="text-[12px] font-bold text-accent hover:underline">Déconnecter les autres</button>
                            @endif
                        </div>
                        <ul class="mt-3 divide-y divide-cloud-200" x-data="{ tout: false }">
                            @foreach ($appareils as $i => $a)
                                <li wire:key="app-{{ $a['id'] }}" data-test="appareil" @if ($i >= 5) x-show="tout" style="display:none" @endif class="flex items-center gap-3 py-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-cloud text-brand"><x-ui.icon :name="preg_match('/Android|iPhone|iPad/', $a['appareil']) ? 'smartphone' : 'nav-projects'" class="size-4" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-semibold text-ink">{{ $a['appareil'] }} @if ($a['courant'])<span class="ml-1 rounded-full bg-[#22A85A]/12 px-2 py-0.5 text-[10.5px] font-bold text-[#1C8F4C]">Cet appareil</span>@endif</p>
                                        <p class="truncate text-[11.5px] text-[#9AA6B8]">Actif le {{ $date($a['actif_le']) }}{{ $a['ip'] ? ' · '.$a['ip'] : '' }}</p>
                                    </div>
                                    @unless ($a['courant'])
                                        <button type="button" wire:click="deconnecterAppareil({{ $a['id'] }})" wire:confirm="Déconnecter « {{ $a['appareil'] }} » ?" class="shrink-0 text-[12px] font-bold text-[#5B677A] hover:text-accent">Déconnecter</button>
                                    @endunless
                                </li>
                            @endforeach
                            @if (count($appareils) > 5)
                                <li x-show="!tout" class="pt-3"><button type="button" x-on:click="tout = true" class="text-[12px] font-bold text-azure hover:underline">Voir les {{ count($appareils) - 5 }} autres sessions</button></li>
                            @endif
                        </ul>
                        <p class="mt-2 text-[11.5px] text-[#9AA6B8]">Un appareil inconnu ? Déconnectez-le et changez votre mot de passe.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════════════ MON COMPTE ══════════════════ --}}
        @if ($onglet === 'compte')
            @php $grand = (bool) ($preferences['texte_grand'] ?? false); @endphp
            <div class="grid gap-5 lg:grid-cols-2">
                <div class="flex flex-col gap-5">
                    <div class="{{ $carte }} flex items-center justify-between gap-4" data-test="pref-texte_grand">
                        <div>
                            <p class="{{ $titreCarte }}">Texte agrandi</p>
                            <p class="mt-0.5 text-[12px] text-[#5B677A]">Agrandit les textes de l'espace membre pour plus de confort de lecture.</p>
                        </div>
                        <button type="button" wire:click="togglePreference('texte_grand')" role="switch" aria-checked="{{ $grand ? 'true' : 'false' }}" aria-label="Texte agrandi" class="{{ $interrupteur($grand) }}">
                            <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200" style="left: {{ $grand ? '21px' : '3px' }}"></span>
                        </button>
                    </div>

                    <div class="{{ $carte }}" data-test="mes-donnees">
                        <p class="{{ $titreCarte }}">Mes données</p>
                        <p class="mt-0.5 text-[12px] leading-relaxed text-[#5B677A]">Téléchargez une copie de toutes les informations liées à votre compte : profil, abonnement, formations, certificats, messages, projets, annonces… (fichier JSON, lisible par d'autres services).</p>
                        <a href="{{ route('espace-membre.mes-donnees') }}" data-test="telecharger-donnees" class="{{ $btn2 }} mt-4"><x-ui.icon name="download" class="size-4" /> Télécharger mes données</a>
                    </div>

                    <div class="{{ $carte }} border-accent/20" x-data="{ ouvert: false }" data-test="cloture">
                        <p class="text-[14px] font-bold text-accent">Clôturer mon compte</p>
                        @if ($estAdmin)
                            <p class="mt-1 text-[12px] text-[#5B677A]">Un compte administrateur ne peut pas être clôturé ici : demandez à un autre administrateur de retirer vos droits.</p>
                        @else
                            <p class="mt-1 text-[12px] leading-relaxed text-[#5B677A]">Votre compte sera désactivé tout de suite et supprimé définitivement après <strong>30 jours</strong>. Pendant ce délai, il suffit de vous reconnecter pour l'annuler.</p>
                            <button type="button" x-show="!ouvert" x-on:click="ouvert = true" class="mt-4 rounded-full border border-accent/30 px-4 py-2 text-[12.5px] font-bold text-accent hover:bg-accent/5">Clôturer mon compte…</button>
                            <form x-show="ouvert" style="display:none" wire:submit="cloturer" wire:confirm="Confirmer la clôture de votre compte ? Vous serez déconnecté(e)." class="mt-4 flex flex-col gap-3">
                                <ul class="list-disc space-y-1 pl-5 text-[12px] text-[#5B677A]">
                                    <li>Votre photo et vos documents personnels du coffre-fort sont <strong>supprimés immédiatement</strong> (téléchargez-les avant si besoin).</li>
                                    <li>Vos annonces, offres et projets sont retirés ; vos messages restent chez vos correspondants sous « Membre supprimé ».</li>
                                    <li>L'historique de vos paiements et le registre de vos certificats sont conservés (obligation légale).</li>
                                </ul>
                                <div><label class="{{ $lab }}">Pourquoi partez-vous ? (facultatif)</label><textarea wire:model="motifCloture" rows="2" maxlength="500" class="{{ $in }}" placeholder="Votre avis nous aide à améliorer le réseau."></textarea></div>
                                <div><label class="{{ $lab }}">Mot de passe</label><x-ui.password-input wire:model="motDePasseCloture" autocomplete="current-password" class="{{ $in }}" />@error('motDePasseCloture') <span class="{{ $err }}">{{ $message }}</span> @enderror</div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" data-test="confirmer-cloture" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Clôturer définitivement</button>
                                    <button type="button" x-on:click="ouvert = false" class="{{ $btn2 }}">Annuler</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="{{ $carte }}" data-test="journal">
                    <p class="{{ $titreCarte }}">Journal de mon compte</p>
                    <p class="mt-0.5 text-[12px] text-[#5B677A]">Connexions, changements de sécurité, téléchargements et consultations de vos documents par l'équipe.</p>
                    @if (empty($journal))
                        <p class="mt-6 text-center text-[12.5px] text-[#9AA6B8]">Rien pour le moment.</p>
                    @else
                        <ol class="mt-4 space-y-0.5">
                            @foreach ($journal as $e)
                                @php
                                    $ic = match ($e['type']) {
                                        'connexion' => ['log-out', 'text-azure'],
                                        'mot_de_passe', 'mot_de_passe_reinitialise', 'email', 'email_demande' => ['lock', 'text-[#B27007]'],
                                        'consultation_equipe' => ['eye', 'text-[#4F6FBF]'],
                                        'cloture', 'deconnexion_appareil' => ['alert-circle', 'text-accent'],
                                        default => ['settings', 'text-[#5B677A]'],
                                    };
                                @endphp
                                <li class="flex gap-3 rounded-[10px] px-2 py-2 hover:bg-cloud/60" data-test="evenement-journal">
                                    <x-ui.icon :name="$ic[0]" class="mt-0.5 size-4 shrink-0 {{ $ic[1] }}" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[12.5px] font-semibold text-ink">{{ $e['libelle'] }}{{ $e['detail'] ? ' — '.$e['detail'] : '' }}</p>
                                        <p class="text-[11px] text-[#9AA6B8]">{{ $date($e['le']) }}{{ $e['appareil'] ? ' · '.$e['appareil'] : '' }}{{ $e['ip'] ? ' · '.$e['ip'] : '' }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        @endif

        </div>
    </div>
</div>
