<div>
    <x-member-light.topbar title="Documents & ressources" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Documents &amp; ressources</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        @if ($docs->isEmpty())
            <div class="py-12 text-center">
                <x-ui.icon name="folder-open" class="mx-auto mb-3.5 size-9 text-[#9AA6B8]" />
                <p class="text-sm text-[#5B677A]">Aucun document disponible.</p>
            </div>
        @else
            <div class="flex max-w-[820px] flex-col gap-9">
                @foreach ($categories as $cat)
                    <div>
                        <p class="mb-3.5 text-[10.5px] font-bold uppercase tracking-[0.14em] text-[#9AA6B8]">{{ $cat }}</p>
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))">
                            @foreach ($docs->where('category', $cat) as $d)
                                <a href="{{ $d->url }}" target="_blank" rel="noopener noreferrer" class="card-hover flex items-center gap-3.5 rounded-2xl border border-brand/10 bg-white px-[18px] py-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                                    <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-accent/10">
                                        <x-ui.icon name="file-text" class="size-[18px] text-accent" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13.5px] font-semibold text-brand">{{ $d->title }}</p>
                                        @if ($d->description)
                                            <p class="mt-0.5 truncate text-xs text-[#5B677A]">{{ $d->description }}</p>
                                        @endif
                                    </div>
                                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-azure">
                                        {{ $d->size }} <x-ui.icon name="download" class="size-[13px]" />
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
