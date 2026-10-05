<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Recherche « en langage courant » : « Je cherche un plombier à Cocody »
 * devient les mots utiles [plombier, cocody]. Chaque mot doit se retrouver
 * dans au moins un des champs. Les mots vides sont ignorés, le pluriel
 * simple et la fin des mots longs sont retirés (« plombiers » trouve
 * « Plombière ») et les colonnes
 * JSON, qui stockent les accents échappés (é), sont aussi interrogées
 * sous cette forme.
 */
class RechercheMots
{
    private const MOTS_VIDES = [
        'je', 'j', 'cherche', 'chercher', 'recherche', 'besoin', 'trouver', 'voudrais', 'veux',
        'un', 'une', 'des', 'de', 'du', 'd', 'la', 'le', 'les', 'l', 'a', 'à', 'au', 'aux',
        'en', 'pour', 'sur', 'dans', 'et', 'ou', 'qui', 'quelqu', 'quelqu\'un', 'avec', 'vers', 'près', 'pres',
    ];

    /** Mots utiles de la requête. */
    public static function mots(string $q): array
    {
        $mots = [];
        foreach (preg_split('/[\s,;:!?.\'’"()]+/u', mb_strtolower(trim($q)), -1, PREG_SPLIT_NO_EMPTY) as $mot) {
            if (in_array($mot, self::MOTS_VIDES, true)) {
                continue;
            }
            // Pluriel simple : plombiers → plombier, travaux → travau (reste un préfixe valable).
            if (mb_strlen($mot) > 4 && in_array(mb_substr($mot, -1), ['s', 'x'], true)) {
                $mot = mb_substr($mot, 0, -1);
            }
            // Racine pour les mots longs : couvre masculin et féminin
            // (plombier/plombière → plombi, coiffeur/coiffeuse → coiffe).
            if (mb_strlen($mot) >= 7) {
                $mot = mb_substr($mot, 0, -2);
            }
            $mots[] = $mot;
        }

        return array_values(array_unique($mots));
    }

    /**
     * Ajoute à la requête une condition par mot utile : le mot doit
     * apparaître dans l'un des champs texte ou l'une des colonnes JSON.
     */
    public static function appliquer($query, string $q, array $champs, array $champsJson = []): void
    {
        $mysql = DB::connection()->getDriverName() === 'mysql';
        foreach (self::mots($q) as $mot) {
            $echappe = trim(json_encode($mot), '"');
            if ($mysql) {
                $echappe = str_replace('\\', '\\\\', $echappe);
            }
            $query->where(function ($qb) use ($mot, $echappe, $champs, $champsJson) {
                // SQLite ne compare sans casse que l'ASCII : « électricien » doit
                // aussi trouver « Électricien ».
                $variantes = array_unique([$mot, mb_convert_case($mot, MB_CASE_TITLE)]);
                foreach ($champs as $champ) {
                    foreach ($variantes as $v) {
                        $qb->orWhere($champ, 'like', "%{$v}%");
                    }
                }
                foreach ($champsJson as $champ) {
                    $qb->orWhereRaw("LOWER({$champ}) like ?", ["%{$echappe}%"]);
                }
            });
        }
    }
}
