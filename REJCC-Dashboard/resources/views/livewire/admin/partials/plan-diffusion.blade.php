{{-- Plan de diffusion de la Communication : type d'information → canaux recommandés.
     $plan vient de GET /admin/plan-diffusion ; $replie (bool) : replié par défaut ;
     $compact (bool) : toujours en liste (colonne étroite, à côté du formulaire). --}}
@php
    $replie = $replie ?? false;
    $compact = $compact ?? false;
@endphp
<section x-data="{ ouvert: {{ $replie ? 'false' : 'true' }} }" class="rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-5" data-test="plan-diffusion">
    <button type="button" @click="ouvert = ! ouvert" :aria-expanded="ouvert" class="flex w-full items-center gap-2 text-left">
        <x-ui.illustration name="megaphone" size="size-7" />
        <span class="min-w-0 flex-1">
            <span class="block text-[15px] font-bold text-brand">Plan de diffusion</span>
            <span class="block text-[12px] text-[#5B677A]">Les canaux recommandés par la Communication pour chaque type d'information.</span>
        </span>
        <x-ui.icon name="chevron-down" class="size-4 shrink-0 text-[#5B677A] transition-transform" x-bind:class="ouvert ? 'rotate-180' : ''" />
    </button>

    <div x-show="ouvert" @if ($replie) style="display: none" @endif>
        {{-- Tableau (tablette et ordinateur) --}}
        @unless ($compact)
        <div class="mt-4 hidden overflow-hidden rounded-[12px] border border-[#EDF0F5] sm:block">
            <table class="w-full text-[12.5px]">
                <thead class="bg-[#F4F6F8] text-[11px] uppercase tracking-[0.06em] text-[#5B677A]">
                    <tr>
                        <th class="px-3 py-2 text-left font-bold">Type d'information</th>
                        @foreach ($plan['canaux'] as $c)
                            <th class="px-2 py-2 text-center font-bold" title="{{ $c['detail'] }}">{{ $c['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plan['types'] as $t)
                        <tr class="border-t border-[#EDF0F5]">
                            <td class="px-3 py-2">
                                <span class="font-semibold text-ink">{{ $t['label'] }}</span>
                                @if ($t['note'])<span class="block text-[11px] text-[#5B677A]">{{ $t['note'] }}</span>@endif
                            </td>
                            @foreach ($plan['canaux'] as $c)
                                <td class="px-2 py-2 text-center">
                                    @if (in_array($c['cle'], $t['canaux'], true))
                                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-[#EAF6EE] text-[#1C8A49]" aria-label="Recommandé"><x-ui.icon name="check" class="size-3.5" /></span>
                                    @else
                                        <span class="text-[#C9D3E6]" aria-label="Non">·</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endunless

        {{-- Liste (téléphone) --}}
        <ul class="mt-4 space-y-2 {{ $compact ? '' : 'sm:hidden' }}">
            @foreach ($plan['types'] as $t)
                <li class="rounded-[12px] border border-[#EDF0F5] px-3 py-2">
                    <p class="text-[12.5px] font-semibold text-ink">{{ $t['label'] }}@if ($t['note']) <span class="font-normal text-[#5B677A]">· {{ $t['note'] }}</span>@endif</p>
                    <p class="mt-1 flex flex-wrap gap-1">
                        @if ($t['tous_les_canaux'])
                            <span class="rounded-full bg-[#EAF6EE] px-2 py-0.5 text-[11px] font-bold text-[#1C8A49]">Tous les canaux</span>
                        @else
                            @forelse ($t['canaux_labels'] as $l)
                                <span class="rounded-full bg-[#EEF6FD] px-2 py-0.5 text-[11px] font-semibold text-[#2D5C8F]">{{ $l }}</span>
                            @empty
                                <span class="text-[11.5px] text-[#5B677A]">À préciser</span>
                            @endforelse
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 flex flex-wrap items-start gap-3 rounded-[12px] border border-[#D9E2F3] bg-[#F3F6FC] px-3.5 py-3" data-test="encadre-annonces">
            <x-ui.icon name="megaphone" class="mt-0.5 size-4 shrink-0 text-azure" />
            <p class="min-w-[200px] flex-1 text-[12.5px] leading-relaxed text-ink">
                <strong>Le canal Annonces, c'est le canal Annonces de la Communauté sur la plateforme REJCC (pas WhatsApp).</strong>
                Seule l'équipe du réseau y publie ; tous les membres connectés le reçoivent.
            </p>
            <a href="{{ \App\Support\Site::url('/espace-membre/communaute') }}" target="_blank" rel="noopener" class="shrink-0 rounded-full border border-azure/30 bg-white px-3 py-1 text-[12px] font-bold text-azure hover:bg-azure/10">Ouvrir la Communauté</a>
        </div>
    </div>
</section>
