@php $me = \App\Support\Api::user()->id; @endphp

<div>
    <x-member-light.topbar title="Messagerie" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Messagerie</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        <div class="grid grid-cols-1 gap-4 rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] lg:grid-cols-[320px_1fr]" style="height: calc(100vh - 280px); min-height: 420px" wire:poll.5s>
            <aside class="overflow-y-auto border-r border-cloud-200 {{ $activeId ? 'hidden lg:block' : 'block' }}">
                @if (empty($this->conversations))
                    <div class="px-6 py-10 text-center">
                        <x-ui.icon name="message-circle" class="mx-auto mb-3 size-8 text-[#9AA6B8]" />
                        <p class="mb-4 text-[13.5px] text-[#5B677A]">Aucune conversation. Démarrez-en une depuis l'annuaire.</p>
                        <a href="{{ route('espace-membre.directory') }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-[10px] border border-azure/25 bg-azure/10 px-4 py-2 text-[12.5px] font-semibold text-azure hover:bg-azure/20">
                            <x-ui.icon name="users" class="size-[13px]" /> Voir l'annuaire
                        </a>
                    </div>
                @else
                    <ul class="list-none py-2">
                        @foreach ($this->conversations as $c)
                            <li>
                                <button
                                    wire:click="openThread({{ $c['user_id'] }})"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors {{ $activeId === $c['user_id'] ? 'bg-cloud' : 'hover:bg-cloud/60' }}"
                                >
                                    <span class="flex size-[42px] shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                        {{ mb_substr($c['prenom'], 0, 1) }}{{ mb_substr($c['nom'], 0, 1) }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="truncate text-[13.5px] font-semibold text-brand">{{ $c['prenom'] }} {{ $c['nom'] }}</span>
                                            @if ($c['unread'] > 0)
                                                <span class="flex min-w-5 shrink-0 items-center justify-center rounded-full bg-accent px-1 text-[10.5px] font-bold text-white">{{ $c['unread'] }}</span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block truncate text-xs text-[#5B677A]">{{ $c['last'] }}</span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <section class="flex flex-col {{ $activeId ? 'flex' : 'hidden lg:flex' }}" @if($activeId) wire:poll.3s="refreshThread" @endif>
                @if (! $activeId)
                    <div class="flex flex-1 items-center justify-center p-10 text-center text-sm text-[#5B677A]">
                        Sélectionnez une conversation pour afficher les messages.
                    </div>
                @else
                    <div class="flex shrink-0 items-center gap-3 border-b border-cloud-200 px-[18px] py-3.5">
                        <button wire:click="closeThread" class="icon-btn rounded-lg p-1 text-[#5B677A] lg:hidden" aria-label="Retour">
                            <x-ui.icon name="arrow-left" class="size-[18px]" />
                        </button>
                        <span class="flex size-[38px] shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ $partner ? mb_substr($partner['prenom'], 0, 1).mb_substr($partner['nom'], 0, 1) : '' }}
                        </span>
                        <p class="text-sm font-bold text-brand">{{ $partner ? $partner['prenom'].' '.$partner['nom'] : '' }}</p>
                    </div>

                    <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto px-[18px] py-4">
                        @foreach ($messages as $m)
                            @php $mine = $m['sender_id'] === $me; @endphp
                            <div class="flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                                <div
                                    class="max-w-[78%] rounded-[14px] px-3.5 py-2.5 text-[13.5px] leading-relaxed {{ $mine ? 'text-white' : 'border border-cloud-200 bg-cloud text-ink' }}"
                                    style="{{ $mine ? 'background: linear-gradient(135deg, #4F6FBF, #031D59);' : '' }}"
                                >
                                    {{ $m['body'] }}
                                </div>
                                <span class="mt-1 px-1 text-[11px] text-[#9AA6B8]">
                                    {{ \Illuminate\Support\Carbon::parse($m['created_at'])->locale('fr')->translatedFormat('d/m H:i') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <form wire:submit="send" class="flex shrink-0 items-center gap-2.5 border-t border-cloud-200 px-3.5 py-3">
                        <input
                            wire:model="body"
                            type="text"
                            placeholder="Votre message…"
                            class="min-w-0 flex-1 rounded-[10px] border border-brand/10 bg-cloud px-4 py-2.5 text-[13.5px] text-ink outline-none focus:border-azure"
                        />
                        <button type="submit" aria-label="Envoyer" class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-accent text-white shadow-sm hover:bg-accent-600 hover:shadow-md">
                            <x-ui.icon name="send" class="size-[15px]" />
                        </button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</div>
