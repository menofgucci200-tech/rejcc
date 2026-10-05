<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormationEnrollment extends Model
{
    protected $fillable = [
        'formation_id', 'user_id', 'progress', 'completed_at',
        'examen_score', 'examen_reussi_at', 'examen_echecs', 'examen_bloque_jusqu',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'examen_reussi_at' => 'datetime', 'examen_bloque_jusqu' => 'datetime'];
    }

    /** Une formation terminée peut compléter un parcours : on attribue alors son badge. */
    protected static function booted(): void
    {
        static::saved(function (FormationEnrollment $e) {
            if ($e->completed_at && ($e->wasRecentlyCreated || $e->wasChanged('completed_at'))) {
                \App\Support\PathBadges::attribuer($e->user_id);
            }
        });
    }

    /** Tous les modules de la formation sont validés. */
    public function modulesTermines(): bool
    {
        $ids = $this->formation->modules()->pluck('id');

        return $ids->isNotEmpty()
            && $ids->diff($this->moduleCompletions()->pluck('formation_module_id'))->isEmpty();
    }

    /** Examen final réussi, ou formation sans examen. */
    public function examenValide(): bool
    {
        return empty($this->formation->examen) || $this->examen_reussi_at !== null;
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moduleCompletions(): HasMany
    {
        return $this->hasMany(FormationModuleCompletion::class);
    }

    /** Référence du certificat (formation certifiante terminée), ex. REJCC-CERT-2026-0012. */
    public function certificateReference(): string
    {
        return 'REJCC-CERT-'.$this->completed_at->format('Y').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Inscriptions qui donnent droit à un certificat : formation certifiante
     * terminée ET dotée d'un vrai contenu (au moins un module). Une formation
     * sans module ne peut plus délivrer de certificat.
     */
    public function scopeCertificats($query)
    {
        return $query->whereNotNull('completed_at')
            ->whereHas('formation', fn ($q) => $q->where('is_certifying', true)->whereHas('modules'));
    }
}
