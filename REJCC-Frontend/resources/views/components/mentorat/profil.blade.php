@props(['mentor', 'compact' => false])

{{-- Bloc « Mentor du réseau » : expertises, présentation, disponibilités et format. --}}
@if ($mentor)
    <div data-test="profil-mentor" {{ $attributes->merge(['class' => 'rounded-[14px] border border-accent/15 bg-accent/[.04] p-4']) }}>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-accent">
                <x-ui.icon name="nav-mentor" class="size-3.5" /> Mentor du réseau
            </p>
            <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold {{ ($mentor['accepte'] ?? true) ? 'bg-[#22A85A]/10 text-[#1C8F4C]' : 'bg-cloud text-[#9AA6B8]' }}">
                {{ ($mentor['accepte'] ?? true) ? 'Accepte de nouveaux mentorés' : 'Complet pour le moment' }}
            </span>
        </div>
        @php $stats = $mentor['stats'] ?? null; @endphp
        @if (($stats['accompagnes'] ?? 0) > 0)
            <p data-test="stats-mentor" class="mt-2 text-[12px] font-semibold text-brand">
                {{ $stats['accompagnes'] }} membre{{ $stats['accompagnes'] > 1 ? 's' : '' }} accompagné{{ $stats['accompagnes'] > 1 ? 's' : '' }} au sein du REJCC
                @if ($stats['note_moyenne'])
                    · <span class="text-[#B27007]">★ {{ number_format($stats['note_moyenne'], 1, ',', '') }}/5</span> <span class="font-normal text-[#9AA6B8]">({{ $stats['nb_avis'] }} avis)</span>
                @endif
            </p>
        @endif
        @if (! empty($mentor['expertises']))
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($mentor['expertises'] as $e)
                    <span class="rounded-full bg-white px-2.5 py-1 text-[11.5px] font-semibold text-brand ring-1 ring-brand/10">{{ $e }}</span>
                @endforeach
            </div>
        @endif
        @if (($mentor['bio'] ?? null) && ! $compact)
            <p class="mt-3 whitespace-pre-line text-[13px] leading-relaxed text-ink">{{ $mentor['bio'] }}</p>
        @endif
        @if (($mentor['disponibilites'] ?? null) || ($mentor['format_label'] ?? null))
            <p class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-[#5B677A]">
                @if ($mentor['format_label'] ?? null)
                    <span class="inline-flex items-center gap-1"><x-ui.icon name="video" class="size-3.5" /> {{ $mentor['format_label'] }}</span>
                @endif
                @if ($mentor['disponibilites'] ?? null)
                    <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="size-3.5" /> {{ $mentor['disponibilites'] }}</span>
                @endif
            </p>
        @endif
    </div>
@endif
