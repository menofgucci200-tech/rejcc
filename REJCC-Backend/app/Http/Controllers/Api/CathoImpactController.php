<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CathoImpactCandidature;
use App\Models\CathoImpactPortrait;
use App\Support\CathoImpact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** CathoImpact côté public : formulaire de présélection et galerie « Ils ont inspiré ». */
class CathoImpactController extends Controller
{
    /** GET /cathoimpact/questionnaire */
    public function questionnaire()
    {
        return response()->json([
            'ok' => true,
            'profils' => CathoImpact::PROFILS,
            'consentement' => CathoImpact::CONSENTEMENT,
            'parcours_max' => CathoImpact::PARCOURS_MAX,
            'inspiration_max' => CathoImpact::INSPIRATION_MAX,
        ]);
    }

    /** POST /cathoimpact/reponses */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profil' => ['required', Rule::in(array_keys(CathoImpact::PROFILS))],
            'nom_complet' => 'required|string|min:3|max:150',
            'email' => 'required|email|max:150',
            'whatsapp' => ['required', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'entreprise' => 'required|string|min:2|max:150',
            'fonction' => 'required|string|min:2|max:150',
            'secteur' => 'required|string|min:2|max:100',
            'ville' => 'required|string|min:2|max:100',
            'parcours' => 'required|string|min:20|max:'.CathoImpact::PARCOURS_MAX,
            'inspiration' => 'required|string|min:10|max:'.CathoImpact::INSPIRATION_MAX,
            'lien' => 'nullable|url:http,https|max:500',
            'photo' => ['nullable', 'string', 'regex:'.CathoImpact::CHEMIN_PHOTO],
            'consentement' => 'accepted',
        ], [
            'parcours.max' => 'Votre parcours dépasse '.number_format(CathoImpact::PARCOURS_MAX, 0, ',', ' ').' caractères.',
            'inspiration.max' => 'Votre réponse dépasse '.number_format(CathoImpact::INSPIRATION_MAX, 0, ',', ' ').' caractères.',
            'lien.url' => 'Le lien doit commencer par https://',
            'consentement.accepted' => 'Votre autorisation de publication est nécessaire.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        $candidature = new CathoImpactCandidature([
            'profil' => $d['profil'],
            'nom_complet' => trim($d['nom_complet']),
            'email' => mb_strtolower(trim($d['email'])),
            'whatsapp' => $d['whatsapp'],
            'entreprise' => trim($d['entreprise']),
            'fonction' => trim($d['fonction']),
            'secteur' => trim($d['secteur']),
            'ville' => trim($d['ville']),
            'parcours' => trim($d['parcours']),
            'inspiration' => trim($d['inspiration']),
            'lien' => $d['lien'] ?? null,
            'photo' => $d['photo'] ?? null,
            'consentement_texte' => CathoImpact::CONSENTEMENT,
            'consentement_at' => now(),
        ]);
        $candidature->changerStatut('recu');
        $candidature->save();

        return response()->json(['ok' => true, 'id' => $candidature->id], 201);
    }

    /** GET /cathoimpact/portraits : cartes visibles, dans l'ordre choisi. */
    public function portraits()
    {
        $portraits = CathoImpactPortrait::where('visible', true)->orderBy('ordre')->orderBy('id')->get()
            ->map(fn (CathoImpactPortrait $p) => [
                'id' => $p->id,
                'nom' => $p->nom,
                'accroche' => $p->accroche,
                'image_url' => $p->image_url,
                'lien' => $p->lien,
                'reseau' => $p->reseau,
                'reseau_label' => CathoImpact::RESEAUX[$p->reseau] ?? 'Voir la publication',
            ]);

        return response()->json(['ok' => true, 'portraits' => $portraits]);
    }
}
