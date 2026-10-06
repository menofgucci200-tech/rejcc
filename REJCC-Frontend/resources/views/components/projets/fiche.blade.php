@props(['fiche', 'besoinsLabels' => []])

{{-- Fiche complète d'un projet : présentation, besoins, porteur ; pour le
     porteur : statut, retour de l'équipe, modifier / retirer. --}}
@if ($fiche)
    @php
        $p = $fiche;
        $couleur = $p['groupe']['couleur'] ?? '#031D59';
        $statutC = \App\Support\ProjectStatus::color($p['statut']);
        $stadeC = \App\Support\ProjectStatus::stade($p['stade']);
        $lienPartage = route('espace-membre.projets', ['projet' => $p['id']]);
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

                {{-- Porteur --}}
                @if ($p['porteur'] ?? null)
                    <section class="mt-5 flex items-center gap-3 rounded-[14px] border border-brand/10 p-4">
                        <x-messagerie.avatar :personne="$p['porteur']" taille="size-11" texte="text-sm" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Porté par</p>
                            <p class="flex flex-wrap items-center gap-1.5 text-[14px] font-bold text-brand">{{ $p['porteur']['prenom'] }} {{ $p['porteur']['nom'] }}
                                @if (($p['porteur']['role'] ?? '') === 'mentor')<span class="rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Mentor</span>@endif
                            </p>
                            @if ($p['porteur']['titre'] ?? null)<p class="text-[12px] text-[#5B677A]">{{ $p['porteur']['titre'] }}</p>@endif
                        </div>
                        @unless ($p['mine'])
                            <a href="{{ route('espace-membre.messaging', ['to' => $p['porteur']['id']]) }}" wire:navigate data-test="fiche-projet-ecrire" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-full border border-brand/15 px-3.5 py-2 text-[12px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="message-circle" class="size-3.5" /> Écrire</a>
                        @endunless
                    </section>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-2">
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
