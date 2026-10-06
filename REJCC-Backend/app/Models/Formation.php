<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    protected $fillable = [
        'title', 'category', 'description', 'duration', 'level',
        'is_free', 'is_certifying', 'modules_count', 'is_published', 'media_url', 'media_name', 'image_url', 'seuil_reussite', 'examen', 'competences',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_certifying' => 'boolean',
            'is_published' => 'boolean',
            'examen' => 'array',
            'competences' => 'array',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(FormationEnrollment::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(FormationModule::class)->orderBy('ordre')->orderBy('id');
    }

    /** Recalcule modules_count à partir des modules réels (si la formation en a). */
    public function syncModulesCount(): void
    {
        $count = $this->modules()->count();
        if ($count > 0) {
            $this->update(['modules_count' => $count]);
        }
    }

    /**
     * Après l'ajout ou la suppression d'un module : recalcule la progression
     * des membres inscrits. Une formation déjà terminée le reste (le certificat
     * obtenu n'est pas retiré) ; les autres avancent ou se terminent selon les
     * modules réellement validés (et l'examen final, s'il existe).
     */
    public function recalculerProgressions(): void
    {
        $ids = $this->modules()->pluck('id');
        $total = $ids->count();
        if ($total === 0) {
            return;
        }

        FormationEnrollment::with(['moduleCompletions', 'formation'])
            ->where('formation_id', $this->id)
            ->whereNull('completed_at')
            ->get()
            ->each(function (FormationEnrollment $e) use ($ids, $total) {
                $fait = $e->moduleCompletions->pluck('formation_module_id')->intersect($ids)->count();
                $e->update([
                    'progress' => (int) round($fait * 100 / $total),
                    'completed_at' => $fait >= $total && $e->examenValide() ? now() : null,
                ]);
            });
    }
}
