@php
    // Teintes des statuts, du premier contact à la publication.
    $teinte = fn (?string $s) => match ($s) {
        'recu' => ['#B26A00', '#FDF2E3'],
        'retenu', 'questionnaire_envoye', 'questionnaire_rempli' => ['#2D5C8F', '#EEF6FD'],
        'attribue', 'en_creation' => ['#5B3FA8', '#F1EDFB'],
        'publie' => ['#1C8A49', '#EAF6EE'],
        default => ['#5B677A', '#F1F3F7'],
    };
    $date = fn (?string $iso, string $format = 'j M Y') => $iso ? \Carbon\Carbon::parse($iso)->locale('fr')->translatedFormat($format) : '';
    $champ = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
@endphp
<div>
    <x-admin-light.topbar title="CathoImpact" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">CathoImpact</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-[12.5px] text-[#5B677A]">Réponses au formulaire du site : présélectionnez les parcours, envoyez le questionnaire détaillé aux personnes retenues, puis suivez la création jusqu'à la publication.</p>
            </div>
            <a href="{{ \App\Support\Site::url('/cathoimpact') }}" target="_blank" rel="noopener" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Voir la page du site</a>
        </div>

        <div class="mb-5 flex gap-1.5 border-b border-brand/10" role="tablist">
            @foreach (['reponses' => 'Réponses', 'galerie' => 'Galerie du site'] as $cle => $libelle)
                <button type="button" role="tab" wire:click="changerOnglet('{{ $cle }}')" data-test="onglet-{{ $cle }}" aria-selected="{{ $onglet === $cle ? 'true' : 'false' }}"
                    class="-mb-px border-b-2 px-4 py-2.5 text-[13px] font-bold transition {{ $onglet === $cle ? 'border-accent text-brand' : 'border-transparent text-[#5B677A] hover:text-brand' }}">{{ $libelle }}</button>
            @endforeach
        </div>

        @if ($message)
            <p class="mb-4 rounded-[12px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-2.5 text-[13px] font-semibold text-[#2E7D5B]" data-test="message">{{ $message }}</p>
        @endif
        @if ($erreur)
            <p class="mb-4 rounded-[12px] border border-[#F0C9C9] bg-[#FDF7F7] px-4 py-2.5 text-[13px] font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if ($onglet === 'reponses')
            @php $total = array_sum($compteurs); @endphp
            <div class="mb-5 flex flex-wrap items-center gap-2 rounded-[14px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Nom, e-mail, entreprise, ville…" class="w-full rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
                <span class="mx-1 hidden h-5 w-px bg-brand/10 sm:block"></span>
                <button wire:click="filtrer('')" class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $filtreStatut === '' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30 hover:text-brand' }}">Toutes ({{ $total }})</button>
                @foreach ($statuts as $valeur => $libelle)
                    @php $n = $compteurs[$valeur] ?? 0; @endphp
                    <button wire:click="filtrer('{{ $valeur }}')" data-test="filtre-{{ $valeur }}" class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $filtreStatut === $valeur ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30 hover:text-brand' }}">{{ $libelle }} ({{ $n }})</button>
                @endforeach
            </div>

            <div class="rounded-[18px] border border-brand/10 bg-white px-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-5">
                @forelse ($liste as $c)
                    @php [$fg, $bg] = $teinte($c['statut']); @endphp
                    <div wire:key="ligne-{{ $c['id'] }}" class="row-hover -mx-4 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:-mx-5 sm:gap-4 sm:px-5" data-test="reponse-{{ $c['id'] }}">
                        @if ($c['a_photo'])
                            <img src="{{ route('admin.cathoimpact.photo', $c['id']) }}" alt="" loading="lazy" class="size-11 shrink-0 rounded-xl object-cover">
                        @else
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #031D59, #4F6FBF)">{{ collect(explode(' ', $c['nom_complet']))->map(fn ($m) => mb_substr($m, 0, 1))->take(2)->implode('') }}</span>
                        @endif
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $c['nom_complet'] }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">{{ $c['fonction'] }} · {{ $c['entreprise'] }}</p>
                            <p class="mt-0.5 text-[11px] text-[#5B677A]">{{ $c['profil_label'] }} · {{ $c['secteur'] }} · {{ $c['ville'] }} · reçue le {{ $date($c['created_at']) }}@if ($c['community_manager']) · CM : {{ $c['community_manager'] }}@endif</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $fg }}; background: {{ $bg }}">{{ $c['statut_label'] }}</span>
                        <button wire:click="ouvrir({{ $c['id'] }})" data-test="ouvrir-{{ $c['id'] }}" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-azure/25 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                            <x-ui.icon name="eye" class="size-3.5" /> {{ $ouverte === $c['id'] ? 'Fermer' : 'Ouvrir' }}
                        </button>
                    </div>

                    @if ($ouverte === $c['id'] && $fiche)
                        <div class="panel-enter mb-4 grid gap-4 rounded-xl bg-[#F8FAFC] p-4 lg:grid-cols-[1fr_340px]" data-test="fiche">
                            <div class="min-w-0 space-y-4">
                                <div class="flex flex-wrap items-start gap-4">
                                    @if ($fiche['a_photo'])
                                        <a href="{{ route('admin.cathoimpact.photo', $fiche['id']) }}" target="_blank" rel="noopener" class="shrink-0" title="Ouvrir la photo en grand">
                                            <img src="{{ route('admin.cathoimpact.photo', $fiche['id']) }}" alt="Photo de {{ $fiche['nom_complet'] }}" class="size-32 rounded-xl object-cover ring-1 ring-brand/10" data-test="fiche-photo">
                                        </a>
                                    @endif
                                    <div class="min-w-[200px] flex-1">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Identité</p>
                                        <p class="mt-1 text-[16px] font-bold text-brand">{{ $fiche['nom_complet'] }}</p>
                                        <p class="mt-0.5 text-[12.5px] text-ink">{{ $fiche['profil_label'] }}</p>
                                        <p class="mt-1.5 text-[12.5px] text-ink"><a href="mailto:{{ $fiche['email'] }}" class="text-azure hover:underline">{{ $fiche['email'] }}</a> · <a href="https://wa.me/{{ preg_replace('/\D/', '', $fiche['whatsapp']) }}" target="_blank" rel="noopener" class="text-azure hover:underline">WhatsApp {{ $fiche['whatsapp'] }}</a></p>
                                        @if ($fiche['a_photo'])
                                            <a href="{{ route('admin.cathoimpact.photo', ['id' => $fiche['id'], 'telecharger' => 1]) }}" class="mt-1.5 inline-block text-[12px] font-semibold text-azure hover:underline">Télécharger la photo</a>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Activité</p>
                                    <div class="mt-1.5 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                        @foreach ([$fiche['libelle_entreprise'] => $fiche['entreprise'], $fiche['libelle_fonction'] => $fiche['fonction'], "Secteur d'activité" => $fiche['secteur'], 'Ville' => $fiche['ville']] as $l => $v)
                                            <div><p class="text-[11px] font-bold text-[#5B677A]">{{ $l }}</p><p class="mt-0.5 text-[12.5px] text-ink">{{ $v }}</p></div>
                                        @endforeach
                                        @if ($fiche['lien'])
                                            <div class="sm:col-span-2"><p class="text-[11px] font-bold text-[#5B677A]">Profil LinkedIn ou page</p><a href="{{ $fiche['lien'] }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block break-all text-[12.5px] text-azure hover:underline">{{ $fiche['lien'] }}</a></div>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Parcours</p>
                                    <p class="mt-1.5 text-[11px] font-bold text-[#5B677A]">Votre parcours en quelques lignes</p>
                                    <p class="mt-0.5 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $fiche['parcours'] }}</p>
                                    <p class="mt-3 text-[11px] font-bold text-[#5B677A]">Qu'est-ce qui rend votre parcours inspirant ?</p>
                                    <p class="mt-0.5 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $fiche['inspiration'] }}</p>
                                </div>

                                <div class="rounded-[10px] border border-[#CDEBDD] bg-[#F3FBF6] px-3 py-2.5">
                                    <p class="text-[11px] font-bold text-[#2E7D5B]">Consentement accepté le {{ $date($fiche['consentement_at'], 'j F Y à H\hi') }}</p>
                                    <p class="mt-0.5 text-[12px] text-ink">« {{ $fiche['consentement_texte'] }} »</p>
                                </div>

                                @if ($fiche['historique'])
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-azure">Historique</p>
                                        <ol class="mt-1.5 space-y-1" data-test="historique">
                                            @foreach (array_reverse($fiche['historique']) as $h)
                                                <li class="flex flex-wrap items-center gap-x-2 text-[12px] text-ink">
                                                    <span class="size-1.5 rounded-full bg-azure"></span>
                                                    <span class="font-semibold">{{ $h['label'] }}</span>
                                                    <span class="text-[#5B677A]">· {{ $date($h['at'] ?? null, 'j M Y, H\hi') }}@if (! empty($h['par'])) · {{ $h['par'] }}@endif</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif
                            </div>

                            <form wire:submit="enregistrer" class="flex flex-col gap-3 rounded-xl border border-brand/10 bg-white p-4 self-start" data-test="suivi">
                                <p class="text-[13px] font-bold text-brand">Suivi <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-bold" style="color: {{ $teinte($fiche['statut'])[0] }}; background: {{ $teinte($fiche['statut'])[1] }}">{{ $fiche['statut_label'] }}</span></p>

                                {{-- Étape suivante en un clic : l'étape demande au besoin le lien du formulaire, le community manager ou le lien publié. --}}
                                @php
                                    $suivante = \App\Livewire\Admin\CathoImpact::ETAPE_SUIVANTE[$fiche['statut']] ?? null;
                                    $saisie = $suivante ? (\App\Livewire\Admin\CathoImpact::SAISIE_ETAPE[$suivante][0] ?? null) : null;
                                @endphp
                                @if ($suivante)
                                    <div wire:key="etape-{{ $fiche['statut'] }}" x-data="{ saisie: {{ $errors->has('lienQuestionnaire') || $errors->has('cm') || $errors->has('lienPublication') ? 'true' : 'false' }} }" class="rounded-[12px] bg-[#F4F7FC] p-3" data-test="etape-suivante">
                                        @if ($saisie)
                                            <div x-show="saisie" x-cloak class="mb-2.5">
                                                @if ($saisie === 'lienQuestionnaire')
                                                    <label for="ci-etape-forms" class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien du Google Form envoyé</label>
                                                    <input wire:model="lienQuestionnaire" id="ci-etape-forms" type="url" placeholder="https://forms.gle/…" class="{{ $champ }}" data-test="etape-saisie">
                                                @elseif ($saisie === 'cm')
                                                    <label for="ci-etape-cm" class="mb-1 block text-xs font-semibold text-[#5B677A]">Community manager</label>
                                                    <select wire:model.live="cm" id="ci-etape-cm" class="{{ $champ }}" data-test="etape-saisie">
                                                        <option value="">Choisir…</option>
                                                        @foreach ($equipe as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                                                        <option value="libre">Autre (saisir un nom)…</option>
                                                    </select>
                                                @else
                                                    <label for="ci-etape-pub" class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien de la publication</label>
                                                    <input wire:model="lienPublication" id="ci-etape-pub" type="url" placeholder="https://www.instagram.com/p/…" class="{{ $champ }}" data-test="etape-saisie">
                                                @endif
                                                @error($saisie) <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                            </div>
                                            <button type="button" x-show="! saisie" x-on:click="saisie = true; $nextTick(() => document.querySelector('[data-test=etape-saisie]')?.focus())" data-test="etape-suivante-bouton"
                                                class="btn-tap w-full rounded-[10px] bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">Passer à l'étape suivante : {{ $statuts[$suivante] ?? $suivante }}</button>
                                            <button type="button" x-show="saisie" x-cloak wire:click="etapeSuivante" wire:loading.attr="disabled" data-test="etape-suivante-confirmer"
                                                class="btn-tap w-full rounded-[10px] bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Confirmer : {{ $statuts[$suivante] ?? $suivante }}</button>
                                        @else
                                            <button type="button" wire:click="etapeSuivante" wire:loading.attr="disabled" data-test="etape-suivante-bouton"
                                                class="btn-tap w-full rounded-[10px] bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Passer à l'étape suivante : {{ $statuts[$suivante] ?? $suivante }}</button>
                                        @endif
                                    </div>
                                @elseif ($fiche['statut'] === 'publie')
                                    <div class="rounded-[12px] bg-[#EAF6EE] p-3" data-test="etape-galerie">
                                        <p class="mb-2 text-[12.5px] font-semibold text-[#1C8A49]">Publié ! Mettez ce portrait en avant sur la page CathoImpact du site.</p>
                                        <button type="button" wire:click="ajouterALaGalerie({{ $fiche['id'] }})" data-test="ajouter-galerie" class="btn-tap w-full rounded-[10px] bg-[#1C8A49] px-4 py-2.5 text-[13px] font-bold text-white hover:bg-[#16703C]">Ajouter à la galerie du site</button>
                                    </div>
                                @endif

                                <details class="group/autres" wire:ignore.self @if (! $suivante && $fiche['statut'] !== 'publie') open @endif>
                                    <summary class="cursor-pointer list-none text-[12px] font-bold text-[#5B677A] hover:text-brand [&::-webkit-details-marker]:hidden">Autre statut, notes et liens… <x-ui.icon name="chevron-down" class="inline size-3.5 transition-transform group-open/autres:rotate-180" /></summary>
                                    <div class="mt-3 flex flex-col gap-3">
                                <div>
                                    <label for="ci-statut" class="mb-1 block text-xs font-semibold text-[#5B677A]">Autre statut…</label>
                                    <select wire:model="statut" id="ci-statut" data-test="suivi-statut" class="{{ $champ }}">
                                        @foreach ($statuts as $valeur => $libelle)
                                            <option value="{{ $valeur }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    @if ($fiche['statut_at'])<p class="mt-1 text-[11px] text-[#5B677A]">Depuis le {{ $date($fiche['statut_at'], 'j M Y à H\hi') }}</p>@endif
                                </div>
                                <div>
                                    <label for="ci-cm" class="mb-1 block text-xs font-semibold text-[#5B677A]">Community manager attribué</label>
                                    <select wire:model.live="cm" id="ci-cm" data-test="suivi-cm" class="{{ $champ }}">
                                        <option value="">Personne pour l'instant</option>
                                        @foreach ($equipe as $m)
                                            <option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>
                                        @endforeach
                                        <option value="libre">Autre (saisir un nom)…</option>
                                    </select>
                                    @if ($cm === 'libre')
                                        <input wire:model="cmLibre" type="text" maxlength="150" placeholder="Nom du community manager" class="{{ $champ }} mt-2">
                                        @error('cmLibre') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                    @endif
                                </div>
                                <div>
                                    <label for="ci-forms" class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien du Google Form envoyé</label>
                                    <input wire:model="lienQuestionnaire" id="ci-forms" type="url" placeholder="https://forms.gle/…" class="{{ $champ }}">
                                    @error('lienQuestionnaire') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="ci-publication" class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien de la publication</label>
                                    <input wire:model="lienPublication" id="ci-publication" type="url" placeholder="https://www.instagram.com/p/…" class="{{ $champ }}">
                                    @error('lienPublication') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                    @if ($fiche['lien_publication'])<a href="{{ $fiche['lien_publication'] }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-[11.5px] font-semibold text-azure hover:underline">Ouvrir la publication</a>@endif
                                </div>
                                <div>
                                    <label for="ci-note" class="mb-1 block text-xs font-semibold text-[#5B677A]">Note interne</label>
                                    <textarea wire:model="note" id="ci-note" rows="3" maxlength="5000" placeholder="Visible par l'équipe Communication uniquement" class="{{ $champ }}"></textarea>
                                </div>
                                <button type="submit" data-test="suivi-enregistrer" wire:loading.attr="disabled" class="btn-tap rounded-[9px] border border-brand/20 bg-white px-4 py-2 text-[13px] font-bold text-brand hover:bg-cloud disabled:opacity-60">Enregistrer le suivi</button>
                                <button type="button" wire:click="supprimer({{ $fiche['id'] }})" wire:confirm="Supprimer définitivement la réponse de {{ $fiche['nom_complet'] }} et sa photo ? Cette action est irréversible." data-test="supprimer"
                                    class="btn-tap rounded-[9px] border border-[#F0C9C9] px-4 py-2 text-[12.5px] font-bold text-accent hover:bg-[#F9E9E9]">Supprimer la réponse</button>
                                    </div>
                                </details>
                            </form>
                        </div>
                    @endif
                @empty
                    <p class="py-12 text-center text-sm text-[#5B677A]">
                        @if ($filtreStatut !== '' || trim($recherche) !== '')
                            Aucune réponse ne correspond à ces critères.
                        @else
                            Aucune réponse pour le moment. Elles arriveront ici dès que le formulaire du site sera rempli.
                        @endif
                    </p>
                @endforelse
            </div>
            <x-ui.pager :meta="$meta" />
        @else
            {{-- Galerie « Ils ont inspiré » de la page /cathoimpact --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="max-w-2xl text-[12.5px] text-[#5B677A]">Ces cartes apparaissent sur la page CathoImpact du site, sous le bouton du formulaire, dans cet ordre. Chacune ouvre la publication sur le réseau social, dans un nouvel onglet.</p>
                <button wire:click="nouvelleCarte" data-test="carte-nouvelle" class="btn-tap shrink-0 rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Nouvelle carte</button>
            </div>

            @if ($carteForm)
                <form wire:submit="enregistrerCarte" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2" data-test="carte-form">
                    <div class="flex items-center justify-between sm:col-span-2">
                        <p class="text-sm font-bold text-brand">{{ $carteId ? 'Modifier la carte' : 'Nouvelle carte' }}</p>
                        <button type="button" wire:click="fermerCarte" class="icon-btn rounded-lg p-1 hover:bg-cloud" aria-label="Fermer"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                    </div>
                    <div>
                        <label for="carte-nom" class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom de la personne</label>
                        <input wire:model="carteNom" id="carte-nom" type="text" maxlength="150" class="{{ $champ }}">
                        @error('carteNom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="carte-accroche" class="mb-1 block text-xs font-semibold text-[#5B677A]">Accroche courte (facultatif)</label>
                        <input wire:model="carteAccroche" id="carte-accroche" type="text" maxlength="200" placeholder="Ex. : fondatrice de Kone Agro" class="{{ $champ }}">
                        @error('carteAccroche') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="carte-lien" class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien de la publication</label>
                        <input wire:model="carteLien" id="carte-lien" type="url" placeholder="https://www.instagram.com/p/…" class="{{ $champ }}">
                        @error('carteLien') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="carte-reseau" class="mb-1 block text-xs font-semibold text-[#5B677A]">Réseau</label>
                        <select wire:model="carteReseau" id="carte-reseau" class="{{ $champ }}">
                            @foreach ($reseaux as $valeur => $libelle)
                                <option value="{{ $valeur }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="carte-candidature" class="mb-1 block text-xs font-semibold text-[#5B677A]">Réponse liée (facultatif)</label>
                        <select wire:model="carteCandidature" id="carte-candidature" class="{{ $champ }}">
                            <option value="">Aucune</option>
                            @foreach ($choixCandidatures as $c)
                                <option value="{{ $c['id'] }}">{{ $c['nom_complet'] }} · {{ $c['statut_label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2.5 self-end pb-2 text-[13px] font-semibold text-brand">
                        <input type="checkbox" wire:model="carteVisible" class="size-4 rounded border-brand/30 text-accent"> Visible sur le site
                    </label>
                    <div class="sm:col-span-2">
                        <x-ui.media-field label="Image de la carte (portrait, format vertical conseillé)" hint="Téléversez une image (JPG, PNG ou WebP) ou collez son lien." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                        @error('mediaUrl') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" data-test="carte-enregistrer" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer la carte</button>
                </form>
            @endif

            @if ($portraits === [])
                <p class="rounded-[18px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucune carte pour l'instant : la section « Ils ont inspiré » reste masquée sur le site.</p>
            @else
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($portraits as $p)
                        <div wire:key="carte-{{ $p['id'] }}" class="flex flex-col rounded-[16px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $p['visible'] ? '' : 'opacity-60' }}" data-test="carte-{{ $p['id'] }}">
                            <div class="flex gap-3">
                                <img src="{{ $p['image_url'] }}" alt="" class="h-24 w-20 shrink-0 rounded-[10px] bg-cloud object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] font-bold text-brand">{{ $p['nom'] }}</p>
                                    @if ($p['accroche'])<p class="line-clamp-2 text-[12px] text-[#5B677A]">{{ $p['accroche'] }}</p>@endif
                                    <p class="mt-0.5 text-[11px] text-[#5B677A]">{{ $p['reseau_label'] }}{{ $p['visible'] ? '' : ' · masquée' }}@if ($p['candidature_nom']) · réponse de {{ $p['candidature_nom'] }}@endif</p>
                                </div>
                            </div>
                            <div class="mt-2.5 flex flex-wrap items-center gap-1 border-t border-[#EDF0F5] pt-2">
                                <button wire:click="deplacer({{ $p['id'] }}, -1)" @disabled($loop->first) aria-label="Monter" title="Monter" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand disabled:opacity-30"><x-ui.icon name="chevron-down" class="size-3.5 rotate-180" /></button>
                                <button wire:click="deplacer({{ $p['id'] }}, 1)" @disabled($loop->last) aria-label="Descendre" title="Descendre" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand disabled:opacity-30"><x-ui.icon name="chevron-down" class="size-3.5" /></button>
                                <button wire:click="basculerVisible({{ $p['id'] }}, {{ $p['visible'] ? 'true' : 'false' }})" data-test="carte-visible-{{ $p['id'] }}" class="rounded-lg px-2 py-1 text-[11.5px] font-semibold text-azure hover:bg-azure/10">{{ $p['visible'] ? 'Masquer' : 'Afficher' }}</button>
                                <a href="{{ $p['lien'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg px-2 py-1 text-[11.5px] font-semibold text-azure hover:bg-azure/10">Ouvrir</a>
                                <span class="flex-1"></span>
                                <button wire:click="modifierCarte({{ $p['id'] }})" aria-label="Modifier" title="Modifier" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand"><x-ui.icon name="pencil" class="size-3.5" /></button>
                                <button wire:click="supprimerCarte({{ $p['id'] }})" wire:confirm="Retirer la carte de {{ $p['nom'] }} de la galerie du site ?" aria-label="Supprimer" title="Supprimer" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-accent/10 hover:text-accent"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
