<div>
@if ($sent)
    <div class="flex flex-col items-center justify-center rounded-3xl border border-brand/10 bg-cloud p-10 text-center">
        <span class="inline-flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-7"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <h3 class="mt-5 text-xl font-bold text-brand">Demande envoyée&nbsp;!</h3>
        <p class="mt-2 text-sm text-ink/70">Merci de votre intérêt. L'équipe du REJCC reviendra vers vous très prochainement pour échanger sur ce partenariat.</p>
    </div>
@else
    <form wire:submit="submit" class="flex flex-col gap-4 rounded-3xl border border-brand/10 bg-white p-6 sm:p-8">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="p-org" class="text-sm font-semibold text-brand">Organisation</label>
                <input wire:model="organisation" id="p-org" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('organisation') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('organisation') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="p-contact" class="text-sm font-semibold text-brand">Personne de contact</label>
                <input wire:model="contact" id="p-contact" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('contact') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('contact') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="p-email" class="text-sm font-semibold text-brand">E-mail</label>
                <input wire:model="email" id="p-email" type="email" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="p-tel" class="text-sm font-semibold text-brand">Téléphone</label>
                <input wire:model="telephone" id="p-tel" type="text" inputmode="numeric" placeholder="0700000000" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('telephone') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="p-type" class="text-sm font-semibold text-brand">Type de partenariat</label>
            <select wire:model="type" id="p-type" class="w-full rounded-xl border bg-white px-4 py-3 pr-10 text-brand outline-none transition focus:ring-2 {{ $errors->has('type') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}">
                <option value="">Sélectionnez…</option>
                @foreach ($partnershipTypes as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
            @error('type') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="p-message" class="text-sm font-semibold text-brand">Votre message</label>
            <textarea wire:model="message" id="p-message" class="min-h-32 w-full resize-y rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('message') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}"></textarea>
            @error('message') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 self-start rounded-full bg-accent px-7 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
            <span wire:loading.remove>Envoyer la demande</span>
            <span wire:loading>Envoi…</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
        </button>
    </form>
@endif
</div>
