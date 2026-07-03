<div>
@if ($sent)
    <div class="flex h-full flex-col items-center justify-center rounded-3xl border border-brand/10 bg-cloud p-10 text-center">
        <span class="inline-flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-7"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <h3 class="mt-5 text-xl font-bold text-brand">Message envoyé&nbsp;!</h3>
        <p class="mt-2 text-sm text-ink/70">Merci, l'équipe du REJCC vous répondra dans les meilleurs délais.</p>
    </div>
@else
    <form wire:submit="submit" class="flex flex-col gap-4 rounded-3xl border border-brand/10 bg-white p-6 sm:p-8">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="c-nom" class="text-sm font-semibold text-brand">Nom complet</label>
                <input wire:model="nom" id="c-nom" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('nom') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('nom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="c-email" class="text-sm font-semibold text-brand">E-mail</label>
                <input wire:model="email" id="c-email" type="email" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="c-sujet" class="text-sm font-semibold text-brand">Sujet</label>
            <input wire:model="sujet" id="c-sujet" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('sujet') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
            @error('sujet') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="c-message" class="text-sm font-semibold text-brand">Message</label>
            <textarea wire:model="message" id="c-message" class="min-h-32 w-full resize-y rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('message') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}"></textarea>
            @error('message') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 self-start rounded-full bg-accent px-7 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
            <span wire:loading.remove>Envoyer le message</span>
            <span wire:loading>Envoi…</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
        </button>
    </form>
@endif
</div>
