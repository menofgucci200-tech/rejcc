<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    /** Circuit de validation. */
    public const STATUTS = [
        'evaluation' => 'En évaluation',
        'a_completer' => 'À compléter',
        'valide' => 'Validé',
        'refuse' => 'Refusé',
        'retire' => 'Retiré',
    ];

    /** Stade d'avancement (affiché une fois le projet validé). */
    public const STADES = [
        'idee' => 'Idée',
        'developpement' => 'En développement',
        'lance' => 'Lancé',
    ];

    /** Ce que le porteur recherche dans le réseau. */
    public const BESOINS = [
        'partenaires' => 'Partenaires',
        'competences' => 'Compétences',
        'mentor' => 'Un mentor',
        'financement' => 'Financement',
        'clients' => 'Premiers clients',
        'fournisseurs' => 'Fournisseurs',
    ];

    protected $fillable = [
        'user_id', 'group_id', 'ville', 'image', 'title', 'accroche', 'description', 'probleme', 'solution', 'cible', 'impact',
        'besoins', 'lien', 'members_count', 'statut', 'stade', 'motif', 'vues', 'soumis_at', 'decide_at',
    ];

    protected $casts = [
        'besoins' => 'array',
        'soumis_at' => 'datetime',
        'decide_at' => 'datetime',
    ];

    public function porteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /** Projets visibles par ce membre : validés, plus les siens quel que soit leur statut. */
    public function scopeVisiblesPour(Builder $q, User $user): Builder
    {
        return $q->where(fn ($w) => $w->where('statut', 'valide')->orWhere('user_id', $user->id));
    }

    public function estVisiblePar(User $user): bool
    {
        return $this->statut === 'valide' || $this->user_id === $user->id || $user->role === 'admin';
    }

    /** Le porteur peut-il modifier son projet ? (pas une fois refusé) */
    public function modifiable(): bool
    {
        return in_array($this->statut, ['evaluation', 'a_completer', 'valide'], true);
    }
}
