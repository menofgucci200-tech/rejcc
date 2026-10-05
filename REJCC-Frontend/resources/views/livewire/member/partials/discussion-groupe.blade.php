{{-- Discussion du groupe : fil de messages entre membres abonnés du groupe, sur la plateforme. --}}
@if ($refusDiscussion)
    <div data-test="discussion-refus" class="flex flex-col items-center rounded-[18px] border border-brand/10 bg-white px-8 py-14 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
        <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-[#F5A623]/10 text-[#B27007]"><x-ui.icon name="lock" class="size-7" /></span>
        <h2 class="mb-2 text-[16px] font-bold text-brand">Discussion réservée aux membres du groupe</h2>
        <p class="max-w-md text-[13px] leading-relaxed text-[#5B677A]">{{ $refusDiscussion['message'] }}</p>
        @if (($refusDiscussion['code'] ?? null) === 'pas_membre')
            <a href="{{ route('espace-membre.groupes', ['rejoindre' => $groupId]) }}" wire:navigate data-test="rejoindre-pour-discuter" class="btn-tap mt-5 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">Rejoindre le groupe</a>
        @elseif (($refusDiscussion['code'] ?? null) === 'subscription_required')
            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap mt-5 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">M'abonner</a>
        @endif
    </div>
@else
    <div data-test="discussion-groupe" wire:poll.6s="rafraichirDiscussion" class="flex flex-col overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]" style="height: min(600px, calc(100vh - 160px)); min-height: 400px">
        <div x-data
            x-init="$el.scrollTop = $el.scrollHeight;
                new MutationObserver(() => { if ($el.scrollHeight - $el.scrollTop - $el.clientHeight < 200) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
            x-on:message-envoye.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
            class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto px-5 py-4">
            @php $jourPrecedent = null; @endphp
            @forelse ($discussion as $m)
                @php
                    $date = \Illuminate\Support\Carbon::parse($m['date'])->setTimezone(config('app.timezone'))->locale('fr');
                    $jour = $date->toDateString();
                    $a = $m['auteur'] ?? ['prenom' => '?', 'nom' => '', 'photo' => null, 'role' => 'member', 'referent' => false];
                @endphp
                @if ($jour !== $jourPrecedent)
                    <div wire:key="dj-{{ $jour }}" class="my-1 flex items-center gap-3 text-[11px] font-semibold text-[#9AA6B8]">
                        <span class="h-px flex-1 bg-cloud-200"></span>
                        {{ $date->isToday() ? "Aujourd'hui" : ($date->isYesterday() ? 'Hier' : ucfirst($date->isoFormat('dddd D MMMM'))) }}
                        <span class="h-px flex-1 bg-cloud-200"></span>
                    </div>
                    @php $jourPrecedent = $jour; @endphp
                @endif
                <div wire:key="dm-{{ $m['id'] }}" data-test="message-groupe" class="group/msg flex gap-3">
                    <x-messagerie.avatar :personne="$a" taille="size-9" texte="text-[11px]" />
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-baseline gap-x-2 text-[12.5px]">
                            <span class="font-bold {{ $m['moi'] ? 'text-azure' : 'text-brand' }}">{{ $m['moi'] ? 'Vous' : trim($a['prenom'].' '.$a['nom']) }}</span>
                            @if ($a['referent'] ?? false)<span class="rounded-full bg-brand px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Référent</span>@endif
                            @if (($a['role'] ?? '') === 'mentor')<span class="rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Mentor</span>@endif
                            <span class="text-[11px] text-[#9AA6B8]">{{ $date->format('H:i') }}</span>
                            @if ($m['peut_supprimer'])
                                <button type="button" wire:click="supprimerMessage({{ $m['id'] }})" wire:confirm="Supprimer ce message de la discussion ?" data-test="supprimer-message-groupe" class="text-[11px] font-semibold text-[#9AA6B8] opacity-0 transition-opacity hover:text-accent group-hover/msg:opacity-100 focus:opacity-100">Supprimer</button>
                            @endif
                        </p>
                        <p class="mt-0.5 whitespace-pre-line break-words text-[13.5px] leading-relaxed text-ink">{!! \App\Support\Texte::liens($m['body'], 'font-semibold text-azure underline underline-offset-2') !!}</p>
                    </div>
                </div>
            @empty
                <div class="m-auto max-w-[360px] text-center">
                    <x-ui.icon name="message-circle" class="mx-auto mb-3 size-8 text-[#9AA6B8]" />
                    <p class="text-[13px] text-[#5B677A]">La discussion du groupe est ouverte. Présentez-vous, partagez une opportunité ou posez une question aux membres du groupe.</p>
                </div>
            @endforelse
        </div>

        <form wire:submit="ecrire" class="shrink-0 border-t border-cloud-200 px-4 py-3"
            x-data="{ ajuster() { const t = $refs.saisie; t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 160) + 'px' } }"
            x-on:message-envoye.window="$nextTick(() => ajuster())">
            @if ($erreurDiscussion)<p data-test="erreur-discussion" class="mb-2 text-[12.5px] font-semibold text-accent">{{ $erreurDiscussion }}</p>@endif
            <div class="flex items-end gap-2.5">
                <textarea x-ref="saisie" wire:model="saisie" rows="1" maxlength="2000" data-test="saisie-discussion" aria-label="Votre message au groupe"
                    placeholder="Écrire au groupe {{ $groupe['name'] ?? '' }}…"
                    x-on:input="ajuster()"
                    x-on:keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); if (! $refs.envoyer.disabled) $refs.envoyer.click() }"
                    class="max-h-40 min-w-0 flex-1 resize-none rounded-[10px] border border-brand/10 bg-cloud px-4 py-2.5 text-[13.5px] leading-relaxed text-ink outline-none focus:border-azure"></textarea>
                <button x-ref="envoyer" type="submit" aria-label="Envoyer" data-test="envoyer-discussion" wire:loading.attr="disabled" wire:target="ecrire"
                    class="btn-tap flex size-[42px] shrink-0 items-center justify-center rounded-[11px] bg-accent text-white hover:bg-accent-600 disabled:opacity-50"><x-ui.icon name="send" class="size-[15px]" /></button>
            </div>
            <p class="mt-1.5 hidden px-1 text-[11px] text-[#9AA6B8] sm:block">Visible des membres du groupe · Entrée pour envoyer · Maj + Entrée pour aller à la ligne</p>
        </form>
    </div>
@endif
