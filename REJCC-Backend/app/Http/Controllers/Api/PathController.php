<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Path;
use App\Models\PathBadge;
use App\Support\PathBadges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Parcours guidé : séquence ordonnée de formations vers un objectif. L'ordre
 * est conseillé, pas imposé (chaque formation reste ouverte au catalogue) ;
 * le badge de fin est obtenu quand toutes les étapes disponibles sont
 * terminées.
 */
class PathController extends Controller
{
    /**
     * Étape « disponible » : formation publiée et dotée d'un contenu (modules).
     * Une étape non disponible ne bloque pas le parcours et ne compte pas dans
     * le total ; une formation n'est « terminée » que si elle est disponible
     * (les anciennes validations au clic, sans contenu, ne comptent pas).
     */
    private function etapes(Path $p, $enrollments)
    {
        $avecContenu = Formation::whereIn('id', $p->formations->pluck('id'))->whereHas('modules')->pluck('id')->all();

        return $p->formations->map(function (Formation $f) use ($enrollments, $avecContenu) {
            $e = $enrollments->get($f->id);
            $disponible = $f->is_published && in_array($f->id, $avecContenu, true);

            return [
                'id' => $f->id,
                'title' => $f->title,
                'category' => $f->category,
                'duration' => $f->duration,
                'is_certifying' => $f->is_certifying && $disponible,
                'has_modules' => in_array($f->id, $avecContenu, true),
                'disponible' => $disponible,
                'enrolled' => (bool) $e,
                'progress' => $e?->progress ?? 0,
                'completed' => $disponible && $e?->completed_at !== null,
            ];
        });
    }

    /**
     * Durée totale des étapes disponibles (« 4 semaines » + « 2 semaines »
     * = « 6 semaines ») ; null si les durées ne sont pas additionnables.
     */
    private function dureeTotale($etapes): ?string
    {
        $jours = 0;
        $heures = 0;
        foreach ($etapes as $e) {
            if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(mois|semaine|jour|heure|h\b)/iu', (string) $e['duration'], $m)) {
                return null;
            }
            $n = (float) str_replace(',', '.', $m[1]);
            match (mb_strtolower($m[2])) {
                'mois' => $jours += $n * 30,
                'semaine' => $jours += $n * 7,
                'jour' => $jours += $n,
                default => $heures += $n,
            };
        }

        if ($jours > 0 && $heures > 0) {
            return null;
        }
        if ($heures > 0) {
            return round($heures).' h';
        }
        if ($jours <= 0) {
            return null;
        }
        if (fmod($jours, 30) == 0) {
            return ($jours / 30).' mois';
        }
        if (fmod($jours, 7) == 0) {
            $s = (int) ($jours / 7);

            return $s.' semaine'.($s > 1 ? 's' : '');
        }

