<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'category', 'group_id', 'description',
        'price', 'prix_valeur', 'contact', 'photo', 'statut', 'reject_reason', 'vues', 'contacts',
        'publie_le', 'expire_le', 'rappel_expiration_at', 'suspension_notifiee_at',
    ];

    /** Durée de publication d'une annonce, en jours. */
    public const DUREE_JOURS = 90;

    /** Rappel avant expiration, en jours. */
    public const RAPPEL_JOURS = 7;

    protected function casts(): array
    {
        return ['publie_le' => 'datetime', 'expire_le' => 'datetime', 'rappel_expiration_at' => 'datetime', 'suspension_notifiee_at' => 'datetime'];
    }

    /**
     * Annonces visibles dans le catalogue : validées, non expirées, d'un
     * vendeur actif et à jour de son abonnement (mentors et admins dispensés).
     */
    public function scopeEnLigne($query)
    {
        return $query->where('marketplace_listings.statut', 'approuve')
            ->where(fn ($q) => $q->whereNull('marketplace_listings.expire_le')->orWhere('marketplace_listings.expire_le', '>', now()))
            ->whereHas('user', fn ($u) => self::vendeurEnRegle($u));
    }

    /** Condition « vendeur actif et abonné » sur une requête de User. */
    public static function vendeurEnRegle($u)
    {
        return $u->where('is_active', true)->when(\App\Support\SubscriptionMode::enforced(), fn ($q) => $q->where(
            fn ($w) => $w->whereIn('role', ['admin', 'mentor'])->orWhere('subscription_expires_at', '>', now())
        ));
    }

    /** Annonce validée mais masquée parce que l'abonnement du vendeur a expiré. */
    public function suspendue(): bool
    {
        return $this->statut === 'approuve' && $this->user && ! $this->user->hasActiveSubscription();
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

        // Abonnement du vendeur expiré : ses annonces sont masquées, il est prévenu une fois.
        $suspendues = 0;
        if (\App\Support\SubscriptionMode::enforced()) {
            $concernees = (clone $base)->whereNull('suspension_notifiee_at')
                ->whereHas('user', fn ($u) => $u->where('is_active', true)->whereNotIn('role', ['admin', 'mentor'])
                    ->where(fn ($w) => $w->whereNull('subscription_expires_at')->orWhere('subscription_expires_at', '<=', now())))
                ->get()->groupBy('user_id');
            foreach ($concernees as $vendeurId => $annonces) {
                MemberNotification::create([
                    'user_id' => $vendeurId,
                    'type' => 'warning',
                    'title' => 'Vos annonces sont suspendues',
                    'body' => $annonces->count() > 1
                        ? "Votre abonnement annuel a expiré : vos {$annonces->count()} annonces ne sont plus visibles sur la Marketplace. Elles réapparaîtront automatiquement dès le renouvellement de votre abonnement."
                        : "Votre abonnement annuel a expiré : votre annonce « {$annonces->first()->title} » n'est plus visible sur la Marketplace. Elle réapparaîtra automatiquement dès le renouvellement de votre abonnement.",
                    'link' => '/espace-membre/abonnement',
                ]);
                static::whereIn('id', $annonces->pluck('id'))->update(['suspension_notifiee_at' => now()]);
                $suspendues += $annonces->count();
            }
            // Abonnement renouvelé : l'avis pourra être renvoyé lors d'une prochaine expiration.
            static::whereNotNull('suspension_notifiee_at')
                ->whereHas('user', fn ($u) => $u->where('subscription_expires_at', '>', now()))
                ->update(['suspension_notifiee_at' => null]);
        }

        return ['rappels' => $rappels, 'expirees' => $expirees, 'suspendues' => $suspendues];
    }

    /**
     * Valeur numérique d'un prix saisi en texte libre, pour le tri :
     * « 15 000 FCFA » → 15000, « 1.500 F la bouteille » → 1500, « Sur devis » → null.
     */
    public static function valeurPrix(?string $prix): ?int
    {
        if ($prix === null || ! preg_match('/\d[\d\s.\x{00A0}\x{202F}]*/u', $prix, $m)) {
            return null;
        }
        $chiffres = preg_replace('/\D/', '', $m[0]);

        return $chiffres === '' ? null : (int) $chiffres;
    }

    protected static function booted(): void
    {
        static::saving(fn (self $l) => $l->prix_valeur = self::valeurPrix($l->price));
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
