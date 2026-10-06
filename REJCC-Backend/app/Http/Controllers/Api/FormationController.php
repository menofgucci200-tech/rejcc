<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\FormationModule;
use App\Models\FormationModuleCompletion;
use App\Support\Quiz;
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
            ->withCount(['modules as modules_reelles_count', 'enrollments'])
            ->orderBy('category')->orderBy('title')
            ->get()
            ->map(function (Formation $f) use ($enrollments) {
                $e = $enrollments->get($f->id);

                return [
                    ...$f->only([
                        'id', 'title', 'category', 'description', 'duration',
                        'level', 'is_free', 'is_certifying', 'modules_count', 'image_url',
                    ]),
                    'has_modules' => $f->modules_reelles_count > 0,
                    // « Certifiante » seulement si la formation peut réellement délivrer un certificat.
                    'certifiante' => $f->is_certifying && $f->modules_reelles_count > 0,
                    'a_examen' => ! empty($f->examen),
                    'inscrits' => $f->enrollments_count,
                    'publiee_le' => $f->created_at?->toIso8601String(),
                    // Le support n'est plus transmis en lien libre : il se consulte sur la fiche.
                    'a_support' => (bool) $f->media_url,
                    'enrolled' => (bool) $e,
                    'progress' => $e?->progress,
                    'completed' => $e?->completed_at !== null,
                ];
            });

        return response()->json(['ok' => true, 'formations' => $formations]);
    }

    /**
     * GET /formations/{id}/fiche — présentation d'une formation avant inscription :
     * description, programme (titres et durées des modules, sans leur contenu),
     * quiz et examen final, nombre d'inscrits, support consultable sur la
     * plateforme (téléchargeable par les abonnés).
     */
    public function fiche(Request $request, int $id)
    {
        $user = $request->user();
        $e = FormationEnrollment::where('formation_id', $id)->where('user_id', $user->id)->first();
        $f = Formation::withCount('enrollments')->find($id);

        if (! $f || (! $f->is_published && ! $e)) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        $modules = $f->modules()->get(['titre', 'description', 'duree', 'quiz', 'ressources']);
        $peutTelecharger = $user->hasActiveSubscription();
        $support = null;
        if ($f->media_url) {
            $estPdf = (bool) preg_match('~\.pdf(\?.*)?$~i', $f->media_url);
            $support = [
                'nom' => $f->media_name ?: 'Support de la formation',
                'pdf' => $estPdf,
                // Un PDF se consulte dans la page ; les autres formats ne sont transmis qu'aux abonnés.
                'url' => ($estPdf || $peutTelecharger) ? $f->media_url : null,
                'telechargeable' => $peutTelecharger,
            ];
        }

        return response()->json(['ok' => true, 'formation' => [
            ...$f->only(['id', 'title', 'category', 'description', 'duration', 'level', 'is_free', 'is_certifying', 'seuil_reussite', 'image_url']),
            'certifiante' => $f->is_certifying && $modules->isNotEmpty(),
            'inscrits' => $f->enrollments_count,
            'programme' => $modules->map(fn ($m) => [
                'titre' => $m->titre,
                'description' => $m->description,
                'duree' => $m->duree,
                'quiz' => ! empty($m->quiz),
                'ressources' => count($m->ressources ?? []),
            ])->values(),
            'examen' => empty($f->examen) ? null : ['nb_questions' => count($f->examen), 'seuil' => (int) $f->seuil_reussite],
            'support' => $support,
            'enrolled' => (bool) $e,
            'completed' => $e?->completed_at !== null,
            'progress' => $e?->progress,
        ]]);
    }

    /** Les formations auxquelles le membre courant est inscrit. */
    public function mine(Request $request)
    {
        $formations = FormationEnrollment::with(['formation', 'moduleCompletions'])
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get()
            ->filter(fn (FormationEnrollment $e) => $e->formation !== null)
            ->values()
            ->map(function (FormationEnrollment $e) {
                $modules = $e->formation->modules()->get(['id', 'titre', 'ordre']);
                $completedIds = $e->moduleCompletions->pluck('formation_module_id')->all();
                $prochain = $modules->first(fn ($m) => ! in_array($m->id, $completedIds, true));

                return [
                    ...$e->formation->only([
                        'id', 'title', 'category', 'duration', 'level', 'modules_count', 'is_certifying',
                    ]),
                    'has_modules' => $modules->isNotEmpty(),
                    'progress' => $e->progress,
                    'completed' => $e->completed_at !== null,
                    'completed_at' => $e->completed_at?->toDateString(),
                    'module_courant' => $prochain?->titre,
                    'modules_reels' => $modules->count(),
                    // Dernière activité (inscription ou module validé) : sert à choisir la formation à reprendre.
                    'derniere_activite' => $e->updated_at?->toIso8601String(),
                    'examen_a_passer' => ! empty($e->formation->examen) && $e->examen_reussi_at === null
                        && $modules->isNotEmpty() && ! $prochain,
                    // Modules réellement validés (ou, pour une formation sans modules
                    // détaillés, la part de modules correspondant à la progression).
                    'modules_done' => $modules->isNotEmpty()
                        ? count(array_intersect($modules->pluck('id')->all(), $completedIds))
                        : ($e->completed_at ? (int) $e->formation->modules_count : intdiv((int) $e->progress * (int) $e->formation->modules_count, 100)),
                ];
            });

        return response()->json(['ok' => true, 'formations' => $formations]);
    }

    /** Inscription du membre courant à une formation publiée (idempotent). */
    public function enroll(Request $request, int $id)
    {
        $formation = Formation::where('is_published', true)->find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        if (! $formation->is_free && ! $request->user()->hasActiveSubscription()) {
            return response()->json([
                'ok' => false,
                'code' => 'subscription_required',
                'message' => 'Cette formation est incluse dans l\'abonnement annuel : activez votre abonnement pour la suivre.',
            ], 402);
        }

        FormationEnrollment::firstOrCreate([
            'formation_id' => $formation->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Désinscription d'une formation non terminée (une formation terminée reste, avec son certificat). */
    public function unenroll(Request $request, int $id)
    {
        $e = $this->inscription($request, $id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Inscription introuvable.'], 404);
        }
        if ($e->completed_at) {
            return response()->json(['ok' => false, 'message' => 'Une formation terminée ne peut pas être retirée.'], 422);
        }

        $e->moduleCompletions()->delete();
        $e->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Sommaire des modules d'une formation pour le membre inscrit : contenu,
     * état de complétion et verrouillage (un module ne se débloque qu'une
     * fois le précédent validé).
     */
    public function modules(Request $request, int $id)
    {
        $enrollment = FormationEnrollment::with('formation')
            ->where('formation_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $enrollment || ! $enrollment->formation) {
            return response()->json(['ok' => false, 'message' => "Vous n'êtes pas inscrit à cette formation."], 404);
        }

        if (! $enrollment->formation->is_free && ! $request->user()->hasActiveSubscription()) {
            return response()->json(['ok' => false, 'code' => 'subscription_required', 'message' => 'Cette formation est incluse dans l\'abonnement annuel : activez votre abonnement pour continuer.'], 402);
        }

        $modules = $enrollment->formation->modules;
        $completedIds = $enrollment->moduleCompletions->pluck('formation_module_id')->all();

        // Téléchargement des ressources : réservé aux abonnés (tout le monde
        // tant que les abonnements ne sont pas obligatoires). Sans ce droit, les
        // liens de téléchargement ne sont pas transmis ; le contenu reste
        // consultable sur la plateforme.
        $peutTelecharger = $request->user()->hasActiveSubscription();

        $debloque = true;
        $liste = $modules->map(function (FormationModule $m) use ($completedIds, &$debloque, $peutTelecharger) {
            $fait = in_array($m->id, $completedIds, true);
            $accessible = $debloque;
            $item = [
                'id' => $m->id,
                'titre' => $m->titre,
                'description' => $m->description,
                // Le contenu d'un module verrouillé n'est pas envoyé.
                'contenu' => $accessible ? $m->contenu : null,
                'video_url' => $accessible ? $m->video_url : null,
                'document_url' => $accessible ? $m->document_url : null,
                'ressources' => $accessible ? collect($m->ressources ?? [])->map(fn ($r) => [
                    'nom' => $r['nom'] ?? 'Ressource',
                    'taille' => $r['taille'] ?? null,
                    'url' => $peutTelecharger ? ($r['url'] ?? null) : null,
                ])->values() : [],
                // Quiz de validation : questions sans la bonne réponse.
                'quiz' => $accessible ? Quiz::forMember($m->quiz) : [],
                'quiz_requis' => ! empty($m->quiz),
                'duree' => $m->duree,
                'termine' => $fait,
                'verrouille' => ! $debloque,
            ];
            if (! $fait) {
                $debloque = false;
            }

            return $item;
        });

        return response()->json([
            'ok' => true,
            'formation' => $enrollment->formation->only(['id', 'title', 'category', 'description', 'is_certifying', 'seuil_reussite']),
            'modules' => $liste,
            'progress' => $enrollment->progress,
            'completed' => $enrollment->completed_at !== null,
            'telechargement_autorise' => $peutTelecharger,
            'examen' => $this->etatExamen($enrollment),
        ]);
    }

    /** État de l'examen final pour une inscription (null si la formation n'en a pas). */
    private function etatExamen(FormationEnrollment $e): ?array
    {
        $examen = $e->formation->examen;
        if (empty($examen)) {
            return null;
        }

        return [
            'nb_questions' => count($examen),
            'seuil' => (int) ($e->formation->seuil_reussite ?? 70),
            'disponible' => $e->modulesTermines(),
            'reussi' => $e->examen_reussi_at !== null,
            'score' => $e->examen_score,
            'bloque_jusqu' => $e->examen_bloque_jusqu?->isFuture() ? $e->examen_bloque_jusqu->toIso8601String() : null,
        ];
    }

    /** GET /formations/{id}/examen — questions de l'examen final (sans les réponses). */
    public function examen(Request $request, int $id)
    {
        $e = $this->inscription($request, $id);
        if (! $e || empty($e->formation->examen)) {
            return response()->json(['ok' => false, 'message' => 'Aucun examen pour cette formation.'], 404);
        }
        if (! $e->modulesTermines()) {
            return response()->json(['ok' => false, 'message' => 'Terminez tous les modules pour accéder à l\'examen.'], 422);
        }

        return response()->json([
            'ok' => true,
            'questions' => Quiz::forMember($e->formation->examen),
            'etat' => $this->etatExamen($e),
        ]);
    }

    /**
     * POST /formations/{id}/examen {reponses} — corrige l'examen final. Réussi :
     * la formation est terminée et le certificat délivré. Après 3 échecs
     * consécutifs, une pause de 24 h est imposée avant de retenter.
     */
    public function passerExamen(Request $request, int $id)
    {
        $e = $this->inscription($request, $id);
        if (! $e || empty($e->formation->examen)) {
            return response()->json(['ok' => false, 'message' => 'Aucun examen pour cette formation.'], 404);
        }
        if ($e->examen_reussi_at) {
            return response()->json(['ok' => true, 'reussi' => true, 'score' => $e->examen_score, 'deja' => true]);
        }
        if (! $e->modulesTermines()) {
            return response()->json(['ok' => false, 'message' => 'Terminez tous les modules pour accéder à l\'examen.'], 422);
        }
        if ($e->examen_bloque_jusqu?->isFuture()) {
            return response()->json(['ok' => false, 'message' => 'Après 3 essais, l\'examen sera de nouveau disponible le '.$e->examen_bloque_jusqu->translatedFormat('j F \à H\hi').'. Profitez-en pour revoir les modules.'], 429);
        }

        $resultat = Quiz::grade($e->formation->examen, (array) $request->input('reponses', []));
        $seuil = (int) ($e->formation->seuil_reussite ?? 70);

        if ($resultat['score'] >= $seuil) {
            $e->update([
                'examen_score' => $resultat['score'],
                'examen_reussi_at' => now(),
                'examen_echecs' => 0,
                'examen_bloque_jusqu' => null,
                'progress' => 100,
                'completed_at' => $e->completed_at ?? now(),
            ]);

            return response()->json(['ok' => true, 'reussi' => true] + $resultat + ['seuil' => $seuil]);
        }

        $echecs = $e->examen_echecs + 1;
        $e->update([
            'examen_score' => $resultat['score'],
            'examen_echecs' => $echecs >= 3 ? 0 : $echecs,
            'examen_bloque_jusqu' => $echecs >= 3 ? now()->addDay() : null,
        ]);

        return response()->json(['ok' => false, 'reussi' => false] + $resultat + [
            'seuil' => $seuil,
            'essais_restants' => $echecs >= 3 ? 0 : 3 - $echecs,
            'message' => "Examen non réussi : {$resultat['correctes']} bonne(s) réponse(s) sur {$resultat['total']} ({$resultat['score']} %), il faut {$seuil} %."
                .($echecs >= 3 ? ' Nouvel essai possible dans 24 h.' : ' Il vous reste '.(3 - $echecs).' essai(s) avant une pause de 24 h.'),
        ], 422);
    }

    private function inscription(Request $request, int $formationId): ?FormationEnrollment
    {
        return FormationEnrollment::with('formation')
            ->where('formation_id', $formationId)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    /** Valide un module précis (formations avec contenu réel) — doit être le prochain débloqué. */
    public function completeFormationModule(Request $request, int $id, int $moduleId)
    {
        $enrollment = FormationEnrollment::with('formation')
            ->where('formation_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $enrollment || ! $enrollment->formation) {
            return response()->json(['ok' => false, 'message' => 'Inscription introuvable.'], 404);
        }

        $modules = $enrollment->formation->modules;
        $module = $modules->firstWhere('id', $moduleId);
        if (! $module) {
            return response()->json(['ok' => false, 'message' => 'Module introuvable.'], 404);
        }

        $completedIds = $enrollment->moduleCompletions->pluck('formation_module_id')->all();
        $prochain = $modules->first(fn ($m) => ! in_array($m->id, $completedIds, true));

        if ($prochain && $prochain->id !== $moduleId) {
            return response()->json(['ok' => false, 'message' => 'Validez les modules dans l\'ordre.'], 422);
        }

        // Module avec quiz : il faut atteindre le seuil de réussite de la formation.
        $quizScore = null;
        if (! empty($module->quiz) && ! in_array($moduleId, $completedIds, true)) {
            $resultat = Quiz::grade($module->quiz, (array) $request->input('reponses', []));
            $seuil = (int) ($enrollment->formation->seuil_reussite ?? 70);

            if ($resultat['score'] < $seuil) {
                return response()->json([
                    'ok' => false,
                    'quiz' => $resultat + ['seuil' => $seuil, 'reussi' => false],
                    'message' => "{$resultat['correctes']} bonne(s) réponse(s) sur {$resultat['total']} ({$resultat['score']} %) : il faut au moins {$seuil} %. Revoyez le contenu du module et réessayez.",
                ], 422);
            }
            $quizScore = $resultat['score'];
        }

        FormationModuleCompletion::firstOrCreate([
            'formation_enrollment_id' => $enrollment->id,
            'formation_module_id' => $moduleId,
        ], ['quiz_score' => $quizScore]);

        $total = $modules->count();
        $fait = count($completedIds) + ($prochain ? 1 : 0);
        $enrollment->update([
            'progress' => $total > 0 ? (int) round($fait * 100 / $total) : $enrollment->progress,
            // Avec un examen final, la formation n'est terminée (et le certificat
            // délivré) qu'une fois l'examen réussi.
            'completed_at' => $fait >= $total && $enrollment->examenValide() ? ($enrollment->completed_at ?? now()) : null,
        ]);

        return response()->json([
            'ok' => true,
            'progress' => $enrollment->progress,
            'completed' => $enrollment->completed_at !== null,
            'quiz_score' => $quizScore,
        ]);
    }

    /** Ancien compteur de modules (formations sans contenu) : désactivé, la validation passe par les modules. */
    public function completeModule(Request $request, int $id)
    {
        $enrollment = FormationEnrollment::with('formation')
            ->where('formation_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $enrollment || ! $enrollment->formation) {
            return response()->json(['ok' => false, 'message' => 'Inscription introuvable.'], 404);
        }

        // Plus de validation « au clic » : une formation se suit et se valide
        // uniquement à travers ses modules (contenu, quiz, examen) sur la plateforme.
        if ($enrollment->formation->modules()->exists()) {
            return response()->json(['ok' => false, 'message' => 'Cette formation utilise désormais des modules détaillés.'], 422);
        }

        return response()->json(['ok' => false, 'message' => 'Le contenu de cette formation est en préparation : elle ne peut pas encore être validée.'], 422);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function index()
    {
        $formations = Formation::withCount(['enrollments', 'modules as modules_reels_count'])
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
            'image_url' => 'nullable|url|max:500',
            'seuil_reussite' => 'integer|min:50|max:100',
            // Compétences validées, imprimées sur le certificat (3 à 6 conseillées).
            'competences' => 'nullable|array|max:8',
            'competences.*' => 'nullable|string|max:60',
            ...Quiz::rules('examen', 40),
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        if ($erreur = Quiz::invalid($request->input('examen'))) {
            return response()->json(['ok' => false, 'message' => 'Examen — '.$erreur], 422);
        }

        $data = $validator->validated();
        if (array_key_exists('examen', $data)) {
            $data['examen'] = Quiz::normalize($data['examen']);
        }
        if (array_key_exists('competences', $data)) {
            $data['competences'] = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), (array) $data['competences']))));
        }
        $formation->fill($data)->save();

        return response()->json(['ok' => true, 'formation' => $formation]);
    }

    /** GET /admin/formations/{id}/modules — liste complète (admin). */
    public function adminModules(int $id)
    {
        $formation = Formation::find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'modules' => $formation->modules]);
    }

    public function storeModule(Request $request, int $id)
    {
        $formation = Formation::find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        return $this->persistModule($request, new FormationModule(['formation_id' => $formation->id]), $formation);
    }

    public function updateModule(Request $request, int $id, int $moduleId)
    {
        $formation = Formation::find($id);
        $module = FormationModule::where('formation_id', $id)->find($moduleId);
        if (! $formation || ! $module) {
            return response()->json(['ok' => false, 'message' => 'Module introuvable.'], 404);
        }

        return $this->persistModule($request, $module, $formation);
    }

    public function destroyModule(int $id, int $moduleId)
    {
        $formation = Formation::find($id);
        if (! $formation) {
            return response()->json(['ok' => false, 'message' => 'Formation introuvable.'], 404);
        }

        FormationModule::where('formation_id', $id)->where('id', $moduleId)->delete();
        $formation->syncModulesCount();
        $formation->recalculerProgressions();

        return response()->json(['ok' => true]);
    }

    private function persistModule(Request $request, FormationModule $module, Formation $formation)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|min:2|max:200',
            'description' => 'nullable|string|max:2000',
            'contenu' => 'nullable|string|max:50000',
            'video_url' => 'nullable|url|max:500',
            'document_url' => 'nullable|url|max:500',
            'ressources' => 'nullable|array|max:15',
            'ressources.*.nom' => 'required|string|max:150',
            'ressources.*.url' => 'required|url|max:500',
            'ressources.*.taille' => 'nullable|string|max:20',
            'duree' => 'nullable|string|max:50',
            'ordre' => 'nullable|integer|min:0|max:1000',
            ...Quiz::rules('quiz', 20),
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        if ($erreur = Quiz::invalid($request->input('quiz'))) {
            return response()->json(['ok' => false, 'message' => $erreur], 422);
        }

        $data = $validator->validated();
        if (array_key_exists('quiz', $data)) {
            $data['quiz'] = Quiz::normalize($data['quiz']);
        }
        $estNouveau = ! $module->exists;
        $module->fill($data)->save();
        $formation->syncModulesCount();
        if ($estNouveau) {
            $formation->recalculerProgressions();
        }

        return response()->json(['ok' => true, 'module' => $module]);
    }
}
