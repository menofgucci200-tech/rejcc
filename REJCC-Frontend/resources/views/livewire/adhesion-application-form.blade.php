<div>
@if ($status === 'success')
    <div class="flex min-h-screen flex-col items-center justify-center px-5 py-10 text-center">
        <span class="mb-5 flex size-[76px] items-center justify-center rounded-full text-white shadow-[0_10px_26px_rgba(34,168,90,.3)]" style="background: linear-gradient(135deg, #22A85A, #1C8A49)">
            <x-ui.icon name="check" class="size-9" />
        </span>
        <h1 class="mb-2.5 text-2xl font-extrabold text-brand">Merci pour votre demande !</h1>
        <p class="mb-2 max-w-[380px] text-sm leading-relaxed text-[#5B677A]">Votre adhésion au REJCC a bien été enregistrée. Notre équipe vous contactera prochainement sur WhatsApp ou par e-mail.</p>
        <p class="mb-7 max-w-[380px] font-serif text-[13.5px] italic text-[#5B677A]">« Tout ce que vous faites, faites-le de bon cœur, comme pour le Seigneur. » — Colossiens 3:23</p>
        <div class="flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('home') }}" wire:navigate class="rounded-[10px] bg-brand px-[22px] py-3 text-[13.5px] font-bold text-white hover:bg-brand/90">Retour à l'accueil</a>
            <a href="{{ route('adhesion.status') }}" wire:navigate class="rounded-[10px] border border-[#C9D3E6] bg-white px-[22px] py-3 text-[13.5px] font-bold text-brand hover:bg-cloud">Suivre l'état de ma candidature</a>
        </div>
    </div>
