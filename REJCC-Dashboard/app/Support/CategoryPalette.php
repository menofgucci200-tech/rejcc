<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Couleur d'une catégorie (formation ou événement), choisie selon son sujet et
 * non tirée au hasard : la même catégorie garde toujours la même couleur.
 *
 * Le rouge de la charte est exclu : il est réservé à l'action principale et
 * aux alertes. Les couleurs vivent côté frontend : l'API ne stocke que des
 * données métier.
 */
class CategoryPalette
{
    /**
     * « tag » sert de texte sur fond clair (contraste ≥ 4,5:1) ;
     * « from » → « to » est un dégradé assez sombre pour un texte blanc.
     *
     * @var array<string, array{tag: string, from: string, to: string}>
     */
    private const TEINTES = [
        'marine' => ['tag' => '#031D59', 'from' => '#021541', 'to' => '#2E4A8F'],
        'azur' => ['tag' => '#3D5AA6', 'from' => '#031D59', 'to' => '#4F6FBF'],
        'vert' => ['tag' => '#17734B', 'from' => '#14663F', 'to' => '#2E9A68'],
        'or' => ['tag' => '#8A5A00', 'from' => '#7A4E00', 'to' => '#C98A0B'],
    ];

    /**
     * Mots-clés (sans accents, en minuscules) → teinte. Le premier qui
     * correspond l'emporte.
     *
     * @var array<string, list<string>>
     */
    private const SUJETS = [
        'vert' => ['agri', 'agro', 'elevage', 'peche', 'environnement', 'ecolog', 'climat', 'sante', 'nature', 'energie'],
        'or' => ['entrepren', 'commerce', 'vente', 'marketing', 'business', 'emploi', 'carriere', 'projet', 'innovation'],
        'azur' => ['financ', 'compta', 'fiscal', 'banque', 'gestion', 'numerique', 'digital', 'informatique', 'tech', 'juridique', 'droit', 'communication'],
        'marine' => ['leadership', 'management', 'gouvernance', 'live', 'conference', 'spiritu', 'mentorat', 'reseau', 'networking', 'assemblee'],
    ];

    /** @return array{tag: string, from: string, to: string} */
    public static function for(string $category): array
    {
        $sujet = Str::lower(Str::ascii($category));

        foreach (self::SUJETS as $teinte => $motsCles) {
            if (Str::contains($sujet, $motsCles)) {
                return self::TEINTES[$teinte];
            }
        }

        // Catégorie inconnue : une teinte calme, stable pour ce nom.
        $teintes = array_values(self::TEINTES);

        return $teintes[abs(crc32($sujet)) % count($teintes)];
    }
}
