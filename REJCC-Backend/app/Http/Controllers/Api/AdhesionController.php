<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdhesionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profil' => 'required|in:etudiant,porteur,entrepreneur',
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'genre' => 'required|in:Homme,Femme',
            'ville' => 'required|string|min:2|max:80',
            'secteur' => 'required|string|max:100',
            'organisation' => 'nullable|string|max:120',
            'message' => 'nullable|string|max:800',
            'paiement' => 'required|in:wave,orange,djamo',
            'consent' => 'accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $reference = 'REJCC-' . strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(3));

        $member = Member::create([
            'reference' => $reference,
            'profil' => $data['profil'],
            'prenom' => $data['prenom'],
            'nom' => $data['nom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'genre' => $data['genre'],
            'ville' => $data['ville'],
            'secteur' => $data['secteur'],
            'organisation' => $data['organisation'] ?? null,
            'message' => $data['message'] ?? null,
            'paiement' => $data['paiement'],
            'statut' => 'pending',
        ]);

        // Paiement enregistré en attente. L'initiation réelle (Wave / Orange
        // Money / Djamo) sera ajoutée dès que les identifiants marchands
        // seront disponibles.
        Payment::create([
            'member_id' => $member->id,
            'reference' => $reference,
            'provider' => $data['paiement'],
            'amount' => 10000,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        // TODO: e-mail de confirmation + instructions de règlement.

        return response()->json(['ok' => true, 'reference' => $reference]);
    }
}
