@php $me = \App\Support\Api::user()->id; @endphp

<div>
    <x-member-light.topbar title="Messagerie" />

    @if ($this->locked())
        <x-member-light.paywall description="La messagerie privée entre membres est réservée aux membres à jour de leur abonnement annuel (10 000 F)." />
    @else
    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Messagerie</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
        </div>

        <div class="grid grid-cols-1 gap-4 rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] lg:grid-cols-[320px_1fr]" style="height: calc(100vh - 280px); min-height: 420px" wire:poll.4s="rafraichir">
            <aside class="flex min-h-0 flex-col border-r border-cloud-200 {{ $activeId ? 'hidden lg:flex' : 'flex' }}">
                <div class="flex shrink-0 items-center gap-2 border-b border-cloud-200 p-3">
                    <div class="relative min-w-0 flex-1">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-[#9AA6B8]" />
                        <input wire:model.live.debounce.300ms="recherche" type="search" data-test="recherche-conversations" aria-label="Rechercher une conversation" placeholder="Rechercher…"
                            class="w-full rounded-[10px] border border-brand/10 bg-cloud py-2 pl-9 pr-3 text-[13px] text-ink outline-none focus:border-azure" />
                    </div>
                    <button type="button" wire:click="ouvrirNouveau" data-test="nouveau-message" title="Nouveau message" aria-label="Nouveau message"
                        class="btn-tap flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-accent text-white hover:bg-accent-600"><x-ui.icon name="pencil" class="size-4" /></button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                @if (empty($conversations))
                    <div class="px-6 py-10 text-center">
                        <x-ui.icon name="message-circle" class="mx-auto mb-3 size-8 text-[#9AA6B8]" />
                        @if (trim($recherche) !== '')
                            <p class="text-[13.5px] text-[#5B677A]">Aucune conversation avec « {{ trim($recherche) }} ».</p>
                        @else
                            <p class="mb-4 text-[13.5px] text-[#5B677A]">Aucune conversation pour l'instant. Écrivez à un membre trouvé dans l'annuaire ou dans un groupe.</p>
                            <button type="button" wire:click="ouvrirNouveau" class="btn-tap inline-flex items-center gap-1.5 rounded-[10px] border border-azure/25 bg-azure/10 px-4 py-2 text-[12.5px] font-semibold text-azure hover:bg-azure/20">
                                <x-ui.icon name="pencil" class="size-[13px]" /> Nouveau message
                            </button>
                        @endif
                    </div>
                @else
                    <ul class="list-none py-1.5">
                        @foreach ($conversations as $c)
                            @php
                                $date = \Illuminate\Support\Carbon::parse($c['at'])->setTimezone(config('app.timezone'))->locale('fr');
                                $quand = $date->isToday() ? $date->format('H:i') : ($date->isYesterday() ? 'Hier' : ($date->gt(now()->subDays(6)->startOfDay()) ? $date->isoFormat('ddd') : $date->format('d/m/y')));
                                $nonLu = $c['unread'] > 0;
                            @endphp
                            <li wire:key="conv-{{ $c['user_id'] }}">
                                <button
                                    wire:click="openThread({{ $c['user_id'] }})"
                                    data-test="conversation"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors {{ $activeId === $c['user_id'] ? 'bg-cloud' : 'hover:bg-cloud/60' }}"
                                >
                                    <x-messagerie.avatar :personne="$c" />
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-baseline justify-between gap-2">
                                            <span class="flex min-w-0 items-center gap-1.5">
                                                <span class="truncate text-[13.5px] text-brand {{ $nonLu ? 'font-extrabold' : 'font-semibold' }}">{{ $c['prenom'] }} {{ $c['nom'] }}</span>
                                                @if ($c['role'] === 'mentor')<span class="shrink-0 rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 tracking-[0.05em] text-white">Mentor</span>@endif
                                            </span>
                                            <span data-test="conversation-heure" class="shrink-0 text-[11px] {{ $nonLu ? 'font-bold text-accent' : 'text-[#9AA6B8]' }}">{{ $quand }}</span>
                                        </span>
                                        <span class="mt-0.5 flex items-center justify-between gap-2">
                                            <span data-test="conversation-apercu" class="truncate text-xs {{ $nonLu ? 'font-semibold text-ink' : 'text-[#5B677A]' }}">
                                                @if ($c['last_moi'])<span class="text-[#9AA6B8]">Vous{{ $c['last_vu'] ? ' (vu)' : '' }} :</span> @endif{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $c['last']), 80) }}
                                            </span>
                                            @if ($nonLu)
                                                <span class="flex min-w-5 shrink-0 items-center justify-center rounded-full bg-accent px-1 text-[10.5px] font-bold text-white">{{ $c['unread'] }}</span>
                                            @endif
                                        </span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                </div>
            </aside>

            <section class="flex min-h-0 flex-col {{ $activeId ? 'flex' : 'hidden lg:flex' }}">
                @if (! $activeId)
                    <div class="flex flex-1 items-center justify-center p-10 text-center text-sm text-[#5B677A]">
                        Sélectionnez une conversation pour afficher les messages.
                    </div>
                @elseif (! $partner)
                    <div data-test="fil-introuvable" class="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center">
                        <x-ui.icon name="user-x" class="size-8 text-[#9AA6B8]" />
                        <p class="text-sm text-[#5B677A]">{{ $erreur ?? 'Cette conversation est indisponible.' }}</p>
                        <button wire:click="closeThread" class="text-[12.5px] font-semibold text-azure hover:underline">Retour aux conversations</button>
                    </div>
                @else
                    <div class="flex shrink-0 items-center gap-3 border-b border-cloud-200 px-[18px] py-3.5">
                        <button wire:click="closeThread" class="icon-btn rounded-lg p-1 text-[#5B677A] lg:hidden" aria-label="Retour">
                            <x-ui.icon name="arrow-left" class="size-[18px]" />
                        </button>
                        <x-messagerie.avatar :personne="$partner" taille="size-[38px]" texte="text-xs" />
                        <p data-test="fil-nom" class="text-sm font-bold text-brand">{{ $partner['prenom'].' '.$partner['nom'] }}</p>
                    </div>

                    {{-- Fil : se place sur le dernier message, suit les nouveaux si l'on est déjà en bas --}}
                    <div wire:key="fil-{{ $activeId }}" data-test="fil"
                        x-data
                        x-init="$el.scrollTop = $el.scrollHeight;
                            new MutationObserver(() => { if ($el.scrollHeight - $el.scrollTop - $el.clientHeight < 200) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
                        x-on:message-envoye.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                        class="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto px-[18px] py-4">
                        @forelse ($messages as $m)
                            @php $mine = $m['sender_id'] === $me; @endphp
                            <div wire:key="msg-{{ $m['id'] }}" data-test="message" class="flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                                <div
                                    class="max-w-[78%] whitespace-pre-line break-words rounded-[14px] px-3.5 py-2.5 text-[13.5px] leading-relaxed {{ $mine ? 'text-white' : 'border border-cloud-200 bg-cloud text-ink' }}"
                                    style="{{ $mine ? 'background: linear-gradient(135deg, #4F6FBF, #031D59);' : '' }}"
                                >{{ $m['body'] }}</div>
                                <span class="mt-1 px-1 text-[11px] text-[#9AA6B8]">
                                    {{ \Illuminate\Support\Carbon::parse($m['created_at'])->setTimezone(config('app.timezone'))->locale('fr')->translatedFormat('d/m H:i') }}
                                </span>
                            </div>
                        @empty
                            <p class="m-auto max-w-[300px] text-center text-[13px] text-[#9AA6B8]">Écrivez votre premier message à {{ $partner['prenom'] }}.</p>
                        @endforelse
                    </div>

                    @if ($peutEcrire)
                        <form wire:submit="send" class="shrink-0 border-t border-cloud-200 px-3.5 py-3"
                            x-data="{ n: 0, ajuster() { const t = $refs.saisie; t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 160) + 'px'; this.n = t.value.length } }"
                            x-on:message-envoye.window="$nextTick(() => ajuster())">
                            @if ($erreur)
                                <p data-test="erreur-message" class="mb-2 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>
                            @endif
                            <div class="flex items-end gap-2.5">
                                <textarea
                                    x-ref="saisie"
                                    wire:model="body"
                                    rows="1"
                                    maxlength="2000"
                                    data-test="saisie-message"
                                    aria-label="Votre message"
                                    placeholder="Votre message…"
                                    x-on:input="ajuster()"
                                    x-on:keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); if (! $refs.envoyer.disabled) $refs.envoyer.click() }"
                                    class="max-h-40 min-w-0 flex-1 resize-none rounded-[10px] border border-brand/10 bg-cloud px-4 py-2.5 text-[13.5px] leading-relaxed text-ink outline-none focus:border-azure"
                                ></textarea>
                                <button x-ref="envoyer" type="submit" aria-label="Envoyer" data-test="envoyer-message" wire:loading.attr="disabled" wire:target="send"
                                    class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-accent text-white shadow-sm hover:bg-accent-600 hover:shadow-md disabled:opacity-50">
                                    <x-ui.icon name="send" class="size-[15px]" wire:loading.remove wire:target="send" />
                                    <x-ui.icon name="loader-2" class="size-[15px] animate-spin" wire:loading wire:target="send" />
                                </button>
                            </div>
                            <p class="mt-1.5 flex justify-between px-1 text-[11px] text-[#9AA6B8]">
                                <span class="hidden sm:inline">Entrée pour envoyer · Maj + Entrée pour aller à la ligne</span>
                                <span x-show="n > 1500" style="display: none" :class="n >= 2000 ? 'text-accent font-semibold' : ''" x-text="n + ' / 2000'"></span>
                            </p>
                        </form>
                    @else
                        <p data-test="ecriture-impossible" class="shrink-0 border-t border-cloud-200 px-5 py-4 text-center text-[12.5px] text-[#5B677A]">Ce compte est suspendu : vous ne pouvez plus lui écrire.</p>
                    @endif
                @endif
            </section>
        </div>
    </div>
    @endif
    {{-- Nouveau message : choisir un membre --}}
    @if ($nouveau)
        <div class="fixed inset-0 z-[90] flex items-start justify-center bg-brand/40 p-4 pt-[12vh]" wire:click.self="fermerNouveau" x-on:keydown.escape.window="$wire.fermerNouveau()">
            <div data-test="fenetre-nouveau-message" role="dialog" aria-modal="true" class="panel-enter w-full max-w-[480px] overflow-hidden rounded-[18px] bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-cloud-200 px-5 py-3.5">
                    <p class="text-[15px] font-bold text-brand">Nouveau message</p>
                    <button wire:click="fermerNouveau" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="p-4">
                    <input wire:model.live.debounce.300ms="rechercheMembre" type="search" autofocus data-test="recherche-destinataire" placeholder="Nom, métier, ville…"
                        class="w-full rounded-[10px] border border-brand/15 px-3.5 py-2.5 text-sm outline-none focus:border-azure" />
                    <div class="mt-3 max-h-[50vh] overflow-y-auto">
                        @if (mb_strlen(trim($rechercheMembre)) < 2)
                            <p class="py-6 text-center text-[12.5px] text-[#9AA6B8]">Tapez au moins 2 lettres pour trouver un membre.</p>
                        @else
                            @forelse ($this->membresTrouves as $mt)
                                <button type="button" wire:click="ecrireA({{ $mt['id'] }})" wire:key="mt-{{ $mt['id'] }}" data-test="destinataire" class="flex w-full items-center gap-3 rounded-[12px] px-2.5 py-2 text-left hover:bg-cloud">
                                    <x-messagerie.avatar :personne="$mt" taille="size-9" texte="text-[11px]" />
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold text-brand">{{ $mt['prenom'] }} {{ $mt['nom'] }}</span>
                                        <span class="block truncate text-[11.5px] text-[#5B677A]">{{ collect([$mt['titre'] ?? null, $mt['ville'] ?? null])->filter()->join(' · ') }}</span>
                                    </span>
                                </button>
                            @empty
                                <p class="py-6 text-center text-[12.5px] text-[#9AA6B8]">Aucun membre trouvé.</p>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
