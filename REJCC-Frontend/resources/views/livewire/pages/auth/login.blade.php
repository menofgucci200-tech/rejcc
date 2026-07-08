<?php

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.site')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $result = Api::post('/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Identifiant ou mot de passe incorrect.');

            return;
        }

        session(['api_token' => $result['token'], 'api_user' => $result['user']]);
        session()->regenerate();

        $target = ($result['user']['role'] ?? null) === 'admin' ? '/admin' : '/espace-membre';

        $this->redirect($target, navigate: true);
    }
}; ?>

<div>
    <x-page-header eyebrow="Espace membre" crumb="Connexion" subtitle="Accédez à votre tableau de bord, à l'annuaire des membres et aux ressources du réseau.">
        Se <span class="font-serif italic normal-case text-azure">connecter</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-md">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form wire:submit="login" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
                        <input wire:model="email" id="email" type="email" required autofocus autocomplete="username"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-semibold text-brand">Mot de passe</label>
                        <input wire:model="password" id="password" type="password" required autocomplete="current-password"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('password') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink/70">
                        <input wire:model="remember" type="checkbox" class="rounded border-brand/20 text-accent focus:ring-accent/25" />
                        Se souvenir de moi
                    </label>

                    <div class="mt-2 flex items-center justify-end gap-3">
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                            <span wire:loading.remove>Se connecter</span>
                            <span wire:loading>Connexion…</span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-ink/65">
                    Pas encore de compte ?
                    <a href="{{ route('adhesion') }}" wire:navigate class="font-semibold text-brand hover:text-accent">Faire une demande d'adhésion</a>
                </p>
                <p class="mt-2 text-center text-sm text-ink/65">
                    <a href="{{ route('adhesion.status') }}" wire:navigate class="font-semibold text-brand hover:text-accent">Suivre l'état de ma candidature</a>
                </p>
            </div>
        </x-ui.container>
    </section>
</div>
