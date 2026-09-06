<div>
    <x-member-light.topbar title="Formation" />

    <div class="mx-auto max-w-[860px] px-8 py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.formations') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Mes formations
            </a>
        </div>

        @if (! $ok)
            <p class="mt-6 rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Vous n'êtes pas inscrit à cette formation.</p>
        @else
            <div class="mb-5">
                <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $formation['title'] }}</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                @if ($formation['description'])
                    <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $formation['description'] }}</p>
                @endif
            </div>

            @if ($message)
                <p class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
            @endif

            <div class="mb-6 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="mb-1.5 flex items-center justify-between">
                    <p class="text-[12px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Progression</p>
                    <p class="text-[12px] font-bold text-brand">{{ $progress }}%</p>
                </div>
                <div class="h-2 w-full rounded-full bg-cloud">
                    <div class="h-2 rounded-full {{ $completed ? 'bg-[#22A85A]' : 'bg-azure' }}" style="width: {{ $progress }}%"></div>
                </div>
                @if ($completed)
                    <p class="mt-3 inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#22A85A]"><x-ui.icon name="award" class="size-4" /> Formation terminée{{ $formation['is_certifying'] ? ' — certificat disponible dans « Certificats »' : '' }} !</p>
                @endif
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($modules as $m)
                    <div class="overflow-hidden rounded-[16px] border bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $m['verrouille'] ? 'border-brand/10 opacity-60' : 'border-brand/10' }}">
                        <button
                            type="button"
                            wire:click="{{ $m['verrouille'] ? '' : "toggleModule({$m['id']})" }}"
                            {{ $m['verrouille'] ? 'disabled' : '' }}
                            class="flex w-full items-center gap-3 p-4 text-left {{ $m['verrouille'] ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-cloud/40' }}"
                        >
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $m['termine'] ? 'bg-[#22A85A]/10 text-[#22A85A]' : ($m['verrouille'] ? 'bg-cloud text-[#9AA6B8]' : 'bg-azure/10 text-azure') }}">
                                <x-ui.icon :name="$m['termine'] ? 'check' : ($m['verrouille'] ? 'shield' : 'play')" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-bold text-brand">{{ $m['titre'] }}</p>
                                @if ($m['duree'])
                                    <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $m['duree'] }}</p>
                                @endif
                            </div>
                            @if (! $m['verrouille'])
                                <x-ui.icon name="chevron-right" class="size-4 shrink-0 text-[#9AA6B8]" />
                            @endif
                        </button>

                        @if ($moduleOuvert === $m['id'])
                            <div class="panel-enter border-t border-cloud-200 p-4">
                                @if ($m['description'])
                                    <p class="mb-3 text-[13px] leading-relaxed text-[#5B677A]">{{ $m['description'] }}</p>
                                @endif
                                <div class="mb-3 flex flex-wrap gap-2">
                                    @if ($m['video_url'])
                                        <a href="{{ $m['video_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-[12px] font-semibold text-azure hover:bg-azure/20">
                                            <x-ui.icon name="play" class="size-3.5" /> Voir la vidéo
                                        </a>
                                    @endif
                                    @if ($m['document_url'])
                                        <a href="{{ $m['document_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3.5 py-1.5 text-[12px] font-semibold text-brand hover:bg-cloud">
                                            <x-ui.icon name="download" class="size-3.5" /> Télécharger le document
                                        </a>
                                    @endif
                                </div>
                                @if (! $m['termine'])
                                    <button wire:click="validerModule({{ $m['id'] }})" wire:loading.attr="disabled" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Marquer ce module comme terminé</button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
