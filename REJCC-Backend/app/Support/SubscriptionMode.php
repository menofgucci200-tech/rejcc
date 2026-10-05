<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Interrupteur général des abonnements, piloté depuis le tableau de bord admin.
 *
 * - Désactivé (par défaut, tant que le paiement en ligne n'est pas branché) :
 *   aucune restriction, tous les membres accèdent à tout.
 * - Activé : les fonctionnalités premium (carte, annuaire, messagerie, projets,
 *   publication marketplace…) sont réservées aux abonnés à jour.
 *
 * Valeur enregistrée dans les réglages (clé subscription.enforced) ; sans
 * réglage, on retombe sur SUBSCRIPTIONS_ENFORCED (config/rejcc.php).
 */
class SubscriptionMode
{
    public const KEY = 'subscription.enforced';

    public static function enforced(): bool
    {
        return once(function () {
            $value = SiteSetting::where('key', self::KEY)->value('value');

            return $value === null ? (bool) config('rejcc.subscriptions_enforced') : (bool) $value;
        });
    }

    public static function set(bool $enforced): void
    {
        SiteSetting::updateOrCreate(['key' => self::KEY], ['value' => $enforced]);
        SiteSetting::updateOrCreate(['key' => 'subscription.enforced_changed_at'], ['value' => now()->toIso8601String()]);
        \Illuminate\Support\Once::flush();
    }
}
