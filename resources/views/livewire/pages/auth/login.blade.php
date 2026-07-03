<?php

use App\Livewire\Forms\LoginForm;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.site')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: AuthRedirect::target(Auth::user()), navigate: true);
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
                        <input wire:model="form.email" id="email" type="email" required autofocus autocomplete="username"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('form.email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('form.email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-semibold text-brand">Mot de passe</label>
                        <input wire:model="form.password" id="password" type="password" required autocomplete="current-password"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('form.password') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('form.password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink/70">
                        <input wire:model="form.remember" type="checkbox" class="rounded border-brand/20 text-accent focus:ring-accent/25" />
                        Se souvenir de moi
                    </label>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-semibold text-brand hover:text-accent">Mot de passe oublié ?</a>
                        @endif
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                            <span wire:loading.remove>Se connecter</span>
                            <span wire:loading>Connexion…</span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-ink/65">
                    Pas encore de compte ?
                    <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand hover:text-accent">Créer un compte</a>
                </p>
            </div>
        </x-ui.container>
    </section>
</div>