        return round($jours).' jour'.($jours > 1 ? 's' : '');
    }

    /** Parcours publiés + progression du membre courant. */
    public function index(Request $request)
    {
        $enrollments = FormationEnrollment::where('user_id', $request->user()->id)->get()->keyBy('formation_id');

        PathBadges::attribuer($request->user()->id);
        $badges = PathBadge::where('user_id', $request->user()->id)->pluck('obtenu_at', 'path_id');

        $paths = Path::where('is_published', true)
            ->orderBy('ordre')->orderBy('id')
            ->with('formations')
            ->get()
            ->map(function (Path $p) use ($enrollments, $badges) {
                $etapes = $this->etapes($p, $enrollments);
                $disponibles = $etapes->where('disponible', true);
                $total = $disponibles->count();
                $termines = $disponibles->where('completed', true)->count();
                $badge = $badges->get($p->id);

                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'description' => $p->description,
                    'objectif' => $p->objectif,
                    'badge_icon' => $p->badge_icon,
                    'badge_couleur' => $p->badge_couleur,
                    'total_formations' => $total,
                    'formations_terminees' => $termines,
                    'a_venir' => $etapes->count() - $total,
                    'pct' => $badge ? 100 : ($total > 0 ? (int) round($termines * 100 / $total) : 0),
                    'commence' => $disponibles->contains(fn ($e) => $e['enrolled']),
                    'duree' => $this->dureeTotale($disponibles),
                    'badge_obtenu' => (bool) $badge,
                    'badge_obtenu_le' => $badge?->toDateString(),
                ];
            });

        return response()->json(['ok' => true, 'paths' => $paths]);
    }

    /** Détail d'un parcours : formations dans l'ordre, débloquées ou non. */
    public function show(Request $request, int $id)
    {
        $path = Path::where('is_published', true)->with('formations')->find($id);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'Parcours introuvable.'], 404);
        }

        $enrollments = FormationEnrollment::where('user_id', $request->user()->id)
            ->whereIn('formation_id', $path->formations->pluck('id'))
            ->get()
            ->keyBy('formation_id');

        // Ordre conseillé (et non imposé : le catalogue reste ouvert) : l'étape
        // conseillée est la première étape disponible non terminée ; les
        // suivantes, pas encore commencées, indiquent l'étape à faire avant.
        $numero = 0;
        $conseillee = null;
        $formations = $this->etapes($path, $enrollments)->map(function (array $item) use (&$numero, &$conseillee) {
            $item['numero'] = $item['disponible'] ? ++$numero : null;
            $item['conseillee'] = false;
            $item['conseillee_apres'] = null;
            if ($item['disponible'] && ! $item['completed']) {
                if ($conseillee === null) {
                    $conseillee = $item['title'];
                    $item['conseillee'] = true;
                } elseif (! $item['enrolled']) {
                    $item['conseillee_apres'] = $conseillee;
                }
            }

            return $item;
        });

        PathBadges::attribuer($request->user()->id);
        $badge = PathBadge::where('user_id', $request->user()->id)->where('path_id', $path->id)->value('obtenu_at');

        return response()->json([
            'ok' => true,
            'path' => [
                'id' => $path->id, 'title' => $path->title, 'description' => $path->description,
                'objectif' => $path->objectif, 'badge_icon' => $path->badge_icon, 'badge_couleur' => $path->badge_couleur,
                'badge_obtenu' => $badge !== null,
                'badge_obtenu_le' => $badge ? \Illuminate\Support\Carbon::parse($badge)->toDateString() : null,
                'duree' => $this->dureeTotale($formations->where('disponible', true)),
            ],
            'formations' => $formations->values(),
        ]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex()
    {
        $paths = Path::withCount('formations')
            ->withCount(['formations as formations_indisponibles_count' => fn ($q) => $q->where(fn ($w) => $w->where('is_published', false)->orWhereDoesntHave('modules'))])
            ->orderBy('ordre')->orderBy('id')
            ->get();

        return response()->json(['ok' => true, 'paths' => $paths]);
    }

    public function adminShow(int $id)
    {
        $path = Path::with(['formations' => fn ($q) => $q->select('formations.id', 'title', 'category', 'is_published')->withCount('modules')])->find($id);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'Parcours introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'path' => $path]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Path());
    }

    public function update(Request $request, int $id)
    {
        $path = Path::find($id);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'Parcours introuvable.'], 404);
        }

        return $this->persist($request, $path);
    }

    public function destroy(int $id)
    {
        Path::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/paths/{id}/formations — définit l'ensemble ordonné des formations du parcours. */
    public function updateFormations(Request $request, int $id)
    {
        $path = Path::find($id);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'Parcours introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'formation_ids' => 'required|array|min:1|max:30',
            'formation_ids.*' => 'integer|exists:formations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $sync = [];
        foreach ($validator->validated()['formation_ids'] as $i => $formationId) {
            $sync[$formationId] = ['ordre' => $i];
        }
        $path->formations()->sync($sync);

        return response()->json(['ok' => true, 'path' => $path->load('formations:id,title,category')]);
    }

    private function persist(Request $request, Path $path)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:150',
            'description' => 'nullable|string|max:2000',
            'objectif' => 'nullable|string|max:300',
            'badge_icon' => 'nullable|string|max:40',
            'badge_couleur' => 'nullable|string|max:20',
            'ordre' => 'nullable|integer|min:0|max:1000',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        if (! $path->exists || $path->title !== ($data['title'] ?? $path->title)) {
            $slug = Str::slug($data['title']);
            $base = $slug;
            $i = 2;
            while (Path::where('slug', $slug)->where('id', '!=', $path->id ?? 0)->exists()) {
                $slug = $base.'-'.$i++;
            }
            $data['slug'] = $slug;
        }

        $path->fill($data)->save();

        return response()->json(['ok' => true, 'path' => $path]);
    }
}
