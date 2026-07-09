<div>
    <x-member-light.topbar title="Notifications" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Notifications</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        @if ($items->isEmpty())
            <div class="py-12 text-center">
                <x-ui.icon name="bell" class="mx-auto mb-3.5 size-9 text-[#9AA6B8]" />
                <p class="text-sm text-[#5B677A]">Aucune notification pour l'instant.</p>
            </div>
        @else
            <ul class="flex max-w-[640px] list-none flex-col gap-2.5 p-0">
                @foreach ($items as $n)
                    <li>
                        @if ($n->link)
                            <a href="{{ $n->link }}" wire:navigate class="block">
                        @endif

                        <div class="flex items-start gap-3.5 rounded-2xl border border-brand/10 bg-white px-[18px] py-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[11px] bg-cloud text-[#5B677A]">
                                <x-ui.icon :name="$n->type === 'message' ? 'message-circle' : 'info'" class="size-[17px]" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-brand">{{ $n->title }}</p>
                                @if ($n->body)
                                    <p class="mt-1 text-[13px] leading-relaxed text-[#5B677A]">{{ $n->body }}</p>
                                @endif
                                <p class="mt-1.5 text-[11.5px] text-[#9AA6B8]">{{ $n->created_at->locale('fr')->translatedFormat('d F, H:i') }}</p>
                            </div>
                            @if (! $n->read_at)
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span>
                            @endif
                        </div>

                        @if ($n->link)
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
