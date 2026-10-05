<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'category', 'description',
        'price', 'contact', 'photo', 'statut', 'reject_reason', 'vues', 'contacts',
        'publie_le', 'expire_le', 'rappel_expiration_at',
    ];

    /** Durée de publication d'une annonce, en jours. */
    public const DUREE_JOURS = 90;

    /** Rappel avant expiration, en jours. */
    public const RAPPEL_JOURS = 7;

    protected function casts(): array
    {
        return ['publie_le' => 'datetime', 'expire_le' => 'datetime', 'rappel_expiration_at' => 'datetime'];
    }

    /** Annonces visibles dans le catalogue : validées et non expirées. */
    public function scopeEnLigne($query)
    {
        return $query->where('statut', 'approuve')
            ->where(fn ($q) => $q->whereNull('expire_le')->orWhere('expire_le', '>', now()));
    }

    /**
     * Fait expirer les annonces arrivées à échéance et prévient leurs
     * vendeurs (rappel 7 jours avant, puis avis d'expiration).
     */
    public static function traiterEcheances(?int $userId = null): array
    {
        $base = static::query()->where('statut', 'approuve')->when($userId, fn ($q) => $q->where('user_id', $userId));

        $rappels = 0;
        foreach ((clone $base)->whereNull('rappel_expiration_at')->whereBetween('expire_le', [now(), now()->addDays(self::RAPPEL_JOURS)])->get() as $l) {
            $jours = max(1, (int) ceil(now()->diffInHours($l->expire_le) / 24));
            MemberNotification::create([
                'user_id' => $l->user_id,
                'type' => 'info',
                'title' => 'Votre annonce expire bientôt',
                'body' => "« {$l->title} » ne sera plus visible dans {$jours} jour".($jours > 1 ? 's' : '').'. Renouvelez-la en un clic depuis « Mes annonces ».',
                'link' => '/espace-membre/marketplace?onglet=mes-annonces',
            ]);
            $l->update(['rappel_expiration_at' => now()]);
            $rappels++;
        }

        $expirees = 0;
        foreach ((clone $base)->where('expire_le', '<=', now())->get() as $l) {
            $l->update(['statut' => 'expiree']);
            MemberNotification::create([
                'user_id' => $l->user_id,
                'type' => 'info',
                'title' => 'Annonce expirée',
                'body' => "« {$l->title} » n'est plus visible sur la Marketplace. Vous pouvez la renouveler pour ".self::DUREE_JOURS.' jours depuis « Mes annonces ».',
                'link' => '/espace-membre/marketplace?onglet=mes-annonces',
            ]);
            $expirees++;
        }

        return ['rappels' => $rappels, 'expirees' => $expirees];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
