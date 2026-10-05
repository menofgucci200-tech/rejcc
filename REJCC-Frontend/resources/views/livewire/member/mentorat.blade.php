<div>
    <x-member-light.topbar title="Mentorat" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $estMentor ? 'Mon espace mentor' : 'Mentorat' }}</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">
                @if ($estMentor)
                    Votre fiche de mentor est visible par les membres dans l'annuaire, sur votre page biographique et dans la recherche de mentors. Tenez-la à jour pour recevoir des demandes adaptées.
                @else
                    Un accompagnement personnalisé par des entrepreneurs et experts confirmés du réseau.
                @endif
            </p>
        </div>

        @if ($message)
            <p data-test="message-mentorat" class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        @if ($estMentor)
            @php
                $demandes = $mentorats->where('je_suis', 'mentor')->where('statut', 'en_attente');
                $suivis = $mentorats->where('je_suis', 'mentor')->where('statut', 'accepte');
                $historique = $mentorats->where('je_suis', 'mentor')->whereIn('statut', ['refuse', 'termine']);
            @endphp

            <div class="mb-6 grid gap-3 sm:grid-cols-3">
                @foreach ([['Demandes en attente', $demandes->count(), 'bell'], ['Mentorés suivis', $suivis->count(), 'users'], ['Places restantes', $placesRestantes ?? 0, 'user-plus']] as [$label, $val, $icon])
                    <div class="flex items-center gap-3 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-accent/10 text-accent"><x-ui.icon :name="$icon" class="size-5" /></span>
                        <div><p class="text-[20px] font-extrabold leading-none text-brand">{{ $val }}</p><p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $label }}</p></div>
                    </div>
                @endforeach
            </div>

            <section data-test="demandes-recues" class="mb-8">
                <h2 class="mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Demandes reçues</h2>
                @forelse ($demandes as $r)
                    <article data-test="demande" wire:key="demande-{{ $r['id'] }}" class="mb-3 rounded-[16px] border border-[#F5A623]/30 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="flex items-start gap-3">
                            <x-mentorat.avatar :personne="$r['autre']" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-[13.5px] font-bold text-brand">{{ $r['autre']['prenom'] }} {{ $r['autre']['nom'] }}</p>
                                    <span class="text-[11.5px] text-[#9AA6B8]">{{ ucfirst(\Carbon\Carbon::parse($r['cree_le'])->diffForHumans()) }}</span>
                                </div>
                                <p class="text-[12px] text-[#5B677A]">{{ collect([$r['autre']['titre'] ?? null, $r['autre']['secteur'] ?? null, $r['autre']['ville'] ?? null])->filter()->join(' · ') }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[13px] text-ink"><span class="font-semibold text-brand">Objectif :</span> {{ $r['objectif'] }}</p>
                        @if ($r['besoin'])
                            <p class="mt-1.5 whitespace-pre-line text-[12.5px] leading-relaxed text-[#5B677A]">{{ $r['besoin'] }}</p>
                        @endif
                        <textarea wire:model="reponses.{{ $r['id'] }}" rows="2" maxlength="1000" data-test="reponse-{{ $r['id'] }}" placeholder="Votre message : mot d'accueil (facultatif) ou explication si vous déclinez" class="mt-3 w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                        @if ($erreurDemande && $erreurPour === $r['id'])
                            <p data-test="erreur-reponse" role="alert" class="mt-2 flex items-start gap-1.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> {{ $erreurDemande }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button wire:click="accepter({{ $r['id'] }})" wire:loading.attr="disabled" data-test="accepter" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#1C8F4C] px-4 py-2 text-[12px] font-bold text-white hover:bg-[#1C8F4C]/90 disabled:opacity-60"><x-ui.icon name="check" class="size-3.5" /> Accepter</button>
                            <button wire:click="refuser({{ $r['id'] }})" wire:loading.attr="disabled" data-test="refuser" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-[12px] font-bold text-brand hover:bg-cloud disabled:opacity-60">Décliner</button>
                        </div>
                    </article>
                @empty
                    <p class="rounded-[16px] border border-brand/10 bg-white py-6 text-center text-[13px] text-[#5B677A]">Aucune demande en attente.</p>
                @endforelse
            </section>

            @if ($suivis->isNotEmpty())
                <section data-test="mes-mentores" class="mb-8">
                    <h2 class="mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Mes mentorés</h2>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($suivis as $r)
                            <article data-test="mentore" class="rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                                <div class="flex items-start gap-3">
                                    <x-mentorat.avatar :personne="$r['autre']" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="truncate text-[13.5px] font-bold text-brand">{{ $r['autre']['prenom'] }} {{ $r['autre']['nom'] }}</p>
                                            <x-mentorat.statut :statut="$r['statut']" :label="$r['statut_label']" />
                                        </div>
                                        <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">Depuis le {{ \Carbon\Carbon::parse($r['repondu_le'])->translatedFormat('j F Y') }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-[12.5px] text-ink"><span class="font-semibold text-brand">Objectif :</span> {{ $r['objectif'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('espace-membre.mentorat.suivi', $r['id']) }}" wire:navigate data-test="ouvrir-suivi" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="calendar" class="size-3.5" /> Suivi &amp; séances</a>
                                    <a href="{{ route('espace-membre.messaging', ['to' => $r['autre']['id']]) }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="message-circle" class="size-3.5" /> Écrire</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($historique->isNotEmpty())
                <details class="mb-8 rounded-[16px] border border-brand/10 bg-white p-4">
                    <summary class="cursor-pointer text-[12.5px] font-bold text-brand">Historique ({{ $historique->count() }})</summary>
                    <ul class="mt-3 flex flex-col gap-2">
                        @foreach ($historique as $r)
                            <li class="flex flex-wrap items-center justify-between gap-2 text-[12.5px] text-[#5B677A]">
                                <span><span class="font-semibold text-brand">{{ $r['autre']['prenom'] }} {{ $r['autre']['nom'] }}</span> — {{ $r['objectif'] }}</span>
                                <x-mentorat.statut :statut="$r['statut']" :label="$r['statut_label']" />
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

            <div class="grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">
                <section data-test="fiche-mentor-form" class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <h2 class="text-[15px] font-bold text-brand">Ma fiche de mentor</h2>
                    <div class="mt-4 flex flex-col gap-4">
                        <div>
                            <label for="mentor-expertises" class="mb-1 block text-xs font-semibold text-[#5B677A]">Domaines d'expertise <span class="font-normal text-[#9AA6B8]">(séparés par des virgules, 8 au plus)</span></label>
                            <input id="mentor-expertises" wire:model="expertises" type="text" placeholder="Ex : Finance, Levée de fonds, Agro-business" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                        </div>
                        <div>
                            <label for="mentor-bio" class="mb-1 block text-xs font-semibold text-[#5B677A]">Présentation de mentor</label>
                            <textarea id="mentor-bio" wire:model="bio" rows="4" placeholder="Votre expérience, ce que vous pouvez apporter à un jeune entrepreneur…" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="mentor-dispo" class="mb-1 block text-xs font-semibold text-[#5B677A]">Disponibilités</label>
                                <input id="mentor-dispo" wire:model="disponibilites" type="text" placeholder="Ex : mardi et jeudi soir" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                            </div>
                            <div>
                                <label for="mentor-format" class="mb-1 block text-xs font-semibold text-[#5B677A]">Format des séances</label>
                                <select id="mentor-format" wire:model="format" class="w-full rounded-[10px] border border-brand/15 py-2.5 pl-3 pr-9 text-sm outline-none focus:border-azure">
                                    <option value="">Non précisé</option>
                                    <option value="visio">En visio</option>
                                    <option value="presentiel">En présentiel</option>
                                    <option value="les_deux">Visio ou présentiel</option>
                                </select>
                            </div>
                            <div>
                                <label for="mentor-capacite" class="mb-1 block text-xs font-semibold text-[#5B677A]">Mentorés suivis en même temps (au plus)</label>
                                <input id="mentor-capacite" wire:model="capacite" type="number" min="1" max="20" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                            </div>
                            <label class="flex items-center gap-2 self-end pb-2.5 text-[13px] font-semibold text-ink">
                                <input wire:model="accepte" type="checkbox" class="size-4 rounded border-brand/20 text-accent" /> J'accepte de nouveaux mentorés
                            </label>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button wire:click="enregistrerProfil" wire:loading.attr="disabled" data-test="enregistrer-fiche" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Enregistrer ma fiche</button>
                            @if ($messageProfil)
                                <span data-test="message-fiche" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> {{ $messageProfil }}</span>
                            @endif
                            @if ($erreurProfil)
                                <span data-test="erreur-fiche" role="alert" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="size-4" /> {{ $erreurProfil }}</span>
                            @endif
                        </div>
                    </div>
                </section>

                <aside class="flex flex-col gap-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Aperçu vu par les membres</p>
                    <x-mentorat.profil :mentor="$user->mentor ?? null" class="bg-white" />
                    <a href="{{ route('espace-membre.carte') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-accent hover:underline">
                        <x-ui.icon name="qr-code" class="size-3.5" /> Voir ma carte de mentor
                    </a>
                </aside>
            </div>
        @else

            {{-- Mes demandes et mentorats --}}
            @if ($mentorats->isNotEmpty())
                <section data-test="mes-mentorats" class="mb-8">
                    <h2 class="mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Mes demandes et mentorats</h2>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($mentorats as $r)
                            <article data-test="relation" class="rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                                <div class="flex items-start gap-3">
                                    <x-mentorat.avatar :personne="$r['autre']" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="truncate text-[13.5px] font-bold text-brand">{{ $r['autre']['prenom'] }} {{ $r['autre']['nom'] }}</p>
                                            <x-mentorat.statut :statut="$r['statut']" :label="$r['statut_label']" />
                                        </div>
                                        <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">Demande du {{ \Carbon\Carbon::parse($r['cree_le'])->translatedFormat('j F Y') }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-[12.5px] text-ink"><span class="font-semibold text-brand">Objectif :</span> {{ $r['objectif'] }}</p>
                                @if ($r['reponse'])
                                    <p class="mt-2 rounded-[10px] bg-cloud/70 px-3 py-2 text-[12.5px] italic text-[#5B677A]">« {{ $r['reponse'] }} »</p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($r['statut'] === 'en_attente')
                                        <button wire:click="annuler({{ $r['id'] }})" wire:confirm="Retirer votre demande de mentorat ?" class="btn-tap rounded-full border border-brand/15 px-3.5 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">Retirer ma demande</button>
                                    @endif
                                    @if (in_array($r['statut'], ['accepte', 'termine'], true))
                                        <a href="{{ route('espace-membre.mentorat.suivi', $r['id']) }}" wire:navigate data-test="ouvrir-suivi" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="calendar" class="size-3.5" /> Suivi &amp; séances</a>
                                    @endif
                                    @if ($r['statut'] === 'accepte')
                                        <a href="{{ route('espace-membre.messaging', ['to' => $r['autre']['id']]) }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="message-circle" class="size-3.5" /> Écrire à mon mentor</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Trouver un mentor --}}
            <section data-test="trouver-mentor">
                <h2 class="mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Trouver un mentor</h2>
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div class="relative w-full max-w-[380px]">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[15px] -translate-y-1/2 text-[#9AA6B8]" />
                        <input wire:model.live.debounce.300ms="recherche" type="search" data-test="recherche-mentor" placeholder="Nom, expertise, secteur, ville…" class="w-full rounded-xl border border-brand/10 bg-white py-2.5 pl-10 pr-4 text-[13.5px] text-ink outline-none focus:border-azure" />
                    </div>
                </div>
                @if ($domaines->isNotEmpty())
                    <div class="mb-5 flex flex-wrap gap-2">
                        @foreach ($domaines as $e)
                            <button wire:click="filtrerExpertise(@js($e))" class="btn-tap rounded-full border px-3 py-1.5 text-[12px] font-semibold transition-colors {{ mb_strtolower($expertise) === mb_strtolower($e) ? 'border-accent bg-accent text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-accent/30' }}">{{ $e }}</button>
                        @endforeach
                    </div>
                @endif

                @if ($mentors->isEmpty())
                    <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">
                        {{ trim($recherche) !== '' || $expertise !== '' ? 'Aucun mentor ne correspond à votre recherche.' : 'Aucun mentor n\'est encore inscrit : revenez bientôt !' }}
                    </p>
                @else
                    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
                        @foreach ($mentors as $m)
                            <article data-test="carte-mentor" wire:key="mentor-{{ $m['id'] }}" class="card-hover flex flex-col rounded-[16px] border border-accent/20 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                                <div class="flex items-start gap-3">
                                    <x-mentorat.avatar :personne="$m" size="size-12" />
                                    <div class="min-w-0">
                                        <p class="truncate text-[14px] font-bold text-brand">{{ $m['prenom'] }} {{ $m['nom'] }}</p>
                                        <p class="line-clamp-2 text-[12px] text-[#5B677A]">{{ $m['titre'] ?: $m['secteur'] }}</p>
                                    </div>
                                </div>
                                @if (! empty($m['mentor']['expertises']))
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach (array_slice($m['mentor']['expertises'], 0, 4) as $e)
                                            <span class="rounded-full bg-accent/[.06] px-2.5 py-1 text-[11px] font-semibold text-accent">{{ $e }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <p class="mt-3 flex flex-1 flex-wrap gap-x-3 gap-y-1 text-[11.5px] text-[#5B677A]">
                                    @if ($m['mentor']['format_label'])<span class="inline-flex items-center gap-1"><x-ui.icon name="video" class="size-3.5" /> {{ $m['mentor']['format_label'] }}</span>@endif
                                    @if ($m['ville'])<span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3.5" /> {{ $m['ville'] }}</span>@endif
                                </p>
                                <div class="mt-4 flex items-center justify-between gap-2">
                                    <span class="text-[11.5px] font-semibold {{ $m['disponible'] ? 'text-[#1C8F4C]' : 'text-[#9AA6B8]' }}">
                                        {{ $m['disponible'] ? $m['places_restantes'].' place'.($m['places_restantes'] > 1 ? 's' : '').' disponible'.($m['places_restantes'] > 1 ? 's' : '') : 'Complet pour le moment' }}
                                    </span>
                                    @php $rel = $m['ma_relation']; @endphp
                                    @if ($rel && $rel['statut'] === 'accepte')
                                        <span class="rounded-full bg-[#22A85A]/10 px-3 py-1.5 text-[11.5px] font-bold text-[#1C8F4C]">Votre mentor</span>
                                    @elseif ($rel)
                                        <span class="rounded-full bg-[#F5A623]/15 px-3 py-1.5 text-[11.5px] font-bold text-[#8A5A00]">Demande envoyée</span>
                                    @else
                                        <button wire:click="ouvrirMentor({{ $m['id'] }})" data-test="voir-mentor" class="btn-tap rounded-full px-4 py-2 text-[12px] font-bold {{ $m['disponible'] ? 'bg-accent text-white hover:bg-accent-600' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $m['disponible'] ? 'Demander un mentorat' : 'Voir la fiche' }}</button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Fiche du mentor + formulaire de demande --}}
            @if ($mentorFiche)
                <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerMentor" @keydown.escape.window="$wire.fermerMentor()">
                    <div data-test="fiche-demande" class="max-h-[90vh] w-full max-w-[580px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-mentorat.avatar :personne="$mentorFiche" size="size-14" texte="text-lg" />
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-bold text-brand">{{ $mentorFiche['prenom'] }} {{ $mentorFiche['nom'] }}</p>
                                    <p class="text-[12px] text-[#5B677A]">{{ collect([$mentorFiche['titre'], $mentorFiche['organisation']])->filter()->join(' · ') }}</p>
                                </div>
                            </div>
                            <button type="button" wire:click="fermerMentor" aria-label="Fermer" class="icon-btn shrink-0 rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                        </div>
                        <x-mentorat.profil :mentor="$mentorFiche['mentor']" />

                        @if ($mentorFiche['disponible'])
                            <div class="mt-5 border-t border-cloud-200 pt-5">
                                <p class="mb-3 text-[14px] font-bold text-brand">Demander un mentorat</p>
                                @if (! $peutDemander)
                                    <p data-test="demande-abonnes" class="rounded-[12px] bg-[#FFF8EC] px-4 py-3 text-[12.5px] text-[#8A5A00]">Le mentorat est réservé aux membres à jour de leur abonnement annuel. <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="font-bold underline">Activer mon abonnement</a></p>
                                @else
                                    <label for="demande-objectif" class="mb-1 block text-xs font-semibold text-[#5B677A]">Votre objectif</label>
                                    <input id="demande-objectif" wire:model="objectif" type="text" maxlength="200" placeholder="Ex : structurer les finances de mon atelier" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure" />
                                    <label for="demande-besoin" class="mb-1 mt-3 block text-xs font-semibold text-[#5B677A]">Votre situation et vos besoins <span class="font-normal text-[#9AA6B8]">(facultatif)</span></label>
                                    <textarea id="demande-besoin" wire:model="besoin" rows="4" maxlength="2000" placeholder="Où en est votre projet ? Sur quoi aimeriez-vous être accompagné ?" class="w-full rounded-[10px] border border-brand/15 px-3 py-2.5 text-sm outline-none focus:border-azure"></textarea>
                                    @if ($erreurDemande)
                                        <p data-test="erreur-demande" role="alert" class="mt-3 flex items-start gap-2 rounded-[12px] bg-accent/5 px-3.5 py-2.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> <span>{{ $erreurDemande }} @if ($abonnementRequis)<a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="underline">Voir mon abonnement</a>@endif</span></p>
                                    @endif
                                    <button wire:click="demander" wire:loading.attr="disabled" data-test="envoyer-demande" class="btn-tap mt-4 w-full rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Envoyer ma demande</button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
