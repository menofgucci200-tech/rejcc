<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReinitialisationMotDePasse;
use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function issueToken(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plain),
            'name' => 'web',
        ]);
        return $plain;
    }

    private function payload(User $u): array
    {
        return [
            ...$u->only([
                'id', 'prenom', 'nom', 'email', 'telephone', 'genre',
                'ville', 'paroisse', 'secteur', 'profil',
                'organisation', 'bio', 'photo', 'piece_identite', 'role', 'permissions',
            ]),
            'reference' => $u->memberNumber(),
            'numero' => $u->memberNumber(),
            'code' => $u->cardCode(),
            'role_label' => $u->roleLabel(),
            'date_naissance' => $u->date_naissance?->toDateString(),
            'preferences' => $u->preferences ?? $u->defaultPreferences(),
            'date_adhesion' => $u->created_at?->toDateString(),
        ];
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150|unique:users,email',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => 'required|string|min:8|max:100',
            'profil' => 'nullable|in:etudiant,porteur,entrepreneur',
            'ville' => 'nullable|string|max:80',
            'secteur' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        $user = User::create([
            'name' => $d['prenom'] . ' ' . $d['nom'],
            'prenom' => $d['prenom'],
            'nom' => $d['nom'],
            'email' => $d['email'],
            'telephone' => $d['telephone'],
            'password' => $d['password'], // hashé via le cast 'hashed'
            'profil' => $d['profil'] ?? null,
            'ville' => $d['ville'] ?? null,
            'secteur' => $d['secteur'] ?? null,
            'role' => 'member',
        ]);

        MemberNotification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Bienvenue au REJCC !',
            'body' => 'Votre espace membre est prêt. Complétez votre profil pour bien démarrer.',
            'link' => '/espace-membre/profil',
        ]);

        return response()->json([
            'ok' => true,
            'token' => $this->issueToken($user),
            'user' => $this->payload($user),
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['ok' => false, 'message' => 'Identifiant ou mot de passe incorrect.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['ok' => false, 'message' => 'Ce compte a été suspendu. Contactez un administrateur.'], 403);
        }

        return response()->json([
            'ok' => true,
            'token' => $this->issueToken($user),
            'user' => $this->payload($user),
        ]);
    }

    /**
     * Mot de passe oublié : envoie un lien de réinitialisation par e-mail.
     * La réponse est identique que le compte existe ou non (anti-énumération).
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && $user->is_active) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            Mailer::send($user->email, new ReinitialisationMotDePasse($user, $token));
        }

        return response()->json([
            'ok' => true,
            'message' => 'Si un compte existe avec cette adresse, un e-mail de réinitialisation vient de lui être envoyé.',
        ]);
    }

    /** Réinitialise le mot de passe à partir du lien reçu par e-mail. */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|max:100|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $row = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        $expired = ! $row || Carbon::parse($row->created_at)->addHour()->isPast();
        if ($expired || ! Hash::check($request->token, $row->token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ce lien de réinitialisation est invalide ou a expiré. Refaites une demande.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Compte introuvable.'], 422);
        }

        $user->password = $request->password; // hashé via le cast 'hashed'
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Déconnecte les éventuelles sessions ouvertes avec l'ancien mot de passe.
        ApiToken::where('user_id', $user->id)->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(['ok' => true, 'user' => $this->payload($request->user())]);
    }

    public function logout(Request $request)
    {
        ApiToken::where('token', hash('sha256', $request->bearerToken() ?? ''))->delete();
        return response()->json(['ok' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'prenom' => 'sometimes|string|min:2|max:80',
            'nom' => 'sometimes|string|min:2|max:80',
            'telephone' => ['sometimes', 'regex:/^[0-9]{10}$/'],
            'genre' => 'nullable|in:Homme,Femme',
            'ville' => 'nullable|string|max:80',
            'date_naissance' => 'nullable|date',
            'paroisse' => 'nullable|string|max:150',
            'secteur' => 'nullable|string|max:100',
            'profil' => 'nullable|in:etudiant,porteur,entrepreneur',
            'organisation' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:600',
            'photo' => 'nullable|url|max:500', // URL de la photo (fichier stocké côté frontend)
            'piece_identite' => 'nullable|url|max:500', // URL de la pièce d'identité
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user->fill($validator->validated());
        if ($user->isDirty(['prenom', 'nom'])) {
            $user->name = $user->prenom . ' ' . $user->nom;
        }
        $user->save();

        return response()->json(['ok' => true, 'user' => $this->payload($user)]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['ok' => false, 'message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->password = $request->password;
        $user->save();

        return response()->json(['ok' => true]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user->preferences = array_merge($user->preferences ?? $user->defaultPreferences(), $request->preferences);
        $user->save();

        return response()->json(['ok' => true, 'preferences' => $user->preferences]);
    }

    public function directory(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $profil = $request->query('profil');

        $query = User::where('role', 'member')
            ->where('id', '!=', $request->user()->id)
            ->orderBy('prenom')
            ->orderBy('nom');

        if (in_array($profil, ['etudiant', 'porteur', 'entrepreneur'], true)) {
            $query->where('profil', $profil);
        }

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('prenom', 'like', "%{$q}%")
                    ->orWhere('nom', 'like', "%{$q}%")
                    ->orWhere('secteur', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%")
                    ->orWhere('organisation', 'like', "%{$q}%");
            });
        }

        $page = $query->paginate(24, ['id', 'prenom', 'nom', 'ville', 'secteur', 'profil', 'organisation', 'photo']);

        return response()->json([
            'ok' => true,
            'members' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }
}
