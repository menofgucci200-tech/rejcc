<?php

use App\Models\MemberNotification;
use App\Models\User;
use App\Support\AuthRedirect;
use App\Support\Content\MembershipContent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.site')] class extends Component
{
    public string $prenom = '';
    public string $nom = '';
    public string $email = '';
    public string $telephone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $profil = '';
    public string $ville = '';
    public string $secteur = '';

    public function register(): void
    {
        $validated = $this->validate([
            'prenom' => ['required', 'string', 'min:2', 'max:80'],
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:'.User::class],
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'profil' => ['nullable', 'in:etudiant,porteur,entrepreneur'],
            'ville' => ['nullable', 'string', 'max:80'],
            'secteur' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['prenom'].' '.$validated['nom'],
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'password' => Hash::make($validated['password']),
            'profil' => $validated['profil'] ?: null,
            'ville' => $validated['ville'] ?: null,
            'secteur' => $validated['secteur'] ?: null,
            'role' => 'member',
        ]);

        MemberNotification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Bienvenue au REJCC !',
            'body' => 'Votre espace membre est prêt. Complétez votre profil pour bien démarrer.',
            'link' => '/espace-membre/profil',
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(AuthRedirect::target($user), navigate: true);
    }

    public function with(): array
    {
        return ['profiles' => MembershipContent::profiles()];
    }
}; ?>

<div>
    <x-page-header eyebrow="Espace membre" crumb="Inscription" subtitle="Rejoignez l'espace membre du REJCC : annuaire, ressources et bien plus.">
        Créer un <span class="font-serif italic normal-case text-azure">compte</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-2xl">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
                <form wire:submit="register" class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="prenom" class="text-sm font-semibold text-brand">Prénom</label>
                        <input wire:model="prenom" id="prenom" type="text" required autofocus autocomplete="given-name"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('prenom') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('prenom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="nom" class="text-sm font-semibold text-brand">Nom</label>
                        <input wire:model="nom" id="nom" type="text" required autocomplete="family-name"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('nom') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('nom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
                        <input wire:model="email" id="email" type="email" required autocomplete="username"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <label for="telephone" class="text-sm font-semibold text-brand">Téléphone</label>
                        <input wire:model="telephone" id="telephone" type="text" inputmode="numeric" placeholder="0700000000" required autocomplete="tel"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('telephone') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-semibold text-brand">Mot de passe (8 caractères min.)</label>
                        <input wire:model="password" id="password" type="password" required autocomplete="new-password"
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('password') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('password') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="text-sm font-semibold text-brand">Confirmer le mot de passe</label>
                        <input wire:model="password_confirmation" id="password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="profil" class="text-sm font-semibold text-brand">Profil</label>
                        <select wire:model="profil" id="profil" class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 pr-10 text-brand outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20">
                            <option value="">Sélectionnez…</option>
                            @foreach ($profiles as $p)
                                <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        @error('profil') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ville" class="text-sm font-semibold text-brand">Ville</label>
                        <input wire:model="ville" id="ville" type="text" placeholder="Abidjan" autocomplete="address-level2"
                            class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                    </div>

                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <label for="secteur" class="text-sm font-semibold text-brand">Secteur</label>
                        <input wire:model="secteur" id="secteur" type="text"
                            class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="sm:col-span-2 inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                        <span wire:loading.remove>Créer mon compte</span>
                        <span wire:loading>Création…</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-ink/65">
                    Déjà membre ?
                    <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand hover:text-accent">Se connecter</a>
                </p>
            </div>
        </x-ui.container>
    </section>
</div>
