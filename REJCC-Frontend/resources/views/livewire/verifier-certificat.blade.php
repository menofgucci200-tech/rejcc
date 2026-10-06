<div>
    <section class="relative overflow-hidden bg-brand py-14 text-white sm:py-20">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60" style="background: repeating-linear-gradient(135deg, rgba(79,111,191,.08) 0 1px, transparent 1px 11px)"></div>
        <div class="relative mx-auto max-w-[920px] px-5 text-center">
            <p class="text-[11.5px] font-bold uppercase tracking-[0.3em] text-[#8FA3D9]">Registre officiel du REJCC</p>
            <h1 class="mt-3 text-[30px] font-extrabold leading-tight sm:text-[40px]">Vérifier un certificat</h1>
            <p class="mx-auto mt-3 max-w-xl text-[14.5px] leading-relaxed text-white/75">Le document imprimé ne fait pas foi : seules les informations de ce registre attestent qu'un certificat ou une attestation a bien été délivré par le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.</p>
            <form wire:submit="verifier" class="mx-auto mt-7 flex max-w-lg flex-col gap-2 sm:flex-row">
                <input wire:model="saisie" type="text" inputmode="text" autocomplete="off" maxlength="15" placeholder="Code de vérification (ex. : K7Q-M2X-9PA)" data-test="code-verification"
                    class="w-full rounded-full border border-white/20 bg-white px-5 py-3 text-center text-[15px] font-semibold uppercase tracking-[0.12em] text-brand placeholder:normal-case placeholder:tracking-normal placeholder:text-[#9AA6B8] sm:text-left" />
                <button type="submit" data-test="btn-verifier" class="btn-tap shrink-0 rounded-full bg-accent px-6 py-3 text-[14px] font-bold text-white hover:bg-accent-600">Vérifier</button>
            </form>
            @if ($erreur)
                <p class="mx-auto mt-3 max-w-lg rounded-xl bg-white/10 px-4 py-2 text-[12.5px] font-semibold text-white">{{ $erreur }}</p>
            @endif
            <p class="mt-3 text-[12px] text-white/55">Le code figure en haut à gauche du certificat. Vous pouvez aussi scanner son QR code.</p>
        </div>
    </section>

    <section class="bg-cloud py-10 sm:py-14">
        <div class="mx-auto max-w-[920px] space-y-6 px-5">
            @if ($resultat)
                @php $r = $resultat['resultat']; $c = $resultat['certificat'] ?? null; @endphp
                <article wire:key="resultat-{{ $r }}" data-test="resultat-verification" data-resultat="{{ $r }}" class="panel-enter overflow-hidden rounded-[22px] border border-brand/10 bg-white shadow-[0_24px_60px_-40px_rgba(3,29,89,.45)]">
                    @if ($r === 'valide')
                        <header class="flex items-start gap-4 bg-[#1C8F4C] px-6 py-5 text-white">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-ui.icon name="shield-check" class="size-6" /></span>
                            <div>
                                <p class="text-[19px] font-extrabold">Certificat authentique</p>
                                <p class="text-[13px] text-white/85">Ce document figure au registre officiel du REJCC et il est valide.</p>
                            </div>
                        </header>
                    @elseif ($r === 'revoque')
                        <header class="flex items-start gap-4 bg-accent px-6 py-5 text-white">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-ui.icon name="x" class="size-6" /></span>
                            <div>
                                <p class="text-[19px] font-extrabold">Certificat révoqué</p>
                                <p class="text-[13px] text-white/90">Il a été annulé le {{ \Illuminate\Support\Carbon::parse($c['revoque_at'])->locale('fr')->isoFormat('D MMMM YYYY') }} et ne doit plus être accepté.</p>
                            </div>
                        </header>
                    @elseif ($r === 'limite')
                        <header class="flex items-start gap-4 bg-[#B27007] px-6 py-5 text-white">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-ui.icon name="clock" class="size-6" /></span>
                            <div><p class="text-[19px] font-extrabold">Trop de vérifications</p><p class="text-[13px] text-white/90">Patientez une minute puis réessayez.</p></div>
                        </header>
                    @else
                        <header class="flex items-start gap-4 bg-accent px-6 py-5 text-white">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-ui.icon name="alert-circle" class="size-6" /></span>
                            <div>
                                <p class="text-[19px] font-extrabold">Code inconnu</p>
                                <p class="text-[13px] text-white/90">Aucun certificat ne correspond au code {{ $code }} dans le registre du REJCC. Vérifiez la saisie : un document présentant ce code n'est pas authentique.</p>
                            </div>
                        </header>
                    @endif

                    @if ($c)
                        <div class="px-6 py-6">
                            @if (($resultat['signature_qr'] ?? null) === false)
                                <p data-test="alerte-signature" class="mb-5 flex items-start gap-2 rounded-[12px] bg-accent/10 px-4 py-3 text-[13px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> Attention : la signature du QR code ne correspond pas à ce certificat. Le document présenté a pu être falsifié : fiez-vous uniquement aux informations ci-dessous.</p>
                            @endif
                            @if (($resultat['signature_qr'] ?? null) === true || ($c['signe_electroniquement'] ?? false))
                                <div class="mb-5 flex flex-wrap gap-2">
                                    @if (($resultat['signature_qr'] ?? null) === true)
                                        <span data-test="signature-ok" class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1 text-[12px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> QR code signé par le REJCC</span>
                                    @endif
                                    @if ($c['signe_electroniquement'] ?? false)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1 text-[12px] font-bold text-[#1C8F4C]"><x-ui.icon name="lock" class="size-3.5" /> PDF signé électroniquement</span>
                                    @endif
                                </div>
                            @endif
                            @if ($r === 'revoque' && $c['motif_revocation'])
                                <p class="mb-5 rounded-[12px] bg-cloud px-4 py-3 text-[13px] text-ink"><span class="font-bold text-brand">Motif :</span> {{ $c['motif_revocation'] }}
                                    @if ($c['remplace_par'])
                                        <a href="{{ route('verifier', ['code' => $c['remplace_par']]) }}" wire:navigate class="ml-1 font-bold text-azure underline">Voir le certificat en vigueur</a>
                                    @endif
                                </p>
                            @endif
                            <x-certificats.fiche :c="$c" />
                            <p class="mt-6 rounded-[12px] border border-brand/10 bg-cloud/60 px-4 py-3 text-[12.5px] leading-relaxed text-[#5B677A]">Comparez ces informations avec le document qui vous a été présenté : le nom, la formation et la date doivent être <strong class="text-brand">identiques</strong>. Une différence signifie que le document a été modifié.</p>
                            @if ($r === 'valide')
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <a href="{{ route('verifier.pdf', ['code' => $c['code']]) }}" target="_blank" rel="noopener" data-test="copie-officielle" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="file-text" class="size-4" /> Voir la copie officielle (PDF)</a>
                                </div>
                            @endif
                        </div>
                    @endif
                </article>
            @endif

            {{-- ══════════ Vérifier un fichier ══════════ --}}
            <article class="rounded-[22px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <p class="text-[16px] font-extrabold text-brand">Vous avez reçu le certificat en PDF ?</p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#5B677A]">Déposez-le ici : nous vérifions qu'il s'agit exactement du fichier délivré par le REJCC, sans la moindre modification. Le fichier est analysé sur place, il n'est ni transmis ni conservé.</p>
                        <p class="mt-1.5 text-[12px] leading-relaxed text-[#9AA6B8]">Les certificats du REJCC sont délivrés uniquement au format PDF : une image (PNG, JPG), une photo ou une capture d'écran n'a aucune valeur officielle. Vérifiez alors le code inscrit sur le document.</p>
                    </div>
                    <label class="btn-tap inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full border border-brand/15 bg-cloud px-5 py-3 text-[13px] font-bold text-brand hover:bg-cloud-200">
                        <x-ui.icon name="download" class="size-4 rotate-180" /> Déposer le PDF
                        <input type="file" wire:model="fichier" accept="application/pdf,.pdf" data-test="fichier-verification" class="hidden" />
                    </label>
                </div>
                <p wire:loading wire:target="fichier" class="mt-3 text-[12.5px] font-semibold text-azure">Analyse du fichier…</p>
                @error('fichier') <p class="mt-3 text-[12.5px] font-semibold text-accent">{{ $message }}</p> @enderror

                @if ($resultatFichier)
                    @php $rf = $resultatFichier['resultat']; $cf = $resultatFichier['certificat'] ?? null; @endphp
                    <div wire:key="fichier-{{ $rf }}" data-test="resultat-fichier" data-resultat="{{ $rf }}" class="panel-enter mt-5 rounded-[16px] border px-5 py-4 {{ $rf === 'intact' ? 'border-[#22A85A]/30 bg-[#22A85A]/[.06]' : 'border-accent/25 bg-accent/[.05]' }}">
                        <p class="flex items-center gap-2 text-[15px] font-extrabold {{ $rf === 'intact' ? 'text-[#1C8F4C]' : 'text-accent' }}">
                            <x-ui.icon :name="$rf === 'intact' ? 'shield-check' : 'alert-circle'" class="size-5" />
                            {{ match ($rf) {
                                'intact' => 'Fichier authentique et intact',
                                'revoque' => 'Fichier authentique, mais certificat révoqué',
                                'modifie' => 'Ce fichier a été modifié',
                                'limite' => 'Trop de vérifications, patientez une minute',
                                default => 'Ce fichier ne correspond à aucun certificat du REJCC',
                            } }}
                        </p>
                        <p class="mt-1 text-[12.5px] text-[#5B677A]">{{ match ($rf) {
                            'intact' => 'Il est identique, à l\'octet près, au document délivré par la plateforme.',
                            'revoque' => 'Ce document a été délivré par le REJCC mais il a depuis été annulé : il ne doit plus être accepté.',
                            'modifie' => 'Il ne correspond pas au fichier délivré pour ce certificat : fiez-vous uniquement aux informations du registre ci-dessus.',
                            'limite' => '',
                            default => 'Il n\'a pas été produit par la plateforme du REJCC, ou il a été modifié.',
                        } }}</p>
                        @if ($cf && $rf !== 'modifie')
                            <x-certificats.fiche :c="$cf" class="mt-4" />
                        @endif
                    </div>
                @endif
            </article>

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['shield-check', 'Un registre qui fait foi', 'Chaque certificat est inscrit au registre du REJCC avec un code unique, impossible à deviner.'],
                    ['lock', 'Un QR code signé', 'Le QR code porte une signature numérique : une copie fabriquée ou recopiée est détectée.'],
                    ['file-text', 'Un fichier scellé', 'L\'empreinte du PDF délivré est enregistrée : la moindre modification est repérée.'],
                ] as [$i, $t, $x])
                    <div class="rounded-[16px] border border-brand/10 bg-white p-4">
                        <x-ui.icon :name="$i" class="size-5 text-azure" />
                        <p class="mt-2 text-[13.5px] font-bold text-brand">{{ $t }}</p>
                        <p class="mt-1 text-[12px] leading-relaxed text-[#5B677A]">{{ $x }}</p>
                    </div>
                @endforeach
            </div>
            @if ($resultat['cle_publique'] ?? null)
                <p class="text-center text-[10.5px] text-[#9AA6B8]">Clé publique de signature (Ed25519) : <span class="font-mono break-all">{{ $resultat['cle_publique'] }}</span></p>
            @endif
        </div>
    </section>
</div>
