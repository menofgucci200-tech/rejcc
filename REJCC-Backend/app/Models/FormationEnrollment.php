<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormationEnrollment extends Model
{
    protected $fillable = ['formation_id', 'user_id', 'progress', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
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
