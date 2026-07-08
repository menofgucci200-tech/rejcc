<?php

namespace App\Console\Commands;

use App\Models\MembershipStep;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedIfEmpty extends Command
{
    protected $signature = 'app:seed-if-empty';

    protected $description = 'Peuple le contenu institutionnel et cree le compte admin si la base est neuve (idempotent, pense pour un demarrage de conteneur sans acces shell).';

    public function handle(): int
    {
        if (Sector::count() === 0) {
            $this->call('db:seed', ['--force' => true]);
        }

        // Correction ponctuelle : l'etape "cotisation" a ete retiree du parcours d'adhesion
        // (remplace par un formulaire externe), mais les bases deja seedees gardent l'ancien texte.
        MembershipStep::where('title', 'Réglez votre cotisation')->update([
            'title' => 'Soumettez votre formulaire',
            'text' => "Remplissez notre formulaire d'inscription en ligne, ça ne prend que quelques minutes.",
        ]);

        $adminEmail = env('ADMIN_EMAIL', 'admin@rejcc.ci');

        if (! User::where('email', $adminEmail)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::random(16);

            User::create([
                'name' => 'Administrateur REJCC',
                'email' => $adminEmail,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            if (env('ADMIN_PASSWORD')) {
                $this->info("Compte admin cree : {$adminEmail}");
            } else {
                $this->warn("Compte admin cree : {$adminEmail} / mot de passe genere : {$password}");
            }
        }

        return self::SUCCESS;
    }
}
