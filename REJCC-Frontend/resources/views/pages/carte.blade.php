<x-site-layout :title="'Carte membre — '.trim(($card->prenom ?? '').' '.($card->nom ?? ''))" description="Carte membre officielle du REJCC — Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire.">
    <section class="bg-cloud py-24 sm:py-32">
        <div class="mx-auto max-w-md px-6">
            <article class="overflow-hidden rounded-[24px] bg-white shadow-[0_30px_70px_-30px_rgba(3,29,89,0.35)]">
                <div class="flex items-center justify-between bg-brand px-6 py-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-9 items-center justify-center rounded-[9px] bg-white">
                            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-6 object-contain">
                        </div>
                        <div>
                            <p class="text-sm font-extrabold tracking-[0.04em] text-white">REJCC</p>
                            <p class="text-[9px] tracking-[0.08em] text-[#8FA3D9]">CARTE MEMBRE</p>
                        </div>
                    </div>
                    @if ($card->is_active)
                        <span class="rounded-full bg-[#22A85A]/20 px-3 py-1 text-[10.5px] font-bold text-[#7FE0A6]">✓ MEMBRE ACTIF</span>
                    @else
                        <span class="rounded-full bg-white/10 px-3 py-1 text-[10.5px] font-bold text-white/60">SUSPENDU</span>
                    @endif
                </div>

                <div class="px-6 py-7">
                    <div class="mb-5 flex items-center gap-4">
                        @if ($card->photo)
                            <img src="{{ $card->photo }}" alt="" class="size-20 rounded-2xl object-cover">
                        @else
                            <span class="flex size-20 items-center justify-center rounded-2xl text-2xl font-extrabold text-white" style="background: linear-gradient(135deg, #031D59, #4F6FBF)">
                                {{ mb_strtoupper(mb_substr($card->prenom ?? $card->name, 0, 1).mb_substr($card->nom ?? '', 0, 1)) }}
                            </span>
                        @endif
                        <div>
                            <h1 class="text-xl font-extrabold leading-tight text-brand">{{ trim(($card->prenom ?? '').' '.($card->nom ?? '')) ?: $card->name }}</h1>
                            <p class="mt-0.5 text-sm font-semibold text-accent">{{ $card->role }}</p>
                        </div>
                    </div>

                    <dl class="space-y-2.5 border-t border-brand/10 pt-5">
                        <div class="flex justify-between text-sm">
                            <dt class="text-[#9AA6B8]">Référence</dt>
                            <dd class="font-bold tracking-wide text-brand">{{ $card->reference }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-[#9AA6B8]">Code carte</dt>
                            <dd class="font-bold text-brand">{{ $card->code }}</dd>
                        </div>
                        @if ($card->ville)
                            <div class="flex justify-between text-sm">
                                <dt class="text-[#9AA6B8]">Ville</dt>
                                <dd class="font-semibold text-ink">{{ $card->ville }}</dd>
                            </div>
                        @endif
                        @if ($card->secteur)
                            <div class="flex justify-between text-sm">
                                <dt class="text-[#9AA6B8]">Secteur</dt>
                                <dd class="font-semibold text-ink">{{ $card->secteur }}</dd>
                            </div>
                        @endif
                        @if ($card->membre_depuis)
                            <div class="flex justify-between text-sm">
                                <dt class="text-[#9AA6B8]">Membre depuis</dt>
                                <dd class="font-semibold text-ink">{{ ucfirst($card->membre_depuis) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <p class="border-t border-brand/10 bg-cloud/60 px-6 py-3 text-center text-[11px] text-[#9AA6B8]">
                    Carte vérifiée sur rejcc.site — {{ now()->translatedFormat('j F Y') }}
                </p>
            </article>
        </div>
    </section>
</x-site-layout>
