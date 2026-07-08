@php $me = \App\Support\Api::user()->id; @endphp

<x-member.dark-page title="Messagerie" subtitle="Échangez en privé avec les membres du réseau." icon="message-circle">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[320px_1fr]" style="height: calc(100vh - 280px); min-height: 420px" wire:poll.5s>
        <!-- Conversations -->
        <aside class="overflow-y-auto rounded-[18px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] {{ $activeId ? 'hidden lg:block' : 'block' }}">
            @if (empty($this->conversations))
                <div class="px-6 py-10 text-center">
                    <x-ui.icon name="message-circle" class="mx-auto mb-3 size-8 text-[var(--ms-dim)]" />
                    <p class="mb-4 text-[13.5px] text-[var(--ms-muted)]">Aucune conversation. Démarrez-en une depuis l'annuaire.</p>
                    <a href="{{ route('espace-membre.directory') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-[10px] px-4 py-2 text-[12.5px] font-semibold no-underline" style="background: rgba(79,111,191,0.14); border: 1px solid rgba(79,111,191,0.24); color: #9DB2EE;">
                        <x-ui.icon name="users" class="size-[13px]" /> Voir l'annuaire
                    </a>
                </div>
            @else
                <ul class="m-0 list-none py-2">
                    @foreach ($this->conversations as $c)
                        <li>
                            <button
                                wire:click="openThread({{ $c['user_id'] }})"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors"
                                style="background: {{ $activeId === $c['user_id'] ? 'var(--ms-surf2)' : 'transparent' }}"
                            >
                                <span class="flex size-[42px] shrink-0 items-center justify-center rounded-full text-[13px] font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                                    {{ mb_substr($c['prenom'], 0, 1) }}{{ mb_substr($c['nom'], 0, 1) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-[13.5px] font-semibold text-[var(--ms-text)]">{{ $c['prenom'] }} {{ $c['nom'] }}</span>
                                        @if ($c['unread'] > 0)
                                            <span class="flex min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10.5px] font-bold text-white" style="background: #E84A43;">{{ $c['unread'] }}</span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-[var(--ms-muted)]">{{ $c['last'] }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>

        <!-- Thread -->
        <section class="flex flex-col rounded-[18px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] {{ $activeId ? 'flex' : 'hidden lg:flex' }}" @if($activeId) wire:poll.3s="refreshThread" @endif>
            @if (! $activeId)
                <div class="flex flex-1 items-center justify-center p-10 text-center text-sm text-[var(--ms-muted)]">
                    Sélectionnez une conversation pour afficher les messages.
                </div>
            @else
                <div class="flex shrink-0 items-center gap-3 border-b border-[var(--ms-bc)] px-[18px] py-3.5">
                    <button wire:click="closeThread" class="p-1 text-[var(--ms-muted)] lg:hidden" aria-label="Retour">
                        <x-ui.icon name="arrow-left" class="size-[18px]" />
                    </button>
                    <span class="flex size-[38px] shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                        {{ $partner ? mb_substr($partner['prenom'], 0, 1).mb_substr($partner['nom'], 0, 1) : '' }}
                    </span>
                    <p class="m-0 text-sm font-bold text-[var(--ms-text)]">{{ $partner ? $partner['prenom'].' '.$partner['nom'] : '' }}</p>
                </div>

                <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto px-[18px] py-4">
                    @foreach ($messages as $m)
                        @php $mine = $m['sender_id'] === $me; @endphp
                        <div class="flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                            <div
                                class="max-w-[78%] rounded-[14px] px-3.5 py-2.5 text-[13.5px] leading-relaxed"
                                style="{{ $mine ? 'background: linear-gradient(135deg, #4F6FBF, #031D59); color: #fff;' : 'background: var(--ms-surf2); color: var(--ms-text); border: 1px solid var(--ms-bc);' }}"
                            >
                                {{ $m['body'] }}
                            </div>
                            <span class="mt-1 px-1 text-[11px] text-[var(--ms-dim)]">
                                {{ \Illuminate\Support\Carbon::parse($m['created_at'])->locale('fr')->translatedFormat('d/m H:i') }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="send" class="flex shrink-0 items-center gap-2.5 border-t border-[var(--ms-bc)] px-3.5 py-3">
                    <input
                        wire:model="body"
                        type="text"
                        placeholder="Votre message…"
                        class="min-w-0 flex-1 rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf2)] px-4 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none"
                    />
                    <button type="submit" aria-label="Envoyer" class="flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-accent text-white transition-colors hover:bg-accent-600">
                        <x-ui.icon name="send" class="size-[15px]" />
                    </button>
                </form>
            @endif
        </section>
    </div>
</x-member.dark-page>
