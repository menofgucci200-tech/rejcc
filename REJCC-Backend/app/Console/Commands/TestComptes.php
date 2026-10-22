<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Campagne de test sur le site réel : crée (ou met à jour) les comptes de
 * test des départements à partir d'un fichier CSV (séparateur « ; » ou « , ») :
 *
 *   departement;prenom;nom;email;acces;sections
 *   Communication;Awa;Koné;awa.kone@exemple.ci;admin;actualites|evenements
 *   Communication;Awa;Koné;awa.kone+membre@exemple.ci;membre;
 *
 * acces : « membre » (espace membre) ou « admin » (administration). Pour un
 * admin, sections limite l'accès aux rubriques listées (slugs séparés par
 * « | ») ; vide = toutes, sauf celles interdites aux comptes de test
 * (newsletter, notifications, adhésions). Les mots de passe générés sont
 * écrits dans storage/app/private/identifiants-test-*.csv.
 *
 * Un compte existant qui n'est pas un compte de test n'est jamais modifié.
 */
class TestComptes extends Command
{
    protected $signature = 'test:comptes {fichier : chemin du CSV}
                            {--nouveaux-mots-de-passe : régénère aussi le mot de passe des comptes existants}';

    protected $description = "Campagne de test : crée les comptes de test des départements à partir d'un CSV.";

    public const SECTIONS = [
        'adhesions', 'membres', 'formations', 'evenements', 'projets', 'communaute', 'certificats',
        'opportunites', 'mentors', 'groupes', 'actualites', 'contenu', 'newsletter', 'documents',
        'contacts', 'messagerie', 'partenariats', 'notifications', 'audit', 'suggestions',
    ];

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_readable($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $lignes = $this->lire($fichier);
        $erreurs = [];
        foreach ($lignes as $n => $l) {
            [$dep, $prenom, $nom, $email, $acces] = $l;
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = 'ligne '.($n + 1)." : e-mail invalide « {$email} »";
            }
            if (! in_array($acces, ['membre', 'admin'], true)) {
                $erreurs[] = 'ligne '.($n + 1)." : accès « {$acces} » (attendu : membre ou admin)";
            }
            foreach ($this->sections($l[5] ?? '') as $s) {
                if (in_array($s, User::RUBRIQUES_INTERDITES_TESTEURS, true)) {
                    $erreurs[] = 'ligne '.($n + 1)." : la rubrique « {$s} » n'est pas ouverte aux comptes de test";
                } elseif (! in_array($s, self::SECTIONS, true)) {
                    $erreurs[] = 'ligne '.($n + 1)." : rubrique inconnue « {$s} » (possibles : ".implode(', ', $this->sectionsOuvertes()).')';
                }
            }
            $existant = User::where('email', $email)->first();
            if ($existant && ! $existant->estTesteur()) {
                $erreurs[] = 'ligne '.($n + 1)." : {$email} est un vrai compte de la plateforme, il ne sera pas transformé en compte de test";
            }
            if ($dep === '' || $prenom === '' || $nom === '') {
                $erreurs[] = 'ligne '.($n + 1).' : département, prénom et nom sont obligatoires';
            }
        }
        if ($erreurs) {
            foreach ($erreurs as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $resultat = [];
        foreach ($lignes as $l) {
            [$dep, $prenom, $nom, $email, $acces] = $l;
            $sections = $this->sections($l[5] ?? '');
            $user = User::where('email', $email)->first();
            $motDePasse = null;
            if (! $user || $this->option('nouveaux-mots-de-passe')) {
                $motDePasse = $this->motDePasse();
            }

            $donnees = [
                'name' => "{$prenom} {$nom}",
                'prenom' => $prenom,
                'nom' => $nom,
                'role' => $acces === 'admin' ? 'admin' : 'member',
                'permissions' => $acces === 'admin' ? ($sections ?: $this->sectionsOuvertes()) : null,
                'departement' => $dep,
                'is_active' => true,
                // Testeurs membres : accès complet, abonnement compris.
                'subscription_expires_at' => now()->addYear(),
            ] + ($motDePasse ? ['password' => $motDePasse] : []);

            if ($user) {
                $user->update($donnees);
            } else {
                $user = User::create($donnees + ['email' => $email, 'conditions_acceptees_at' => now()]);
            }
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

            $resultat[] = [$dep, "{$prenom} {$nom}", $email, $acces === 'admin' ? 'Administration ('.($sections ? implode(', ', $sections) : 'toutes rubriques ouvertes').')' : 'Espace membre', $motDePasse ?? '(inchangé)'];
        }

        $this->table(['Département', 'Nom', 'E-mail', 'Accès', 'Mot de passe'], $resultat);

        $chemin = 'identifiants-test-'.now()->format('Ymd-His').'.csv';
        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(';', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $r)),
            array_merge([['Département', 'Nom', 'E-mail', 'Accès', 'Mot de passe']], $resultat)))."\n";
        Storage::disk('local')->put($chemin, $csv);
        $this->info(count($resultat).' compte(s) prêt(s). Identifiants : '.Storage::disk('local')->path($chemin));

        return self::SUCCESS;
    }

    /** Lignes du CSV, sans l'en-tête ni les lignes vides. */
    private function lire(string $fichier): array
    {
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($fichier));
        $lignes = array_values(array_filter(preg_split('/\r\n|\r|\n/', $contenu), fn ($l) => trim($l) !== ''));
        $sep = substr_count($lignes[0] ?? '', ';') >= substr_count($lignes[0] ?? '', ',') ? ';' : ',';
        $lignes = array_map(fn ($l) => array_map('trim', str_getcsv($l, $sep)), $lignes);
        if ($lignes && in_array(mb_strtolower($lignes[0][0] ?? ''), ['departement', 'département'], true)) {
            array_shift($lignes);
        }

        return array_map(fn ($l) => [$l[0] ?? '', $l[1] ?? '', $l[2] ?? '', mb_strtolower($l[3] ?? ''), mb_strtolower($l[4] ?? ''), $l[5] ?? ''], $lignes);
    }

    /** Rubriques qu'un compte de test peut recevoir. */
    private function sectionsOuvertes(): array
    {
        return array_values(array_diff(self::SECTIONS, User::RUBRIQUES_INTERDITES_TESTEURS));
    }

    private function sections(string $valeur): array
    {
        return array_values(array_filter(array_map(fn ($s) => mb_strtolower(trim($s)), preg_split('/[|\s]+/', $valeur))));
    }

    /** Mot de passe lisible à dicter ou recopier : « Rejcc-K7P4-2958 ». */
    private function motDePasse(): string
    {
        $lettres = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $bloc = '';
        for ($i = 0; $i < 4; $i++) {
            $bloc .= $lettres[random_int(0, strlen($lettres) - 1)];
        }

        return 'Rejcc-'.$bloc.'-'.random_int(1000, 9999);
    }
}
