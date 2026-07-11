<x-site-layout :title="'Carte membre — '.trim(($card->prenom ?? '').' '.($card->nom ?? ''))" description="Carte membre officielle du REJCC — Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire.">
    <section class="bg-cloud py-16 sm:py-24">
        <div class="mx-auto max-w-[1120px] px-5">
            <div class="mb-8 text-center">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Carte officielle</p>
                <h1 class="mt-1 text-2xl font-extrabold text-brand sm:text-3xl">{{ trim(($card->prenom ?? '').' '.($card->nom ?? '')) ?: $card->name }}</h1>
                <p class="mt-1 text-sm font-semibold text-[#5B677A]">{{ $card->role_label ?? 'Membre' }} · REJCC
                    @if (! ($card->is_active ?? true)) <span class="ml-1 rounded-full bg-[#EEF1F5] px-2 py-0.5 text-[11px] font-bold text-[#9AA6B8]">Compte suspendu</span> @endif
                </p>
            </div>

            <x-member-card
                :name="trim(($card->prenom ?? '').' '.($card->nom ?? '')) ?: $card->name"
                :roleLabel="$card->role_label ?? 'Membre officiel'"
                :role="$card->role ?? 'member'"
                :numero="$card->numero ?? '—'"
                :code="$card->code ?? ''"
                :photo="$card->photo ?? null"
                :dateAdhesion="$card->membre_depuis"
            />

            <p class="mt-8 text-center text-[12px] text-[#9AA6B8]">Carte vérifiée sur rejcc.site — {{ now()->translatedFormat('j F Y') }}</p>
        </div>
    </section>
</x-site-layout>
