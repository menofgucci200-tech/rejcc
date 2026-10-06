@php
    $statutBadge = fn ($s) => match ($s) {
        'success' => ['#1C8F4C', '#EAF6EE', 'Payé'],
        'failed' => ['#AC0100', '#F9E9E9', 'Échoué'],
        'abandonne' => ['#7A8699', '#EEF1F5', 'Abandonné'],
        default => ['#B27007', '#FCF1DD', 'En attente'],
    };
    $f = fn ($n) => number_format((int) $n, 0, ',', ' ').' F';
    $d = fn ($v, $fmt = 'j F Y') => $v ? \Carbon\Carbon::parse($v)->translatedFormat($fmt) : '—';
    $expiresAt = $s['expires_at'] ?? null;
    $joursRestants = $s['jours_restants'] ?? null;
    $peutRenouveler = (bool) ($s['peut_renouveler'] ?? true);
    $graceJours = (int) ($s['grace_jours'] ?? 5);
    $avantages = [
        ['qr-code', 'Carte de membre officielle', 'Avec QR code vérifiable et page biographique.'],
        ['users', 'Annuaire des membres', 'Coordonnées et profils complets du réseau.'],
        ['message-circle', 'Messagerie', 'Écrire à tout membre, mentor ou vendeur.'],
        ['store', 'Marketplace', 'Publier vos services et produits.'],
        ['nav-projects', 'Projets', 'Soumettre vos projets et rejoindre des équipes.'],
        ['network', 'Groupes sectoriels', 'Fiches professionnelles et avis des membres.'],
    ];
@endphp