@else
    <div class="min-h-screen bg-cloud">
        <div class="mx-auto w-full max-w-[600px] px-4 py-8 sm:py-10">

            {{-- En-tête clair : flèche retour + titre + étape --}}
            <div class="mb-4 flex items-start gap-3.5">
                <a href="{{ route('home') }}" wire:navigate aria-label="Retour à l'accueil" class="flex size-11 shrink-0 items-center justify-center rounded-[12px] border border-brand/15 bg-white text-brand shadow-[0_2px_6px_rgba(3,29,89,.06)] hover:bg-cloud">
                    <x-ui.icon name="arrow-left" class="size-[18px]" />
                </a>
                <div class="min-w-0 pt-0.5">
                    <h1 class="text-[19px] font-extrabold leading-tight text-brand">Formulaire d'adhésion</h1>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire</p>
                    <p class="mt-1.5 text-[13px] font-bold text-brand">Étape {{ $step + 1 }} sur 8 · {{ $stepTitles[$step] }}</p>
                </div>
            </div>

            {{-- Barre de progression fine --}}
            <div class="mb-6 h-1.5 overflow-hidden rounded-full bg-brand/10">
                <div class="h-full rounded-full transition-all duration-300" style="width: {{ round(($step + 1) / 8 * 100) }}%; background: linear-gradient(90deg, #AC0100, #D95B5A)"></div>
            </div>

            {{-- Carte du formulaire, mise en avant --}}
            <div class="rounded-[20px] border border-brand/10 bg-white p-6 shadow-[0_18px_50px_-24px_rgba(3,29,89,.28)] sm:p-8">

                @if ($step === 0)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Informations générales</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Bienvenue dans le formulaire d'adhésion au REJCC. Parlons d'abord de vous.</p>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-nom-complet" class="text-[13px] font-bold text-brand">Nom et prénoms *</label>
                        <input wire:model="nom_complet" id="ma-nom-complet" type="text" placeholder="Votre nom complet" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('nom_complet') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <p class="mb-2 text-[13px] font-bold text-brand">Sexe *</p>
                        <div class="flex gap-2">
                            @foreach ($sexes as $option)
                                <x-ui.chip wire:click="select('sexe', {{ \Illuminate\Support\Js::from($option) }})" :active="$sexe === $option" class="flex-1 justify-center">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('sexe') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-age" class="text-[13px] font-bold text-brand">Tranche d'âge *</label>
                        <select wire:model="tranche_age" id="ma-age" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                            <option value="">Sélectionnez…</option>
                            @foreach ($tranchesAge as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('tranche_age') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-whatsapp" class="text-[13px] font-bold text-brand">Numéro WhatsApp *</label>
                        <input wire:model="whatsapp" id="ma-whatsapp" type="tel" placeholder="+225 07 00 00 00 00" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('whatsapp') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-email" class="text-[13px] font-bold text-brand">Adresse e-mail *</label>
                        <input wire:model="email" id="ma-email" type="email" placeholder="vous@exemple.com" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-ville" class="text-[13px] font-bold text-brand">Ville *</label>
                        <input wire:model="ville" id="ma-ville" type="text" placeholder="Abidjan" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('ville') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-1.5 flex flex-col gap-1.5">
                        <label for="ma-password" class="text-[13px] font-bold text-brand">Mot de passe (8 caractères min.) *</label>
                        <input wire:model="password" id="ma-password" type="password" autocomplete="new-password" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-1.5 flex flex-col gap-1.5">
                        <label for="ma-password-confirmation" class="text-[13px] font-bold text-brand">Confirmer le mot de passe *</label>
                        <input wire:model="password_confirmation" id="ma-password-confirmation" type="password" autocomplete="new-password" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                    </div>
                    <p class="text-xs text-[#9AA6B8]">Ce mot de passe vous servira à vous connecter à votre espace membre une fois votre candidature acceptée.</p>
                @endif

                @if ($step === 1)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Diocèse &amp; paroisse</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Aidez-nous à situer votre communauté paroissiale.</p>

                    <div class="mb-4 flex flex-col gap-1.5">
                        <label for="ma-diocese" class="text-[13px] font-bold text-brand">À quel diocèse appartient votre paroisse ? *</label>
                        <select wire:model="diocese" id="ma-diocese" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                            <option value="">Sélectionnez…</option>
                            @foreach ($dioceses as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('diocese') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ma-paroisse" class="text-[13px] font-bold text-brand">Quelle est votre paroisse ? *</label>
                        <select wire:model="paroisse" id="ma-paroisse" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                            <option value="">Sélectionnez…</option>
                            @foreach ($paroisses as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('paroisse') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($step === 2)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Profil</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Quelques informations sur votre situation actuelle.</p>

                    <div class="mb-5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Quel est votre statut actuel ? * <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($statutsActuels as $option)
                                <x-ui.chip wire:click="toggle('statut_actuel', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $statut_actuel, true)">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('statut_actuel') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ma-etudes" class="text-[13px] font-bold text-brand">Quel est votre plus haut niveau d'études ? *</label>
                        <select wire:model="niveau_etudes" id="ma-etudes" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                            <option value="">Sélectionnez…</option>
                            @foreach ($niveauxEtudes as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('niveau_etudes') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($step === 3)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Compétences</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Parlez-nous de votre formation et de votre savoir-faire.</p>

                    <div class="mb-4.5 flex flex-col gap-1.5">
                        <label for="ma-domaines-formation" class="text-[13px] font-bold text-brand">Domaines de formation ou spécialités *</label>
                        <input wire:model="domaines_formation" id="ma-domaines-formation" type="text" placeholder="Ex. Gestion des entreprises" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        @error('domaines_formation') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4.5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Dans quels domaines avez-vous des compétences ? * <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($competencesOptions as $option)
                                <x-ui.chip wire:click="toggle('competences', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $competences, true)">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('competences') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ma-competences-desc" class="text-[13px] font-bold text-brand">Décrivez brièvement vos compétences ou activités <span class="font-medium text-[#9AA6B8]">(facultatif)</span></label>
                        <textarea wire:model="description_competences" id="ma-competences-desc" rows="3" placeholder="Quelques lignes sur ce que vous savez faire…" class="resize-y rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure"></textarea>
                        @error('description_competences') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($step === 4)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Entrepreneuriat</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Avez-vous déjà une activité en cours ?</p>

                    <div class="mb-5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Avez-vous actuellement une ou des activités génératrices de revenus ou une entreprise ? *</p>
                        <div class="flex gap-2">
                            @foreach (['Oui', 'Non'] as $option)
                                <x-ui.chip wire:click="select('a_activite', {{ \Illuminate\Support\Js::from($option) }})" :active="$a_activite === $option" class="flex-1 justify-center">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('a_activite') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    @if ($a_activite === 'Oui')
                        <div class="mb-4.5 flex flex-col gap-1.5">
                            <label for="ma-nom-activite" class="text-[13px] font-bold text-brand">Nom de vos activités ou entreprises <span class="font-medium text-[#9AA6B8]">(facultatif)</span></label>
                            <input wire:model="nom_activite" id="ma-nom-activite" type="text" placeholder="Ex. Boutique solidaire" class="rounded-[9px] border border-brand/15 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure" />
                        </div>

                        <div class="mb-4.5">
                            <p class="mb-2.5 text-[13px] font-bold text-brand">Le ou les secteurs d'activités * <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($secteursActivite as $option)
                                    <x-ui.chip wire:click="toggle('secteurs_activite', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $secteurs_activite, true)">{{ $option }}</x-ui.chip>
                                @endforeach
                            </div>
                            @error('secteurs_activite') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="ma-anciennete" class="text-[13px] font-bold text-brand">Depuis combien d'années exercez-vous ? *</label>
                            <select wire:model="anciennete" id="ma-anciennete" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                                <option value="">Sélectionnez…</option>
                                @foreach ($anciennetes as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('anciennete') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>
                    @endif
                @endif

                @if ($step === 5)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Projet futur</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Même sans activité aujourd'hui, quels secteurs vous intéressent ?</p>

                    <div>
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Dans quels domaines souhaitez-vous créer une activité dans le futur ? @if ($a_activite === 'Non') * @endif <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($secteursActivite as $option)
                                <x-ui.chip wire:click="toggle('domaines_futurs', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $domaines_futurs, true)">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('domaines_futurs') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($step === 6)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Attentes</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Dernière ligne droite : parlons de vos attentes et de vos besoins.</p>

                    <div class="mb-4.5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Qu'attendez-vous principalement du réseau ? * <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($attentesOptions as $option)
                                <x-ui.chip wire:click="toggle('attentes', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $attentes, true)">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('attentes') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4.5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Quelles formations vous intéressent le plus ? * <span class="font-medium text-[#9AA6B8]">(plusieurs choix possibles)</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($formationsInteretOptions as $option)
                                <x-ui.chip wire:click="toggle('formations_interet', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $formations_interet, true)">{{ $option }}</x-ui.chip>
                            @endforeach
                        </div>
                        @error('formations_interet') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4.5">
                        <p class="mb-2.5 text-[13px] font-bold text-brand">Quel est aujourd'hui votre principal défi ? *</p>
                        <div class="flex flex-col gap-2">
                            @foreach ($defis as $option)
                                <button type="button" wire:click="select('defi_principal', {{ \Illuminate\Support\Js::from($option) }})" class="rounded-[10px] border px-3.5 py-2.5 text-left text-[13px] font-semibold {{ $defi_principal === $option ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-[#5B677A]' }}">
                                    {{ $option }}
                                </button>
                            @endforeach
                        </div>
                        @error('defi_principal') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ma-revenu" class="text-[13px] font-bold text-brand">Quel est votre revenu mensuel actuel approximatif ? *</label>
                        <select wire:model="revenu_mensuel" id="ma-revenu" class="rounded-[9px] border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none focus:border-azure">
                            <option value="">Sélectionnez…</option>
                            @foreach ($revenus as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('revenu_mensuel') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($step === 7)
                    <p class="mb-1 text-[15px] font-extrabold text-brand">Récapitulatif</p>
                    <p class="mb-4.5 text-[12.5px] leading-relaxed text-[#5B677A]">Vérifiez vos informations avant l'envoi.</p>

                    <div class="flex flex-col gap-3">
                        @foreach ($recap as $r)
                            <div class="border-t border-[#EDF0F5] pt-2.5 first:border-t-0 first:pt-0">
                                <p class="text-[11.5px] font-bold uppercase tracking-[0.04em] text-[#9AA6B8]">{{ $r['label'] }}</p>
                                <p class="mt-0.5 text-[13.5px] leading-relaxed text-ink">{{ $r['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    @error('email') <p class="mt-4 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                    @error('revenu_mensuel') <p class="mt-4 text-xs font-medium text-accent">{{ $message }}</p> @enderror
                @endif

                {{-- Boutons intégrés dans la carte --}}
                <div class="mt-7 flex gap-3 border-t border-brand/10 pt-6">
                    <button type="button" wire:click="back" @disabled($step === 0) class="flex-1 rounded-[10px] border border-[#C9D3E6] bg-white py-3 text-sm font-bold text-brand hover:bg-cloud disabled:opacity-40">Précédent</button>
                    <button type="button" wire:click="next" wire:loading.attr="disabled" class="flex-[1.4] rounded-[10px] bg-accent py-3 text-sm font-bold text-white shadow-[0_8px_20px_-8px_rgba(172,1,0,.6)] hover:bg-accent-600 disabled:opacity-70">
                        <span wire:loading.remove>{{ $step === 7 ? 'Envoyer ma demande' : 'Suivant' }}</span>
                        <span wire:loading>Envoi…</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
@endif
</div>
