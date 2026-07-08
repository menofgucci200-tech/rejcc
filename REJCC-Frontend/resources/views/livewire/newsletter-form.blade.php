<div>
    <form wire:submit="submit" class="mt-4">
        <div class="flex items-center gap-2 rounded-full border border-white/15 bg-white/5 p-1.5 backdrop-blur">
            <input
                wire:model="email"
                type="email"
                required
                placeholder="Votre adresse e-mail"
                aria-label="Votre adresse e-mail"
                class="min-w-0 flex-1 bg-transparent px-4 py-2 text-sm text-white placeholder:text-white/40 focus:outline-none"
            />
            <button
                type="submit"
                wire:loading.attr="disabled"
                aria-label="S'inscrire à la newsletter"
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-accent text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
            >
                <span wire:loading>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                </span>
                <span wire:loading.remove>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </button>
        </div>
        @error('email')
            <p class="mt-2 px-2 text-xs text-accent/90">{{ $message }}</p>
        @enderror
        @if ($sent)
            <p class="mt-2 px-2 text-xs text-white/70">Merci ! Votre inscription sera confirmée prochainement.</p>
        @endif
    </form>
</div>
