<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Pièces d'identité déposées avant le coffre-fort : elles étaient rangées
 * dans le dossier public du site (accessibles à quiconque connaissait
 * l'adresse). `pieces:securiser` les sort du dossier public ; à la visite
 * de ses paramètres, le membre voit la sienne reprise, chiffrée, dans
 * « Mes documents personnels », puis l'ancien fichier est effacé.
 */
class PiecesAnciennes
{
    public const DOSSIER_PUBLIC = 'uploads/membres/pieces';

    public const DOSSIER_PRIVE = 'pieces-anciennes';

    /** Déplace tous les fichiers du dossier public vers le stockage privé. */
    public static function securiser(): int
    {
        $source = public_path(self::DOSSIER_PUBLIC);
        if (! File::isDirectory($source)) {
            return 0;
        }
        $n = 0;
        foreach (File::allFiles($source) as $f) {
            Storage::disk('local')->put(self::DOSSIER_PRIVE.'/'.$f->getRelativePathname(), File::get($f->getPathname()));
            File::delete($f->getPathname());
            $n++;
        }

        return $n;
    }

    /** Contenu de l'ancienne pièce à partir de son adresse (dossier privé, à défaut public). */
    private static function lire(string $url): ?array
    {
        $rel = ltrim((string) str($url)->after('/'.self::DOSSIER_PUBLIC.'/'), '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }
        if (Storage::disk('local')->exists(self::DOSSIER_PRIVE.'/'.$rel)) {
            return [Storage::disk('local')->get(self::DOSSIER_PRIVE.'/'.$rel), $rel, fn () => Storage::disk('local')->delete(self::DOSSIER_PRIVE.'/'.$rel)];
        }
        $pub = public_path(self::DOSSIER_PUBLIC.'/'.$rel);
        if (is_file($pub)) {
            return [File::get($pub), $rel, fn () => File::delete($pub)];
        }

        return null;
    }

    /**
     * Reprend la pièce du membre connecté dans son coffre-fort (partagée avec
     * l'équipe, comme avant) et retire l'ancienne adresse du profil.
     * Renvoie true si une pièce a été reprise.
     */
    public static function importer(object $user, ?string $token): bool
    {
        $url = (string) ($user->piece_identite ?? '');
        if ($url === '') {
            return false;
        }
        $fichier = self::lire($url);
        if ($fichier) {
            [$contenu, $rel, $effacer] = $fichier;
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contenu) ?: 'application/octet-stream';
            $chemin = Coffre::rangerContenu($contenu, (int) $user->id);
            $r = Api::post('/mes-documents', [
                'type' => 'cni', 'titre' => "Pièce d'identité", 'partage' => true,
                'fichier' => $chemin, 'fichier_nom' => 'piece-identite.'.(pathinfo($rel, PATHINFO_EXTENSION) ?: 'pdf'),
                'mime' => $mime, 'octets' => strlen($contenu),
            ], $token);
            if (! ($r['ok'] ?? false)) {
                Coffre::supprimer($chemin);

                return false;
            }
            $effacer();
        }
        $r = Api::put('/auth/profile', ['piece_identite' => null], $token);
        if ($r['ok'] ?? false) {
            session(['api_user' => $r['user']]);
        }

        return (bool) $fichier;
    }
}
