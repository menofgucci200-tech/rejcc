<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeStat;
use App\Models\MembershipStep;
use App\Models\Partner;
use App\Models\Sector;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * CRUD du contenu éditorial de la vitrine, piloté depuis l'admin
 * (onglet « Contenu du site »). Un type = une table éditable.
 */
class SiteContentController extends Controller
{
    private const TYPES = [
        'sectors' => [
            'model' => Sector::class,
            'rules' => [
                'icon' => 'required|string|max:40',
                'title' => 'required|string|min:2|max:120',
                'blurb' => 'required|string|min:5|max:300',
                'items' => 'required|array|min:1|max:12',
                'items.*' => 'string|max:80',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
        'testimonials' => [
            'model' => Testimonial::class,
            'rules' => [
                'name' => 'required|string|min:2|max:120',
                'role' => 'required|string|min:2|max:120',
                'quote' => 'required|string|min:10|max:600',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
        'partners' => [
            'model' => Partner::class,
            'rules' => [
                'name' => 'required|string|min:2|max:120',
                'sector' => 'required|string|min:2|max:120',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
        'stats' => [
            'model' => HomeStat::class,
            'rules' => [
                'label' => 'required|string|min:2|max:120',
                'value' => 'required|integer|min:0',
                'suffix' => 'nullable|string|max:10',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
        'steps' => [
            'model' => MembershipStep::class,
            'rules' => [
                'icon' => 'required|string|max:40',
                'title' => 'required|string|min:2|max:160',
                'text' => 'required|string|min:5|max:400',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
        'gallery' => [
            'model' => \App\Models\GalleryPhoto::class,
            'rules' => [
                'url' => 'required|url|max:500',
                'caption' => 'nullable|string|max:200',
                'ordre' => 'nullable|integer|min:0',
            ],
        ],
    ];

    public function index(string $type)
    {
        $config = self::TYPES[$type] ?? null;
        if (! $config) {
            return response()->json(['ok' => false, 'message' => 'Type de contenu inconnu.'], 404);
        }

        return response()->json(['ok' => true, 'items' => $config['model']::orderBy('ordre')->orderBy('id')->get()]);
    }

    public function store(Request $request, string $type)
    {
        return $this->persist($request, $type);
    }

    public function update(Request $request, string $type, int $id)
    {
        return $this->persist($request, $type, $id);
    }

    public function destroy(string $type, int $id)
    {
        $config = self::TYPES[$type] ?? null;
        if (! $config) {
            return response()->json(['ok' => false, 'message' => 'Type de contenu inconnu.'], 404);
        }

        $config['model']::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    private function persist(Request $request, string $type, ?int $id = null)
    {
        $config = self::TYPES[$type] ?? null;
        if (! $config) {
            return response()->json(['ok' => false, 'message' => 'Type de contenu inconnu.'], 404);
        }

        $item = $id !== null ? $config['model']::find($id) : new $config['model']();
        if (! $item) {
            return response()->json(['ok' => false, 'message' => 'Élément introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), $config['rules']);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        // Les initiales des témoignages/partenaires sont dérivées du nom.
        if (in_array($type, ['testimonials', 'partners'], true)) {
            $data['initials'] = collect(explode(' ', $data['name']))
                ->filter()
                ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
                ->take(2)
                ->implode('');
        }

        $item->fill($data)->save();

        return response()->json(['ok' => true, 'item' => $item]);
    }
}
