@props(['fiche', 'besoinsLabels' => [], 'candidats' => [], 'info' => null, 'rejoindreOuvert' => false, 'recherche' => ''])

{{-- Fiche complète d'un projet : présentation, besoins, porteur ; pour le
     porteur : statut, retour de l'équipe, modifier / retirer. --}}
@if ($fiche)
    @php
        $p = $fiche;
        $couleur = $p['groupe']['couleur'] ?? '#031D59';
        $statutC = \App\Support\ProjectStatus::color($p['statut']);
        $stadeC = \App\Support\ProjectStatus::stade($p['stade']);
        $lienPartage = route('espace-membre.projets', ['projet' => $p['id']]);
        $relation = $p['relation'] ?? null;
        $equipe = in_array($relation, ['porteur', 'membre'], true);
        $sections = array_filter([
            'Le problème' => $p['probleme'] ?? null,
            'La solution' => $p['solution'] ?? null,
            'Pour qui ?' => $p['cible'] ?? null,
            'Impact attendu' => $p['impact'] ?? null,
        ]);
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFiche" x-on:keydown.escape.window="$wire.fermerFiche()">
        <div data-test="fiche-projet" role="dialog" aria-modal="true" class="panel-enter flex max-h-[92vh] w-full max-w-[760px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
            <div class="relative shrink-0">
                @if ($p['image'] ?? null)
                    <img src="{{ $p['image'] }}" alt="{{ $p['title'] }}" class="max-h-[260px] w-full bg-cloud object-cover">
                @else
                    <div class="flex h-24 items-center justify-center" style="background: linear-gradient(135deg, {{ $couleur }}, {{ $couleur }}B3)"><x-ui.icon :name="$p['groupe']['icone'] ?? 'nav-projects'" class="size-9 text-white/80" /></div>
                @endif
                <button type="button" wire:click="fermerFiche" aria-label="Fermer" class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white"><x-ui.icon name="x" class="size-4" /></button>
            </div>

            <div class="overflow-y-auto p-5 sm:p-6">
                <div class="mb-2 flex flex-wrap items-center gap-1.5">
                    @if ($p['mine'])
                        <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $statutC }}1A; color: {{ $statutC }}">{{ $p['statut_label'] }}</span>
                    @endif
                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $stadeC }}14; color: {{ $stadeC }}">{{ $p['stade_label'] }}</span>
                    @if ($p['groupe'] ?? null)
                        <a href="{{ route('espace-membre.groupes.membres', $p['groupe']['id']) }}" wire:navigate class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold hover:underline" style="color: {{ $couleur }}; background: {{ $couleur }}14"><x-ui.icon :name="$p['groupe']['icone']" class="size-3" /> {{ $p['groupe']['nom'] }}</a>
                    @endif
                    @if ($p['ville'] ?? null)
                        <span class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-[#9AA6B8]"><x-ui.icon name="map-pin" class="size-3" /> {{ $p['ville'] }}</span>
                    @endif
                </div>
                <h2 data-test="fiche-projet-titre" class="text-[20px] font-extrabold leading-snug text-brand">{{ $p['title'] }}</h2>
                @if ($p['accroche'] ?? null)
                    <p class="mt-1 text-[14px] font-semibold text-[#5B677A]">{{ $p['accroche'] }}</p>
                @endif

                {{-- Invitation reçue --}}
                @if ($relation === 'invite')
                    <div data-test="fiche-invitation" class="mt-4 rounded-[12px] border border-azure/25 bg-azure/[.06] p-4">
                        <p class="text-[13px] font-bold text-brand">{{ $p['porteur']['prenom'] ?? 'Le porteur' }} vous invite à rejoindre l'équipe{{ ($p['mon_role'] ?? null) ? ' comme '.$p['mon_role'] : '' }}.</p>
                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="rejoindre" data-test="accepter-invitation" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">Accepter</button>
                            <button type="button" wire:click="declinerInvitation" class="btn-tap rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Décliner</button>
                        </div>
                    </div>
                @elseif ($relation === 'demande')
                    <p class="mt-4 rounded-[12px] bg-cloud px-4 py-3 text-[12.5px] font-semibold text-[#5B677A]">Votre demande pour rejoindre l'équipe est envoyée : le porteur vous répondra.</p>
                @endif

                {{-- Retour de l'équipe (porteur uniquement) --}}
                @if ($p['mine'])
                    @php
                        $bandeau = match ($p['statut']) {
                            'evaluation' => "Votre projet est en cours d'examen par l'équipe REJCC. Vous serez notifié(e) de sa décision.",
                            'a_completer' => "L'équipe a besoin de précisions : ".($p['motif'] ?? '').' Modifiez votre projet pour le renvoyer en évaluation.',
                            'refuse' => "Projet non retenu. Motif : ".($p['motif'] ?? '—').' Vous pouvez le retravailler et le soumettre à nouveau.',
                            'retire' => "Vous avez retiré ce projet : il n'est plus visible des membres.",
                            default => 'Votre projet est visible par les membres du réseau.',
                        };
                    @endphp
                    <p data-test="fiche-projet-statut" class="mt-4 rounded-[12px] px-4 py-3 text-[12.5px] font-semibold" style="background: {{ $statutC }}12; color: {{ $statutC }}">{{ $bandeau }}</p>
                @endif

                <div class="mt-4 whitespace-pre-line text-[13.5px] leading-relaxed text-ink">{!! \App\Support\Texte::liens($p['description']) !!}</div>

                @if ($sections)
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($sections as $titre => $texte)
                            <div class="rounded-[12px] bg-cloud/60 p-3.5">
                                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ $titre }}</p>
                                <p class="whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $texte }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! empty($p['besoins']))
                    <section class="mt-5">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Le projet recherche</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($p['besoins'] as $b)
                                <span class="rounded-full bg-accent/[.08] px-3 py-1 text-[12px] font-bold text-accent">{{ $besoinsLabels[$b] ?? $b }}</span>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($p['lien'] ?? null)
                    <a href="{{ $p['lien'] }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-semibold text-azure hover:underline"><x-ui.icon name="external-link" class="size-3.5" /> {{ parse_url($p['lien'], PHP_URL_HOST) ?: $p['lien'] }}</a>
                @endif

                {{-- Équipe --}}
                <section class="mt-5 rounded-[14px] border border-brand/10 p-4">
                    <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">L'équipe · {{ $p['equipe_taille'] ?? 1 }} membre{{ ($p['equipe_taille'] ?? 1) > 1 ? 's' : '' }} sur la plateforme</p>
                    <div class="space-y-2.5">
                        @php $personnes = collect([['role' => 'Porteur du projet', 'membre' => $p['porteur'] ?? null, 'id' => null]])->merge($p['equipe'] ?? [])->filter(fn ($x) => $x['membre']); @endphp
                        @foreach ($personnes as $x)
                            <div wire:key="eq-{{ $x['membre']['id'] }}" class="flex items-center gap-3">
                                <x-messagerie.avatar :personne="$x['membre']" taille="size-10" texte="text-[12px]" />
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-bold text-brand">{{ $x['membre']['prenom'] }} {{ $x['membre']['nom'] }}
                                        @if (($x['membre']['role'] ?? '') === 'mentor')<span class="rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Mentor</span>@endif
                                    </p>
                                    <p class="truncate text-[11.5px] text-[#5B677A]">{{ $x['role'] ?: "Membre de l'équipe" }}</p>
                                </div>
                                @if ($x['id'] && $relation === 'porteur')
                                    <button type="button" wire:click="retirerLien({{ $x['id'] }}, 'Membre retiré de l\'équipe.')" wire:confirm="Retirer {{ $x['membre']['prenom'] }} de l'équipe ?" class="text-[11.5px] font-semibold text-[#9AA6B8] hover:text-accent">Retirer</button>
                                @elseif ($x['membre']['id'] !== (\App\Support\Api::user()->id ?? null))
                                    <a href="{{ route('espace-membre.messaging', ['to' => $x['membre']['id']]) }}" wire:navigate class="icon-btn rounded-full border border-brand/15 p-2 text-brand hover:bg-cloud" title="Écrire"><x-ui.icon name="message-circle" class="size-3.5" /></a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- En attente (visible de l'équipe) --}}
                    @if ($equipe && ! empty($p['en_attente']))
                        <div class="mt-4 border-t border-[#EDF0F5] pt-3">
                            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">En attente</p>
                            @foreach ($p['en_attente'] as $x)
                                <div wire:key="att-{{ $x['id'] }}" data-test="en-attente" class="mb-2 rounded-[10px] bg-cloud/60 p-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <x-messagerie.avatar :personne="$x['membre']" taille="size-8" texte="text-[10px]" />
                                        <p class="min-w-0 flex-1 text-[12.5px]"><span class="font-bold text-brand">{{ $x['membre']['prenom'] }} {{ $x['membre']['nom'] }}</span>
                                            <span class="text-[#5B677A]">{{ $x['statut'] === 'demande' ? 'demande à rejoindre' : 'invité(e)' }}{{ $x['role'] ? ' · '.$x['role'] : '' }}</span></p>
                                        @if ($x['statut'] === 'demande')
                                            <button type="button" wire:click="accepterLien({{ $x['id'] }})" data-test="accepter-demande" class="btn-tap rounded-full bg-brand px-3 py-1 text-[11.5px] font-bold text-white">Accepter</button>
                                            <button type="button" wire:click="retirerLien({{ $x['id'] }}, 'Demande refusée : le membre est prévenu.')" class="text-[11.5px] font-semibold text-[#9AA6B8] hover:text-accent">Refuser</button>
                                        @elseif ($relation === 'porteur')
                                            <button type="button" wire:click="retirerLien({{ $x['id'] }}, 'Invitation annulée.')" class="text-[11.5px] font-semibold text-[#9AA6B8] hover:text-accent">Annuler</button>
                                        @endif
                                    </div>
                                    @if ($x['message'])<p class="mt-1.5 pl-[42px] text-[12px] italic text-[#5B677A]">« {{ $x['message'] }} »</p>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Inviter un membre --}}
                    @if ($equipe && in_array($p['statut'], ['evaluation', 'a_completer', 'valide'], true))
                        <div class="mt-4 border-t border-[#EDF0F5] pt-3">
                            <p class="mb-2 text-[12px] font-bold text-brand">Inviter un membre du réseau</p>
                            <div class="grid gap-2 sm:grid-cols-[1fr_180px]">
                                <input wire:model.live.debounce.300ms="rechercheCandidat" type="search" placeholder="Nom du membre…" data-test="recherche-candidat" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                                <input wire:model="roleInvite" type="text" placeholder="Rôle (optionnel)" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                            </div>
                            @if (! empty($candidats))
                                <div class="mt-2 divide-y divide-[#EDF0F5] rounded-[10px] border border-brand/10">
                                    @foreach ($candidats as $c)
                                        <div wire:key="cand-{{ $c['id'] }}" class="flex items-center gap-2.5 px-3 py-2">
                                            <x-messagerie.avatar :personne="$c" taille="size-8" texte="text-[10px]" />
                                            <p class="min-w-0 flex-1 truncate text-[12.5px]"><span class="font-bold text-brand">{{ $c['prenom'] }} {{ $c['nom'] }}</span> <span class="text-[#9AA6B8]">{{ collect([$c['titre'] ?? null, $c['ville'] ?? null])->filter()->join(' · ') }}</span></p>
                                            <button type="button" wire:click="inviter({{ $c['id'] }})" data-test="inviter-candidat" class="btn-tap rounded-full bg-brand px-3 py-1 text-[11.5px] font-bold text-white hover:bg-brand/90">Inviter</button>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif (mb_strlen(trim($recherche)) >= 2)
                                <p class="mt-2 text-[12px] text-[#9AA6B8]">Aucun membre trouvé (seuls les membres visibles dans l'annuaire peuvent être invités).</p>
                            @endif
                        </div>
                    @endif
                </section>

                {{-- Avancées --}}
                @if ($p['statut'] === 'valide' && (! empty($p['avancees']) || ($p['peut_publier'] ?? false)))
                    <section class="mt-5">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Les avancées du projet</p>
                        @if ($p['peut_publier'] ?? false)
                            <div class="mb-3 rounded-[12px] border border-brand/10 p-3">
                                <textarea wire:model="texteAvancee" rows="2" maxlength="2000" data-test="texte-avancee" placeholder="Partagez une nouvelle : une étape franchie, un besoin, un événement…" class="w-full resize-none text-[13px] outline-none"></textarea>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] text-[#9AA6B8]">Envoyée à ceux qui suivent le projet ({{ $p['nb_suivis'] ?? 0 }}) et à l'équipe.</span>
                                    <button type="button" wire:click="publierAvancee" wire:loading.attr="disabled" data-test="publier-avancee" class="btn-tap shrink-0 rounded-full bg-brand px-4 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Publier</button>
                                </div>
                            </div>
                        @endif
                        <div class="space-y-3">
                            @forelse ($p['avancees'] ?? [] as $a)
                                <div wire:key="av-{{ $a['id'] }}" data-test="avancee" class="flex gap-2.5">
                                    @if ($a['auteur'])<x-messagerie.avatar :personne="$a['auteur']" taille="size-8" texte="text-[10px]" />@endif
                                    <div class="min-w-0 flex-1 rounded-[12px] bg-cloud/60 px-3 py-2">
                                        <p class="text-[11.5px] text-[#9AA6B8]"><span class="font-bold text-brand">{{ $a['auteur']['prenom'] ?? '' }} {{ $a['auteur']['nom'] ?? '' }}</span> · @php $d = \Illuminate\Support\Carbon::parse($a['date'])->locale('fr'); @endphp{{ $d->diffInSeconds(now()) < 60 ? "à l'instant" : $d->diffForHumans() }}
                                            @if ($a['supprimable'])<button type="button" wire:click="supprimerAvancee({{ $a['id'] }})" wire:confirm="Supprimer cette nouvelle ?" class="ml-1 font-semibold hover:text-accent">· Supprimer</button>@endif
                                        </p>
                                        <p class="mt-0.5 whitespace-pre-line text-[13px] leading-relaxed text-ink">{!! \App\Support\Texte::liens($a['body']) !!}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-[12px] text-[#9AA6B8]">Aucune nouvelle publiée pour le moment.</p>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($info)<p data-test="fiche-projet-info" class="mt-4 text-[12.5px] font-semibold text-azure">{{ $info }}</p>@endif

                {{-- Contribuer / rejoindre --}}
                @if ($p['statut'] === 'valide' && ! $equipe && ! in_array($relation, ['invite', 'demande'], true) && ($p['porteur'] ?? null))
                    @if ($rejoindreOuvert)
                        <div wire:key="form-rejoindre" class="panel-enter mt-4 rounded-[12px] border border-brand/10 bg-cloud/40 p-3.5">
                            <p class="mb-2 text-[13px] font-bold text-brand">Rejoindre l'équipe</p>
                            <input wire:model="roleRejoindre" type="text" maxlength="80" placeholder="Votre rôle (ex : comptable, commercial…)" class="mb-2 w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                            <textarea wire:model="messageRejoindre" rows="2" maxlength="500" data-test="message-rejoindre" placeholder="Présentez-vous et dites ce que vous pouvez apporter." class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                            <button type="button" wire:click="rejoindre" data-test="envoyer-demande" class="btn-tap mt-2 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">Envoyer ma demande</button>
                        </div>
                    @endif
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if ($p['statut'] === 'valide' && ! $equipe && ($p['porteur'] ?? null))
                        <a href="{{ route('espace-membre.messaging', ['to' => $p['porteur']['id'], 'projet' => $p['id']]) }}" wire:navigate data-test="contribuer" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="message-circle" class="size-4" /> Je veux contribuer</a>
                        @unless (in_array($relation, ['invite', 'demande'], true))
                            <button type="button" wire:click="ouvrirRejoindre" data-test="rejoindre" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="users" class="size-4" /> Rejoindre l'équipe</button>
                        @endunless
                    @endif
                    @if ($p['statut'] === 'valide' && ! $p['mine'])
                        <button type="button" wire:click="suivre" data-test="suivre" class="btn-tap inline-flex items-center gap-2 rounded-full border px-4 py-2.5 text-[13px] font-bold {{ ($p['suivi'] ?? false) ? 'border-azure/40 bg-azure/[.06] text-azure' : 'border-brand/15 text-brand hover:bg-cloud' }}">
                            <x-ui.icon name="bell" class="size-4" /> {{ ($p['suivi'] ?? false) ? 'Suivi' : 'Suivre' }}
                        </button>
                    @endif
                    @if ($relation === 'membre')
                        <button type="button" wire:click="quitterEquipe" wire:confirm="Quitter l'équipe de ce projet ?" class="ml-auto text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Quitter l'équipe</button>
                    @endif
                    @if ($p['mine'])
                        @if ($p['modifiable'] ?? false)
                            <button type="button" wire:click="openEdit({{ $p['id'] }})" data-test="fiche-projet-modifier" class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">
                                <x-ui.icon name="pencil" class="size-4" /> {{ in_array($p['statut'], ['a_completer', 'refuse'], true) ? 'Compléter et renvoyer' : 'Modifier' }}
                            </button>
                        @endif
                        @if ($p['statut'] === 'valide')
                            <span class="text-[12px] text-[#9AA6B8]">{{ $p['vues'] ?? 0 }} vue{{ ($p['vues'] ?? 0) > 1 ? 's' : '' }} par les membres</span>
                        @endif
                        @if ($p['statut'] !== 'retire')
                            <button type="button" wire:click="retirer({{ $p['id'] }})" wire:confirm="Retirer ce projet ? Il ne sera plus visible des membres." class="ml-auto text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Retirer le projet</button>
                        @else
                            <button type="button" wire:click="supprimer({{ $p['id'] }})" wire:confirm="Supprimer définitivement ce projet ?" class="ml-auto text-[12px] font-semibold text-[#9AA6B8] hover:text-accent">Supprimer</button>
                        @endif
                    @endif
                    @if ($p['statut'] === 'valide')
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
