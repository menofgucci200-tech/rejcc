@props(['fiche'])

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
                    @if ($o['publie_at']) <span class="text-[11.5px] text-[#9AA6B8]">Publiée {{ \Illuminate\Support\Carbon::parse($o['publie_at'])->locale('fr')->diffForHumans() }}</span> @endif
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

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    {{ $actions ?? '' }}
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
