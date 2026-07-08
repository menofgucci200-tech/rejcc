@php
    $sectionTitles = [
        'general' => 'Informations générales',
        'paroisse' => 'Votre paroisse',
        'profil' => 'Profil',
        'competences' => 'Compétences',
        'entrepreneuriat' => 'Entrepreneuriat',
        'activite_actuelle' => 'Compréhension de votre activité',
        'activite_future' => 'Compréhension de votre future activité',
        'attentes' => 'Attentes et profil',
    ];
    $isLastStep = $step === 'attentes';
@endphp

<div>
@if ($status === 'success')
    <div class="flex flex-col items-center justify-center rounded-3xl border border-brand/10 bg-cloud p-10 text-center">
        <span class="inline-flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-7"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <h3 class="mt-5 text-xl font-bold text-brand">Candidature envoyée !</h3>
        <p class="mt-2 max-w-md text-sm text-ink/70">Merci d'avoir rempli votre formulaire d'adhésion. Notre équipe va l'étudier et reviendra vers vous rapidement.</p>
    </div>
@else
    <div class="rounded-3xl border border-brand/10 bg-white p-6 sm:p-8">
        @if ($step === 'general')
            <p class="mb-6 text-sm leading-relaxed text-ink/70">
                Bienvenue dans le formulaire d'adhésion au Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire (REJCC).
                Ce formulaire nous permettra de mieux vous connaître, d'identifier vos compétences, vos projets et vos attentes afin de construire une communauté dynamique d'entrepreneurs, de professionnels et de jeunes porteurs de projets unis par les valeurs chrétiennes.
                Ensemble, développons nos talents, créons des opportunités et contribuons au développement de notre Église et de notre pays.
            </p>
        @endif

        <p class="mb-6 text-xs font-semibold uppercase tracking-[0.16em] text-brand/50">{{ $sectionTitles[$step] ?? '' }}</p>

        <form wire:submit="{{ $isLastStep ? 'submit' : 'next' }}" class="flex flex-col gap-6">

            @if ($step === 'general')
                <div class="flex flex-col gap-1.5">
                    <label for="ma-nom" class="text-sm font-semibold text-brand">Nom et prénoms</label>
                    <input wire:model="nom_prenoms" id="ma-nom" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('nom_prenoms') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('nom_prenoms') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Sexe</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($sexes as $option)
                            <x-ui.chip wire:click="select('sexe', {{ \Illuminate\Support\Js::from($option) }})" :active="$sexe === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('sexe') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Dans quelle tranche d'âge vous situez-vous ?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tranchesAge as $option)
                            <x-ui.chip wire:click="select('tranche_age', {{ \Illuminate\Support\Js::from($option) }})" :active="$tranche_age === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('tranche_age') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="ma-whatsapp" class="text-sm font-semibold text-brand">Numéro Whatsapp</label>
                        <input wire:model="whatsapp" id="ma-whatsapp" type="tel" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('whatsapp') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('whatsapp') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="ma-email" class="text-sm font-semibold text-brand">Adresse e-mail</label>
                        <input wire:model="email" id="ma-email" type="email" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Connotation religieuse</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($connotationsReligieuses as $option)
                            <x-ui.chip wire:click="select('connotation_religieuse', {{ \Illuminate\Support\Js::from($option) }})" :active="$connotation_religieuse === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('connotation_religieuse') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'paroisse')
                <div class="flex flex-col gap-1.5">
                    <label for="ma-paroisse" class="text-sm font-semibold text-brand">Quel est votre paroisse ?</label>
                    <select wire:model="paroisse" id="ma-paroisse" class="w-full rounded-xl border bg-white px-4 py-3 text-brand outline-none transition focus:ring-2 {{ $errors->has('paroisse') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}">
                        <option value="">Sélectionnez votre paroisse</option>
                        @foreach ($paroisses as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('paroisse') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'profil')
                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Quel est votre statut actuel ? <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($statutsActuels as $option)
                            <x-ui.chip wire:click="toggle('statut_actuel', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $statut_actuel, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('statut_actuel') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Quel est votre plus haut niveau d'études ?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($niveauxEtudes as $option)
                            <x-ui.chip wire:click="select('niveau_etudes', {{ \Illuminate\Support\Js::from($option) }})" :active="$niveau_etudes === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('niveau_etudes') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="ma-domaines-formation" class="text-sm font-semibold text-brand">Domaines de formations ou spécialités</label>
                    <input wire:model="domaines_formation" id="ma-domaines-formation" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('domaines_formation') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('domaines_formation') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'competences')
                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Dans quels domaines avez-vous des compétences ? <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($competencesOptions as $option)
                            <x-ui.chip wire:click="toggle('competences', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $competences, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('competences') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="ma-description-competences" class="text-sm font-semibold text-brand">Décrivez brièvement vos compétences ou vos différentes activités <span class="font-normal text-ink/50">(facultatif)</span></label>
                    <textarea wire:model="description_competences" id="ma-description-competences" class="min-h-28 w-full resize-y rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('description_competences') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}"></textarea>
                    @error('description_competences') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'entrepreneuriat')
                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Avez-vous actuellement une ou des activités génératrices de revenus ou une entreprise ?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['Oui', 'Non'] as $option)
                            <x-ui.chip wire:click="select('a_activite', {{ \Illuminate\Support\Js::from($option) }})" :active="$a_activite === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('a_activite') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'activite_actuelle')
                <div class="flex flex-col gap-1.5">
                    <label for="ma-nom-activite" class="text-sm font-semibold text-brand">Nom de vos activités ou entreprises <span class="font-normal text-ink/50">(facultatif)</span></label>
                    <input wire:model="nom_activite" id="ma-nom-activite" type="text" class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Le ou les secteurs d'activités <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($secteursActivite as $option)
                            <x-ui.chip wire:click="toggle('secteurs_activite', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $secteurs_activite, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('secteurs_activite') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Depuis combien d'années exercez-vous ?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($anciennetes as $option)
                            <x-ui.chip wire:click="select('anciennete', {{ \Illuminate\Support\Js::from($option) }})" :active="$anciennete === $option">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('anciennete') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'activite_future')
                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Dans quels domaines souhaitez-vous créer une activité dans le futur ? <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($secteursActivite as $option)
                            <x-ui.chip wire:click="toggle('domaines_futurs', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $domaines_futurs, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('domaines_futurs') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($step === 'attentes')
                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Qu'attendez-vous principalement du réseau ? <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($attentesOptions as $option)
                            <x-ui.chip wire:click="toggle('attentes', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $attentes, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('attentes') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Quelles formations vous intéressent le plus ? <span class="font-normal text-ink/50">(plusieurs choix possibles)</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($formationsInteretOptions as $option)
                            <x-ui.chip wire:click="toggle('formations_interet', {{ \Illuminate\Support\Js::from($option) }})" :active="in_array($option, $formations_interet, true)">{{ $option }}</x-ui.chip>
                        @endforeach
                    </div>
                    @error('formations_interet') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Quel est aujourd'hui votre principal défi ?</p>
                    <div class="flex flex-col gap-2">
                        @foreach ($defis as $option)
                            <button type="button" wire:click="select('defi_principal', {{ \Illuminate\Support\Js::from($option) }})" class="w-full rounded-xl border px-4 py-3 text-left text-sm font-medium transition-colors {{ $defi_principal === $option ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:border-brand/40' }}">
                                {{ $option }}
                            </button>
                        @endforeach
                    </div>
                    @error('defi_principal') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-brand">Quel est votre revenu mensuel actuel approximatif ?</p>
                    <div class="flex flex-col gap-2">
                        @foreach ($revenus as $option)
                            <button type="button" wire:click="select('revenu_mensuel', {{ \Illuminate\Support\Js::from($option) }})" class="w-full rounded-xl border px-4 py-3 text-left text-sm font-medium transition-colors {{ $revenu_mensuel === $option ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:border-brand/40' }}">
                                {{ $option }}
                            </button>
                        @endforeach
                    </div>
                    @error('revenu_mensuel') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="mt-2 flex items-center justify-between gap-4">
                @if ($history !== [])
                    <button type="button" wire:click="back" class="inline-flex items-center gap-2 rounded-full border border-brand/15 px-6 py-3 font-semibold text-brand transition-colors hover:bg-brand/5">
                        Précédent
                    </button>
                @else
                    <span></span>
                @endif

                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-7 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                    <span wire:loading.remove>{{ $isLastStep ? 'Envoyer ma candidature' : 'Suivant' }}</span>
                    <span wire:loading>Envoi…</span>
                </button>
            </div>
        </form>
    </div>
@endif
</div>
