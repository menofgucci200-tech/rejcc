@php
    $typeLabels = ['info' => 'Information', 'message' => 'Message', 'alert' => 'Urgent'];
@endphp
<div>
    <x-admin-light.topbar title="Notifications" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Diffuser une notification</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <form wire:submit="send" class="flex flex-col gap-3 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @if ($membres)
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Destinataire</label>
                            <select wire:model.live="cible" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                                <option value="">Tous les membres</option>
                                @foreach ($membres as $m)
                                    <option value="{{ $m['id'] }}">{{ $m['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                        <input wire:model="title" type="text" placeholder="Ex : Nouvel atelier disponible" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Message</label>
                        <textarea wire:model="body" rows="3" placeholder="Contenu du message…" class="w-full resize-y rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Lien (optionnel)</label>
                        <input wire:model="link" type="text" placeholder="/espace-membre/evenements" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type</label>
                        <select wire:model="type" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                            <option value="info">Information</option>
                            <option value="message">Message</option>
                            <option value="alert">Urgent</option>
                        </select>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-accent py-2.5 text-sm font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md disabled:opacity-60">
                        <span wire:loading.remove>{{ $cible !== '' ? 'Envoyer à ce membre' : 'Envoyer à tous les membres' }}</span>
                        <span wire:loading>Envoi…</span>
                    </button>
                    @if ($sentTo !== null)
                        <p class="text-xs font-semibold text-[#22A85A]">Notification envoyée à {{ $sentTo }} membre{{ $sentTo > 1 ? 's' : '' }}.</p>
                    @endif
                </form>
            </section>

            <section>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Historique des diffusions</h2>
                <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
                <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @forelse ($historique as $h)
                        <div class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                            <p class="text-[13px] font-bold text-brand">{{ $h->title }}</p>
                            <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $typeLabels[$h->type] ?? $h->type }} · envoyée {{ $h->created_at->diffForHumans() }} · {{ $h->destinataires }} destinataires</p>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-[#5B677A]">Aucune diffusion pour le moment.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
