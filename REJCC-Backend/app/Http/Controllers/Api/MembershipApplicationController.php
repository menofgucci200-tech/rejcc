<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MembershipApplicationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom_prenoms' => 'required|string|min:2|max:150',
            'sexe' => 'required|in:Homme,Femme',
            'tranche_age' => 'required|string',
            'whatsapp' => 'required|string|min:8|max:20',
            'email' => 'required|email|max:150',
            'ville' => 'required|string|max:80',
            'password' => 'required|string|min:8|confirmed',
            'connotation_religieuse' => 'required|string',
            'paroisse' => 'nullable|string|required_if:connotation_religieuse,Catholique',
            'statut_actuel' => 'required|array|min:1',
            'niveau_etudes' => 'required|string',
            'domaines_formation' => 'required|string|max:200',
            'competences' => 'required|array|min:1',
            'description_competences' => 'nullable|string|max:1000',
            'a_activite' => 'required|in:Oui,Non',
            'nom_activite' => 'nullable|string|max:150',
            'secteurs_activite' => 'nullable|array|min:1|required_if:a_activite,Oui',
            'anciennete' => 'nullable|string|required_if:a_activite,Oui',
            'domaines_futurs' => 'nullable|array|min:1|required_if:a_activite,Non',
            'attentes' => 'required|array|min:1',
            'formations_interet' => 'required|array|min:1',
            'defi_principal' => 'required|string',
            'revenu_mensuel' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['ok' => false, 'message' => 'Un compte existe déjà avec cette adresse e-mail.'], 422);
        }

        if (MembershipApplication::where('email', $validated['email'])->whereIn('statut', ['en_attente', 'accepte'])->exists()) {
            return response()->json(['ok' => false, 'message' => 'Une candidature est déjà en cours ou a déjà été acceptée pour cette adresse e-mail.'], 422);
        }

        $application = MembershipApplication::create([
            'nom_prenoms' => $validated['nom_prenoms'],
            'sexe' => $validated['sexe'],
            'tranche_age' => $validated['tranche_age'],
            'whatsapp' => $validated['whatsapp'],
            'email' => $validated['email'],
            'ville' => $validated['ville'],
            'password' => $validated['password'],
            'connotation_religieuse' => $validated['connotation_religieuse'],
            'paroisse' => $validated['connotation_religieuse'] === 'Catholique' ? $validated['paroisse'] : null,
            'statut_actuel' => $validated['statut_actuel'],
            'niveau_etudes' => $validated['niveau_etudes'],
            'domaines_formation' => $validated['domaines_formation'],
            'competences' => $validated['competences'],
            'description_competences' => $validated['description_competences'] ?? null,
            'a_activite' => $validated['a_activite'],
            'nom_activite' => $validated['a_activite'] === 'Oui' ? ($validated['nom_activite'] ?? null) : null,
            'secteurs_activite' => $validated['a_activite'] === 'Oui' ? $validated['secteurs_activite'] : null,
            'anciennete' => $validated['a_activite'] === 'Oui' ? $validated['anciennete'] : null,
            'domaines_futurs' => $validated['a_activite'] === 'Non' ? $validated['domaines_futurs'] : null,
            'attentes' => $validated['attentes'],
            'formations_interet' => $validated['formations_interet'],
            'defi_principal' => $validated['defi_principal'],
            'revenu_mensuel' => $validated['revenu_mensuel'],
            'statut' => 'en_attente',
        ]);

        return response()->json(['ok' => true, 'id' => $application->id]);
    }

    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $query = MembershipApplication::orderBy('created_at', 'desc');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('nom_prenoms', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return response()->json(['ok' => true, 'applications' => $query->get()]);
    }

    public function show($id)
    {
        return response()->json(['ok' => true, 'application' => MembershipApplication::findOrFail($id)]);
    }

    public function accept($id)
    {
        $application = MembershipApplication::findOrFail($id);

        if ($application->statut !== 'en_attente') {
            return response()->json(['ok' => false, 'message' => 'Cette candidature a déjà été traitée.'], 422);
        }

        if (User::where('email', $application->email)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Un compte existe déjà avec cette adresse e-mail.'], 422);
        }

        [$prenom, $nom] = $this->splitName($application->nom_prenoms);

        $user = User::create([
            'name' => $application->nom_prenoms,
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $application->email,
            'telephone' => $application->whatsapp,
            'password' => \Illuminate\Support\Str::random(32),
            'genre' => $application->sexe,
            'ville' => $application->ville,
            'secteur' => $application->secteurs_activite[0] ?? ($application->domaines_futurs[0] ?? null),
            'profil' => $this->deriveProfil($application),
            'role' => 'member',
        ]);

        // La candidature stocke déjà le mot de passe hashé choisi par le candidat :
        // on le recopie tel quel via une requête brute pour éviter un double hachage
        // par le cast 'hashed' du modèle User.
        DB::table('users')->where('id', $user->id)->update(['password' => $application->password]);

        $application->update(['statut' => 'accepte', 'user_id' => $user->id]);

        MemberNotification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Bienvenue au REJCC !',
            'body' => "Votre candidature a été acceptée. Connectez-vous avec l'e-mail et le mot de passe renseignés lors de votre adhésion.",
            'link' => '/espace-membre/profil',
        ]);

        return response()->json(['ok' => true, 'user_id' => $user->id]);
    }

    public function reject($id)
    {
        $application = MembershipApplication::findOrFail($id);

        if ($application->statut !== 'en_attente') {
            return response()->json(['ok' => false, 'message' => 'Cette candidature a déjà été traitée.'], 422);
        }

        $application->update(['statut' => 'refuse']);

        return response()->json(['ok' => true]);
    }

    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $application = MembershipApplication::where('email', $request->email)
            ->orderByDesc('created_at')
            ->first();

        if (! $application) {
            return response()->json(['ok' => false, 'message' => 'Aucune candidature trouvée pour cette adresse e-mail.'], 404);
        }

        return response()->json([
            'ok' => true,
            'statut' => $application->statut,
            'nom_prenoms' => $application->nom_prenoms,
            'created_at' => $application->created_at,
        ]);
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? $fullName, $parts[1] ?? ''];
    }

    private function deriveProfil(MembershipApplication $application): string
    {
        if ($application->a_activite === 'Oui' || in_array('Entrepreneur', $application->statut_actuel, true)) {
            return 'entrepreneur';
        }

        if (in_array('Étudiant', $application->statut_actuel, true) || in_array('Élève', $application->statut_actuel, true)) {
            return 'etudiant';
        }

        return 'porteur';
    }
}
