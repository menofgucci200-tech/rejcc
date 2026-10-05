<x-site-layout :title="($card->locked ?? false) ? 'Carte membre REJCC' : 'Profil membre — '.trim(($card->prenom ?? '').' '.($card->nom ?? ''))" description="Page biographique vérifiée d'un membre du REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.">
    @php
        $fullName = trim(($card->prenom ?? '').' '.($card->nom ?? '')) ?: ($card->name ?? 'Membre');
        $accent = match ($card->role ?? 'member') {
            'mentor' => '#F5A623',
            'admin' => '#4F6FBF',
            default => '#AC0100',
        };
        $activite = array_filter([
            'Entreprise / projet' => $card->organisation ?? null,
            'Domaine d\'activité' => $card->secteur ?? null,
            'Profil' => $card->profil_label ?? null,
            'Ville' => $card->ville ?? null,
        ]);
        $foi = array_filter([
            'Paroisse' => $card->paroisse ?? null,
            'Diocèse' => $card->diocese ?? null,
        ]);
        $competences = collect($card->competences ?? []);
        $parcours = collect($card->parcours ?? []);
        $certificats = collect($card->certificats ?? []);
        $groupes = collect($card->groupes ?? []);
        $projets = collect($card->projets ?? []);
        $listings = collect($card->listings ?? []);
        $liens = array_filter((array) ($card->liens ?? []));
        $engagement = (array) ($card->engagement ?? []);
        $telephone = $card->telephone ?? null;
        $email = $card->email ?? null;
        $valable = ($card->valable_jusqu ?? null) ? \Carbon\Carbon::parse($card->valable_jusqu)->translatedFormat('j F Y') : null;
        $libellesLiens = ['site' => ['Site web', 'globe'], 'linkedin' => ['LinkedIn', 'external-link'], 'facebook' => ['Facebook', 'external-link'], 'instagram' => ['Instagram', 'external-link']];
    @endphp

    @if ($card->locked ?? false)
        <section class="bg-cloud pb-20 pt-28">
            <div class="mx-auto max-w-[520px] px-5 text-center">
                <div class="rounded-3xl border border-brand/10 bg-white p-10 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)]">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#F5A623]/10">
                        <x-ui.icon name="shield" class="size-6 text-[#B27007]" />
                    </div>
                    <h1 data-test="carte-indisponible" class="mt-5 text-lg font-extrabold text-brand">Carte non valide actuellement</h1>
                    <p class="mt-2 text-[13.5px] leading-relaxed text-[#5B677A]">
                        Ce titulaire n'est pas à jour de son abonnement annuel au REJCC : sa carte de membre n'est pas valide en ce moment. Elle le redeviendra dès le renouvellement de l'abonnement.
                    </p>
                </div>
            </div>
        </section>
    @else
    {{-- pt : laisse la place à la barre de navigation fixe du site --}}
    <section class="bg-cloud pb-10 pt-24 sm:pb-16 sm:pt-28">
        <div class="mx-auto max-w-[1040px] px-4 sm:px-5">

            {{-- ═════ En-tête : identité ═════ --}}
            <header data-test="bio-entete" class="overflow-hidden rounded-3xl border border-brand/10 bg-white shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)]">
                <div class="relative h-28 sm:h-36" style="background: linear-gradient(120deg, #0B1F52, #1D2556 55%, {{ $accent }} 160%)">
                    <img src="{{ asset('brand/rejcc-monogram-white.png') }}" alt="" aria-hidden="true" class="absolute -right-6 -top-8 w-44 opacity-[.07]">
                    <span data-test="statut-public" class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-[11.5px] font-bold text-[#1C8F4C] shadow-sm sm:right-6 sm:top-5">
                        <x-ui.icon name="shield-check" class="size-3.5" /> Membre à jour{{ $valable ? ' — valable jusqu\'au '.$valable : '' }}
                    </span>
                </div>
                {{-- relative : passe au-dessus du bandeau (positionné) qu'il chevauche --}}
                <div class="relative px-5 pb-6 sm:px-9 sm:pb-8">
                    {{-- La photo chevauche le bandeau ; le nom reste dessous, sur fond blanc. --}}
                    <div class="-mt-14 sm:-mt-16">
                        @if ($card->photo ?? null)
                            <img src="{{ $card->photo }}" alt="Photo de {{ $fullName }}" class="size-28 rounded-2xl border-4 border-white object-cover shadow-lg sm:size-32">
                        @else
                            <div class="flex size-28 items-center justify-center rounded-2xl border-4 border-white text-3xl font-bold text-white shadow-lg sm:size-32" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                {{ mb_strtoupper(mb_substr($card->prenom ?? $fullName, 0, 1).mb_substr($card->nom ?? '', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="mt-3">
                        <h1 class="text-2xl font-extrabold leading-tight text-brand sm:text-[30px]">{{ $fullName }}</h1>
                        @if ($card->titre ?? null)
                            <p data-test="bio-titre" class="mt-1 text-[14.5px] font-semibold text-ink/80">{{ $card->titre }}</p>
                        @endif
                        <p class="mt-1 text-[12px] font-bold uppercase tracking-[0.14em]" style="color: {{ $accent }}">{{ $card->role_label ?? 'Membre officiel' }} · REJCC</p>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[12.5px] text-[#5B677A]">
                        @if ($card->ville ?? null)
                            <span class="inline-flex items-center gap-1.5"><x-ui.icon name="map-pin" class="size-3.5 text-azure" /> {{ $card->ville }}</span>
                        @endif
                        @if ($card->secteur ?? null)
                            <span class="inline-flex items-center gap-1.5"><x-ui.icon name="target" class="size-3.5 text-azure" /> {{ $card->secteur }}</span>
                        @endif
                        @if ($card->membre_depuis ?? null)
                            <span class="inline-flex items-center gap-1.5"><x-ui.icon name="calendar" class="size-3.5 text-azure" /> Membre depuis le {{ $card->membre_depuis }}</span>
                        @endif
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2.5">
                        @if ($telephone)
                            <a href="tel:{{ $telephone }}" class="inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2.5 text-[12.5px] font-bold text-white transition-colors hover:bg-brand/90">
                                <x-ui.icon name="phone" class="size-3.5" /> {{ $telephone }}
                            </a>
                        @endif
                        @if ($email)
                            <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2.5 text-[12.5px] font-bold text-brand transition-colors hover:bg-cloud">
                                <x-ui.icon name="send" class="size-3.5" /> {{ $email }}
                            </a>
                        @endif
                        <a href="{{ url('/carte/'.$card->code.'/contact.vcf') }}" data-test="vcard" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2.5 text-[12.5px] font-bold text-brand transition-colors hover:bg-cloud">
                            <x-ui.icon name="user-plus" class="size-3.5" /> Enregistrer le contact
                        </a>
                        <button type="button" x-data="{ ok: false }" @click="navigator.share ? navigator.share({ title: @js($fullName.' — REJCC'), url: location.href }) : navigator.clipboard.writeText(location.href).then(() => { ok = true; setTimeout(() => ok = false, 2000) })"
                            class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2.5 text-[12.5px] font-bold text-brand transition-colors hover:bg-cloud">
                            <x-ui.icon name="external-link" class="size-3.5" /> <span x-text="ok ? 'Lien copié !' : 'Partager'">Partager</span>
                        </button>
                        @foreach ($liens as $k => $url)
                            @if (isset($libellesLiens[$k]))
                                <a href="{{ $url }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 rounded-full bg-azure/10 px-4 py-2.5 text-[12.5px] font-bold text-azure transition-colors hover:bg-azure/20">
                                    <x-ui.icon :name="$libellesLiens[$k][1]" class="size-3.5" /> {{ $libellesLiens[$k][0] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </header>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1.6fr_1fr]">
                {{-- ═════ Colonne principale ═════ --}}
                <div class="flex min-w-0 flex-col gap-6">
                    @if ($card->bio ?? null)
                        <section data-test="bio-apropos" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                            <h2 class="text-[15px] font-extrabold text-brand">À propos</h2>
                            <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <p class="whitespace-pre-line text-[14.5px] leading-relaxed text-ink/85">{{ $card->bio }}</p>
                        </section>
                    @endif

                    @if ($parcours->isNotEmpty())
                        <section data-test="bio-parcours" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                            <h2 class="text-[15px] font-extrabold text-brand">Parcours</h2>
                            <div class="mb-5 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <ol class="relative ml-1.5 border-l-2 border-cloud-200">
                                @foreach ($parcours as $etape)
                                    <li class="relative pb-5 pl-6 last:pb-0">
                                        <span class="absolute -left-[7px] top-1.5 size-3 rounded-full border-2 border-white" style="background: {{ $accent }}"></span>
                                        @if ($etape['periode'] ?? null)
                                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $etape['periode'] }}</p>
                                        @endif
                                        <p class="text-[14px] font-bold text-brand">{{ $etape['titre'] }}</p>
                                        @if ($etape['structure'] ?? null)
                                            <p class="text-[13px] text-[#5B677A]">{{ $etape['structure'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    @if ($projets->isNotEmpty())
                        <section data-test="bio-projets" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                            <h2 class="text-[15px] font-extrabold text-brand">Projets</h2>
                            <div class="mb-5 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                @foreach ($projets as $pr)
                                    <div class="rounded-2xl border border-brand/10 bg-cloud/40 p-4">
                                        <span class="rounded-full bg-azure/10 px-2.5 py-0.5 text-[10.5px] font-bold text-azure">{{ $pr['status'] }}</span>
                                        <p class="mt-2 text-[13.5px] font-bold text-brand">{{ $pr['title'] }}</p>
                                        @if ($pr['description'] ?? null)
                                            <p class="mt-1 line-clamp-3 text-[12.5px] text-[#5B677A]">{{ $pr['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($listings->isNotEmpty())
                        <section data-test="bio-offres" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                            <h2 class="text-[15px] font-extrabold text-brand">Services &amp; produits proposés</h2>
                            <div class="mb-5 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                @foreach ($listings as $l)
                                    <div class="rounded-2xl border border-brand/10 bg-cloud/40 p-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold uppercase tracking-[0.08em] {{ ($l['type'] ?? '') === 'produit' ? 'bg-[#F5A623]/15 text-[#B27007]' : 'bg-azure/10 text-azure' }}">{{ ($l['type'] ?? '') === 'produit' ? 'Produit' : 'Service' }}</span>
                                            @if ($l['price'] ?? null)
                                                <span class="text-[12px] font-bold text-brand">{{ $l['price'] }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-[13.5px] font-bold text-brand">{{ $l['title'] }}</p>
                                        <p class="text-[11.5px] font-semibold text-[#9AA6B8]">{{ $l['category'] }}</p>
                                        @if ($l['description'] ?? null)
                                            <p class="mt-1.5 line-clamp-2 text-[12.5px] text-[#5B677A]">{{ $l['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($activite)
                        <section class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                            <h2 class="text-[15px] font-extrabold text-brand">Activité</h2>
                            <div class="mb-5 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                @foreach ($activite as $label => $value)
                                    <div class="border-b border-cloud-200 pb-3">
                                        <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $label }}</dt>
                                        <dd class="mt-1 text-[14px] font-semibold text-ink">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                </div>

                {{-- ═════ Colonne latérale ═════ --}}
                <aside class="flex min-w-0 flex-col gap-6">
                    <section data-test="bio-engagement" class="rounded-3xl bg-brand p-6 text-white shadow-[0_12px_32px_rgba(3,29,89,.22)]">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#8FA3D9]">Engagement dans le réseau</p>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            @foreach ([['formations_terminees', 'Formations terminées'], ['certificats', 'Certificats'], ['evenements', 'Événements']] as [$k, $label])
                                <div class="rounded-2xl bg-white/[.08] px-1.5 py-3">
                                    <p class="text-2xl font-extrabold leading-none">{{ (int) ($engagement[$k] ?? 0) }}</p>
                                    <p class="mt-1.5 text-[10.5px] leading-tight text-[#C4D0EC]">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if ($competences->isNotEmpty())
                        <section data-test="bio-competences" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <h2 class="text-[15px] font-extrabold text-brand">Compétences</h2>
                            <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($competences as $c)
                                    <span class="rounded-full bg-azure/10 px-3 py-1.5 text-[12px] font-semibold text-azure">{{ $c }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($certificats->isNotEmpty())
                        <section data-test="bio-certificats" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <h2 class="text-[15px] font-extrabold text-brand">Certificats REJCC</h2>
                            <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <ul class="flex flex-col gap-3">
                                @foreach ($certificats as $ce)
                                    <li class="flex gap-3">
                                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#F5A623]/15 text-[#B27007]"><x-ui.icon name="award" class="size-4" /></span>
                                        <span class="min-w-0">
                                            <span class="block text-[13px] font-bold text-brand">{{ $ce['titre'] }}</span>
                                            <span class="block text-[11.5px] text-[#9AA6B8]">{{ \Carbon\Carbon::parse($ce['obtenu_le'])->translatedFormat('F Y') }} · {{ $ce['reference'] }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($groupes->isNotEmpty())
                        <section data-test="bio-groupes" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <h2 class="text-[15px] font-extrabold text-brand">Groupes sectoriels</h2>
                            <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <ul class="flex flex-col gap-2.5">
                                @foreach ($groupes as $g)
                                    <li>
                                        <p class="text-[13px] font-bold text-brand">{{ $g['nom'] }}</p>
                                        @if ($g['specialite'] ?? null)
                                            <p class="text-[12px] text-[#5B677A]">{{ $g['specialite'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($foi)
                        <section data-test="bio-foi" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <h2 class="text-[15px] font-extrabold text-brand">Vie de foi</h2>
                            <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <dl class="flex flex-col gap-3">
                                @foreach ($foi as $label => $value)
                                    <div>
                                        <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $label }}</dt>
                                        <dd class="mt-0.5 text-[13.5px] font-semibold text-ink">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                </aside>
            </div>

            {{-- ═════ Références réseau ═════ --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-brand/10 bg-white px-6 py-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-8">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">N° membre</p>
                    <p class="mt-0.5 text-[14px] font-bold tracking-[0.06em] text-brand">{{ $card->numero ?? '—' }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-[11.5px] font-bold text-[#1C8F4C]">
                    <x-ui.icon name="shield-check" class="size-3.5" /> Membre vérifié par le REJCC
                </span>
            </div>

            <p class="mt-6 text-center text-[12px] text-[#9AA6B8]">Informations publiées par le membre et vérifiées par le REJCC — consultées le {{ now()->translatedFormat('j F Y') }}</p>
        </div>
    </section>
    @endif
</x-site-layout>
