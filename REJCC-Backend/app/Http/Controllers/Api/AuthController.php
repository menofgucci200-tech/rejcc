<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReinitialisationMotDePasse;
use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Mailer;
use App\Support\MemberProfile;
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
                'organisation', 'titre', 'diocese', 'bio', 'competences', 'parcours', 'liens',
                'photo', 'piece_identite', 'role', 'permissions',
            ]),
            'reference' => $u->memberNumber(),
            'numero' => $u->memberNumber(),
            'code' => $u->cardCode(),
            'role_label' => $u->roleLabel(),
            'date_naissance' => $u->date_naissance?->toDateString(),
            'preferences' => $u->preferencesEffectives(),
            'date_adhesion' => $u->created_at?->toDateString(),
            'subscription_active' => $u->hasActiveSubscription(),
            'subscription_paid' => $u->hasPaidSubscription(),
            'subscriptions_enforced' => \App\Support\SubscriptionMode::enforced(),
            'subscription_expires_at' => $u->subscription_expires_at?->toDateString(),
            'subscription_exempt' => $u->isExemptFromSubscription(),
            'mentor' => $u->role === 'mentor' ? \App\Support\MemberProfile::mentor($u) : null,
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
            'titre' => 'nullable|string|max:120',
            'diocese' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:1500',
            // Page biographique publique
            'competences' => 'nullable|array|max:15',
            'competences.*' => 'string|min:2|max:40',
            'parcours' => 'nullable|array|max:8',
            'parcours.*.periode' => 'nullable|string|max:40',
            'parcours.*.titre' => 'required|string|max:100',
            'parcours.*.structure' => 'nullable|string|max:120',
            'liens' => 'nullable|array',
            'liens.site' => 'nullable|url|max:300',
            'liens.linkedin' => 'nullable|url|max:300',
            'liens.facebook' => 'nullable|url|max:300',
            'liens.instagram' => 'nullable|url|max:300',
            'photo' => 'nullable|url|max:500', // URL de la photo (fichier stocké côté frontend)
            'piece_identite' => 'nullable|url|max:500', // URL de la pièce d'identité
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        // Page biographique : on ne garde que les clés attendues, sans valeurs vides.
        if (array_key_exists('liens', $data)) {
            $data['liens'] = array_filter(
                array_intersect_key((array) $data['liens'], array_flip(['site', 'linkedin', 'facebook', 'instagram'])),
            ) ?: null;
        }
        if (array_key_exists('parcours', $data)) {
            $data['parcours'] = array_values(array_map(
                fn (array $p) => array_intersect_key($p, array_flip(['periode', 'titre', 'structure'])),
                (array) $data['parcours'],
            )) ?: null;
        }
        if (array_key_exists('competences', $data)) {
            $data['competences'] = array_values(array_unique(array_map('trim', (array) $data['competences']))) ?: null;
        }

        $user->fill($data);
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

        $user->preferences = array_merge($user->preferencesEffectives(), $request->preferences);
        $user->save();

        return response()->json(['ok' => true, 'preferences' => $user->preferences]);
    }

    public function directory(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $profil = $request->query('profil');

        // Membres et mentors du réseau (les mentors sont signalés par leur rôle).
        $query = User::whereIn('role', ['member', 'mentor'])
            ->where('is_active', true)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('prenom')
            ->orderBy('nom');

        if ($request->boolean('mentors')) {
            $query->where('role', 'mentor');
        }

        // Les membres qui ont choisi de ne pas apparaître dans l'annuaire en sont exclus.
        $query->where(fn ($w) => $w->whereNull('preferences')
            ->orWhereNull('preferences->apparaitre_annuaire')
            ->orWhere('preferences->apparaitre_annuaire', true));

        if (in_array($profil, ['etudiant', 'porteur', 'entrepreneur'], true)) {
            $query->where('profil', $profil);
        }

        if ($secteur = trim((string) $request->query('secteur', ''))) {
            $query->where('secteur', $secteur);
        }
        if ($ville = trim((string) $request->query('ville', ''))) {
            $query->where('ville', $ville);
        }
        if ($groupe = (int) $request->query('groupe')) {
            $query->whereHas('groups', fn ($g) => $g->where('groups.id', $groupe));
        }

        // Chaque mot doit se retrouver dans au moins un champ : « Koffi Yao »
        // trouve le membre dont le prénom est Koffi et le nom Yao. Les listes
        // JSON (compétences, expertises) stockent les accents échappés : on
        // cherche aussi la forme échappée.
        $mysql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql';
        foreach (preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) as $mot) {
            $echappe = trim(json_encode(mb_strtolower($mot)), '"');
            if ($mysql) {
                $echappe = str_replace('\\', '\\\\', $echappe);
            }
            $query->where(function ($qb) use ($mot, $echappe) {
                foreach (['prenom', 'nom', 'secteur', 'ville', 'organisation', 'titre', 'bio', 'paroisse'] as $champ) {
                    $qb->orWhere($champ, 'like', "%{$mot}%");
                }
                foreach (['competences', 'mentor_expertises'] as $champ) {
                    $qb->orWhereRaw("LOWER({$champ}) like ?", ["%{$echappe}%"]);
                }
            });
        }

        if ($request->query('tri') === 'recents') {
            $query->reorder()->orderByDesc('created_at')->orderByDesc('id');
        }

        $page = $query->paginate(24, ['id', 'prenom', 'nom', 'ville', 'secteur', 'profil', 'organisation', 'titre', 'competences', 'photo', 'role', 'mentor_expertises', 'created_at']);

        return response()->json([
            'ok' => true,
            'members' => collect($page->items())->map(fn (User $u) => [
                'id' => $u->id,
                'prenom' => $u->prenom,
                'nom' => $u->nom,
                'ville' => $u->ville,
                'secteur' => $u->secteur,
                'profil' => $u->profil,
                'organisation' => $u->organisation,
                'titre' => $u->titre,
                'competences' => array_slice($u->competences ?? [], 0, 3),
                'photo' => $u->photo,
                'role' => $u->role,
                'mentor_expertises' => $u->mentor_expertises,
                // Arrivé il y a moins de 30 jours : à accueillir.
                'nouveau' => $u->created_at?->gt(now()->subDays(30)) ?? false,
            ])->values(),
            // Valeurs disponibles pour les filtres (membres et mentors actifs).
            'filtres' => [
                'secteurs' => User::whereIn('role', ['member', 'mentor'])->where('is_active', true)->whereNotNull('secteur')->where('secteur', '!=', '')->distinct()->orderBy('secteur')->pluck('secteur'),
                'villes' => User::whereIn('role', ['member', 'mentor'])->where('is_active', true)->whereNotNull('ville')->where('ville', '!=', '')->distinct()->orderBy('ville')->pluck('ville'),
                'groupes' => \App\Models\Group::orderBy('ordre')->get(['id', 'name'])->map(fn ($g) => ['id' => $g->id, 'nom' => $g->name]),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    /**
     * GET /members-apercu — chiffres de l'annuaire pour les non-abonnés
     * (aucune donnée personnelle : totaux et principaux secteurs).
     */
    public function apercuAnnuaire()
    {
        $base = User::whereIn('role', ['member', 'mentor'])->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('preferences')
                ->orWhereNull('preferences->apparaitre_annuaire')
                ->orWhere('preferences->apparaitre_annuaire', true));

        return response()->json(['ok' => true, 'apercu' => [
            'membres' => (clone $base)->count(),
            'mentors' => (clone $base)->where('role', 'mentor')->count(),
            'villes' => (clone $base)->whereNotNull('ville')->where('ville', '!=', '')->distinct()->count('ville'),
            'secteurs' => (clone $base)->whereNotNull('secteur')->where('secteur', '!=', '')
                ->selectRaw('secteur, count(*) as nombre')->groupBy('secteur')->orderByDesc('nombre')->limit(6)
                ->pluck('nombre', 'secteur'),
        ]]);
    }

    /** Fiche détaillée d'un membre (clic depuis l'annuaire ou le trombinoscope d'un groupe). */
    public function show(int $id)
    {
        $user = User::whereIn('role', ['member', 'mentor'])->find($id);

        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'member' => MemberProfile::payload($user)]);
    }
}
