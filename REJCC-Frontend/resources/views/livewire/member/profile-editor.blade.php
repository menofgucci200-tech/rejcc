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
                        <div class="flex size-[84px] shrink-0 items-center justify-center rounded-full text-2xl font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ mb_substr($prenom ?: '?', 0, 1) }}{{ mb_substr($nom ?: '', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-[13.5px] font-bold text-brand">Photo de profil</p>
                            <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">Fonctionnalité bientôt disponible.</p>
                        </div>
                    </div>

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

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Paroisse / Diocèse
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

                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Bio
                            <textarea wire:model="bio" placeholder="Décrivez-vous en quelques mots…" class="min-h-[90px] resize-y rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure"></textarea>
                        </label>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="self-start rounded-[9px] px-5 py-2.5 text-[12.5px] font-bold text-white transition-colors"
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
                                class="relative h-6 w-[42px] shrink-0 rounded-full transition-colors"
                                style="background: {{ $pref['on'] ? '#22A85A' : '#E6EAF0' }}"
                            >
                                <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all" style="left: {{ $pref['on'] ? '21px' : '3px' }}"></span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Sécurité --}}
        <section class="mb-6">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Sécurité</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="max-w-[480px] rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <form wire:submit="updatePassword" class="flex flex-col gap-3">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Mot de passe actuel
                        <input wire:model="current_password" type="password" placeholder="••••••••" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        @error('current_password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Nouveau mot de passe
                        <input wire:model="password" type="password" placeholder="••••••••" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-[#5B677A]">Confirmer le nouveau mot de passe
                        <input wire:model="password_confirmation" type="password" placeholder="••••••••" class="rounded-[9px] border border-brand/10 px-3 py-2.5 text-[13px] text-ink outline-none focus:border-azure" />
                    </label>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="self-start rounded-[9px] px-5 py-2.5 text-[12.5px] font-bold text-white"
                        style="background: {{ $passwordStatus === 'saved' ? '#22A85A' : '#031D59' }}"
                    >
                        <span wire:loading.remove>{{ $passwordStatus === 'saved' ? 'Mot de passe mis à jour !' : 'Mettre à jour le mot de passe' }}</span>
                        <span wire:loading>Mise à jour…</span>
                    </button>
                </form>
            </div>
        </section>

        {{-- Carte de membre & pièce d'identité --}}
        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Carte de membre &amp; pièce d'identité</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>

            <div class="grid gap-7 rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] lg:grid-cols-2">
                <div>
                    <p class="mb-1.5 text-[13px] font-bold text-brand">Carte de membre REJCC</p>
                    <p class="mb-3.5 text-[11.5px] text-[#9AA6B8]">Générée automatiquement à partir de votre profil.</p>

                    <div class="grid grid-cols-2 gap-3.5">
                        {{-- Recto --}}
                        <div>
                            <p class="mb-2 text-[11px] font-bold tracking-[0.06em] text-[#5B677A]">RECTO</p>
                            <div class="relative aspect-[1.6/1] overflow-hidden rounded-2xl p-4 text-white" style="background: linear-gradient(135deg,#0B1F52,#031D59)">
                                <div class="pointer-events-none absolute -bottom-[30px] -left-3.5 font-serif text-[90px] font-bold leading-none text-white/[.06]">JCC</div>
                                <div class="relative flex h-full gap-3">
                                    <div class="flex h-[68px] w-[58px] shrink-0 items-center justify-center rounded-lg bg-white/10 text-lg font-bold">
                                        {{ mb_substr($prenom ?: '?', 0, 1) }}{{ mb_substr($nom ?: '', 0, 1) }}
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-col pb-7">
                                        <div class="text-center">
                                            <img src="{{ asset('brand/rejcc-logo-white.png') }}" alt="" class="mx-auto h-[22px] w-[22px] object-contain">
                                            <p class="mt-0.5 font-serif text-[11px] font-bold tracking-[0.15em]">REJCC</p>
                                            <div class="mx-0 my-1 flex items-center gap-1.5">
                                                <span class="h-px flex-1 bg-white/40"></span>
                                                <span class="size-[3px] shrink-0 rounded-full bg-[#D95B5A]"></span>
                                                <span class="h-px flex-1 bg-white/40"></span>
                                            </div>
                                        </div>
                                        <div class="mt-auto">
                                            <p class="truncate font-serif text-xs uppercase tracking-[0.06em]">{{ $prenom }} {{ $nom }}</p>
                                            <p class="mt-px text-[8.5px] font-bold tracking-[0.1em] text-[#D95B5A]">MEMBRE OFFICIEL</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute inset-x-4 bottom-3">
                                    <p class="text-[6.5px] font-bold leading-[1.4] tracking-[0.08em] text-white">RESEAU ENTREPRENEURIAL DES JEUNES CATHOLIQUES</p>
                                    <p class="text-[6.5px] font-bold tracking-[0.08em] text-[#D95B5A]">DE COTE D'IVOIRE</p>
                                </div>
                            </div>
                        </div>

                        {{-- Verso --}}
                        <div>
                            <p class="mb-2 text-[11px] font-bold tracking-[0.06em] text-[#5B677A]">VERSO</p>
                            <div class="relative aspect-[1.6/1] overflow-hidden rounded-2xl p-4 text-white" style="background: linear-gradient(135deg,#0B1F52,#031D59)">
                                <div class="pointer-events-none absolute -right-10 -top-5 size-[140px] rounded-full border border-white/[.06]"></div>
                                <div class="relative flex max-w-[150px] flex-col gap-0.5">
                                    <div class="grid size-14 grid-cols-5 grid-rows-5 gap-px rounded-lg bg-white p-1.5">
                                        <div class="col-span-2 row-span-2 rounded-sm bg-brand"></div>
                                        <div class="col-span-2 col-start-4 row-span-2 rounded-sm bg-brand"></div>
                                        <div class="col-span-2 row-span-2 row-start-4 rounded-sm bg-brand"></div>
                                        <div class="col-start-3 row-start-3 bg-brand"></div>
                                        <div class="col-start-4 row-start-4 bg-brand"></div>
                                        <div class="col-start-5 row-start-5 bg-brand"></div>
                                        <div class="col-start-4 row-start-5 bg-brand"></div>
                                    </div>
                                    <p class="mt-2 text-[6.5px] font-bold leading-[1.5] tracking-[0.05em]">SCANNEZ POUR ACCÉDER À<br>VOTRE PROFIL MEMBRE</p>
                                    <div class="my-2 h-px w-[30px] bg-[#D95B5A]"></div>
                                    <p class="text-[6px] font-bold tracking-[0.08em] text-[#C4D0EC]">N° MEMBRE</p>
                                    <p class="mb-1.5 text-[7px] font-bold tracking-[0.05em] text-[#D95B5A]">{{ $reference }}</p>
                                    <p class="text-[6px] font-bold tracking-[0.08em] text-[#C4D0EC]">DATE D'ADHÉSION</p>
                                    <p class="text-[7px] font-bold tracking-[0.05em] text-[#D95B5A]">
                                        {{ $date_adhesion ? \Illuminate\Support\Carbon::parse($date_adhesion)->locale('fr')->translatedFormat('d F Y') : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="mt-3.5 rounded-[9px] border border-[#C9D3E6] px-4.5 py-2.5 text-[12.5px] font-bold text-brand hover:bg-cloud">Télécharger la carte</button>
                </div>

                <div>
                    <p class="mb-1.5 text-[13px] font-bold text-brand">Pièce d'identité (vérification)</p>
                    <p class="mb-3 text-[11.5px] text-[#9AA6B8]">CNI, passeport ou attestation — recto et verso.</p>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <div class="flex aspect-[8/5] w-full items-center justify-center rounded-[10px] border-2 border-dashed border-brand/15 bg-cloud text-[11px] text-[#9AA6B8]">Recto</div>
                        </div>
                        <div>
                            <div class="flex aspect-[8/5] w-full items-center justify-center rounded-[10px] border-2 border-dashed border-brand/15 bg-cloud text-[11px] text-[#9AA6B8]">Verso</div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="size-2 shrink-0 rounded-full bg-[#F5A623]"></span>
                        <span class="text-[11.5px] text-[#5B677A]">Envoi de documents bientôt disponible</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
