<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    public const TYPES = [
        'emploi' => 'Emploi',
        'stage' => 'Stage',
        'alternance' => 'Alternance',
        'freelance' => 'Freelance',
        'mission' => 'Mission',
    ];

    public const CONTRATS = ['cdi' => 'CDI', 'cdd' => 'CDD', 'interim' => 'Intérim'];

    public const TELETRAVAIL = ['sur_site' => 'Sur site', 'hybride' => 'Hybride', 'distance' => 'À distance'];

    public const STATUTS = [
        'en_attente' => 'En attente de validation',
        'a_corriger' => 'À corriger',
        'publiee' => 'En ligne',
        'refusee' => 'Refusée',
        'pourvue' => 'Pourvue',
        'cloturee' => 'Clôturée',
    ];

    /** Durée de publication par défaut (sans date limite). */
    public const DUREE_JOURS = 60;

    protected $fillable = [
        'title', 'description', 'missions', 'profil', 'competences', 'type', 'contrat', 'statut', 'motif', 'entreprise', 'site_url',
        'lieu', 'teletravail', 'remuneration', 'debut', 'duree', 'contact', 'deadline', 'author_id', 'group_id', 'media_url', 'media_name',
        'vues', 'expire_le', 'rappel_at', 'publie_at', 'decide_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'debut' => 'date',
        'expire_le' => 'date',
        'competences' => 'array',
        'rappel_at' => 'datetime',
        'publie_at' => 'datetime',
        'decide_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function candidatures()
    {
        return $this->hasMany(OpportunityApplication::class);
    }

    public function groupe()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /** Offres en ligne : publiées et non expirées. */
    public function scopeEnLigne(Builder $q): Builder
    {
        return $q->where('statut', 'publiee')->where(fn ($w) => $w->whereNull('expire_le')->orWhereDate('expire_le', '>=', today()));
    }

    public function estExpiree(): bool
    {
        return $this->statut === 'publiee' && $this->expire_le !== null && $this->expire_le->lt(today());
    }

    /** Date d'expiration à la publication : date limite, sinon 60 jours. */
    public function calculerExpiration(): void
    {
        $this->expire_le = $this->deadline ?? today()->addDays(self::DUREE_JOURS);
        $this->rappel_at = null;
    }

    /**
     * Tâche quotidienne : prévient l'auteur 3 jours avant l'expiration de son offre.
     *
     * @return int nombre de rappels envoyés
     */
    public static function rappelsExpiration(): int
    {
        $n = 0;
        foreach (self::where('statut', 'publiee')->whereNull('rappel_at')->whereNotNull('author_id')
            ->whereDate('expire_le', '<=', today()->addDays(3))->whereDate('expire_le', '>=', today())->get() as $o) {
            MemberNotification::create([
                'user_id' => $o->author_id, 'type' => 'info',
                'title' => "Votre offre expire bientôt : {$o->title}",
                'body' => 'Elle ne sera plus visible après le '.$o->expire_le->locale('fr')->isoFormat('D MMMM').'. Prolongez-la ou marquez-la comme pourvue depuis « Mes offres ».',
                'link' => "/espace-membre/emplois?offre={$o->id}",
            ]);
            $o->update(['rappel_at' => now()]);
            $n++;
        }

        return $n;
    }
}
