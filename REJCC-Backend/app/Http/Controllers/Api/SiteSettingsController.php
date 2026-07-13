<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Réglages du site (identité, coordonnées, réseaux sociaux, bandeau
 * d'annonce, SEO) et sections des pages vitrine (contenu + visibilité).
 * Lecture publique (la vitrine s'en sert à chaque rendu), écriture admin.
 */
class SiteSettingsController extends Controller
{
    /** Préfixes de clés autorisés à l'écriture (garde-fou). */
    private const KEY_PREFIXES = ['identity.', 'contact.', 'social.', 'banner.', 'seo.'];

    public function index()
    {
        return response()->json([
            'ok' => true,
            'settings' => SiteSetting::pluck('value', 'key'),
            'sections' => PageSection::all(['page', 'section', 'content', 'visible']),
        ]);
    }

    /** PUT /admin/site-settings — upsert en masse {settings: {clé: valeur}}. */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        foreach ($request->input('settings') as $key => $value) {
            if (! is_string($key) || strlen($key) > 120 || ! $this->allowedKey($key)) {
                return response()->json(['ok' => false, 'message' => "Clé de réglage invalide : {$key}"], 422);
            }

            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/page-sections/{page}/{section} — contenu et/ou visibilité. */
    public function updateSection(Request $request, string $page, string $section)
    {
        $validator = Validator::make(
            ['page' => $page, 'section' => $section] + $request->all(),
            [
                'page' => 'required|string|max:60|regex:/^[a-z0-9\-]+$/',
                'section' => 'required|string|max:60|regex:/^[a-z0-9\-]+$/',
                'content' => 'nullable|array',
                'visible' => 'nullable|boolean',
            ],
        );

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $row = PageSection::firstOrNew(['page' => $page, 'section' => $section]);

        if ($request->has('content')) {
            // Les champs vides sont retirés : la vitrine retombe sur sa valeur par défaut.
            $content = array_filter(
                $request->input('content', []),
                fn ($v) => $v !== null && $v !== '' && $v !== [],
            );
            $row->content = $content ?: null;
        }
        if ($request->has('visible')) {
            $row->visible = $request->boolean('visible');
        }

        $row->save();

        return response()->json(['ok' => true, 'section' => $row->only(['page', 'section', 'content', 'visible'])]);
    }

    private function allowedKey(string $key): bool
    {
        foreach (self::KEY_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
