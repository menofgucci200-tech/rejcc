<div>
    <x-member-light.topbar title="Catalogue des formations" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Catalogue des formations</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFiltre('toutes')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'toutes' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Toutes</button>
                <button wire:click="setFiltre('gratuit')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'gratuit' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Gratuites</button>
                <button wire:click="setFiltre('certifiante')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'certifiante' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Certifiantes</button>
            </div>
        </div>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($cours as $c)
                <article class="card-hover overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex h-24 items-center justify-center" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">
                        <x-ui.icon name="graduation-cap" class="size-9 text-white/85" />
                    </div>
                    <div class="p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold" style="background: {{ $c['tagColor'] }}1A; color: {{ $c['tagColor'] }}">{{ $c['tag'] }}</span>
                            @if ($c['certifiante'])
                                <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#F5A623]">
                                    <x-ui.icon name="award" class="size-3" /> Certifiante
                                </span>
                            @endif
                        </div>
                        <p class="mb-2 text-[13.5px] font-bold leading-snug text-brand">{{ $c['titre'] }}</p>
                        <div class="mb-3 flex items-center gap-3 text-xs text-[#9AA6B8]">
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="size-3" /> {{ $c['duree'] }}</span>
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="target" class="size-3" /> {{ $c['niveau'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold {{ $c['gratuit'] ? 'text-[#22A85A]' : 'text-brand' }}">{{ $c['gratuit'] ? 'Gratuit' : 'Sur adhésion' }}</span>
                            @if ($c['inscrit'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                                    <x-ui.icon name="check" class="size-3.5" /> Inscrit
                                </span>
                            @else
                                <button wire:click="inscrire({{ $c['id'] }})" wire:loading.attr="disabled" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand/90 disabled:opacity-60">S'inscrire</button>
                            @endif
                        </div>
                        @if ($c['media'])
                            <a href="{{ $c['media'] }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-azure hover:underline">
                                <x-ui.icon name="download" class="size-3.5" /> Support de la formation
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
