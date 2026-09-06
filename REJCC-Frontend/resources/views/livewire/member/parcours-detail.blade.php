<div>
    <x-member-light.topbar title="Parcours" />

    <div class="mx-auto max-w-[860px] px-8 py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.parcours') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Tous les parcours
            </a>
        </div>

        @if (! $ok)
            <p class="mt-6 rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Parcours introuvable.</p>
        @else
            <div class="mb-6 flex items-start gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-[14px]" style="background: {{ $path['badge_couleur'] ?? '#4F6FBF' }}1A; color: {{ $path['badge_couleur'] ?? '#4F6FBF' }}">
                    <x-ui.icon :name="$path['badge_icon'] ?: 'rocket'" class="size-6" />
                </span>
                <div>
                    <h1 class="text-[17px] font-bold text-brand">{{ $path['title'] }}</h1>
                    @if ($path['objectif'])
                        <p class="mt-1 text-[13px] text-[#5B677A]">{{ $path['objectif'] }}</p>
                    @endif
                </div>
            </div>

            @if ($path['description'])
                <p class="mb-6 text-[13px] leading-relaxed text-[#5B677A]">{{ $path['description'] }}</p>
            @endif

            @if ($message)
                <p class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
            @endif

            @if ($path['badge_obtenu'])
                <div class="mb-6 flex items-center gap-3 rounded-[16px] border border-[#22A85A]/20 bg-[#22A85A]/5 p-4">
                    <x-ui.icon name="award" class="size-6 shrink-0 text-[#1C8F4C]" />
                    <p class="text-[13px] font-bold text-[#1C8F4C]">Bravo, vous avez terminé ce parcours et obtenu le badge !</p>
                </div>
            @endif

            <div class="flex flex-col gap-3">
                @foreach ($formations as $i => $f)
                    <div class="flex items-center gap-4 rounded-[16px] border bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $f['verrouille'] ? 'border-brand/10 opacity-60' : 'border-brand/10' }}">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-[13px] font-bold {{ $f['completed'] ? 'bg-[#22A85A]/10 text-[#22A85A]' : ($f['verrouille'] ? 'bg-cloud text-[#9AA6B8]' : 'bg-azure/10 text-azure') }}">
                            @if ($f['completed'])
                                <x-ui.icon name="check" class="size-4" />
                            @elseif ($f['verrouille'])
                                <x-ui.icon name="shield" class="size-4" />
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-bold text-brand">{{ $f['title'] }}</p>
                            <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $f['category'] }}@if ($f['duration']) · {{ $f['duration'] }}@endif@if ($f['is_certifying']) · Certifiante @endif</p>
                            @if ($f['enrolled'] && ! $f['completed'])
                                <div class="mt-2 h-1.5 w-full max-w-[180px] rounded-full bg-cloud">
                                    <div class="h-1.5 rounded-full bg-azure" style="width: {{ $f['progress'] }}%"></div>
                                </div>
                            @endif
                        </div>
                        @if ($f['completed'])
                            <span class="shrink-0 rounded-full bg-[#22A85A]/10 px-3 py-1.5 text-[11.5px] font-bold text-[#22A85A]">Terminée</span>
                        @elseif ($f['verrouille'])
                            <span class="shrink-0 rounded-full bg-cloud px-3 py-1.5 text-[11.5px] font-bold text-[#9AA6B8]">Verrouillée</span>
                        @elseif ($f['enrolled'] && $f['has_modules'])
                            <a href="{{ route('espace-membre.formations.detail', $f['id']) }}" wire:navigate class="btn-tap shrink-0 rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90">Continuer</a>
                        @elseif ($f['enrolled'])
                            <a href="{{ route('espace-membre.formations') }}" wire:navigate class="btn-tap shrink-0 rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90">Continuer</a>
                        @else
                            <button wire:click="demarrer({{ $f['id'] }})" wire:loading.attr="disabled" class="btn-tap shrink-0 rounded-full bg-brand px-4 py-2 text-[12px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Commencer</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
