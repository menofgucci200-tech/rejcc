<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormationController extends Controller
{
    /** Catalogue des formations publiées, avec l'état d'inscription du membre courant. */
    public function catalogue(Request $request)
    {
        $enrollments = FormationEnrollment::where('user_id', $request->user()->id)
            ->get()
            ->keyBy('formation_id');

        $formations = Formation::where('is_published', true)
            ->orderBy('category')->orderBy('title')
            ->get()
            ->map(function (Formation $f) use ($enrollments) {
                $e = $enrollments->get($f->id);

                return [
                    ...$f->only([
                        'id', 'title', 'category', 'description', 'duration',
                        'level', 'is_free', 'is_certifying', 'modules_count', 'media_url', 'media_name',
                    ]),
                    'enrolled' => (bool) $e,
                    'progress' => $e?->progress,
                ];
            });

        return response()->json(['ok' => true, 'formations' => $formations]);
    }

    /** Les formations auxquelles le membre courant est inscrit. */
    public function mine(Request $request)
    {
        $formations = FormationEnrollment::with('formation')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->filter(fn (FormationEnrollment $e) => $e->formation !== null)
            ->values()
            ->map(fn (FormationEnrollment $e) => [
                ...$e->formation->only([
                    'id', 'title', 'category', 'duration', 'level', 'modules_count', 'is_certifying',
                ]),
                'progress' => $e->progress,
                'completed' => $e->completed_at !== null,
                'completed_at' => $e->completed_at?->toDateString(),
            ]);

        return response()->json(['ok' => true, 'formations' => $formations]);
    }

    /** Inscription du membre courant à une formation publiée (idempotent). */
    public function enroll(Request $request, int $id)
    {
        $formation = Formation::where('is_published', true)->find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        FormationEnrollment::firstOrCreate([
            'formation_id' => $formation->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Valide le module en cours : avance la progression d'un module. */
    public function completeModule(Request $request, int $id)
    {
        $enrollment = FormationEnrollment::with('formation')
            ->where('formation_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $enrollment || ! $enrollment->formation) {
            return response()->json(['ok' => false, 'message' => 'Inscription introuvable.'], 404);
        }

        if ($enrollment->completed_at === null) {
            $modules = max(1, (int) $enrollment->formation->modules_count);
            $fait = min($modules, (int) round($enrollment->progress * $modules / 100) + 1);

            $enrollment->update([
                'progress' => (int) round($fait * 100 / $modules),
                'completed_at' => $fait >= $modules ? now() : null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'progress' => $enrollment->progress,
            'completed' => $enrollment->completed_at !== null,
        ]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function index()
    {
        $formations = Formation::withCount('enrollments')
            ->orderBy('category')->orderBy('title')
            ->get();

        return response()->json(['ok' => true, 'formations' => $formations]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Formation());
    }

    public function update(Request $request, int $id)
    {
        $formation = Formation::find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        return $this->persist($request, $formation);
    }

    public function destroy(int $id)
    {
        Formation::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    private function persist(Request $request, Formation $formation)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:200',
            'category' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:2000',
            'duration' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:50',
            'is_free' => 'boolean',
            'is_certifying' => 'boolean',
            'modules_count' => 'integer|min:1|max:50',
            'is_published' => 'boolean',
            'media_url' => 'nullable|url|max:500',
            'media_name' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $formation->fill($validator->validated())->save();

        return response()->json(['ok' => true, 'formation' => $formation]);
    }
}
