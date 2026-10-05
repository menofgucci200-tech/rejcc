<div>
    <x-admin-light.topbar title="Mentorat" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Programme de mentorat</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">Mentors, mises en relation, candidatures « Devenir mentor ».</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="ouvrirAttribution" data-test="ouvrir-attribution" class="btn-tap rounded-full border border-accent/30 bg-white px-4 py-1.5 text-xs font-bold text-accent hover:bg-accent/5">Attribuer un mentor</button>
                <a href="{{ route('admin.inscription') }}" wire:navigate class="rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600">+ Inscrire un mentor</a>
            </div>
        </div>

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Données du mentorat indisponibles (accès à la section « Mentors » requis).</p>
        @else
            {{-- Indicateurs --}}
            <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
                @foreach ([
                    ['Mentors', $stats['mentors'] ?? 0, '#AC0100'],
                    ['Mentorats en cours', $stats['en_cours'] ?? 0, '#1C8F4C'],
                    ['Demandes en attente', $stats['en_attente'] ?? 0, '#B27007'],
                    ['Sans réponse > 7 j', $stats['en_souffrance'] ?? 0, ($stats['en_souffrance'] ?? 0) ? '#AC0100' : '#9AA6B8'],
                    ['Candidatures', $stats['candidatures'] ?? 0, '#4F6FBF'],
                ] as [$label, $val, $color])
                    <div class="rounded-[14px] border border-brand/10 bg-white px-4 py-3 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <p class="text-[22px] font-extrabold leading-none" style="color: {{ $color }}">{{ $val }}</p>
                        <p class="mt-1 text-[11.5px] text-[#5B677A]">{{ $label }}</p>
                    </div>
                @endforeach
            </div>

            @if ($message)
                <p data-test="message-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
            @endif
            @if ($erreur)
                <p data-test="erreur-admin" role="alert" class="panel-enter mb-4 flex items-start gap-2 rounded-[12px] border border-accent/20 bg-accent/5 px-4 py-2.5 text-[12.5px] font-semibold text-accent"><x-ui.icon name="alert-circle" class="mt-px size-4 shrink-0" /> {{ $erreur }}</p>
            @endif

            {{-- Attribution manuelle --}}
            @if ($formAttribution)
                <div data-test="form-attribution" class="panel-enter mb-5 grid gap-3 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] md:grid-cols-2">
                    <p class="text-sm font-bold text-brand md:col-span-2">Mettre en relation un membre et un mentor</p>
                    <div>
                        <label for="attr-mentor" class="mb-1 block text-xs font-semibold text-[#5B677A]">Mentor</label>
                        <select id="attr-mentor" wire:model="mentorId" class="w-full rounded-[9px] border border-brand/15 py-2 pl-3 pr-9 text-sm outline-none focus:border-azure">
                            <option value="">Choisir…</option>
                            @foreach ($mentors->where('actif', true) as $m)
                                <option value="{{ $m['id'] }}">{{ $m['nom'] }} ({{ $m['en_cours'] }}/{{ $m['capacite'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="attr-membre" class="mb-1 block text-xs font-semibold text-[#5B677A]">Membre à accompagner</label>
                        <select id="attr-membre" wire:model="mentoreId" class="w-full rounded-[9px] border border-brand/15 py-2 pl-3 pr-9 text-sm outline-none focus:border-azure">
                            <option value="">Choisir…</option>
                            @foreach ($membres as $u)
                                <option value="{{ $u['id'] }}">{{ $u['nom'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="attr-objectif" class="mb-1 block text-xs font-semibold text-[#5B677A]">Objectif du mentorat</label>
                        <input id="attr-objectif" wire:model="objectif" type="text" maxlength="200" placeholder="Ex : préparer le salon de l'agriculture" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div class="flex gap-2 md:col-span-2">
                        <button wire:click="attribuer" wire:loading.attr="disabled" data-test="valider-attribution" class="btn-tap rounded-[9px] bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60">Créer le mentorat</button>
                        <button wire:click="$set('formAttribution', false)" class="btn-tap rounded-[9px] border border-brand/15 px-5 py-2 text-sm font-bold text-brand hover:bg-cloud">Annuler</button>
                    </div>
                </div>
            @endif

            {{-- Onglets --}}
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach (['mentors' => 'Mentors', 'relations' => 'Mises en relation', 'candidatures' => 'Candidatures'.(($stats['candidatures'] ?? 0) ? ' ('.$stats['candidatures'].')' : '')] as $cle => $label)
                    <button wire:click="setOnglet('{{ $cle }}')" data-test="onglet-{{ $cle }}" class="btn-tap rounded-full border px-4 py-1.5 text-xs font-semibold {{ $onglet === $cle ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
                @endforeach
            </div>

            @if ($onglet === 'mentors')
                <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($mentors as $m)
                        <div data-test="ligne-mentor" class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0 {{ $m['actif'] ? '' : 'opacity-55' }}">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #AC0100, #D95B5A)">{{ mb_strtoupper(mb_substr($m['nom'], 0, 1)) }}</span>
                            <div class="min-w-[220px] flex-1">
                                <p class="text-[13.5px] font-bold text-brand">{{ $m['nom'] }} @unless ($m['accepte'])<span class="ml-1 rounded-full bg-cloud px-2 py-0.5 text-[10px] font-bold text-[#9AA6B8]">Complet / pause</span>@endunless</p>
                                <p class="text-xs text-[#5B677A]">{{ $m['email'] }}{{ $m['telephone'] ? ' · '.$m['telephone'] : '' }}{{ $m['ville'] ? ' · '.$m['ville'] : '' }}</p>
                                @if ($m['expertises'])
                                    <p class="mt-0.5 text-[11px] font-semibold text-accent">{{ implode(' · ', $m['expertises']) }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-4 text-center text-[11px] text-[#5B677A]">
                                <div><p class="text-[15px] font-extrabold text-[#1C8F4C]">{{ $m['en_cours'] }}/{{ $m['capacite'] }}</p>en cours</div>
                                <div><p class="text-[15px] font-extrabold text-[#B27007]">{{ $m['en_attente'] }}</p>en attente</div>
                                <div><p class="text-[15px] font-extrabold text-brand">{{ $m['termines'] }}</p>terminés</div>
                                <div><p class="text-[15px] font-extrabold text-[#B27007]">{{ $m['stats']['note_moyenne'] ? '★ '.number_format($m['stats']['note_moyenne'], 1, ',', '') : '—' }}</p>avis</div>
                            </div>
                            <div class="flex shrink-0 gap-1.5">
                                <button wire:click="ouvrirAttribution({{ $m['id'] }})" class="rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Attribuer</button>
                                <a href="{{ route('admin.members', ['role' => 'mentor', 'q' => $m['email']]) }}" wire:navigate class="rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Compte</a>
                            </div>
                        </div>
                    @empty
                        <p class="py-12 text-center text-sm text-[#5B677A]">Aucun mentor pour le moment. Inscrivez-en un ou validez une candidature.</p>
                    @endforelse
                </div>
            @elseif ($onglet === 'relations')
                <div class="mb-3 flex flex-wrap gap-2">
                    @foreach (['tous' => 'Toutes', 'en_attente' => 'En attente', 'accepte' => 'En cours', 'termine' => 'Terminées', 'refuse' => 'Non retenues'] as $cle => $label)
                        <button wire:click="$set('filtreStatut', '{{ $cle }}')" class="rounded-full border px-3 py-1 text-[11.5px] font-semibold {{ $filtreStatut === $cle ? 'border-accent bg-accent text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="overflow-x-auto rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <table class="w-full min-w-[760px] text-left text-[12.5px]">
                        <thead class="border-b border-[#EDF0F5] text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">
                            <tr><th class="px-4 py-3">Mentor</th><th class="px-4 py-3">Mentoré·e</th><th class="px-4 py-3">Objectif</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Séances</th><th class="px-4 py-3">Avis</th><th class="px-4 py-3">Depuis</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($relations as $r)
                                <tr data-test="ligne-relation" class="border-t border-[#EDF0F5] {{ $r['en_souffrance'] ? 'bg-accent/[.03]' : '' }}">
                                    <td class="px-4 py-3 font-semibold text-brand">{{ $r['mentor'] }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $r['mentore'] }}</td>
                                    <td class="max-w-[260px] px-4 py-3 text-[#5B677A]">{{ $r['objectif'] }} @if ($r['cree_par_admin'])<span class="ml-1 rounded-full bg-azure/10 px-1.5 py-0.5 text-[9.5px] font-bold text-azure">ÉQUIPE</span>@endif</td>
                                    <td class="px-4 py-3"><x-mentorat.statut :statut="$r['statut']" :label="$r['statut_label']" />@if ($r['en_souffrance'])<span class="mt-1 block text-[10.5px] font-bold text-accent">Sans réponse depuis plus de 7 jours</span>@endif</td>
                                    <td class="px-4 py-3 text-center">{{ $r['seances'] }}</td>
                                    <td class="px-4 py-3 text-[#B27007]">{{ $r['note'] ? str_repeat('★', $r['note']) : '—' }}</td>
                                    <td class="px-4 py-3 text-[#9AA6B8]">{{ \Carbon\Carbon::parse($r['cree_le'])->translatedFormat('j M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-[#5B677A]">Aucune mise en relation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @forelse ($candidatures as $c)
                        <article data-test="candidature" wire:key="cand-{{ $c['id'] }}" class="rounded-[16px] border bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $c['statut'] === 'en_attente' ? 'border-azure/30' : 'border-brand/10 opacity-75' }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-[14px] font-bold text-brand">{{ $c['nom'] }}</p>
                                    <p class="text-[12px] text-[#5B677A]">{{ collect([$c['titre'], $c['email'], $c['ville']])->filter()->join(' · ') }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ ['en_attente' => 'bg-[#F5A623]/15 text-[#8A5A00]', 'acceptee' => 'bg-[#22A85A]/10 text-[#1C8F4C]', 'refusee' => 'bg-cloud text-[#5B677A]'][$c['statut']] ?? '' }}">{{ $c['statut_label'] }} · {{ \Carbon\Carbon::parse($c['cree_le'])->translatedFormat('j M Y') }}</span>
                            </div>
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($c['expertises'] as $e)
                                    <span class="rounded-full bg-accent/[.06] px-2.5 py-1 text-[11px] font-semibold text-accent">{{ $e }}</span>
                                @endforeach
                            </div>
                            <p class="mt-3 whitespace-pre-line text-[12.5px] text-ink"><span class="font-semibold text-brand">Expérience :</span> {{ $c['experience'] }}</p>
                            <p class="mt-1.5 whitespace-pre-line text-[12.5px] text-ink"><span class="font-semibold text-brand">Motivation :</span> {{ $c['motivation'] }}</p>
                            @if ($c['disponibilites'])
                                <p class="mt-1.5 text-[12px] text-[#5B677A]">Disponibilités : {{ $c['disponibilites'] }}</p>
                            @endif
                            @if ($c['statut'] === 'en_attente')
                                <textarea wire:model="reponses.{{ $c['id'] }}" rows="2" maxlength="1000" placeholder="Message au membre (obligatoire en cas de refus)" class="mt-3 w-full rounded-[9px] border border-brand/15 px-3 py-2 text-[12.5px] outline-none focus:border-azure"></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="accepterCandidature({{ $c['id'] }})" wire:confirm="Faire de {{ $c['nom'] }} un mentor du réseau ?" data-test="accepter-candidature" class="btn-tap rounded-[9px] bg-[#1C8F4C] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]/90">Accepter</button>
                                    <button wire:click="refuserCandidature({{ $c['id'] }})" data-test="refuser-candidature" class="btn-tap rounded-[9px] border border-[#C9D3E6] px-4 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Ne pas retenir</button>
                                </div>
                            @elseif ($c['reponse'])
                                <p class="mt-2 text-[12px] italic text-[#5B677A]">Réponse : « {{ $c['reponse'] }} »</p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-[18px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucune candidature pour le moment.</p>
                    @endforelse
                </div>
            @endif
        @endif
    </div>
</div>