<div>
    <x-member-light.topbar title="Mon abonnement" />

    <div class="mx-auto max-w-[900px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Mon abonnement annuel</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">
                L'abonnement annuel au REJCC ({{ $f($amount) }} par an) donne accès à l'ensemble des services réservés aux membres.
                Le paiement se fait en ligne, en toute sécurité.
            </p>
        </div>

        @if ($retour)
            @php
                [$rc, $rbg, $ri] = match ($retour[0]) {
                    'success' => ['#1C8F4C', '#F2FBF5', 'check-circle'],
                    'error' => ['#AC0100', '#FDF3F3', 'x-circle'],
                    default => ['#B27007', '#FFF8EC', 'clock'],
                };
            @endphp
            <div data-test="retour-paiement" data-statut="{{ $retour[0] }}" class="panel-enter mb-5 flex items-start gap-3.5 rounded-[16px] border p-5" style="background: {{ $rbg }}; border-color: {{ $rc }}33">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl" style="background: {{ $rc }}1A; color: {{ $rc }}"><x-ui.icon :name="$ri" class="size-5" /></span>
                <div class="flex-1">
                    <p class="text-[14px] font-bold text-brand">{{ $retour[1] }}</p>
                    <p class="mt-0.5 text-[12.5px] leading-relaxed text-[#5B677A]">{{ $retour[2] }}</p>
                </div>
                <button wire:click="$set('retour', null)" class="text-[#9AA6B8] hover:text-brand" aria-label="Fermer"><x-ui.icon name="x" class="size-4" /></button>
            </div>
        @endif

        @if (! $enforced)
            <div data-test="abonnements-libres" class="mb-5 flex items-start gap-3.5 rounded-[16px] border border-[#22A85A]/30 bg-[#F2FBF5] p-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#22A85A]/15 text-[#1C8F4C]"><x-ui.icon name="shield-check" class="size-5" /></span>
                <div>
                    <p class="text-[14px] font-bold text-brand">Accès libre pour le moment</p>
                    <p class="mt-0.5 text-[12.5px] leading-relaxed text-[#5B677A]">Les abonnements ne sont pas encore ouverts : toutes les fonctionnalités de l'espace membre sont accessibles gratuitement. Vous serez prévenu avant leur mise en place.</p>
                </div>
            </div>
        @endif

        @if ($exempt)
            <div data-test="abonnement-dispense" class="mb-5 flex items-start gap-3.5 rounded-[16px] border border-[#22A85A]/30 bg-[#F2FBF5] p-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#22A85A]/15 text-[#1C8F4C]"><x-ui.icon name="shield-check" class="size-5" /></span>
                <div>
                    <p class="text-[14px] font-bold text-brand">Dispensé d'abonnement</p>
                    <p class="mt-0.5 text-[12.5px] leading-relaxed text-[#5B677A]">{{ $role === 'mentor' ? 'En tant que mentor du réseau, vous avez accès à toutes les fonctionnalités sans cotisation : merci pour le temps que vous donnez aux membres.' : 'Votre statut d\'administrateur vous donne accès à toutes les fonctionnalités sans cotisation.' }}</p>
                </div>
            </div>
        @endif

        @if ($enforced && ! $exempt)
            @php
                [$etat, $couleur, $fond, $icone] = match (true) {
                    $grace => ['Délai de grâce', '#B27007', '#F5A623', 'clock'],
                    $active => ['Abonnement actif', '#1C8F4C', '#22A85A', 'shield-check'],
                    default => ['Abonnement non actif', '#B27007', '#F5A623', 'shield'],
                };
            @endphp
            <div data-test="carte-abonnement" data-etat="{{ $grace ? 'grace' : ($active ? 'actif' : 'inactif') }}" class="rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-[240px] flex-1 items-start gap-3.5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl" style="background: {{ $fond }}1A; color: {{ $couleur }}">
                            <x-ui.icon :name="$icone" class="size-5" />
                        </span>
                        <div>
                            <p class="text-[14px] font-bold text-brand">{{ $etat }}</p>
                            <p class="mt-0.5 text-[12.5px] leading-relaxed text-[#5B677A]">
                                @if ($grace)
                                    Votre abonnement est arrivé à échéance le {{ $d($expiresAt) }}. Vous gardez l'accès jusqu'au
                                    <strong class="text-brand">{{ $d($s['grace_fin'] ?? null) }}</strong> : renouvelez-le d'ici là, la date anniversaire est conservée.
                                @elseif ($active && $expiresAt)
                                    Valable jusqu'au <strong class="text-brand">{{ $d($expiresAt) }}</strong>
                                    @if ($joursRestants !== null) · {{ $joursRestants }} jour{{ $joursRestants > 1 ? 's' : '' }} restant{{ $joursRestants > 1 ? 's' : '' }} @endif
                                @elseif ($expiresAt)
                                    Votre abonnement a expiré le {{ $d($expiresAt) }}. Renouvelez-le pour retrouver l'accès à tout le réseau.
                                @else
                                    Réglez votre cotisation pour débloquer l'ensemble des services réservés aux membres.
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($peutRenouveler)
                        <button wire:click="payer" wire:loading.attr="disabled" wire:target="payer" data-test="payer"
                            class="btn-tap inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:w-auto">
                            <span wire:loading.remove wire:target="payer" class="inline-flex items-center gap-2">
                                <x-ui.icon name="credit-card" class="size-4" />
                                {{ $active || $grace ? 'Renouveler · '.$f($amount) : 'Payer '.$f($amount) }}
                            </span>
                            <span wire:loading wire:target="payer" class="inline-flex items-center gap-2">
                                <x-ui.icon name="loader-2" class="size-4 animate-spin" /> Redirection…
                            </span>
                        </button>
                    @else
                        <p data-test="renouvellement-plus-tard" class="max-w-[240px] rounded-xl bg-cloud px-3.5 py-2 text-[11.5px] leading-relaxed text-[#5B677A]">
                            Le renouvellement sera possible à partir du <strong class="text-brand">{{ $d(\Carbon\Carbon::parse($expiresAt)->subDays(30)) }}</strong>.
                        </p>
                    @endif
                </div>

                @if ($active && ! $grace && $joursRestants !== null)
                    <div class="mt-5">
                        <div class="h-1.5 overflow-hidden rounded-full bg-cloud-200">
                            <div class="h-full rounded-full {{ $joursRestants <= 30 ? 'bg-[#F5A623]' : 'bg-[#22A85A]' }}" style="width: {{ max(3, min(100, round($joursRestants / 365 * 100))) }}%"></div>
                        </div>
                        @if ($joursRestants <= 30)
                            <p class="mt-2 text-[11.5px] text-[#B27007]">Votre abonnement arrive bientôt à échéance : renouvelez-le dès maintenant, la nouvelle période démarrera le {{ $d($expiresAt) }}.</p>
                        @endif
                    </div>
                @endif

                <p class="mt-4 flex items-center gap-1.5 text-[11.5px] text-[#9AA6B8]"><x-ui.icon name="lock" class="size-3.5" /> Paiement sécurisé via CinetPay : Wave, Orange Money, MTN Money, Moov Money ou carte bancaire. Après l'échéance, l'accès est maintenu {{ $graceJours }} jours.</p>
            </div>

            @if ($enAttente)
                <div data-test="paiement-en-attente" class="mt-4 flex flex-wrap items-center gap-3.5 rounded-[16px] border border-[#F5A623]/35 bg-[#FFF8EC] px-5 py-4">
                    <x-ui.icon name="clock" class="size-5 shrink-0 text-[#B27007]" />
                    <p class="min-w-[220px] flex-1 text-[12.5px] leading-relaxed text-brand">
                        Un paiement de <strong>{{ $f($enAttente['montant']) }}</strong> lancé le {{ $d($enAttente['created_at'], 'j F à H\hi') }} attend la confirmation de l'opérateur.
                        <span class="text-[#5B677A]">Si vous avez été débité, vérifiez-le ici.</span>
                    </p>
                    <button wire:click="verifier('{{ $enAttente['reference'] }}')" wire:loading.attr="disabled" wire:target="verifier" data-test="verifier-paiement"
                        class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud disabled:opacity-60">
                        <x-ui.icon name="refresh-cw" class="size-3.5" wire:loading.class="animate-spin" wire:target="verifier" /> Vérifier mon paiement
                    </button>
                </div>
            @endif

            <div class="mt-6 rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-4 text-[13px] font-bold text-brand">Ce que comprend l'abonnement</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($avantages as [$ic, $titre, $texte])
                        <div class="flex items-start gap-3 rounded-xl bg-cloud/60 p-3.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-[#22A85A]/12 text-[#1C8F4C]' : 'bg-brand/8 text-brand' }}"><x-ui.icon :name="$ic" class="size-4" /></span>
                            <div>
                                <p class="text-[12.5px] font-bold text-brand">{{ $titre }}</p>
                                <p class="text-[11.5px] leading-snug text-[#5B677A]">{{ $texte }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-[11.5px] text-[#9AA6B8]">Les formations, parcours, événements et documents ouverts à tous restent accessibles sans abonnement.</p>
            </div>
        @endif

        @if ($enforced)
            <div data-test="offrir" class="mt-6 rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start gap-3.5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent"><x-ui.icon name="gift" class="size-5" /></span>
                        <div>
                            <p class="text-[14px] font-bold text-brand">Offrir un abonnement</p>
                            <p class="mt-0.5 max-w-md text-[12.5px] leading-relaxed text-[#5B677A]">Parrainez un jeune du réseau en réglant son abonnement annuel. Il sera prévenu, et vous recevrez le reçu du paiement.</p>
                        </div>
                    </div>
                    <button wire:click="basculerOffrir" data-test="ouvrir-offrir" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">
                        {{ $offrir ? 'Fermer' : 'Choisir un membre' }}
                    </button>
                </div>

                @if ($offrir)
                    <div class="panel-enter mt-5">
                        <div class="relative">
                            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[#9AA6B8]" />
                            <input wire:model.live.debounce.400ms="recherche" type="search" data-test="recherche-beneficiaire" autocomplete="off"
                                placeholder="Nom du membre ou numéro de membre (REJCC-…)"
                                class="w-full rounded-xl border border-brand/15 bg-white py-2.5 pl-10 pr-4 text-[13px] text-ink placeholder:text-[#9AA6B8]" />
                        </div>
                        <p class="mt-2 text-[11.5px] text-[#9AA6B8]">Seuls les membres visibles dans l'annuaire apparaissent par leur nom. Pour un autre membre, saisissez son numéro de membre complet, tel qu'il figure sur sa carte.</p>

                        @if (mb_strlen(trim($recherche)) >= 3)
                            <div class="mt-3 divide-y divide-cloud-200 rounded-xl border border-cloud-200" wire:loading.class="opacity-60" wire:target="recherche">
                                @forelse ($membres as $m)
                                    <div data-test="beneficiaire" class="flex items-center gap-3 px-4 py-3">
                                        @if (! empty($m['photo']))
                                            <img src="{{ $m['photo'] }}" alt="" class="size-9 shrink-0 rounded-full object-cover" />
                                        @else
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand/10 text-[12px] font-bold text-brand">{{ mb_strtoupper(collect(explode(' ', $m['nom']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('')) }}</span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13px] font-semibold text-ink">{{ $m['nom'] }}</p>
                                            <p class="truncate text-[11.5px] text-[#9AA6B8]">{{ $m['ville'] ?: 'Membre du réseau' }}</p>
                                        </div>
                                        @if ($m['peut_recevoir'])
                                            <button wire:click="offrirA({{ $m['id'] }})" wire:loading.attr="disabled" wire:target="offrirA({{ $m['id'] }})"
                                                wire:confirm="Offrir l'abonnement annuel ({{ $f($amount) }}) à {{ $m['nom'] }} ? Vous allez être redirigé vers le paiement sécurisé."
                                                data-test="offrir-a" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-accent px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">
                                                <x-ui.icon name="gift" class="size-3.5" /> Offrir
                                            </button>
                                        @else
                                            <span class="rounded-full bg-[#EAF6EE] px-2.5 py-1 text-[11px] font-bold text-[#1C8F4C]">Déjà abonné(e)</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="px-4 py-4 text-[12.5px] text-[#5B677A]">Aucun membre trouvé. Vérifiez l'orthographe ou demandez-lui son numéro de membre.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if (! empty($history))
            <div data-test="historique" class="mt-6 rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-4 text-[13px] font-bold text-brand">Historique des paiements</p>
                <div class="space-y-2.5">
                    @foreach ($history as $h)
                        @php [$color, $bg, $label] = $statutBadge($h['statut'] ?? null); @endphp
                        <div data-test="paiement" class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-cloud-200 px-4 py-3">
                            <div class="min-w-[200px] flex-1">
                                <p class="flex items-center gap-1.5 text-[13px] font-semibold text-ink">
                                    @if ($h['pour'])
                                        <x-ui.icon name="gift" class="size-3.5 text-accent" /> Abonnement offert à {{ $h['pour'] }}
                                    @elseif ($h['par'])
                                        <x-ui.icon name="gift" class="size-3.5 text-accent" /> Abonnement offert par {{ $h['par'] }}
                                    @else
                                        Abonnement annuel
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">
                                    {{ $d($h['paye_at'] ?? $h['created_at'], 'j M Y') }}
                                    @if ($h['periode_debut'] && $h['periode_fin']) · période du {{ $d($h['periode_debut'], 'j M Y') }} au {{ $d($h['periode_fin'], 'j M Y') }} @endif
                                    @if ($h['moyen']) · {{ $h['moyen'] }} @endif
                                </p>
                            </div>
                            <span class="text-[13px] font-bold text-brand">{{ $f($h['montant']) }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" style="background: {{ $bg }}; color: {{ $color }}">{{ $label }}</span>
                            @if ($h['recu'])
                                <a href="{{ route('espace-membre.abonnement.recu', $h['reference']) }}" target="_blank" data-test="recu" title="Reçu {{ $h['recu'] }}"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-brand/15 px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">
                                    <x-ui.icon name="receipt" class="size-3.5" /> Reçu
                                </a>
                            @elseif (($h['statut'] ?? null) === 'pending' && ! $h['par'])
                                <button wire:click="verifier('{{ $h['reference'] }}')" class="text-[11.5px] font-bold text-azure hover:underline">Vérifier</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
