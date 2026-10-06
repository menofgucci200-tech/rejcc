<div>
    <x-member-light.topbar title="Certificats" />

    @php
        $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->locale('fr')->isoFormat('D MMMM YYYY') : '';
        $labels = ['formation' => ['certificat', 'certificats'], 'evenement' => ["attestation d'événement", "attestations d'événement"], 'parcours' => ['attestation de parcours', 'attestations de parcours']];
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Mes certificats &amp; attestations</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-xl text-xs text-[#5B677A]">Vos documents officiels, délivrés et vérifiables par le registre du REJCC. Partagez leur lien de vérification avec un recruteur ou un partenaire.</p>
            </div>
            @if ($parType->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($parType as $type => $n)
                        <span class="rounded-full bg-white px-3 py-1.5 text-[11.5px] font-bold text-brand shadow-[0_1px_3px_rgba(3,29,89,.08)]">{{ $n }} {{ $labels[$type][$n > 1 ? 1 : 0] ?? $type }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($certificats->isEmpty())
            <div class="rounded-[18px] border border-dashed border-brand/20 bg-white px-6 py-12 text-center">
                <x-ui.icon name="award" class="mx-auto mb-3 size-10 text-[#9AA6B8]" />
                <p class="text-sm font-bold text-brand">Vous n'avez pas encore de certificat</p>
                <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-[#5B677A]">Ils sont délivrés automatiquement quand vous réussissez l'examen d'une formation certifiante, terminez un parcours, ou participez à un événement qui délivre une attestation.</p>
                <a href="{{ route('espace-membre.catalogue') }}" wire:navigate class="btn-tap mt-4 inline-flex rounded-full bg-brand px-5 py-2.5 text-xs font-bold text-white">Voir les formations certifiantes</a>
            </div>
        @else
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(min(280px, 100%), 1fr))">
                @foreach ($certificats as $c)
                    <button type="button" wire:click="ouvrir({{ $c['id'] }})" wire:key="cert-{{ $c['id'] }}" data-test="carte-certificat"
                        class="card-hover overflow-hidden rounded-[16px] border border-brand/10 bg-white p-3 text-left shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <x-certificats.miniature :c="$c" />
                        <div class="px-1 pb-1 pt-3">
                            <p class="text-[13.5px] font-bold leading-snug text-brand">{{ $c['titre'] }}</p>
                            <p class="mt-0.5 text-[11.5px] text-[#5B677A]">{{ $c['intitule'] }} · {{ $date($c['delivre_le']) }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @if ($c['statut'] === 'valide')
                                    <span class="rounded-full bg-[#22A85A]/10 px-2 py-0.5 text-[10.5px] font-bold text-[#1C8F4C]">Valide</span>
                                @else
                                    <span class="rounded-full bg-accent/10 px-2 py-0.5 text-[10.5px] font-bold text-accent">Révoqué</span>
                                @endif
                                @if ($c['visible_bio'])<span class="rounded-full bg-azure/10 px-2 py-0.5 text-[10.5px] font-bold text-azure">Sur ma page publique</span>@endif
                                @if ($c['verifications'])<span class="text-[10.5px] text-[#9AA6B8]">vérifié {{ $c['verifications'] }} fois</span>@endif
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ══════════ Détail d'un certificat ══════════ --}}
    @if ($cert)
        @php $valide = $cert['statut'] === 'valide'; @endphp
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/50 p-3 sm:p-6" wire:click.self="fermer" x-on:keydown.escape.window="$wire.fermer()">
            <div data-test="detail-certificat" role="dialog" aria-modal="true" class="panel-enter flex h-full max-h-[94vh] w-full max-w-[1100px] flex-col overflow-hidden rounded-[18px] bg-white shadow-2xl lg:flex-row">
                <div class="relative min-h-[240px] flex-1 bg-[#E9EDF4] lg:min-h-0">
                    @if ($valide)
                        <iframe src="{{ route('espace-membre.certificats.pdf', $cert['id']) }}#view=FitH&toolbar=0" title="{{ $cert['intitule'] }}" data-test="apercu-certificat" class="absolute inset-0 h-full w-full border-0"></iframe>
                    @else
                        <div class="flex h-full items-center justify-center p-6"><x-certificats.miniature :c="$cert" class="w-full max-w-lg" /></div>
                    @endif
                </div>
                <aside class="flex w-full shrink-0 flex-col overflow-y-auto border-t border-[#EDF0F5] lg:w-[360px] lg:border-l lg:border-t-0">
                    <div class="flex items-start gap-3 border-b border-[#EDF0F5] px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $cert['intitule'] }}</p>
                            <p class="mt-0.5 text-[15px] font-bold leading-snug text-brand">{{ $cert['titre'] }}</p>
                            <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $date($cert['delivre_le']) }} · {{ $cert['reference'] }}</p>
                        </div>
                        <button type="button" wire:click="fermer" aria-label="Fermer" class="flex size-9 shrink-0 items-center justify-center rounded-full text-brand hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        @if ($message)
                            <p class="flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3 py-2.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
                        @endif
                        @if (! $valide)
                            <p class="rounded-[12px] bg-accent/10 px-3 py-2.5 text-[12.5px] text-accent"><span class="font-bold">Certificat révoqué</span>{{ $cert['motif_revocation'] ? ' — '.$cert['motif_revocation'] : '' }}</p>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('espace-membre.certificats.pdf', ['id' => $cert['id'], 'telecharger' => 1]) }}" data-test="telecharger-certificat" class="btn-tap inline-flex items-center justify-center gap-1.5 rounded-full bg-brand px-3 py-2.5 text-[12.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-4" /> Télécharger</a>
                                <a href="{{ \App\Livewire\Member\Certificats::lienLinkedin($cert) }}" target="_blank" rel="noopener" data-test="linkedin" class="btn-tap inline-flex items-center justify-center gap-1.5 rounded-full bg-[#0A66C2] px-3 py-2.5 text-[12.5px] font-bold text-white hover:bg-[#0A66C2]/90"><x-ui.icon name="linkedin" class="size-4" /> LinkedIn</a>
                            </div>

                            <div class="rounded-[14px] border border-brand/10 p-3.5">
                                <p class="text-[12px] font-bold text-brand">Lien de vérification</p>
                                <p class="mt-0.5 text-[11.5px] text-[#5B677A]">À joindre à un CV ou une candidature : il prouve l'authenticité du document.</p>
                                <div class="mt-2 flex items-center gap-2" x-data="{ copie: false }">
                                    <input type="text" readonly value="{{ $cert['url_verification'] }}" data-test="lien-verification" class="min-w-0 flex-1 rounded-[9px] border border-brand/15 bg-cloud/50 px-2.5 py-1.5 text-[11.5px] text-brand" x-on:focus="$el.select()" />
                                    <button type="button" x-on:click="navigator.clipboard.writeText(@js($cert['url_verification'])); copie = true; rjToast('Lien copié.'); setTimeout(() => copie = false, 1800)" class="btn-tap shrink-0 rounded-[9px] bg-brand/[.07] px-3 py-1.5 text-[11.5px] font-bold text-brand" x-text="copie ? 'Copié' : 'Copier'">Copier</button>
                                </div>
                                <p class="mt-2 text-[11px] text-[#9AA6B8]">Code : <span class="font-semibold tracking-wider text-brand">{{ $cert['code'] }}</span>{{ $cert['verifications'] ? ' · vérifié '.$cert['verifications'].' fois' : '' }}</p>
                            </div>

                            <label class="flex items-start gap-3 rounded-[14px] border border-brand/10 p-3.5" x-data="{ v: @js($cert['visible_bio']) }">
                                <input type="checkbox" x-model="v" x-on:change="$wire.basculerBio({{ $cert['id'] }}, v)" data-test="visible-bio" class="mt-0.5 size-4 rounded border-brand/25 text-brand" />
                                <span><span class="block text-[12.5px] font-bold text-brand">Afficher sur ma page publique</span><span class="block text-[11.5px] text-[#5B677A]">La page ouverte en scannant votre carte membre, avec le lien de vérification.</span></span>
                            </label>

                            @if ($cert['correction_demandee'])
                                <p class="rounded-[12px] bg-[#F5A623]/12 px-3 py-2.5 text-[12px] text-[#8A5A00]"><span class="font-bold">Correction demandée</span> le {{ $date($cert['correction_demandee_at']) }} : « {{ $cert['correction_demandee'] }} »</p>
                            @elseif ($correction)
                                <div class="rounded-[14px] border border-brand/10 p-3.5">
                                    <p class="text-[12.5px] font-bold text-brand">Signaler une erreur</p>
                                    <textarea wire:model="messageCorrection" rows="3" data-test="message-correction" placeholder="Ex. : mon nom s'écrit « Traoré-Koné »" class="mt-2 w-full rounded-[10px] border border-brand/15 px-3 py-2 text-[12.5px]"></textarea>
                                    @if ($erreur)<p class="mt-1 text-[11.5px] font-semibold text-accent">{{ $erreur }}</p>@endif
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="envoyerCorrection" data-test="envoyer-correction" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white">Envoyer</button>
                                        <button wire:click="$set('correction', false)" class="btn-tap rounded-full px-3 py-2 text-[12px] font-bold text-[#5B677A]">Annuler</button>
                                    </div>
                                </div>
                            @else
                                <button wire:click="$set('correction', true)" data-test="signaler-erreur" class="text-[12px] font-semibold text-[#5B677A] underline-offset-2 hover:text-brand hover:underline">Une erreur sur votre certificat ? Signalez-la</button>
                            @endif
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    @endif
</div>
