@props(['avis', 'moi' => false, 'prenom' => '', 'erreur' => null])

{{-- Avis des membres sur un professionnel : moyenne, répartition, liste et
     formulaire (un avis par membre, modifiable). Actions Livewire : noter(),
     retirerAvis(). --}}
@php
    $moyenne = $avis['moyenne'] ?? null;
    $nombre = $avis['nombre'] ?? 0;
    $mien = $avis['mon_avis'] ?? null;
@endphp
<section data-test="fiche-pro-avis" class="mt-5 border-t border-cloud-200 pt-4"
    x-data="{
        {{-- x-data volontairement identique d'un rendu à l'autre (valeurs lues
             dans $wire) : s'il changeait, Livewire désynchroniserait Alpine. --}}
        edition: ! $wire.detail?.avis?.mon_avis,
        note: $wire.detail?.avis?.mon_avis?.note ?? 0,
        survol: 0,
        commentaire: $wire.detail?.avis?.mon_avis?.commentaire ?? '',
        envoi: false,
        libelles: ['', 'Décevant', 'Passable', 'Correct', 'Très bien', 'Excellent'],
        async envoyer() {
            if (! this.note) return;
            this.envoi = true;
            await $wire.noter(this.note, this.commentaire);
            this.envoi = false;
            if (! $wire.avisErreur) this.edition = false;
        },
    }">
    <div class="flex items-center justify-between gap-3">
        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Avis des membres</p>
        @if ($nombre)
            <p data-test="avis-moyenne" class="flex items-center gap-1.5 text-[13px] font-extrabold text-brand">
                <x-ui.icon name="star" class="size-4 fill-[#F5A623] text-[#F5A623]" />
                {{ number_format($moyenne, 1, ',', ' ') }}<span class="font-semibold text-[#9AA6B8]">/5 · {{ $nombre }} avis</span>
            </p>
        @endif
    </div>

    @if ($nombre)
        {{-- Répartition des notes --}}
        <div class="mt-3 space-y-1" wire:key="avis-repartition">
            @foreach ($avis['repartition'] ?? [] as $etoiles => $n)
                <div class="flex items-center gap-2 text-[11.5px] text-[#5B677A]">
                    <span class="w-6 shrink-0 font-semibold">{{ $etoiles }} <span class="text-[#F5A623]">★</span></span>
                    <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-cloud">
                        <span class="block h-full rounded-full bg-[#F5A623]" style="width: {{ $nombre ? round($n / $nombre * 100) : 0 }}%"></span>
                    </span>
                    <span class="w-5 shrink-0 text-right">{{ $n }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p wire:key="avis-vide" class="mt-2 text-[13px] text-[#5B677A]">{{ $moi ? "Vous n'avez pas encore reçu d'avis." : "Aucun avis pour l'instant. Vous avez fait appel à {$prenom} ? Partagez votre expérience." }}</p>
    @endif

    {{-- Mon avis --}}
    @unless ($moi)
        <div class="mt-4 rounded-[14px] border border-brand/10 bg-cloud/40 p-4">
            {{-- Structure fixe (pas de @if autour des blocs) : le rafraîchissement
                 Livewire conserve ainsi les liaisons Alpine après publication. --}}
            <div wire:key="avis-mien" x-show="! edition" class="flex items-start justify-between gap-3">
                @if ($mien)
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-brand">Votre avis</p>
                        <p class="mt-0.5 text-[15px] tracking-wide text-[#F5A623]">{{ str_repeat('★', $mien['note']) }}<span class="text-[#D5DCE8]">{{ str_repeat('★', 5 - $mien['note']) }}</span></p>
                        @if ($mien['commentaire'])<p class="mt-1 text-[12.5px] text-ink">{{ $mien['commentaire'] }}</p>@endif
                        @if ($mien['masque'] ?? false)<p class="mt-1 text-[11.5px] font-semibold text-accent">Masqué par la modération : il n'est pas visible des autres membres.</p>@endif
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button type="button" @click="edition = true" data-test="avis-modifier" class="rounded-full px-3 py-1.5 text-[12px] font-bold text-azure hover:bg-azure/10">Modifier</button>
                        <button type="button" @click="if (confirm('Retirer votre avis ?')) { await $wire.retirerAvis(); note = 0; commentaire = ''; edition = true }" data-test="avis-retirer" class="rounded-full px-3 py-1.5 text-[12px] font-bold text-accent hover:bg-accent/5">Retirer</button>
                    </div>
                @endif
            </div>

            <form wire:key="avis-form" x-show="edition" @submit.prevent="envoyer" data-test="form-avis">
                <p class="text-[12px] font-bold text-brand" x-text="$wire.detail?.avis?.mon_avis ? 'Modifier votre avis' : 'Donner votre avis'">{{ $mien ? 'Modifier votre avis' : 'Donner votre avis' }}</p>
                <div class="mt-1.5 flex items-center gap-2" @mouseleave="survol = 0">
                    <div class="flex" role="radiogroup" aria-label="Note sur 5">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" role="radio" :aria-checked="note === {{ $i }}" aria-label="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}" data-test="etoile-{{ $i }}"
                                @click="note = {{ $i }}" @mouseenter="survol = {{ $i }}"
                                class="px-0.5 text-[26px] leading-none transition-transform hover:scale-110"
                                :class="(survol || note) >= {{ $i }} ? 'text-[#F5A623]' : 'text-[#D5DCE8]'">★</button>
                        @endfor
                    </div>
                    <span class="text-[12px] font-semibold text-[#5B677A]" x-text="libelles[survol || note] || 'Choisissez une note'"></span>
                </div>
                <textarea x-model="commentaire" rows="2" maxlength="1000" data-test="avis-commentaire" placeholder="Votre expérience (facultatif) : qualité, ponctualité, accueil…"
                    class="mt-2.5 w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] text-ink focus:border-azure focus:outline-none focus:ring-2 focus:ring-azure/20"></textarea>
                @if ($erreur)<p class="mt-1 text-[12px] font-semibold text-accent">{{ $erreur }}</p>@endif
                <div class="mt-2 flex items-center justify-end gap-2">
                    <button type="button" x-show="$wire.detail?.avis?.mon_avis" @click="edition = false" class="rounded-full px-4 py-2 text-[12.5px] font-bold text-[#5B677A] hover:bg-cloud">Annuler</button>
                    <button type="submit" :disabled="! note || envoi" data-test="avis-publier"
                        class="btn-tap rounded-full bg-brand px-5 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-show="! envoi">Publier</span><span x-show="envoi" style="display: none">Envoi…</span>
                    </button>
                </div>
            </form>
        </div>
    @endunless

    {{-- Liste des avis --}}
    @if (! empty($avis['liste']))
        <ul wire:key="avis-liste" class="mt-4 space-y-3" data-test="avis-liste">
            @foreach ($avis['liste'] as $a)
                <li class="flex gap-3" wire:key="avis-{{ $a['id'] }}">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand/[.07] text-[11px] font-bold text-brand">{{ mb_strtoupper(collect(explode(' ', $a['auteur']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('')) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-baseline gap-x-2 text-[12.5px]">
                            <span class="font-bold text-brand">{{ $a['auteur'] }}</span>
                            <span class="tracking-wide text-[#F5A623]">{{ str_repeat('★', $a['note']) }}<span class="text-[#D5DCE8]">{{ str_repeat('★', 5 - $a['note']) }}</span></span>
                            <span class="text-[11px] text-[#9AA6B8]">{{ $a['date'] ? (\Illuminate\Support\Carbon::parse($a['date'])->gt(now()->subMinute()) ? "à l'instant" : \Illuminate\Support\Carbon::parse($a['date'])->locale('fr')->diffForHumans()) : '' }}{{ $a['groupe'] ? ' · '.$a['groupe'] : '' }}</span>
                        </p>
                        @if ($a['commentaire'])<p class="mt-0.5 text-[12.5px] leading-relaxed text-ink">{{ $a['commentaire'] }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
