<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Programme de mentorat : profil des mentors, mise en relation avec les
 * membres, demandes, séances et suivi.
 */
class MentoratController extends Controller
{
    /** PUT /mentorat/profil — le mentor met à jour sa fiche de mentor. */
    public function updateProfil(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'mentor') {
            return response()->json(['ok' => false, 'message' => 'Réservé aux mentors du réseau.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'expertises' => 'array|max:8',
            'expertises.*' => 'string|min:2|max:60',
            'bio' => 'nullable|string|max:1500',
            'disponibilites' => 'nullable|string|max:255',
            'format' => 'nullable|in:'.implode(',', array_keys(MemberProfile::FORMATS_MENTORAT)),
            'capacite' => 'required|integer|min:1|max:20',
            'accepte' => 'boolean',
        ], [
            'expertises.max' => 'Indiquez au plus 8 domaines d\'expertise.',
            'capacite.min' => 'Accueillez au moins 1 mentoré.',
            'capacite.max' => 'Au plus 20 mentorés à la fois.',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $user->update([
            'mentor_expertises' => collect($request->input('expertises', []))->map(fn ($e) => trim($e))->filter()->unique()->values()->all(),
            'mentor_bio' => $request->input('bio'),
            'mentor_disponibilites' => $request->input('disponibilites'),
            'mentor_format' => $request->input('format'),
            'mentor_capacite' => (int) $request->input('capacite'),
            'mentor_accepte' => $request->boolean('accepte', true),
        ]);

        return response()->json(['ok' => true, 'mentor' => MemberProfile::mentor($user->fresh())]);
    }
}
