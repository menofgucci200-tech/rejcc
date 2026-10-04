<?php

namespace App\Support;

/**
 * Taux de complétion du profil membre — calcul unique partagé par le tableau
 * de bord et la page Paramètres, pour que les deux affichent le même chiffre.
 */
class ProfileCompletion
{
    /** Champs pris en compte et leur libellé (pour dire au membre ce qui manque). */
    public const FIELDS = [
        'prenom' => 'prénom',
        'nom' => 'nom',
        'telephone' => 'téléphone',
        'ville' => 'ville',
        'paroisse' => 'paroisse',
        'genre' => 'genre',
        'secteur' => 'secteur d\'activité',
        'profil' => 'profil',
        'bio' => 'présentation',
        'photo' => 'photo',
    ];

    /** Libellés des champs encore vides. */
    public static function missing(array|object|null $user): array
    {
        $user = (array) $user;

        return array_values(array_filter(
            self::FIELDS,
            fn (string $label, string $key) => trim((string) ($user[$key] ?? '')) === '',
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    public static function percent(array|object|null $user): int
    {
        $filled = count(self::FIELDS) - count(self::missing($user));

        return (int) round($filled / count(self::FIELDS) * 100);
    }
}
