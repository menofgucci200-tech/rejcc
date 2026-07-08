<x-member.dark-page title="Documents & ressources" subtitle="Guides, chartes et ressources mis à disposition par le réseau." icon="folder-open">
    @if ($docs->isEmpty())
        <div class="py-12 text-center">
            <x-ui.icon name="folder-open" class="mx-auto mb-3.5 size-9 text-[var(--ms-dim)]" />
            <p class="text-sm text-[var(--ms-muted)]">Aucun document disponible.</p>
        </div>
    @else
        <div class="flex max-w-[820px] flex-col gap-9">
            @foreach ($categories as $cat)
                <div>
                    <p class="mb-3.5 text-[10.5px] font-bold uppercase tracking-[0.14em] text-[var(--ms-dim)]">{{ $cat }}</p>
                    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))">
                        @foreach ($docs->where('category', $cat) as $d)
                            <a href="{{ $d->url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3.5 rounded-2xl border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-[18px] py-4 no-underline transition-transform hover:-translate-y-0.5">
                                <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[11px]" style="background: rgba(232,74,67,0.13)">
                                    <x-ui.icon name="file-text" class="size-[18px] text-[#E84A43]" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="m-0 truncate text-[13.5px] font-semibold text-[var(--ms-text)]">{{ $d->title }}</p>
                                    @if ($d->description)
                                        <p class="m-0 mt-0.5 truncate text-xs text-[var(--ms-muted)]">{{ $d->description }}</p>
                                    @endif
                                </div>
                                <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-[#9DB2EE]">
                                    {{ $d->size }} <x-ui.icon name="download" class="size-[13px]" />
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-member.dark-page>
