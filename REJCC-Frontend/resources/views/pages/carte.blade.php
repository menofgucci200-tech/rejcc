<x-site-layout :title="'Profil membre — '.trim(($card->prenom ?? '').' '.($card->nom ?? ''))" description="Profil professionnel vérifié d'un membre du REJCC — Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire.">
    @php
        $fullName = trim(($card->prenom ?? '').' '.($card->nom ?? '')) ?: ($card->name ?? 'Membre');
        $accent = match ($card->role ?? 'member') {
            'mentor' => '#F5A623',
            'admin' => '#4F6FBF',
            default => '#AC0100',
        };
        $infos = array_filter([
            'Profil' => $card->profil_label ?? null,
            'Domaine d\'activité' => $card->secteur ?? null,
            'Entreprise / projet' => $card->organisation ?? null,
            'Ville' => $card->ville ?? null,
            'Paroisse / Diocèse' => $card->paroisse ?? null,
        ]);
        $listings = collect($card->listings ?? []);
        $telephone = $card->telephone ?? null;
        $email = $card->email ?? null;
    @endphp

    <section class="bg-cloud py-14 sm:py-20">
        <div class="mx-auto max-w-[880px] px-5">

            {{-- ═════ En-tête : identité ═════ --}}
            <div class="overflow-hidden rounded-3xl border border-brand/10 bg-white shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)]">
                <div class="h-24 sm:h-28" style="background: linear-gradient(120deg, #0B1F52, #1D2556 55%, {{ $accent }} 160%)"></div>
                <div class="px-6 pb-6 sm:px-9 sm:pb-8">
                    <div class="-mt-12 flex flex-wrap items-end gap-4 sm:-mt-14">
                        @if ($card->photo ?? null)
                            <img src="{{ $card->photo }}" alt="Photo de {{ $fullName }}" class="size-24 shrink-0 rounded-2xl border-4 border-white object-cover shadow-lg sm:size-28">
                        @else
                            <div class="flex size-24 shrink-0 items-center justify-center rounded-2xl border-4 border-white text-3xl font-bold text-white shadow-lg sm:size-28" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                {{ mb_strtoupper(mb_substr($fullName, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1 pb-1">
                            <h1 class="text-xl font-extrabold text-brand sm:text-2xl">{{ $fullName }}</h1>
                            <p class="mt-0.5 text-[13px] font-bold uppercase tracking-[0.14em]" style="color: {{ $accent }}">{{ $card->role_label ?? 'Membre officiel' }}</p>
                        </div>
                        @if (! ($card->is_active ?? true))
                            <span class="mb-1 rounded-full bg-[#EEF1F5] px-3 py-1 text-[11px] font-bold text-[#9AA6B8]">Compte suspendu</span>
                        @endif
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

                    @if ($card->bio ?? null)
                        <p class="mt-5 max-w-2xl text-[14px] leading-relaxed text-ink/80">{{ $card->bio }}</p>
                    @endif

                    @if ($telephone || $email)
                        <div class="mt-5 flex flex-wrap gap-2.5">
                            @if ($telephone)
                                <a href="tel:{{ $telephone }}" class="inline-flex items-center gap-2 rounded-full bg-brand px-4.5 py-2.5 text-[12.5px] font-bold text-white transition-colors hover:bg-brand/90">
                                    <x-ui.icon name="phone" class="size-3.5" /> {{ $telephone }}
                                </a>
                            @endif
                            @if ($email)
                                <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4.5 py-2.5 text-[12.5px] font-bold text-brand transition-colors hover:bg-cloud">
                                    <x-ui.icon name="send" class="size-3.5" /> {{ $email }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═════ Informations professionnelles ═════ --}}
            @if ($infos)
                <div class="mt-6 rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
                    <h2 class="text-[15px] font-extrabold text-brand">Parcours &amp; activité</h2>
                    <div class="mb-5 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                    <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        @foreach ($infos as $label => $value)
                            <div class="border-b border-cloud-200 pb-3">
                                <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">{{ $label }}</dt>
                                <dd class="mt-1 text-[14px] font-semibold text-ink">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- ═════ Offres marketplace ═════ --}}
            @if ($listings->isNotEmpty())
                <div class="mt-6 rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-8">
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
                </div>
            @endif

            {{-- ═════ Références réseau ═════ --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-brand/10 bg-white px-6 py-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-8">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">N° membre</p>
                    <p class="mt-0.5 text-[14px] font-bold tracking-[0.06em] text-brand">{{ $card->numero ?? '—' }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-[11.5px] font-bold text-[#1C8F4C]">
                    <x-ui.icon name="shield-check" class="size-3.5" /> Profil vérifié — REJCC
                </span>
            </div>

            <p class="mt-6 text-center text-[12px] text-[#9AA6B8]">Profil membre vérifié sur rejcc.site — {{ now()->translatedFormat('j F Y') }}</p>
        </div>
    </section>
</x-site-layout>
