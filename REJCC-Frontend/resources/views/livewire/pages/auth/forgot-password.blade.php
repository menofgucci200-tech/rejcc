<?php

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.site')] class extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendLink(): void
    {
        $this->validate([
            'email' => 'required|string|email',
        ]);

        $result = Api::post('/auth/forgot-password', ['email' => $this->email]);

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Une erreur est survenue. Réessayez dans quelques instants.');

            return;
        }

        $this->sent = true;
    }
}; ?>

<div>
    <x-page-header eyebrow="Espace membre" crumb="Mot de passe oublié" subtitle="Indiquez l'adresse e-mail de votre compte : nous vous enverrons un lien pour choisir un nouveau mot de passe.">
        Mot de passe <span class="font-serif italic normal-case text-azure">oublié</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-md">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
                @if ($sent)
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="flex size-12 items-center justify-center rounded-full bg-[#22A85A]/10 text-[#22A85A]">
                            <x-ui.icon name="check-circle" class="size-6" />
                        </span>
                        <p class="text-sm font-semibold text-brand">E-mail envoyé !</p>
                        <p class="text-sm text-ink/70">Si un compte existe avec l'adresse <strong>{{ $email }}</strong>, un lien de réinitialisation vient de lui être envoyé. Pensez à vérifier vos courriers indésirables.</p>
                        <a href="{{ route('login') }}" wire:navigate class="mt-2 text-sm font-semibold text-brand hover:text-accent">← Retour à la connexion</a>
                    </div>
                @else
                    <form wire:submit="sendLink" class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
                            <input wire:model="email" id="email" type="email" required autofocus autocomplete="username"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                            @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-2 flex items-center justify-end gap-3">
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                                <span wire:loading.remove>Envoyer le lien</span>
                                <span wire:loading>Envoi…</span>
                            </button>
                        </div>
                    </form>

                    <p class="mt-6 text-center text-sm text-ink/65">
                        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand hover:text-accent">← Retour à la connexion</a>
                    </p>
                @endif
            </div>
        </x-ui.container>
    </section>
</div>
