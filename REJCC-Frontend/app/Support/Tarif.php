<?php

namespace App\Support;

use App\Support\Content\SiteRemote;

/**
 * Tarif de l'abonnement annuel, réglé par l'administration (Abonnements)
 * et publié avec les réglages du site.
 */
class Tarif
{
    public static function abonnement(): int
    {
        $v = SiteRemote::setting('abonnement.montant');

        return is_numeric($v) && (int) $v > 0 ? (int) $v : 10000;
    }

    /** « 10 000 F » */
    public static function libelle(?int $montant = null): string
    {
        return number_format($montant ?? self::abonnement(), 0, ',', ' ').' F';
    }
}
