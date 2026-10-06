<?php

namespace App\Models;

use App\Support\RechercheMots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Alerte « M'alerter » : le membre est notifié des nouvelles offres qui correspondent. */
class JobAlert extends Model
{
    protected $fillable = ['user_id', 'type', 'group_id', 'ville', 'q'];

    public function groupe()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /** Libellé lisible : « Stage · Informatique · Abidjan · « laravel » ». */
    public function libelle(): string
    {
        return collect([
            $this->type ? (Opportunity::TYPES[$this->type] ?? $this->type) : 'Toutes les offres',
            $this->groupe?->name, $this->ville, $this->q ? "« {$this->q} »" : null,
        ])->filter()->join(' · ');
    }

    public function correspond(Opportunity $o): bool
    {
        $norm = fn ($s) => Str::lower(Str::ascii((string) $s));
        if ($this->type && $this->type !== $o->type) {
            return false;
        }
        if ($this->group_id && $this->group_id !== $o->group_id) {
            return false;
        }
        if ($this->ville && ! str_contains($norm($o->lieu), $norm($this->ville))) {
            return false;
        }
        if ($this->q) {
            $texte = $norm(implode(' ', [$o->title, $o->description, $o->entreprise, $o->missions, $o->profil, implode(' ', $o->competences ?? [])]));
            foreach (RechercheMots::mots($this->q) as $mot) {
                if (! str_contains($texte, $norm($mot))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Prévient les membres dont une alerte correspond à l'offre publiée.
     *
     * @return int nombre de membres prévenus
     */
    public static function prevenir(Opportunity $o): int
    {
        $prevenus = [];
        foreach (self::with('groupe:id,name')->where('user_id', '!=', (int) $o->author_id)->get() as $a) {
            if (isset($prevenus[$a->user_id]) || ! $a->correspond($o)) {
                continue;
            }
            MemberNotification::create([
                'user_id' => $a->user_id, 'type' => 'info',
                'title' => 'Nouvelle offre : '.$o->title,
                'body' => trim(($o->entreprise ? $o->entreprise.' · ' : '').$o->lieu).'. Elle correspond à votre alerte « '.$a->libelle().' ».',
                'link' => "/espace-membre/emplois?offre={$o->id}",
            ]);
            $prevenus[$a->user_id] = true;
        }

        return count($prevenus);
    }
}
