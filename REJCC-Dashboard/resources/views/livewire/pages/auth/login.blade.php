<?php

use App\Support\Api;
use App\Support\Espace;
use App\Support\Site;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $email = '';

    public string $password = '';

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

        // Le dashboard n'accueille que l'administration et les départements :
        // un compte membre est renvoyé vers son espace, sans session ouverte ici.
        if (! Espace::autorise($result['user'] ?? null)) {
            Api::post('/auth/logout', [], $result['token']);
            $this->addError('email', 'Ce compte n\'a pas accès au dashboard. Pour votre espace membre, connectez-vous sur '.parse_url(Site::url('/'), PHP_URL_HOST).'.');

            return;
        }

        session()->regenerate();
        session(['api_token' => $result['token'], 'api_user' => $result['user'], 'api_user_synced_at' => now()->timestamp]);

        // Retour vers la page demandée avant la connexion.
        $voulu = (string) session()->pull('url.intended', '');
        $this->redirect(str_starts_with($voulu, '/') && ! str_starts_with($voulu, '//') ? $voulu : Espace::accueil($result['user']), navigate: true);
    }
}; ?>

<div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
    <div class="mb-7 flex items-center gap-3">
        <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="" class="size-11 object-contain">
        <div class="leading-tight">
            <p class="text-[17px] font-bold tracking-wide text-brand">REJCC · Dashboard</p>
            <p class="text-[12.5px] text-[#5B677A]">Administration et départements du Bureau</p>
        </div>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-xl bg-accent/5 px-4 py-3 text-sm font-semibold text-accent">{{ session('status') }}</p>
    @endif

    <form wire:submit="login" class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
            <input wire:model="email" id="email" type="email" required autofocus autocomplete="username"
                class="w-full rounded-xl border bg-white px-4 py-3 text-brand outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
            @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-semibold text-brand">Mot de passe</label>
            <x-ui.password-input wire:model="password" id="password" required autocomplete="current-password"
                class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
            @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
        </div>

        <div class="mt-2 flex items-center justify-between gap-3">
            <a href="{{ Site::url('/mot-de-passe-oublie') }}" class="text-sm font-semibold text-brand hover:text-accent">Mot de passe oublié ?</a>
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                <span wire:loading.remove>Se connecter</span>
                <span wire:loading>Connexion…</span>
            </button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink/65">
        Vous êtes membre du réseau ?
        <a href="{{ Site::url('/connexion') }}" class="font-semibold text-brand hover:text-accent">Accéder à l'espace membre</a>
    </p>
</div>
