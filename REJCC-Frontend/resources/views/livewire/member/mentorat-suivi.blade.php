<div>
    <x-member-light.topbar title="Suivi du mentorat" />

    <div class="mx-auto max-w-[920px] px-8 py-8">
        <a href="{{ route('espace-membre.mentorat') }}" wire:navigate class="mb-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
            <x-ui.icon name="arrow-left" class="size-3.5" /> Mentorat
        </a>

        @if (! $mentorat)
            <p class="mt-6 rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Mentorat introuvable.</p>
        @else
            @php
                $estMentor = $mentorat['je_suis'] === 'mentor';
                $autre = $mentorat['autre'];
                $enCours = $mentorat['statut'] === 'accepte';
                $quand = fn ($iso) => ucfirst(\Carbon\Carbon::parse($iso)->translatedFormat('l j F Y \à H\hi'));
            @endphp

            {{-- En-tête --}}
            <header data-test="suivi-entete" class="mb-6 rounded-[18px] border border-accent/15 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-mentorat.avatar :personne="$autre" size="size-14" texte="text-lg" />
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-accent">{{ $estMentor ? 'Votre mentoré·e' : 'Votre mentor' }}</p>
                            <p class="truncate text-[16px] font-bold text-brand">{{ $autre['prenom'] }} {{ $autre['nom'] }}</p>
                            <p class="text-[12px] text-[#5B677A]">{{ collect([$autre['titre'] ?? null, $autre['ville'] ?? null])->filter()->join(' · ') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-mentorat.statut :statut="$mentorat['statut']" :label="$mentorat['statut_label']" />
                        <a href="{{ route('espace-membre.messaging', ['to' => $autre['id']]) }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="message-circle" class="size-3.5" /> Messagerie</a>
                    </div>
                </div>
                <div class="mt-4 rounded-[12px] bg-cloud/60 px-4 py-3">
                    <p class="text-[13px] text-ink"><span class="font-semibold text-brand">Objectif :</span> {{ $mentorat['objectif'] }}</p>
                    @if ($mentorat['besoin'])
                        <p class="mt-1 whitespace-pre-line text-[12.5px] text-[#5B677A]">{{ $mentorat['besoin'] }}</p>
                    @endif
                    @if ($mentorat['debut'])
                        <p class="mt-1.5 text-[11.5px] text-[#9AA6B8]">Mentorat commencé le {{ \Carbon\Carbon::parse($mentorat['debut'])->translatedFormat('j F Y') }}</p>
                    @endif
                </div>
            </header>

            @if ($message)
                <p data-test="message-suivi" class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
            @endif
            @if ($erreur)
                <p data-test="erreur-suivi" role="alert" class="panel-enter mb-5 flex items-start gap-2 rounded-[14px] border border-accent/20 bg-accent/5 px-4 py-3 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> {{ $erreur }}</p>
            @endif

            {{-- Séances à venir --}}
            <section data-test="seances-a-venir" class="mb-8">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Séances à venir</h2>
                    @if ($enCours && ! $formSeance)
                        <button wire:click="ouvrirFormSeance(@js($mentorat['format_mentor'] ?? 'visio'))" data-test="proposer-seance" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-2 text-[12px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="plus" class="size-3.5" /> Proposer une séance</button>
                    @endif
                </div>

                @if ($formSeance)
                    <div data-test="form-seance" class="panel-enter mb-4 grid gap-3 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                        <div>
                            <label for="seance-debut" class="mb-1 block text-xs font-semibold text-[#5B677A]">Date et heure</label>
                            <input id="seance-debut" wire:model="debut" type="datetime-local" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="seance-duree" class="mb-1 block text-xs font-semibold text-[#5B677A]">Durée</label>
                                <select id="seance-duree" wire:model="duree" class="w-full rounded-[10px] border border-brand/15 py-2.5 pl-3 pr-9 text-sm outline-none focus:border-azure">
                                    @foreach ([30 => '30 min', 45 => '45 min', 60 => '1 h', 90 => '1 h 30', 120 => '2 h'] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="seance-format" class="mb-1 block text-xs font-semibold text-[#5B677A]">Format</label>
                                <select id="seance-format" wire:model.live="format" class="w-full rounded-[10px] border border-brand/15 py-2.5 pl-3 pr-9 text-sm outline-none focus:border-azure">
                                    <option value="visio">Visio</option>
                                    <option value="presentiel">Présentiel</option>
                                </select>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="seance-lieu" class="mb-1 block text-xs font-semibold text-[#5B677A]">{{ $format === 'visio' ? 'Lien de la visio' : 'Lieu du rendez-vous' }} <span class="font-normal text-[#9AA6B8]">(facultatif)</span></label>
                            <input id="seance-lieu" wire:model="lieu" type="text" maxlength="255" placeholder="{{ $format === 'visio' ? 'https://meet.google.com/…' : 'Adresse, quartier, café…' }}" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="seance-odj" class="mb-1 block text-xs font-semibold text-[#5B677A]">Ordre du jour <span class="font-normal text-[#9AA6B8]">(facultatif)</span></label>
                            <textarea id="seance-odj" wire:model="ordreDuJour" rows="2" maxlength="1000" placeholder="Ce que vous souhaitez aborder" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure"></textarea>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:col-span-2">
                            <button wire:click="proposer" wire:loading.attr="disabled" data-test="envoyer-seance" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Proposer ce créneau</button>
                            <button wire:click="$set('formSeance', false)" class="btn-tap rounded-full border border-brand/15 bg-white px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Annuler</button>
                        </div>
                    </div>
                @endif

                @forelse ($aVenir as $s)
                    <article data-test="seance" wire:key="seance-{{ $s['id'] }}" class="mb-3 rounded-[16px] border bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $s['statut'] === 'confirmee' ? 'border-[#22A85A]/30' : 'border-[#F5A623]/35' }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-[14px] font-bold text-brand">{{ $quand($s['debut']) }}</p>
                                <p class="mt-0.5 text-[12px] text-[#5B677A]">{{ $s['format'] === 'visio' ? 'En visio' : 'En présentiel' }} · {{ $s['duree_minutes'] }} min @if (! $s['proposee_par_moi']) · proposée par {{ $autre['prenom'] }} @endif</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $s['statut'] === 'confirmee' ? 'bg-[#22A85A]/10 text-[#1C8F4C]' : 'bg-[#F5A623]/15 text-[#8A5A00]' }}">{{ $s['statut'] === 'proposee' && $s['proposee_par_moi'] ? 'En attente de confirmation' : $s['statut_label'] }}</span>
                        </div>
                        @if ($s['lieu'])
                            <p class="mt-2 text-[12.5px] text-ink">
                                <x-ui.icon :name="$s['format'] === 'visio' ? 'video' : 'map-pin'" class="inline size-3.5 text-[#9AA6B8]" />
                                @if (str_starts_with($s['lieu'], 'http'))
                                    <a href="{{ $s['lieu'] }}" target="_blank" rel="noopener" class="font-semibold text-azure hover:underline">{{ $s['lieu'] }}</a>
                                @else
                                    {{ $s['lieu'] }}
                                @endif
                            </p>
                        @endif
                        @if ($s['ordre_du_jour'])
                            <p class="mt-1.5 whitespace-pre-line text-[12.5px] text-[#5B677A]"><span class="font-semibold text-brand">Ordre du jour :</span> {{ $s['ordre_du_jour'] }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($s['statut'] === 'proposee' && ! $s['proposee_par_moi'])
                                <button wire:click="confirmer({{ $s['id'] }})" data-test="confirmer-seance" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#1C8F4C] px-4 py-1.5 text-[12px] font-bold text-white hover:bg-[#1C8F4C]/90"><x-ui.icon name="check" class="size-3.5" /> Confirmer</button>
                            @endif
                            <button x-data @click="const motif = prompt('Motif de l\'annulation (facultatif) :'); if (motif !== null) $wire.annuler({{ $s['id'] }}, motif)" data-test="annuler-seance" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">{{ $s['statut'] === 'proposee' && ! $s['proposee_par_moi'] ? 'Décliner' : 'Annuler' }}</button>
                        </div>
                    </article>
                @empty
                    @unless ($formSeance)
                        <p class="rounded-[16px] border border-brand/10 bg-white py-6 text-center text-[13px] text-[#5B677A]">{{ $enCours ? 'Aucune séance prévue : proposez un créneau.' : 'Aucune séance à venir.' }}</p>
                    @endunless
                @endforelse
            </section>

            {{-- Séances passées et comptes rendus --}}
            @if ($passees->isNotEmpty())
                <section data-test="seances-passees" class="mb-8">
                    <h2 class="mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Séances passées et comptes rendus</h2>
                    @foreach ($passees as $s)
                        <article data-test="seance-passee" wire:key="passee-{{ $s['id'] }}" class="mb-3 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="text-[13.5px] font-bold text-brand">{{ $quand($s['debut']) }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $s['statut'] === 'realisee' ? 'bg-brand/10 text-brand' : 'bg-cloud text-[#5B677A]' }}">{{ $s['statut'] === 'realisee' ? 'Réalisée' : 'Compte rendu à rédiger' }}</span>
                            </div>
                            @if ($compteRenduPour === $s['id'])
                                <div class="mt-3">
                                    <label for="cr-{{ $s['id'] }}" class="mb-1 block text-xs font-semibold text-[#5B677A]">Compte rendu</label>
                                    <textarea id="cr-{{ $s['id'] }}" wire:model="compteRendu" rows="3" maxlength="3000" placeholder="Ce que vous avez abordé, les points forts, les difficultés…" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                                    <label for="etapes-{{ $s['id'] }}" class="mb-1 mt-2 block text-xs font-semibold text-[#5B677A]">Prochaines étapes pour votre mentoré·e</label>
                                    <textarea id="etapes-{{ $s['id'] }}" wire:model="prochainesEtapes" rows="2" maxlength="1500" placeholder="Actions à mener d'ici la prochaine séance" class="w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="enregistrerCompteRendu" wire:loading.attr="disabled" data-test="enregistrer-cr" class="btn-tap rounded-full bg-brand px-4 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Partager le compte rendu</button>
                                        <button wire:click="$set('compteRenduPour', null)" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Annuler</button>
                                    </div>
                                </div>
                            @elseif ($s['compte_rendu'])
                                <p class="mt-2 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $s['compte_rendu'] }}</p>
                                @if ($s['prochaines_etapes'])
                                    <div class="mt-2.5 rounded-[12px] bg-accent/[.05] px-3.5 py-2.5">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-accent">Prochaines étapes</p>
                                        <p class="mt-1 whitespace-pre-line text-[13px] text-ink">{{ $s['prochaines_etapes'] }}</p>
                                    </div>
                                @endif
                                @if ($estMentor)
                                    <button wire:click="ouvrirCompteRendu({{ $s['id'] }}, @js($s['compte_rendu']), @js($s['prochaines_etapes']))" class="mt-2 text-[12px] font-semibold text-azure hover:underline">Modifier le compte rendu</button>
                                @endif
                            @elseif ($estMentor)
                                <button wire:click="ouvrirCompteRendu({{ $s['id'] }})" data-test="rediger-cr" class="btn-tap mt-3 rounded-full bg-brand px-4 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90">Rédiger le compte rendu</button>
                            @else
                                <p class="mt-2 text-[12.5px] text-[#9AA6B8]">Le compte rendu de votre mentor apparaîtra ici.</p>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endif

            {{-- Clôture --}}
            @if ($mentorat['statut'] === 'termine')
                <section data-test="mentorat-termine" class="mb-8 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <h2 class="text-[15px] font-bold text-brand">Mentorat terminé</h2>
                    <p class="mt-0.5 text-[12px] text-[#9AA6B8]">Le {{ \Carbon\Carbon::parse($mentorat['termine_le'])->translatedFormat('j F Y') }}{{ $mentorat['termine_par_moi'] ? ', par vous' : '' }}</p>
                    @if ($mentorat['bilan'])
                        <p class="mt-3 whitespace-pre-line rounded-[12px] bg-cloud/60 px-4 py-3 text-[13px] text-ink"><span class="font-semibold text-brand">Bilan :</span> {{ $mentorat['bilan'] }}</p>
                    @endif

                    @if ($mentorat['peut_evaluer'])
                        <div data-test="form-avis" class="mt-4 border-t border-cloud-200 pt-4">
                            <p class="text-[13.5px] font-bold text-brand">Votre avis sur cet accompagnement</p>
                            <div class="mt-2 flex gap-1" role="radiogroup" aria-label="Note de 1 à 5 étoiles">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('note', {{ $i }})" role="radio" aria-checked="{{ $note === $i ? 'true' : 'false' }}" aria-label="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}" data-test="etoile-{{ $i }}" class="text-[26px] leading-none transition-transform hover:scale-110 {{ $note >= $i ? 'text-[#F5A623]' : 'text-[#D5DCE7]' }}">★</button>
                                @endfor
                            </div>
                            <textarea wire:model="avis" rows="3" maxlength="1500" placeholder="Ce que ce mentorat vous a apporté (facultatif, visible par votre mentor)" class="mt-3 w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                            <button wire:click="evaluer" wire:loading.attr="disabled" data-test="envoyer-avis" class="btn-tap mt-2 rounded-full bg-accent px-5 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Envoyer mon avis</button>
                        </div>
                    @elseif ($mentorat['note'])
                        <p data-test="avis-donne" class="mt-3 text-[13px] text-ink">
                            {{ $estMentor ? 'Avis de votre mentoré·e :' : 'Votre avis :' }}
                            <span class="text-[#F5A623]">{{ str_repeat('★', $mentorat['note']) }}</span><span class="text-[#D5DCE7]">{{ str_repeat('★', 5 - $mentorat['note']) }}</span>
                            @if ($mentorat['avis'])
                                <span class="mt-1 block italic text-[#5B677A]">« {{ $mentorat['avis'] }} »</span>
                            @endif
                        </p>
                    @endif
                </section>
            @elseif ($enCours)
                <section class="mb-8">
                    @if ($formFin)
                        <div data-test="form-fin" class="panel-enter rounded-[16px] border border-brand/10 bg-white p-5">
                            <p class="text-[13.5px] font-bold text-brand">Terminer le mentorat</p>
                            <p class="mt-0.5 text-[12px] text-[#5B677A]">Les séances encore prévues seront annulées.{{ $estMentor ? ' Votre mentoré·e pourra ensuite donner son avis.' : '' }}</p>
                            <textarea wire:model="bilan" rows="3" maxlength="2000" placeholder="Bilan : objectifs atteints, suite conseillée… (facultatif)" class="mt-3 w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                            <div class="mt-2 flex gap-2">
                                <button wire:click="terminer" wire:loading.attr="disabled" data-test="confirmer-fin" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Terminer le mentorat</button>
                                <button wire:click="$set('formFin', false)" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-[12px] font-bold text-brand hover:bg-cloud">Annuler</button>
                            </div>
                        </div>
                    @else
                        <button wire:click="$set('formFin', true)" data-test="terminer-mentorat" class="text-[12.5px] font-semibold text-[#9AA6B8] hover:text-brand hover:underline">Objectif atteint ? Terminer le mentorat</button>
                    @endif
                </section>
            @endif

            @if ($annulees->isNotEmpty())
                <details class="rounded-[16px] border border-brand/10 bg-white p-4">
                    <summary class="cursor-pointer text-[12.5px] font-bold text-brand">Séances annulées ou non confirmées ({{ $annulees->count() }})</summary>
                    <ul class="mt-3 flex flex-col gap-1.5 text-[12.5px] text-[#5B677A]">
                        @foreach ($annulees as $s)
                            <li>{{ $quand($s['debut']) }} — {{ $s['statut'] === 'annulee' ? 'annulée'.($s['motif_annulation'] ? ' : « '.$s['motif_annulation'].' »' : '') : 'non confirmée à temps' }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endif
    </div>
</div>
