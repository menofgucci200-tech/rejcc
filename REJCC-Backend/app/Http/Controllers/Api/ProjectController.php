<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /** Projets de la communauté (tous statuts, l'étiquette informe du stade). */
    public function index(Request $request)
    {
        $projects = Project::with('porteur:id,prenom,nom')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $p) => [
                ...$p->only(['id', 'title', 'description', 'members_count', 'status', 'in_incubator']),
                'porteur' => $p->porteur ? trim($p->porteur->prenom.' '.$p->porteur->nom) : null,
                'mine' => $p->user_id === $request->user()->id,
            ]);

        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    /** Un membre propose un projet (entre en évaluation). */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:4|max:160',
            'description' => 'required|string|min:20|max:3000',
            'members_count' => 'nullable|integer|min:1|max:500',
            'funding_goal' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $project = Project::create([
            ...$validator->validated(),
            'user_id' => $request->user()->id,
            'status' => 'En évaluation',
            'milestones' => Project::defaultMilestones(),
        ]);

        return response()->json(['ok' => true, 'project' => $project], 201);
    }

    /** Les projets suivis par l'incubateur (financement + jalons). */
    public function incubator()
    {
        $projects = Project::where('in_incubator', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $p) => [
                ...$p->only(['id', 'title', 'status', 'funding_goal', 'funding_raised']),
                'milestones' => $p->milestones ?? Project::defaultMilestones(),
            ]);

        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex()
    {
        $projects = Project::with('porteur:id,prenom,nom')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $p) => [
                ...$p->only([
                    'id', 'title', 'description', 'members_count', 'status',
                    'in_incubator', 'funding_goal', 'funding_raised',
                ]),
                'milestones' => $p->milestones ?? Project::defaultMilestones(),
                'porteur' => $p->porteur ? trim($p->porteur->prenom.' '.$p->porteur->nom) : null,
                'created_at' => $p->created_at,
            ]);

        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $project = Project::find($id);
        if (! $project) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:4|max:160',
            'description' => 'required|string|min:20|max:3000',
            'members_count' => 'nullable|integer|min:1|max:500',
            'status' => 'required|string|min:2|max:60',
            'in_incubator' => 'boolean',
            'funding_goal' => 'nullable|integer|min:0',
            'funding_raised' => 'nullable|integer|min:0',
            'milestones' => 'nullable|array|max:8',
            'milestones.*.label' => 'required|string|max:80',
            'milestones.*.done' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $project->fill($validator->validated())->save();

        return response()->json(['ok' => true, 'project' => $project]);
    }

    public function adminDestroy(int $id)
    {
        Project::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
