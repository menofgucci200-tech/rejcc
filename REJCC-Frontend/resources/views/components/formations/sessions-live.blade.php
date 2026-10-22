@props(['sessions' => []])
{{-- Sessions live à venir d'une formation : inscription et lien de la visio
     depuis la fiche de l'événement, dans le calendrier. --}}
@if ($sessions)
    <section {{ $attributes->merge(['class' => 'rounded-[18px] border border-[#CDEBDD] bg-[#F4FBF7] p-5']) }} data-test="sessions-live">
        <h2 class="flex items-center gap-2 text-[15px] font-extrabold text-brand"><x-ui.icon name="calendar-days" class="size-4 text-[#2E9E6E]" /> Sessions live</h2>
        <p class="mt-1 text-[12.5px] text-[#5B677A]">En visio avec le formateur. Inscrivez-vous pour recevoir le lien et un rappel.</p>
        <div class="mt-3 space-y-2">
            @foreach ($sessions as $s)
                @php $debut = \Carbon\Carbon::parse($s['starts_at'])->locale('fr'); @endphp
                <a href="{{ route('espace-membre.evenements', ['evenement' => $s['id']]) }}" wire:navigate
                   class="flex flex-wrap items-center gap-3 rounded-[14px] border border-white bg-white px-4 py-3 transition hover:border-[#7FD1AE]">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13.5px] font-bold text-brand">{{ $s['title'] }}</span>
                        <span class="text-[12px] text-[#5B677A]">{{ $s['en_cours'] ? 'En ce moment' : ucfirst($debut->isoFormat('dddd D MMMM [à] HH[h]mm')) }}@if ($s['animateur']) · {{ $s['animateur'] }}@endif</span>
                    </span>
                    <span class="shrink-0 rounded-full px-3 py-1 text-[11.5px] font-bold {{ $s['inscrit'] ? 'bg-[#EAF6EE] text-[#22A85A]' : 'bg-[#2E9E6E] text-white' }}">{{ $s['inscrit'] ? 'Inscrit' : "S'inscrire" }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
