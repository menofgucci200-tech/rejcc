<x-member.dark-page title="Notifications" subtitle="Vos dernières alertes et messages du réseau." icon="bell">
    @if ($items->isEmpty())
        <div class="py-12 text-center">
            <x-ui.icon name="bell" class="mx-auto mb-3.5 size-9 text-[var(--ms-dim)]" />
            <p class="text-sm text-[var(--ms-muted)]">Aucune notification pour l'instant.</p>
        </div>
    @else
        <ul class="m-0 flex max-w-[640px] list-none flex-col gap-2.5 p-0">
            @foreach ($items as $n)
                <li>
                    @if ($n->link)
                        <a href="{{ $n->link }}" wire:navigate class="block no-underline">
                    @endif

                    <div class="flex items-start gap-3.5 rounded-2xl border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-[18px] py-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[11px] bg-[var(--ms-surf2)] text-[var(--ms-muted)]">
                            <x-ui.icon :name="$n->type === 'message' ? 'message-circle' : 'info'" class="size-[17px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-sm font-semibold text-[var(--ms-text)]">{{ $n->title }}</p>
                            @if ($n->body)
                                <p class="m-0 mt-1 text-[13px] leading-relaxed text-[var(--ms-muted)]">{{ $n->body }}</p>
                            @endif
                            <p class="m-0 mt-1.5 text-[11.5px] text-[var(--ms-dim)]">{{ $n->created_at->locale('fr')->translatedFormat('d F, H:i') }}</p>
                        </div>
                        @if (! $n->read_at)
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-[#E84A43]"></span>
                        @endif
                    </div>

                    @if ($n->link)
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-member.dark-page>
