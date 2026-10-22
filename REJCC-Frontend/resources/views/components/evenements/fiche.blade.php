@props(['fiche', 'erreur' => null])

{{-- Fiche complète d'un événement : visuel, date, lieu, places, description,
     inscription, ajout à l'agenda, partage. --}}
@if ($fiche)
    @php
        $e = $fiche;
        $debut = \Illuminate\Support\Carbon::parse($e['starts_at'])->setTimezone(config('app.timezone'))->locale('fr');
        $fin = $e['ends_at'] ? \Illuminate\Support\Carbon::parse($e['ends_at'])->setTimezone(config('app.timezone'))->locale('fr') : null;
        $couleur = \App\Support\CategoryPalette::for($e['category'])['tag'];
        $lien = route('espace-membre.evenements', ['evenement' => $e['id']]);
        $plan = (! $e['en_ligne'] && $e['location']) ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($e['location']) : null;
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFiche" x-on:keydown.escape.window="$wire.fermerFiche()">
        <div data-test="fiche-evenement" role="dialog" aria-modal="true" class="panel-enter flex max-h-[92vh] w-full max-w-[720px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
            <div class="relative shrink-0">
                @if ($e['image'])
                    <img src="{{ $e['image'] }}" alt="" class="max-h-[260px] w-full object-cover">
                @else
                    <div class="flex h-28 items-end p-5" style="background: linear-gradient(135deg, #031D59, {{ $couleur }})">
                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">{{ $e['category'] }}</span>
                    </div>
                @endif
                <button type="button" wire:click="fermerFiche" aria-label="Fermer" class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white"><x-ui.icon name="x" class="size-4" /></button>
            </div>

            <div class="overflow-y-auto p-6">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $couleur }}1A; color: {{ $couleur }}">{{ $e['category'] }}</span>
                    @if ($e['statut'] === 'annule')<span class="rounded-full bg-accent px-2.5 py-0.5 text-[10.5px] font-bold text-white">Annulé</span>@endif
                    @if ($e['reserve_abonnes'])<span class="rounded-full bg-[#F5A623]/15 px-2.5 py-0.5 text-[10.5px] font-bold text-[#B27007]">Réservé aux abonnés</span>@endif
                    @if ($e['en_ligne'])<span class="rounded-full bg-azure/10 px-2.5 py-0.5 text-[10.5px] font-bold text-azure">En ligne</span>@endif
                </div>
                <h2 data-test="fiche-evenement-titre" class="text-[20px] font-extrabold leading-snug text-brand">{{ $e['title'] }}</h2>
                @if (($e['live'] ?? false) && (($e['formation'] ?? null) || ($e['animateur'] ?? null)))
                    <p class="mt-1 text-[13px] text-[#3D4A60]">@if ($e['formation'])Formation : <span class="font-semibold">{{ $e['formation'] }}</span>@endif @if ($e['animateur'])· avec <span class="font-semibold">{{ $e['animateur'] }}</span>@endif</p>
                @endif
                @if ($e['statut'] === 'annule' && $e['motif_annulation'])
                    <p class="mt-2 rounded-[10px] bg-accent/5 px-3 py-2 text-[12.5px] font-semibold text-accent">Annulé : {{ $e['motif_annulation'] }}</p>
                @endif

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="flex items-start gap-2.5 rounded-[12px] bg-cloud/70 p-3">
                        <x-ui.icon name="calendar" class="mt-0.5 size-4 shrink-0 text-brand" />
                        <div class="text-[13px]">
                            <p class="font-bold text-brand">{{ ucfirst($debut->isoFormat('dddd D MMMM YYYY')) }}</p>
                            <p class="text-[#5B677A]">{{ $e['time_label'] ?: $debut->format('H\hi') }}@if ($fin && ! $e['time_label']) – {{ $fin->isSameDay($debut) ? $fin->format('H\hi') : $fin->isoFormat('D MMM, H[h]mm') }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 rounded-[12px] bg-cloud/70 p-3">
                        <x-ui.icon :name="$e['en_ligne'] ? 'video' : 'map-pin'" class="mt-0.5 size-4 shrink-0 text-brand" />
                        <div class="min-w-0 text-[13px]">
                            <p class="font-bold text-brand">{{ $e['en_ligne'] ? 'En ligne' : ($e['location'] ?: 'Lieu à préciser') }}</p>
                            @if ($plan)<a href="{{ $plan }}" target="_blank" rel="noopener" class="text-[12px] font-semibold text-azure hover:underline">Voir sur la carte</a>@endif
                            @if ($e['en_ligne'])<p class="text-[12px] text-[#5B677A]">Visio Google Meet : le bouton « Rejoindre » s'active 15 min avant.</p>@endif
                        </div>
                    </div>
                </div>

                <p data-test="fiche-evenement-places" class="mt-3 text-[12.5px] font-semibold {{ $e['complet'] ? 'text-accent' : 'text-[#5B677A]' }}">
                    <x-ui.icon name="users" class="mr-1 inline size-4 align-[-3px]" />
                    {{ $e['attendees_count'] }} inscrit{{ $e['attendees_count'] > 1 ? 's' : '' }}
                    @if ($e['capacity'] !== null)
                        · {{ $e['complet'] ? 'Complet' : $e['places_restantes'].' place'.($e['places_restantes'] > 1 ? 's' : '').' restante'.($e['places_restantes'] > 1 ? 's' : '') }}
                    @endif
                    @if ($e['date_limite'] && ! $e['passe'])
                        · inscriptions jusqu'au {{ \Illuminate\Support\Carbon::parse($e['date_limite'])->setTimezone(config('app.timezone'))->locale('fr')->isoFormat('D MMMM [à] HH[h]mm') }}
                    @endif
                </p>

                @php
                    // Le résumé n'est affiché que s'il apporte autre chose que le début de la description.
                    $resume = trim((string) $e['excerpt']);
                    $resumeUtile = $resume !== '' && ! str_starts_with(trim((string) $e['description']), rtrim($resume, '.… '));
                @endphp
                @if ($resumeUtile)<p class="mt-4 text-[14px] font-semibold leading-relaxed text-ink">{{ $e['excerpt'] }}</p>@endif
                @if ($e['description'])<p class="mt-3 whitespace-pre-line text-[13.5px] leading-relaxed text-[#3D4A60]">{!! \App\Support\Texte::liens($e['description'], 'font-semibold text-azure underline underline-offset-2') !!}</p>@endif

                @if ($e['en_ligne'] && ! $e['passe'] && $e['statut'] === 'publie')
                    @php $tel = preg_replace('/\D+/', '', (string) \App\Support\Content\SiteConfig::get()['contact']['phone']); @endphp
                    <section data-test="comment-ca-se-passe" class="mt-5 rounded-[14px] border border-[#CDEBDD] bg-[#F4FBF7] p-4">
                        <p class="mb-2 text-[13px] font-bold text-[#1C6B47]">Comment ça se passe ?</p>
                        <ul class="space-y-1.5 text-[13px] leading-relaxed text-[#2F4A3C]">
                            <li>1. Cliquez sur « Rejoindre la visio » quelques minutes avant : vous arrivez sur Google Meet, une personne du REJCC vous fait entrer.</li>
                            <li>2. Coupez votre caméra pour économiser vos données ; gardez le micro coupé quand vous ne parlez pas.</li>
                            <li>3. Un souci pour entrer ?@if ($tel) <a href="https://wa.me/{{ $tel }}" target="_blank" rel="noopener" class="font-semibold text-[#1C6B47] underline">Écrivez-nous sur WhatsApp</a>.@else Contactez l'équipe du REJCC.@endif</li>
                        </ul>
                    </section>
                @endif

                {{-- Ils participent : membres inscrits visibles dans l'annuaire --}}
                @php $p = $e['participants'] ?? ['total' => 0, 'visible' => false, 'membres' => []]; @endphp
                @if ($p['total'] > 0)
                    <section data-test="ils-participent" class="mt-5 rounded-[14px] border border-brand/10 p-4" x-data="{ tous: false }">
                        <p class="mb-2.5 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">
                            {{ $e['passe'] ? 'Ils ont participé' : 'Ils participent' }} · {{ $p['total'] }} membre{{ $p['total'] > 1 ? 's' : '' }}{{ $e['registered'] ? ' en plus de vous' : '' }}
                        </p>
                        @if ($p['visible'])
                            <ul class="grid gap-2 sm:grid-cols-2">
                                @foreach ($p['membres'] as $i => $m)
                                    <li @if ($i >= 6) x-show="tous" style="display: none" @endif class="flex items-center gap-2.5 rounded-[12px] p-1.5 hover:bg-cloud/60">
                                        <x-messagerie.avatar :personne="$m" taille="size-9" texte="text-[11px]" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[12.5px] font-bold text-brand">{{ $m['prenom'] }} {{ $m['nom'] }}@if ($m['role'] === 'mentor') <span class="ml-0.5 rounded-full bg-accent px-1.5 align-middle text-[8.5px] font-bold uppercase text-white">Mentor</span>@endif</span>
                                            <span class="block truncate text-[11px] text-[#9AA6B8]">{{ collect([$m['titre'], $m['ville']])->filter()->join(' · ') }}</span>
                                        </span>
                                        <a href="{{ route('espace-membre.messaging', ['to' => $m['id']]) }}" wire:navigate data-test="ecrire-participant" title="Écrire à {{ $m['prenom'] }}" class="icon-btn shrink-0 rounded-lg p-1.5 text-azure hover:bg-azure/10"><x-ui.icon name="message-circle" class="size-4" /></a>
                                    </li>
                                @endforeach
                            </ul>
                            @if (count($p['membres']) > 6)
                                <button type="button" x-show="! tous" x-on:click="tous = true" class="mt-2 text-[12px] font-semibold text-azure hover:underline">Voir les {{ count($p['membres']) }} participants</button>
                            @endif
                        @else
                            <p class="text-[12.5px] text-[#5B677A]">Découvrez qui participe et prenez contact avant l'événement : réservé aux membres abonnés.
                                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="font-semibold text-accent hover:underline">M'abonner</a></p>
                        @endif
                    </section>
                @endif

                @if ($erreur)<p data-test="fiche-evenement-erreur" class="mt-4 rounded-[10px] bg-accent/5 px-3 py-2 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>@endif

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if ($e['passe'])
                        <span class="rounded-full bg-cloud px-4 py-2.5 text-[13px] font-semibold text-[#5B677A]">Événement terminé{{ $e['registered'] ? ' — vous étiez inscrit(e)' : '' }}</span>
                    @elseif ($e['registered'])
                        <span data-test="fiche-inscrit" class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-4 py-2.5 text-[13px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> Vous êtes inscrit(e)</span>
                        @if (($e['billet'] ?? null) && ! $e['en_ligne'])
                            <div x-data="{ billet: false }" class="contents">
                                <button type="button" x-on:click="billet = true" data-test="mon-billet" class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="qr-code" class="size-4" /> Mon billet</button>
                                <template x-teleport="body">
                                    <div x-show="billet" x-transition.opacity style="display: none" class="fixed inset-0 z-[95] flex items-center justify-center bg-brand/60 p-4" x-on:click.self="billet = false" x-on:keydown.escape.window="billet = false">
                                        <div data-test="billet" class="w-full max-w-[340px] overflow-hidden rounded-[20px] bg-white text-center shadow-2xl">
                                            <div class="px-6 pb-4 pt-6" style="background: linear-gradient(135deg, #031D59, {{ $couleur }})">
                                                <p class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-white/70">Billet REJCC</p>
                                                <p class="mt-1 text-[15px] font-extrabold leading-snug text-white">{{ $e['title'] }}</p>
                                                <p class="mt-1 text-[12px] text-white/80">{{ ucfirst($debut->isoFormat('dddd D MMMM · HH[h]mm')) }}</p>
                                            </div>
                                            <div class="p-6">
                                                <canvas x-init="window.QRCode && window.QRCode.toCanvas($el, @js($e['billet']), { width: 220, margin: 1, color: { dark: '#031D59', light: '#ffffff' } }, () => { $el.style.width = ''; $el.style.height = ''; })" class="mx-auto !size-[220px]"></canvas>
                                                <p class="mt-3 font-mono text-[15px] font-bold tracking-[0.12em] text-brand">{{ $e['billet'] }}</p>
                                                <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ \App\Support\Api::user()->prenom ?? '' }} {{ \App\Support\Api::user()->nom ?? '' }}</p>
                                                @if ($e['present'] ?? false)
                                                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1 text-[12px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> Présence enregistrée</p>
                                                @else
                                                    <p class="mt-3 text-[11.5px] text-[#9AA6B8]">Présentez ce QR code à l'accueil (votre carte membre fonctionne aussi).</p>
                                                @endif
                                                <button type="button" x-on:click="billet = false" class="mt-4 text-[12.5px] font-semibold text-azure hover:underline">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif
                        <x-evenements.rejoindre :e="$e" />
                        <a href="{{ route('espace-membre.evenements.agenda', $e['id']) }}" data-test="ajouter-agenda" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="calendar" class="size-4" /> Ajouter à mon agenda</a>
                        <button type="button" wire:click="desinscrire({{ $e['id'] }})" wire:confirm="Annuler votre inscription à « {{ $e['title'] }} » ?" data-test="desinscrire" class="text-[12.5px] font-semibold text-[#9AA6B8] hover:text-accent">Annuler mon inscription</button>
                    @elseif ($e['refus'])
                        <span data-test="fiche-refus" class="rounded-full bg-cloud px-4 py-2.5 text-[13px] font-semibold text-[#5B677A]">{{ $e['refus'] }}</span>
                        @if ($e['reserve_abonnes'] && str_contains($e['refus'], 'abonnement'))
                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap rounded-full bg-accent px-4 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">M'abonner</a>
                        @endif
                    @elseif ($e['visio_ouverte'] ?? false)
                        <x-evenements.rejoindre :e="$e" taille="grand" />
                    @else
                        <button type="button" wire:click="inscrire({{ $e['id'] }})" wire:loading.attr="disabled" data-test="fiche-inscrire" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600 disabled:opacity-60"><x-ui.icon name="check" class="size-4" /> Je m'inscris</button>
                    @endif
                    <button type="button" data-test="partager-evenement" x-data="{ copie: false }"
                        x-on:click="navigator.clipboard?.writeText(@js($lien)); copie = true; setTimeout(() => copie = false, 2000)"
                        class="btn-tap ml-auto inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">
                        <x-ui.icon name="external-link" class="size-4" /> <span x-text="copie ? 'Lien copié !' : 'Partager'">Partager</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
