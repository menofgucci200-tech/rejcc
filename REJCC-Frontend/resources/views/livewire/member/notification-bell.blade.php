{{-- Rafraîchit le badge et la liste toutes les 30 s quand l'onglet est visible --}}
<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" wire:poll.visible.30s class="relative shrink-0">
    <button
        type="button"
        @click="open = !open"
        aria-label="Notifications"
        class="relative flex size-10 items-center justify-center rounded-[10px] border border-brand/10 bg-white transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-cloud hover:shadow-md active:scale-90"
    >
        <x-ui.icon name="bell" class="size-[18px] text-brand" />
        @if ($unread > 0)
            <span class="tab-dot absolute -right-1 -top-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-white bg-accent px-1 text-[10px] font-bold text-white">{{ $unread }}</span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 top-12 z-[90] w-[330px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-[16px] border border-brand/10 bg-white shadow-[0_24px_60px_-20px_rgba(3,29,89,.35)]"
    >
        <div class="flex items-center justify-between border-b border-cloud-200 px-4 py-3">
            <p class="text-[13px] font-bold text-brand">Notifications</p>
            @if ($unread > 0)
                <button type="button" wire:click="markAllRead" class="text-[11px] font-semibold text-azure hover:underline">Tout marquer comme lu</button>
            @endif
        </div>

        <div class="max-h-[340px] overflow-y-auto" data-lenis-prevent>
            @forelse ($items as $n)
                <a
                    href="{{ $n->link ?: route('espace-membre.notifications') }}"
                    class="flex gap-3 border-b border-cloud-200 px-4 py-3 transition-colors last:border-b-0 hover:bg-cloud/60 {{ $n->read_at ? '' : 'bg-azure/[.04]' }}"
                >
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $n->type === 'alert' ? 'bg-accent/10 text-accent' : ($n->type === 'message' ? 'bg-azure/10 text-azure' : 'bg-brand/10 text-brand') }}">
                        <x-ui.icon :name="$n->type === 'alert' ? 'alert-circle' : ($n->type === 'message' ? 'message-circle' : 'info')" class="size-4" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[12.5px] {{ $n->read_at ? 'font-semibold' : 'font-bold' }} leading-snug text-brand">{{ $n->title }}</span>
                        @if ($n->body)
                            <span class="mt-0.5 line-clamp-2 block text-[11.5px] leading-snug text-[#5B677A]">{{ $n->body }}</span>
                        @endif
                        <span class="mt-1 block text-[10.5px] font-semibold text-[#9AA6B8]">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($n->read_at)
                        <span class="ml-auto mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span>
                    @endunless
                </a>
            @empty
                <p class="px-4 py-8 text-center text-[12.5px] text-[#9AA6B8]">Aucune notification pour le moment.</p>
            @endforelse
        </div>

        <a href="{{ route('espace-membre.notifications') }}" class="block border-t border-cloud-200 bg-cloud/50 px-4 py-2.5 text-center text-[12px] font-bold text-brand transition-colors hover:bg-cloud">
            Voir toutes les notifications
        </a>
    </div>
</div>
