@php
    $user = \App\Support\Api::user();
@endphp

<div>
    <x-member-light.topbar title="Paramètres" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">

        {{-- Profil & préférences --}}
        <section class="mb-6">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Profil &amp; préférences</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="grid gap-7 rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] lg:grid-cols-2">
                <div>
                    <p class="mb-3.5 text-[13px] font-bold text-brand">Profil</p>
                    <div class="mb-4 flex items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="Photo de profil" class="size-[84px] shrink-0 rounded-full object-cover ring-2 ring-brand/10">
                        @else
                            <div class="flex size-[84px] shrink-0 items-center justify-center rounded-full text-2xl font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                {{ mb_substr($prenom ?: '?', 0, 1) }}{{ mb_substr($nom ?: '', 0, 1) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-[13.5px] font-bold text-brand">Photo de profil</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                <label class="btn-tap inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-brand px-3 py-1.5 text-[11.5px] font-bold text-white hover:bg-brand/90">
                                    <x-ui.icon name="image" class="size-3.5" /> {{ $photo ? 'Changer' : 'Ajouter' }}
                                    <input type="file" wire:model="photoFile" accept="image/*" class="hidden">
                                </label>
                                @if ($photo)
                                    <button type="button" wire:click="removePhoto" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-semibold text-[#5B677A] hover:bg-cloud">Retirer</button>
                                @endif
                                <span wire:loading wire:target="photoFile" class="text-[11px] font-semibold text-azure">Envoi…</span>
                            </div>
                            @error('photoFile') <p class="mt-1 text-[11px] text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Complétion du profil --}}
                    <div class="mb-4 rounded-[12px] border border-brand/10 bg-cloud/50 p-3.5">
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="text-[12px] font-bold text-brand">Complétion du profil</span>
                            <span data-test="completion" class="text-[12px] font-bold text-azure">{{ $completion }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full transition-all" style="width: {{ $completion }}%; background: linear-gradient(90deg,#4F6FBF,#22A85A)"></div>
                        </div>
                        @if ($completion < 100)
                            <p class="mt-2 text-[11px] text-[#9AA6B8]">Il manque : {{ implode(', ', $champsManquants) }}.</p>
                        @endif
                    </div>

                    {{-- Pièce d'identité --}}
                    <div class="mb-4 rounded-[12px] border border-brand/10 p-3.5">
                        <p class="text-[12.5px] font-bold text-brand">Pièce d'identité</p>
                        <p class="mt-0.5 text-[11px] text-[#9AA6B8]">CNI, passeport ou attestation (image ou PDF, 5 Mo max). Confidentiel, visible par l'administration uniquement.</p>
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            @if ($piece_identite)
                                <a href="{{ $piece_identite }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1.5 text-[11.5px] font-semibold text-[#22A85A]">
                                    <x-ui.icon name="check-circle" class="size-3.5" /> Document fourni · voir
                                </a>
                            @endif
                            <label class="btn-tap inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">
                                <x-ui.icon name="file-text" class="size-3.5" /> {{ $piece_identite ? 'Remplacer' : 'Téléverser' }}
                                <input type="file" wire:model="idFile" accept="image/*,application/pdf" class="hidden">
                            </label>
                            <span wire:loading wire:target="idFile" class="text-[11px] font-semibold text-azure">Envoi…</span>
                        </div>
                        @error('idFile') <p class="mt-1 text-[11px] text-accent">{{ $message }}</p> @enderror
                    </div>

                    @if ($mediaMessage)
                        <p class="panel-enter mb-3 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1.5 text-[11.5px] font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $mediaMessage }}</p>
                    @endif

                    <form wire:submit="save" class="flex flex-col gap-3">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Nom complet
                            <span class="grid grid-cols-2 gap-2">
                                <input wire:model="prenom" type="text" placeholder="Prénom" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                                <input wire:model="nom" type="text" placeholder="Nom" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                            </span>
                            @error('prenom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            @error('nom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Adresse e-mail
                            <input type="email" value="{{ $email }}" disabled class="cursor-not-allowed rounded-[9px] border border-brand/10 bg-cloud px-3 py-2.5 text-[13px] text-[#9AA6B8] outline-none" />
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Téléphone
                            <input wire:model="telephone" type="tel" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                            @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Date de naissance
                                <input wire:model="date_naissance" type="date" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                            </label>
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Ville
                                <input wire:model="ville" type="text" placeholder="Abidjan" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                            </label>
                        </div>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Paroisse
                            <input wire:model="paroisse" type="text" placeholder="Paroisse Saint-Jean, Abidjan" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Genre
                            <select wire:model="genre" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure">
                                <option value="">—</option>
                                <option value="Homme">Homme</option>
                                <option value="Femme">Femme</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Domaine d'activité
                            <input wire:model="secteur" type="text" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Profil
                            <select wire:model="profil" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure">
                                <option value="">—</option>
                                @foreach ($profiles as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Entreprise / projet
                            <input wire:model="organisation" type="text" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        </label>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="btn-tap self-start rounded-[9px] px-5 py-2.5 text-[12.5px] font-bold text-white shadow-sm hover:shadow-md"
                            style="background: {{ $status === 'saved' ? '#22A85A' : '#031D59' }}"
                        >
                            <span wire:loading.remove>{{ $status === 'saved' ? 'Enregistré !' : 'Enregistrer' }}</span>
                            <span wire:loading>Enregistrement…</span>
                        </button>
                    </form>
                </div>

                <div>
                    <p class="mb-3.5 text-[13px] font-bold text-brand">Préférences</p>
                    @foreach ($preferenceRows as $pref)
                        <div class="flex items-center justify-between border-t border-cloud-200 py-[11px] first:border-t-0">
                            <div>
                                <p class="text-[13px] font-semibold text-ink">{{ $pref['label'] }}</p>
                                <p class="text-[11.5px] text-[#9AA6B8]">{{ $pref['detail'] }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="togglePreference('{{ $pref['key'] }}')"
                                class="relative h-6 w-[42px] shrink-0 rounded-full transition-colors duration-200 active:scale-95"
                                style="background: {{ $pref['on'] ? '#22A85A' : '#E6EAF0' }}"
                            >
                                <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200 ease-out" style="left: {{ $pref['on'] ? '21px' : '3px' }}"></span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Page biographique publique (ouverte par le QR code de la carte) --}}
        <section class="mb-6" data-test="section-bio">
            <div class="mb-1 flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-[17px] font-bold text-brand">Ma page biographique</h2>
                @if ($pagePublique)
                    <a href="{{ $pagePublique }}" target="_blank" rel="noopener" data-test="voir-page-publique" class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-azure hover:underline">
                        <x-ui.icon name="external-link" class="size-3.5" /> Voir ma page publique
                    </a>
                @endif
            </div>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-5 max-w-3xl text-[12.5px] text-[#5B677A]">C'est la page que découvre toute personne qui scanne le QR code de votre carte membre : votre carte de visite et votre CV dans le réseau. Vos certificats, groupes, projets et offres REJCC s'y ajoutent automatiquement. Votre téléphone et votre e-mail n'y figurent que si vous activez « Coordonnées sur ma page publique ».</p>

                <form wire:submit="saveBio" class="grid gap-5 lg:grid-cols-2">
                    <div class="flex flex-col gap-3">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Fonction / titre
                            <input wire:model="titre" type="text" placeholder="Ex : Fondatrice d'AgroVert, consultante en agrobusiness" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                            @error('titre') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Diocèse
                            <input wire:model="diocese" type="text" placeholder="Ex : Archidiocèse d'Abidjan" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]" x-data="{ n: {{ mb_strlen($bio) }} }">
                            <span class="flex justify-between">À propos de moi <span class="font-normal text-[#9AA6B8]" x-text="n + ' / 1 500'"></span></span>
                            <textarea wire:model="bio" @input="n = $event.target.value.length" rows="6" maxlength="1500" placeholder="Votre histoire, votre vision, ce qui vous anime, ce que vous apportez au réseau…" class="resize-y rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure"></textarea>
                            @error('bio') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </label>
                        <div class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Compétences ({{ count($competences) }} / 15)
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($competences as $i => $c)
                                    <span wire:key="comp-{{ $i }}" class="inline-flex items-center gap-1 rounded-full bg-azure/10 py-1 pl-3 pr-1.5 text-[12px] font-semibold text-azure">
                                        {{ $c }}
                                        <button type="button" wire:click="retirerCompetence({{ $i }})" aria-label="Retirer {{ $c }}" class="flex size-4 items-center justify-center rounded-full hover:bg-azure/20"><x-ui.icon name="x" class="size-3" /></button>
                                    </span>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input wire:model="nouvelleCompetence" wire:keydown.enter.prevent="ajouterCompetence" type="text" placeholder="Ex : Comptabilité, Marketing digital…" class="min-w-0 flex-1 rounded-[9px] border border-brand/10 px-3 py-2 text-[13px] font-normal text-ink outline-none focus:border-azure" />
                                <button type="button" wire:click="ajouterCompetence" class="btn-tap rounded-[9px] border border-brand/15 px-3 text-[12px] font-bold text-brand hover:bg-cloud">Ajouter</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Parcours (postes, réalisations, formations)
                            @foreach ($parcours as $i => $etape)
                                <div wire:key="etape-{{ $i }}" class="rounded-[10px] border border-brand/10 p-2.5">
                                    <div class="grid gap-2 sm:grid-cols-[110px_1fr]">
                                        <input wire:model="parcours.{{ $i }}.periode" type="text" placeholder="2022 – auj." class="rounded-[8px] border border-brand/10 px-2.5 py-2 text-[12.5px] font-normal text-ink outline-none focus:border-azure" />
                                        <input wire:model="parcours.{{ $i }}.titre" type="text" placeholder="Poste ou réalisation" class="rounded-[8px] border border-brand/10 px-2.5 py-2 text-[12.5px] font-normal text-ink outline-none focus:border-azure" />
                                    </div>
                                    <div class="mt-2 flex gap-2">
                                        <input wire:model="parcours.{{ $i }}.structure" type="text" placeholder="Entreprise, organisation, école…" class="min-w-0 flex-1 rounded-[8px] border border-brand/10 px-2.5 py-2 text-[12.5px] font-normal text-ink outline-none focus:border-azure" />
                                        <button type="button" wire:click="retirerEtape({{ $i }})" class="px-1 text-[11.5px] font-semibold text-accent hover:underline">Retirer</button>
                                    </div>
                                    @error("parcours.{$i}.titre") <span class="mt-1 block text-xs font-medium text-accent">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                            @if (count($parcours) < 8)
                                <button type="button" wire:click="ajouterEtape" class="btn-tap inline-flex w-fit items-center gap-1.5 rounded-[9px] border border-dashed border-brand/25 px-3 py-2 text-[12px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="plus" class="size-3.5" /> Ajouter une étape</button>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Liens
                            @foreach (['site' => 'Site web', 'linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'instagram' => 'Instagram'] as $k => $label)
                                <input wire:model="liens.{{ $k }}" type="url" placeholder="{{ $label }} — https://…" class="rounded-[9px] border border-brand/10 px-3 py-2 text-[12.5px] font-normal text-ink outline-none focus:border-azure" />
                                @error("liens.{$k}") <span class="text-xs font-medium text-accent">{{ $label }} : {{ $message }}</span> @enderror
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 lg:col-span-2">
                        <button type="submit" wire:loading.attr="disabled" data-test="enregistrer-bio" class="btn-tap rounded-[9px] px-5 py-2.5 text-[12.5px] font-bold text-white shadow-sm hover:shadow-md" style="background: {{ $bioStatus === 'saved' ? '#22A85A' : '#031D59' }}">
                            <span wire:loading.remove wire:target="saveBio">{{ $bioStatus === 'saved' ? 'Page publiée !' : 'Publier ma page' }}</span>
                            <span wire:loading wire:target="saveBio">Publication…</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- Sécurité --}}
        <section class="mb-6">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Sécurité</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="max-w-[480px] rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <form wire:submit="updatePassword" class="flex flex-col gap-3">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Mot de passe actuel
                        <x-ui.password-input wire:model="current_password" placeholder="••••••••" class="w-full rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        @error('current_password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Nouveau mot de passe
                        <x-ui.password-input wire:model="password" placeholder="••••••••" class="w-full rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Confirmer le nouveau mot de passe
                        <x-ui.password-input wire:model="password_confirmation" placeholder="••••••••" class="w-full rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                    </label>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="btn-tap self-start rounded-[9px] px-5 py-2.5 text-[12.5px] font-bold text-white shadow-sm hover:shadow-md"
                        style="background: {{ $passwordStatus === 'saved' ? '#22A85A' : '#031D59' }}"
                    >
                        <span wire:loading.remove>{{ $passwordStatus === 'saved' ? 'Mot de passe mis à jour !' : 'Mettre à jour le mot de passe' }}</span>
                        <span wire:loading>Mise à jour…</span>
                    </button>
                </form>
            </div>
        </section>

        {{-- Ma carte membre : la carte officielle vit sur sa propre page --}}
        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Ma carte membre</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="max-w-xl text-[12.5px] text-[#5B677A]">Votre carte officielle REJCC (recto, verso et QR code) est générée automatiquement à partir de votre profil. Vous pouvez y ajouter votre photo et l'imprimer ou l'enregistrer en PDF.</p>
                <a href="{{ route('espace-membre.carte') }}" wire:navigate class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                    <x-ui.icon name="qr-code" class="size-3.5" /> Voir ma carte membre
                </a>
            </div>
        </section>
    </div>
</div>
