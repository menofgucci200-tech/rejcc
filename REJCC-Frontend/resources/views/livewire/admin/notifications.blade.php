<div class="max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand">Envoyer une notification</h1>
        <p class="text-sm text-ink/60">Diffusez un message à tous les membres en un clic.</p>
    </div>

    <form wire:submit="send" class="rounded-2xl border border-brand/10 bg-white p-6">
        <div class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-semibold text-brand">Type</label>
                <select wire:model="type" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand">
                    <option value="info">ℹ️ Information</option>
                    <option value="alert">⚠️ Alerte</option>
                    <option value="message">💬 Message</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-brand">Titre <span class="text-accent">*</span></label>
                <input wire:model="title" type="text" placeholder="Ex : Événement de networking — Juin 2026" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-brand">Corps du message</label>
                <textarea wire:model="body" rows="3" placeholder="Détails supplémentaires…" class="w-full resize-none rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-brand">Lien (optionnel)</label>
                <input wire:model="link" type="text" placeholder="/espace-membre/evenements" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
            </div>
        </div>

        @if ($sentTo !== null)
            <div class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                <x-ui.icon name="bell" class="size-4" /> Notification envoyée à {{ $sentTo }} membre{{ $sentTo > 1 ? 's' : '' }}.
            </div>
        @endif

        <button type="submit" wire:loading.attr="disabled" class="mt-5 inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 font-semibold text-white transition hover:bg-accent-600 disabled:opacity-60">
            <span wire:loading.remove>Diffuser à tous les membres</span>
            <span wire:loading>Envoi…</span>
            <x-ui.icon name="send" class="size-4" />
        </button>
    </form>
</div>
