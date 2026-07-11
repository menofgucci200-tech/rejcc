<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    /** Bibliothèque des ressources publiées (espace membre). */
    public function index()
    {
        $resources = Resource::where('is_published', true)
            ->orderBy('type')->orderByDesc('created_at')
            ->get();

        return response()->json(['ok' => true, 'resources' => $resources]);
    }

    /** Comptabilise un téléchargement/consultation. */
    public function download(int $id)
    {
        $resource = Resource::where('is_published', true)->find($id);
        if (! $resource) {
            return response()->json(['ok' => false, 'message' => 'Ressource introuvable.'], 404);
        }

        $resource->increment('downloads');

        return response()->json(['ok' => true, 'url' => $resource->url]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex()
    {
        return response()->json(['ok' => true, 'resources' => Resource::orderBy('type')->orderByDesc('created_at')->get()]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Resource());
    }

    public function update(Request $request, int $id)
    {
        $resource = Resource::find($id);
        if (! $resource) {
            return response()->json(['ok' => false, 'message' => 'Ressource introuvable.'], 404);
        }

        return $this->persist($request, $resource);
    }

    public function destroy(int $id)
    {
        Resource::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    private function persist(Request $request, Resource $resource)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:200',
            'type' => 'required|string|min:2|max:40',
            'description' => 'nullable|string|max:1000',
            'url' => 'required|url|max:500',
            'size' => 'nullable|string|max:20',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $resource->fill($validator->validated())->save();

        return response()->json(['ok' => true, 'resource' => $resource]);
    }
}
