<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'besoins', 'lien', 'public_ok', 'a_la_une', 'members_count', 'statut', 'stade', 'motif', 'vues', 'soumis_at', 'decide_at',
    ];

    protected $casts = [
        'besoins' => 'array',
        'public_ok' => 'boolean',
        'a_la_une' => 'boolean',
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

    public function equipe(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function suivis(): HasMany
    {
        return $this->hasMany(ProjectFollow::class);
    }

    public function avancees(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    /** Projets visibles par ce membre : validés, les siens, et ceux dont il est (ou est invité dans) l'équipe. */
    public function scopeVisiblesPour(Builder $q, User $user): Builder
    {
        return $q->where(fn ($w) => $w->where('statut', 'valide')->orWhere('user_id', $user->id)
            ->orWhereHas('equipe', fn ($e) => $e->where('user_id', $user->id)->whereIn('statut', ['membre', 'invite'])));
    }

    public function estVisiblePar(User $user): bool
    {
        return $this->statut === 'valide' || $this->user_id === $user->id || $user->role === 'admin'
            || $this->equipe()->where('user_id', $user->id)->whereIn('statut', ['membre', 'invite'])->exists();
    }

    /** Porteur ou membre confirmé de l'équipe. */
    public function estDeLEquipe(User $user): bool
    {
        return $this->user_id === $user->id || $this->equipe()->where('user_id', $user->id)->where('statut', 'membre')->exists();
    }

    /** Taille de l'équipe sur la plateforme (porteur compris). */
    public function tailleEquipe(): int
    {
        return 1 + $this->equipe()->where('statut', 'membre')->count();
    }

    /** Le porteur peut-il modifier son projet ? (pas une fois refusé) */
    public function modifiable(): bool
    {
        return in_array($this->statut, ['evaluation', 'a_completer', 'valide'], true);
    }
}
