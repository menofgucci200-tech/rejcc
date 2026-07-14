<?php

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.site')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->token = (string) request()->query('token', '');
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $result = Api::post('/auth/reset-password', [
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        if (! ($result['ok'] ?? false)) {
            $this->addError('password', $result['message'] ?? 'Une erreur est survenue. Réessayez.');

            return;
        }

        session()->flash('status', 'Mot de passe réinitialisé ! Vous pouvez maintenant vous connecter.');

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <x-page-header eyebrow="Espace membre" crumb="Nouveau mot de passe" subtitle="Choisissez un nouveau mot de passe pour votre compte.">
        Nouveau <span class="font-serif italic normal-case text-azure">mot de passe</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-md">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
                <form wire:submit="resetPassword" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
                        <input wire:model="email" id="email" type="email" required autocomplete="username"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-semibold text-brand">Nouveau mot de passe</label>
                        <x-ui.password-input wire:model="password" id="password" required autocomplete="new-password"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('password') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="text-sm font-semibold text-brand">Confirmer le mot de passe</label>
                        <x-ui.password-input wire:model="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                            class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                    </div>

                    <div class="mt-2 flex items-center justify-end gap-3">
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                            <span wire:loading.remove>Réinitialiser le mot de passe</span>
                            <span wire:loading>Réinitialisation…</span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-ink/65">
                    <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand hover:text-accent">← Retour à la connexion</a>
                </p>
            </div>
        </x-ui.container>
    </section>
</div>
