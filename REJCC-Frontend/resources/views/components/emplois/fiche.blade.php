@props(['fiche', 'candidatures' => [], 'voirCandidatures' => false, 'postulerOuvert' => false, 'cvName' => '', 'info' => null])

{{-- Fiche complète d'une offre ; pour l'auteur : statut, retour de l'équipe,
     modifier, marquer pourvue, clôturer, prolonger. --}}
@if ($fiche)
    @php
        $o = $fiche;
        $tc = \App\Support\OffreStatus::type($o['type']);
        $sc = \App\Support\OffreStatus::statut($o['statut']);
        $couleur = $o['groupe']['couleur'] ?? '#031D59';
        $lienPartage = route('espace-membre.emplois', ['offre' => $o['id']]);
        $infos = array_filter([
            ['map-pin', $o['lieu'].($o['teletravail_label'] && $o['teletravail'] !== 'sur_site' ? ' · '.$o['teletravail_label'] : '')],
            $o['remuneration'] ? ['award', $o['remuneration']] : null,
            $o['debut'] ? ['calendar', 'Début : '.\Illuminate\Support\Carbon::parse($o['debut'])->locale('fr')->isoFormat('D MMMM YYYY')] : null,
            $o['duree'] ? ['clock', 'Durée : '.$o['duree']] : null,
            $o['deadline'] ? ['alert-circle', 'Candidature avant le '.\Illuminate\Support\Carbon::parse($o['deadline'])->locale('fr')->isoFormat('D MMMM YYYY')] : null,
        ]);
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFiche" x-on:keydown.escape.window="$wire.fermerFiche()">
        <div data-test="fiche-offre" role="dialog" aria-modal="true" class="panel-enter flex max-h-[92vh] w-full max-w-[760px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
            <div class="relative flex shrink-0 items-center gap-4 px-6 py-5 text-white" style="background: linear-gradient(135deg, {{ $couleur }}, #031D59)">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-ui.icon :name="$o['groupe']['icone'] ?? 'nav-briefcase'" class="size-6" /></span>
                <div class="min-w-0 pr-8">
                    <p class="text-[12px] font-semibold text-white/80">{{ $o['entreprise'] }}{{ $o['groupe'] ? ' · '.$o['groupe']['nom'] : '' }}</p>
                    <h2 data-test="fiche-offre-titre" class="text-[19px] font-extrabold leading-snug">{{ $o['title'] }}</h2>
                </div>
                <button type="button" wire:click="fermerFiche" aria-label="Fermer" class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white"><x-ui.icon name="x" class="size-4" /></button>
            </div>

            <div class="overflow-y-auto p-5 sm:p-6">
                <div class="mb-3 flex flex-wrap items-center gap-1.5">
                    @if ($o['mine'])
                        <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $sc }}1A; color: {{ $sc }}">{{ $o['statut_label'] }}</span>
                    @endif
                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $tc }}14; color: {{ $tc }}">{{ $o['type_label'] }}{{ $o['contrat_label'] ? ' · '.$o['contrat_label'] : '' }}</span>
                    @if ($o['publie_at']) <span class="text-[11.5px] text-[#9AA6B8]">Publiée {{ \App\Support\Texte::depuis($o['publie_at']) }}</span> @endif
                </div>

                {{-- Retour de l'équipe (auteur) --}}
                @if ($o['mine'])
                    @php
                        $bandeau = match ($o['statut']) {
                            'en_attente' => "Votre offre est en cours de vérification par l'équipe REJCC. Vous serez notifié(e) dès sa publication.",
                            'a_corriger' => "L'équipe demande une correction : ".($o['motif'] ?? '').' Modifiez l\'offre pour la renvoyer en validation.',
                            'refusee' => 'Offre non publiée. Motif : '.($o['motif'] ?? '—'),
                            'pourvue' => "Offre pourvue : elle n'est plus visible des membres.",
                            'cloturee' => "Offre clôturée : elle n'est plus visible des membres.",
                            'expiree' => "Offre expirée : prolongez-la pour la remettre en ligne.",
                            default => 'En ligne jusqu\'au '.\Illuminate\Support\Carbon::parse($o['expire_le'])->locale('fr')->isoFormat('D MMMM YYYY').' · '.($o['vues'] ?? 0).' vue'.(($o['vues'] ?? 0) > 1 ? 's' : '').'.',
                        };
                    @endphp
                    <p data-test="fiche-offre-statut" class="mb-4 rounded-[12px] px-4 py-3 text-[12.5px] font-semibold" style="background: {{ $sc }}12; color: {{ $sc }}">{{ $bandeau }}</p>
                @endif

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($infos as [$icone, $texte])
                        <p class="flex items-center gap-2 rounded-[10px] bg-cloud/70 px-3 py-2 text-[12.5px] font-semibold text-brand"><x-ui.icon :name="$icone" class="size-4 shrink-0 text-azure" /> {{ $texte }}</p>
                    @endforeach
                </div>

                <div class="mt-5 whitespace-pre-line text-[13.5px] leading-relaxed text-ink">{!! \App\Support\Texte::liens($o['description']) !!}</div>
                @foreach (['missions' => 'Missions', 'profil' => 'Profil recherché'] as $k => $t)
                    @if ($o[$k] ?? null)
                        <section class="mt-5">
                            <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">{{ $t }}</p>
                            <p class="whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $o[$k] }}</p>
                        </section>
                    @endif
                @endforeach
                @if (! empty($o['competences']))
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($o['competences'] as $c) <span class="rounded-full bg-brand/[.06] px-3 py-1 text-[12px] font-semibold text-brand">{{ $c }}</span> @endforeach
                    </div>
                @endif
                <div class="mt-4 flex flex-wrap gap-3 text-[12.5px] font-semibold">
                    @if ($o['site_url']) <a href="{{ $o['site_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-azure hover:underline"><x-ui.icon name="globe" class="size-3.5" /> Site de l'entreprise</a> @endif
                    @if ($o['media_url']) <a href="{{ $o['media_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-azure hover:underline"><x-ui.icon name="file-text" class="size-3.5" /> Fiche de poste</a> @endif
                </div>

                @if ($o['auteur'] && ! $o['mine'])
                    <section class="mt-5 flex items-center gap-3 rounded-[14px] border border-brand/10 p-4">
                        <x-messagerie.avatar :personne="$o['auteur']" taille="size-10" texte="text-[12px]" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Offre partagée par</p>
                            <p class="text-[13.5px] font-bold text-brand">{{ $o['auteur']['prenom'] }} {{ $o['auteur']['nom'] }}</p>
                        </div>
                    </section>
                @endif

                {{-- Ma candidature (candidat) --}}
                @if (! $o['mine'] && ($o['ma_candidature'] ?? null) && $o['ma_candidature']['statut'] !== 'retiree')
                    @php $mc = $o['ma_candidature']; $mcc = ['recue' => '#4F6FBF', 'preselection' => '#B27007', 'retenue' => '#1C8F4C', 'non_retenue' => '#9AA6B8'][$mc['statut']] ?? '#4F6FBF'; @endphp
                    <div data-test="ma-candidature" class="mt-5 flex flex-wrap items-center gap-3 rounded-[14px] p-4" style="background: {{ $mcc }}12">
                        <x-ui.icon name="check-circle" class="size-5 shrink-0" style="color: {{ $mcc }}" />
                        <p class="min-w-0 flex-1 text-[13px] font-semibold" style="color: {{ $mcc }}">Vous avez postulé {{ \App\Support\Texte::depuis($mc['date']) }} · Candidature {{ mb_strtolower($mc['statut_label']) }}</p>
                        @if ($o['auteur'])
                            <a href="{{ route('espace-membre.messaging', ['to' => $o['auteur']['id'], 'offre' => $o['id']]) }}" wire:navigate class="text-[12px] font-bold text-brand hover:underline">Écrire au recruteur</a>
                        @endif
                        @if (in_array($mc['statut'], ['recue', 'preselection'], true))
                            <button type="button" wire:click="retirerCandidature" wire:confirm="Retirer votre candidature ?" class="text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Retirer</button>
                        @endif
                    </div>
                @elseif (! $o['mine'] && $o['statut'] === 'publiee')
                    {{-- Postuler sur la plateforme --}}
                    @if ($postulerOuvert)
                        <div wire:key="form-postuler" data-test="form-postuler" class="panel-enter mt-5 rounded-[14px] border border-brand/10 bg-cloud/40 p-4">
                            <p class="text-[13.5px] font-bold text-brand">Postuler à cette offre</p>
                            <p class="mb-2 text-[11.5px] text-[#5B677A]">Le recruteur verra votre message, votre CV et votre profil REJCC (nom, titre, ville, coordonnées).</p>
                            <textarea wire:model="messageCandidature" rows="4" maxlength="3000" data-test="message-candidature" placeholder="Présentez-vous : votre parcours, ce qui vous motive pour ce poste, vos disponibilités." class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if ($cvName)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-[12px] font-semibold text-brand"><x-ui.icon name="file-text" class="size-3.5 text-azure" /> {{ $cvName }}
                                        <button type="button" wire:click="retirerCv" class="text-[#9AA6B8] hover:text-accent" aria-label="Retirer le CV"><x-ui.icon name="x" class="size-3" /></button></span>
                                @else
                                    <label class="btn-tap inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">
                                        <x-ui.icon name="download" class="size-3.5 rotate-180" /> Joindre mon CV (PDF, Word)
                                        <input type="file" wire:model="cvFile" accept=".pdf,.doc,.docx" data-test="cv-candidature" class="hidden" />
                                    </label>
                                    <span wire:loading wire:target="cvFile" class="text-[11.5px] font-semibold text-azure">Envoi du CV…</span>
                                @endif
                                @error('cvFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                            </div>
                            <button type="button" wire:click="postuler" wire:loading.attr="disabled" wire:target="postuler,cvFile" data-test="envoyer-candidature" class="btn-tap mt-3 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Envoyer ma candidature</button>
                        </div>
                    @endif
                @endif

                {{-- Candidatures reçues (auteur) --}}
                @if ($o['mine'] && $voirCandidatures)
                    <section wire:key="candidatures" data-test="liste-candidatures" class="panel-enter mt-5">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Candidatures reçues</p>
                        @if (empty($candidatures))
                            <p class="rounded-[12px] bg-cloud/60 px-4 py-4 text-center text-[12.5px] text-[#5B677A]">Aucune candidature pour le moment.</p>
                        @else
                            <input wire:model="motCandidat" type="text" maxlength="500" placeholder="Message joint à votre prochaine décision (optionnel)" class="mb-3 w-full rounded-[9px] border border-brand/15 px-3 py-2 text-[12.5px] outline-none focus:border-azure" />
                            <div class="space-y-3">
                                @foreach ($candidatures as $c)
                                    @php $k = $c['candidat']; $cc = ['recue' => '#4F6FBF', 'preselection' => '#B27007', 'retenue' => '#1C8F4C', 'non_retenue' => '#9AA6B8'][$c['statut']] ?? '#4F6FBF'; @endphp
                                    <div wire:key="cand-{{ $c['id'] }}" data-test="candidature" class="rounded-[14px] border border-brand/10 p-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <x-messagerie.avatar :personne="$k" taille="size-10" texte="text-[12px]" />
                                            <div class="min-w-0 flex-1">
                                                <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-bold text-brand">{{ $k['prenom'] }} {{ $k['nom'] }}
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: {{ $cc }}1A; color: {{ $cc }}">{{ $c['statut_label'] }}</span>
                                                    @if ($c['nouvelle'])<span class="rounded-full bg-accent px-1.5 text-[9.5px] font-bold leading-4 text-white">Nouvelle</span>@endif
                                                </p>
                                                <p class="text-[11.5px] text-[#5B677A]">{{ collect([$k['titre'], $k['ville'], 'postulé '.\App\Support\Texte::depuis($c['date'])])->filter()->join(' · ') }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-2.5 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $c['message'] }}</p>
                                        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[12px] font-semibold">
                                            @if ($c['cv_url'])<a href="{{ $c['cv_url'] }}" target="_blank" rel="noopener" data-test="cv-candidat" class="inline-flex items-center gap-1 text-azure hover:underline"><x-ui.icon name="file-text" class="size-3.5" /> CV : {{ $c['cv_name'] ?: 'ouvrir' }}</a>@endif
                                            <a href="{{ url('/carte/'.$k['code']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-azure hover:underline"><x-ui.icon name="external-link" class="size-3.5" /> Profil REJCC</a>
                                            @if ($k['email'])<span class="text-[#5B677A]">{{ $k['email'] }}</span>@endif
                                            @if ($k['telephone'])<span class="text-[#5B677A]">{{ $k['telephone'] }}</span>@endif
                                        </div>
                                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                            <a href="{{ route('espace-membre.messaging', ['to' => $k['id'], 'offre' => $o['id']]) }}" wire:navigate class="btn-tap inline-flex items-center gap-1 rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="message-circle" class="size-3.5" /> Écrire</a>
                                            @if ($c['statut'] === 'recue')
                                                <button type="button" wire:click="statutCandidature({{ $c['id'] }}, 'preselection')" data-test="preselectionner" class="btn-tap rounded-full border border-[#B27007]/40 px-3 py-1.5 text-[11.5px] font-bold text-[#B27007]">Présélectionner</button>
                                            @endif
                                            @if (in_array($c['statut'], ['recue', 'preselection'], true))
                                                <button type="button" wire:click="statutCandidature({{ $c['id'] }}, 'retenue')" data-test="retenir" class="btn-tap rounded-full bg-[#1C8F4C] px-3 py-1.5 text-[11.5px] font-bold text-white">Retenir</button>
                                                <button type="button" wire:click="statutCandidature({{ $c['id'] }}, 'non_retenue')" wire:confirm="Indiquer à {{ $k['prenom'] }} que sa candidature n'est pas retenue ?" class="btn-tap rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-[#5B677A]">Non retenue</button>
                                            @endif
                                        </div>
                                        <div class="mt-2 flex gap-2">
                                            <input wire:model="notes.{{ $c['id'] }}" type="text" placeholder="Note privée (visible de vous seul)" class="min-w-0 flex-1 rounded-[9px] border border-brand/10 bg-cloud/40 px-3 py-1.5 text-[12px] outline-none focus:border-azure" />
                                            <button type="button" wire:click="enregistrerNote({{ $c['id'] }})" class="shrink-0 text-[11.5px] font-semibold text-azure hover:underline">Enregistrer</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                @if ($info)<p data-test="fiche-offre-info" class="mt-4 text-[12.5px] font-semibold text-azure">{{ $info }}</p>@endif

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if (! $o['mine'] && $o['statut'] === 'publiee' && ! (($o['ma_candidature'] ?? null) && $o['ma_candidature']['statut'] !== 'retiree') && ! $postulerOuvert)
                        <button type="button" wire:click="ouvrirPostuler" data-test="postuler" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="send" class="size-4" /> Postuler</button>
                    @endif
                    @if ($o['mine'] && ($o['nb_candidatures'] ?? 0) > 0)
                        <button type="button" wire:click="basculerCandidatures" data-test="voir-candidatures" class="btn-tap inline-flex items-center gap-2 rounded-full border px-4 py-2.5 text-[13px] font-bold {{ $voirCandidatures ? 'border-brand bg-brand text-white' : 'border-brand/15 text-brand hover:bg-cloud' }}">
                            <x-ui.icon name="users" class="size-4" /> {{ $o['nb_candidatures'] }} candidature{{ $o['nb_candidatures'] > 1 ? 's' : '' }}
                            @if (($o['nb_nouvelles'] ?? 0) > 0)<span class="rounded-full bg-accent px-1.5 text-[10px] leading-4 text-white">{{ $o['nb_nouvelles'] }} nouvelle{{ $o['nb_nouvelles'] > 1 ? 's' : '' }}</span>@endif
                        </button>
                    @endif
                    @if ($o['mine'])
                        @if (! in_array($o['statut'], ['pourvue', 'cloturee'], true))
                            <button type="button" wire:click="openEdit({{ $o['id'] }})" data-test="modifier-offre" class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="pencil" class="size-4" /> {{ in_array($o['statut'], ['a_corriger', 'refusee'], true) ? 'Corriger et renvoyer' : 'Modifier' }}</button>
                        @endif
                        @if ($o['statut'] === 'publiee')
                            <button type="button" wire:click="changerStatut({{ $o['id'] }}, 'pourvue')" data-test="offre-pourvue" class="btn-tap rounded-full border border-[#7C3AED]/30 px-4 py-2.5 text-[13px] font-bold text-[#7C3AED] hover:bg-[#7C3AED]/5">Poste pourvu</button>
                            <button type="button" wire:click="prolonger({{ $o['id'] }})" class="btn-tap rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Prolonger de 30 jours</button>
                            <button type="button" wire:click="changerStatut({{ $o['id'] }}, 'cloturee')" wire:confirm="Clôturer cette offre ? Elle ne sera plus visible." class="text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Clôturer</button>
                        @elseif ($o['statut'] === 'expiree')
                            <button type="button" wire:click="prolonger({{ $o['id'] }})" data-test="prolonger-offre" class="btn-tap rounded-full bg-[#1C8F4C] px-4 py-2.5 text-[13px] font-bold text-white">Remettre en ligne 30 jours</button>
                        @elseif (in_array($o['statut'], ['pourvue', 'cloturee'], true))
                            <button type="button" wire:click="changerStatut({{ $o['id'] }}, 'publiee')" class="btn-tap rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Rouvrir l'offre</button>
                        @endif
                        <button type="button" wire:click="supprimer({{ $o['id'] }})" wire:confirm="Supprimer définitivement cette offre ?" class="ml-auto text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Supprimer</button>
                    @endif
                    @if (! $o['mine'] && $o['statut'] === 'publiee')
                        <button type="button" wire:click="basculerFavori({{ $o['id'] }})" data-test="favori-offre" class="btn-tap inline-flex items-center gap-2 rounded-full border px-4 py-2.5 text-[13px] font-bold {{ ($o['favori'] ?? false) ? 'border-accent/30 bg-accent/5 text-accent' : 'border-brand/15 text-brand hover:bg-cloud' }}">{{ ($o['favori'] ?? false) ? '♥ Sauvegardée' : '♡ Sauvegarder' }}</button>
                    @endif
                    @if ($o['statut'] === 'publiee')
                        <button type="button" x-data="{ copie: false }"
                            x-on:click="navigator.clipboard?.writeText(@js($lienPartage)); copie = true; setTimeout(() => copie = false, 2000)"
                            class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">
                            <x-ui.icon name="external-link" class="size-4" /> <span x-text="copie ? 'Lien copié !' : 'Partager'">Partager</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
