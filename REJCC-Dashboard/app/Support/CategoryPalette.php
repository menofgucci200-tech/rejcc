<?php

namespace App\Support;

/**
 * Attribue une palette (couleur de tag + dégradé) stable à une catégorie de
 * formation. Les couleurs vivent côté frontend : l'API ne stocke que des
 * données métier.
 */
class CategoryPalette
{
    private const PALETTE = [
        ['tag' => '#4F6FBF', 'from' => '#031D59', 'to' => '#4F6FBF'],
        ['tag' => '#AC0100', 'from' => '#AC0100', 'to' => '#F58B8B'],
        ['tag' => '#22A85A', 'from' => '#22A85A', 'to' => '#7FE0A6'],
        ['tag' => '#F5A623', 'from' => '#F5A623', 'to' => '#F7C873'],
        ['tag' => '#4F6FBF', 'from' => '#4F6FBF', 'to' => '#8FA3D9'],
        ['tag' => '#AC0100', 'from' => '#031D59', 'to' => '#AC0100'],
    ];

    /** @return array{tag: string, from: string, to: string} */
    public static function for(string $category): array
    {
        return self::PALETTE[abs(crc32(mb_strtolower($category))) % count(self::PALETTE)];
    }
}
