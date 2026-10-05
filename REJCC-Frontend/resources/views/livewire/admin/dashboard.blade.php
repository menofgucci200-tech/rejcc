<div>
    <x-admin-light.topbar title="Vue d'ensemble" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-6">
            <h1 class="mb-1.5 text-[26px] font-extrabold tracking-tight text-brand">Bonjour, {{ \App\Support\Api::user()->prenom }} 👋</h1>
            <p class="text-sm text-[#5B677A]">Voici l'état du réseau aujourd'hui, {{ now()->translatedFormat('d F Y') }}.</p>
        </section>

        {{-- Interrupteur général des abonnements --}}
        @php $ab = $abonnements; $sansAbonnement = max(0, $ab['membres'] - $ab['abonnes']); @endphp
        <section data-test="interrupteur-abonnements" class="mb-7 flex flex-wrap items-center gap-5 rounded-[18px] border p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $ab['obligatoires'] ? 'border-accent/25 bg-white' : 'border-[#22A85A]/30 bg-[#F2FBF5]' }}">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $ab['obligatoires'] ? 'bg-accent/10 text-accent' : 'bg-[#22A85A]/15 text-[#1C8F4C]' }}">
                <x-ui.icon :name="$ab['obligatoires'] ? 'shield' : 'shield-check'" class="size-6" />
            </span>
            <div class="min-w-[260px] flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-[15px] font-bold text-brand">Abonnements</p>
                    <span data-test="etat-abonnements" class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $ab['obligatoires'] ? 'bg-accent text-white' : 'bg-[#22A85A] text-white' }}">
                        {{ $ab['obligatoires'] ? 'Activés — restrictions appliquées' : 'Désactivés — accès libre pour tous' }}
                    </span>
                </div>
                <p class="mt-1 text-[12.5px] leading-relaxed text-[#5B677A]">
                    @if ($ab['obligatoires'])
                        Carte membre, annuaire, messagerie, projets et publication sur la marketplace sont réservés aux membres à jour de leur abonnement annuel (10 000 F).
                    @else
                        Tous les membres accèdent à toutes les fonctionnalités, sans abonnement. Activez les abonnements quand le paiement en ligne sera prêt.
                    @endif
                </p>
                <p class="mt-1.5 text-[11.5px] text-[#9AA6B8]">{{ $ab['abonnes'] }} abonné(s) à jour sur {{ $ab['membres'] }} membre(s){{ $ab['depuis'] ? ' · dernier changement le '.$ab['depuis'] : '' }}</p>
                @if ($messageAbonnements)
                    <p class="panel-enter mt-2 inline-flex items-center gap-1.5 text-[12px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $messageAbonnements }}</p>
                @endif
            </div>
            @if ($ab['modifiable'])
                @if ($ab['obligatoires'])
                    <button type="button" wire:click="basculerAbonnements(false)" data-test="desactiver-abonnements"
                        wire:confirm="Désactiver les abonnements ? Tous les membres auront accès à toutes les fonctionnalités, sans abonnement."
                        class="btn-tap rounded-xl border border-brand/15 bg-white px-5 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">Désactiver les abonnements</button>
                @else
                    <button type="button" wire:click="basculerAbonnements(true)" data-test="activer-abonnements"
                        wire:confirm="Activer les abonnements ? {{ $sansAbonnement }} membre(s) sans abonnement à jour perdront immédiatement l'accès à la carte membre, l'annuaire, la messagerie, les projets et la publication sur la marketplace."
                        class="btn-tap rounded-xl bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">Activer les abonnements</button>
                @endif
            @else
                <p class="text-[11.5px] text-[#9AA6B8]">Réglage réservé aux administrateurs ayant la section « Membres ».</p>
            @endif
        </section>

        <section class="mb-7 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            @foreach ($cards as $c)
                <div class="card-hover rounded-2xl border border-brand/10 bg-white p-[18px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="text-xs font-semibold text-[#5B677A]">{{ $c['label'] }}</p>
                    <p class="mt-1.5 text-[26px] font-extrabold text-brand">{{ $c['value'] }}</p>
                    <p class="mt-1 text-[11.5px] font-bold" style="color: {{ $c['subColor'] }}">{{ $c['sub'] }}</p>
                </div>
            @endforeach
        </section>

        <div class="mb-7 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <section>
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="mb-1 text-[17px] font-bold text-brand">Évolution du réseau</h2>
                        <div class="h-[3px] w-9 rounded bg-accent"></div>
                    </div>
                    <div class="flex gap-1.5">
                        @foreach ([3, 6, 12] as $p)
                            <button wire:click="setPeriode({{ $p }})" class="rounded-full border px-3 py-1.5 text-[11.5px] font-semibold transition-all duration-200 ease-out active:scale-95 {{ $periode === $p ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30 hover:text-brand' }}">{{ $p }} mois</button>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex h-40 items-end gap-3">
                        @foreach ($mois as $m)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[11px] font-bold text-[#5B677A]">{{ $m['valeur'] }}</span>
                                <div class="w-full max-w-[34px] rounded-t-lg rounded-b-[3px]" style="height: {{ $m['h'] }}px; background: linear-gradient(180deg,#4F6FBF,#031D59)"></div>
                                <span class="text-[10.5px] text-[#5B677A]">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">En attente d'action</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-4.5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($enAttente as $a)
                        <a href="{{ route($a['route']) }}" wire:navigate class="group flex items-center gap-3 border-t border-[#EDF0F5] py-3 -mx-4.5 px-4.5 transition-colors duration-200 first:border-t-0 hover:bg-cloud/60">
                            <span class="size-2 shrink-0 rounded-full" style="background: {{ $a['dot'] }}"></span>
                            <span class="min-w-0 flex-1 text-[13px] font-semibold text-ink">{{ $a['texte'] }}</span>
                            <span class="nudge-x shrink-0 text-[11.5px] text-[#9AA6B8] group-hover:text-accent">→</span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm text-[#5B677A]">Rien à traiter pour le moment.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Inscriptions par formation</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($parcoursRepartition as $p)
                    <div class="flex items-center gap-3.5 py-2">
                        <span class="w-[170px] shrink-0 text-[12.5px] text-ink">{{ $p['nom'] }}</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-[#EDF0F5]">
                            <div class="h-full rounded-full" style="width: {{ $p['pct'] }}%; background: {{ $p['color'] }}"></div>
                        </div>
                        <span class="w-[70px] shrink-0 text-right text-xs font-bold text-brand">{{ $p['membres'] }} inscrit{{ $p['membres'] > 1 ? 's' : '' }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-[#5B677A]">Les inscriptions aux formations apparaîtront ici.</p>
                @endforelse
            </div>
        </section>

        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Exporter les données</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-4 text-[13px] text-[#5B677A]">Téléchargez un jeu de données au format CSV (ouvrable directement dans Excel, Google Sheets ou LibreOffice).</p>
                <div class="flex flex-wrap gap-2.5">
                    @foreach ([
                        'members' => 'Membres', 'candidatures' => 'Candidatures', 'contacts' => 'Contacts',
                        'newsletter' => 'Newsletter', 'formations' => 'Formations', 'evenements' => 'Événements',
                        'opportunites' => 'Opportunités',
                    ] as $ds => $label)
                        <a href="{{ route('admin.export', $ds) }}" class="inline-flex items-center gap-2 rounded-[10px] border border-brand/15 bg-cloud/60 px-4 py-2.5 text-xs font-bold text-brand transition-all duration-200 ease-out hover:-translate-y-0.5 hover:border-azure/30 hover:bg-cloud hover:shadow-md active:scale-95">
                            <x-ui.icon name="download" class="size-3.5 text-azure" /> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>
