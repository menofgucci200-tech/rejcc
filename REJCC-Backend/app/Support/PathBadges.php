<?php

namespace App\Support;

use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\MemberNotification;
use App\Models\Path;
use App\Models\PathBadge;

/**
 * Badges de parcours : un parcours est réussi quand toutes ses étapes
 * disponibles (formations publiées, dotées de modules) sont terminées.
 * Le badge est alors enregistré une fois pour toutes et le membre notifié.
 */
class PathBadges
{
    /** Identifiants des formations « disponibles » parmi celles données. */
    public static function disponibles(iterable $formations): array
    {
        $ids = collect($formations)->filter(fn (Formation $f) => $f->is_published)->pluck('id');

        return $ids->isEmpty() ? [] : Formation::whereIn('id', $ids)->whereHas('modules')->pluck('id')->all();
    }

    /** Attribue au membre les badges des parcours qu'il vient de réussir. */
    public static function attribuer(int $userId): void
    {
        $terminees = FormationEnrollment::where('user_id', $userId)->whereNotNull('completed_at')->pluck('formation_id')->all();
        if ($terminees === []) {
            return;
        }

        $deja = PathBadge::where('user_id', $userId)->pluck('path_id')->all();

        Path::where('is_published', true)
            ->whereNotIn('id', $deja)
            ->whereHas('formations', fn ($q) => $q->whereIn('formations.id', $terminees))
            ->with('formations')
            ->get()
            ->each(function (Path $p) use ($userId, $terminees) {
                $disponibles = static::disponibles($p->formations);
                if ($disponibles === [] || array_diff($disponibles, $terminees) !== []) {
                    return;
                }

                $badge = PathBadge::firstOrCreate(['user_id' => $userId, 'path_id' => $p->id], ['obtenu_at' => now()]);
                if ($badge->wasRecentlyCreated) {
                    \App\Support\Certificats::pourParcours($badge);
                    MemberNotification::create([
                        'user_id' => $userId,
                        'type' => 'success',
                        'title' => "Badge « {$p->title} » obtenu !",
                        'body' => 'Bravo, vous avez terminé toutes les formations du parcours. Votre badge apparaît désormais sur votre tableau de bord et votre page biographique.',
                        'link' => "/espace-membre/parcours/{$p->id}",
                    ]);
                }
            });
    }
}
