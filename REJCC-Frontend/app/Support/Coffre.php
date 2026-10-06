<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Coffre-fort des documents personnels : les fichiers sont chiffrés (clé de
 * l'application) dans un dossier propre à chaque membre, sur le disque privé.
 * Attention : changer APP_KEY rendrait ces fichiers illisibles.
 */
class Coffre
{
    public static function dossier(int $userId): string
    {
        return 'personnels/'.$userId;
    }

    /** Chiffre et range le fichier ; renvoie son chemin (nom aléatoire, sans lien avec le nom d'origine). */
    public static function ranger(UploadedFile $fichier, int $userId): string
    {
        $chemin = self::dossier($userId).'/'.Str::random(40).'.enc';
        Storage::disk('local')->put($chemin, Crypt::encryptString(base64_encode($fichier->get())));

        return $chemin;
    }

    public static function lire(string $chemin): ?string
    {
        if (! Storage::disk('local')->exists($chemin)) {
            return null;
        }

        return base64_decode(Crypt::decryptString(Storage::disk('local')->get($chemin)));
    }

    public static function supprimer(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, 'personnels/')) {
            Storage::disk('local')->delete($chemin);
        }
    }

    /** Suppression du compte d'un membre : tout son coffre-fort disparaît. */
    public static function vider(int $userId): void
    {
        Storage::disk('local')->deleteDirectory(self::dossier($userId));
    }
}
