<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Document;
use App\Models\FormationEnrollment;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Models\PartnershipRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // ── Stats générales ──────────────────────────────────────────────────────

    public function stats()
    {
        // Croissance : total cumulé de membres à la fin de chacun des 12 derniers mois.
        $croissance = collect(range(11, 0))->map(function (int $offset) {
            $fin = now()->subMonths($offset)->endOfMonth();

            return [
                'l' => ucfirst($fin->translatedFormat('M')),
                'v' => User::where('role', 'member')->where('created_at', '<=', $fin)->count(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'stats' => [
                'membres'    => User::where('role', 'member')->count(),
                'admins'     => User::where('role', 'admin')->count(),
                'mentors'    => User::where('role', 'mentor')->count(),
                'adhesions'  => Member::count(),
                'contacts'   => Contact::count(),
                'documents'  => Document::count(),
                'non_traites'=> Contact::where('traite', false)->count(),
                'candidatures_attente' => MembershipApplication::where('statut', 'en_attente')->count(),
                'adhesions_attente' => Member::where('statut', 'en_attente')->count(),
                'newsletter' => NewsletterSubscriber::count(),
                'partenariats_attente' => PartnershipRequest::where('statut', 'nouveau')->count(),
                'formations' => \App\Models\Formation::where('is_published', true)->count(),
                'certificats' => FormationEnrollment::whereNotNull('completed_at')
                    ->whereHas('formation', fn ($q) => $q->where('is_certifying', true))
                    ->count(),
                'fonds_incubateur' => (int) \App\Models\Project::where('in_incubator', true)->sum('funding_raised'),
                'croissance' => $croissance,
            ],
        ]);
    }

    // ── Membres (comptes) ─────────────────────────────────────────────────────

    public function members(Request $request)
    {
        $q = $request->query('q', '');
        $query = User::orderBy('created_at', 'desc');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('prenom', 'like', "%{$q}%")
                   ->orWhere('nom', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $members = $query->get([
            'id', 'prenom', 'nom', 'email', 'telephone',
            'ville', 'profil', 'secteur', 'role', 'permissions', 'is_active', 'created_at',
        ]);

        return response()->json(['ok' => true, 'members' => $members]);
    }

    public function createMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150|unique:users,email',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => 'required|string|min:8',
            'role' => 'required|in:member,mentor,admin',
            'permissions' => 'nullable|array', // null = accès complet (admins uniquement)
            'permissions.*' => 'string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();

        $user = User::create([
            'name' => $d['prenom'].' '.$d['nom'],
            'prenom' => $d['prenom'],
            'nom' => $d['nom'],
            'email' => $d['email'],
            'telephone' => $d['telephone'],
            'password' => $d['password'],
            'role' => $d['role'],
            'permissions' => $d['role'] === 'admin' ? ($d['permissions'] ?? null) : null,
        ]);

        return response()->json(['ok' => true, 'member' => $user->only(['id', 'prenom', 'nom', 'email', 'role', 'permissions'])]);
    }

    /** Dossier complet d'un membre : profil, candidature d'origine, formations. */
    public function memberDetail($id)
    {
        $user = User::findOrFail($id);

        if (! $user->reference) {
            $user->reference = 'REJCC-'.now()->format('Y').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
            $user->save();
        }

        $application = MembershipApplication::where('email', $user->email)
            ->orderByDesc('created_at')
            ->first();

        $formations = FormationEnrollment::with('formation')
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn (FormationEnrollment $e) => $e->formation)
            ->values()
            ->map(fn (FormationEnrollment $e) => [
                'title' => $e->formation->title,
                'progress' => $e->completed_at ? 100 : (int) $e->progress,
                'completed' => $e->completed_at !== null,
            ]);

        return response()->json([
            'ok' => true,
            'member' => [
                ...$user->only([
                    'id', 'prenom', 'nom', 'email', 'telephone', 'genre', 'ville',
                    'paroisse', 'secteur', 'profil', 'organisation', 'bio', 'photo',
                    'role', 'permissions', 'reference', 'is_active',
                ]),
                'code' => str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'date_naissance' => $user->date_naissance?->toDateString(),
                'date_adhesion' => $user->created_at?->toDateString(),
            ],
            'application' => $application,
            'formations' => $formations,
        ]);
    }

    public function updateMember(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'prenom' => 'sometimes|string|min:2|max:80',
            'nom' => 'sometimes|string|min:2|max:80',
            'email' => 'sometimes|email|max:150|unique:users,email,'.$user->id,
            'telephone' => ['sometimes', 'nullable', 'regex:/^[0-9]{10}$/'],
            'ville' => 'sometimes|nullable|string|max:80',
            'secteur' => 'sometimes|nullable|string|max:100',
            'profil' => 'sometimes|nullable|in:etudiant,porteur,entrepreneur',
            'role' => 'sometimes|in:member,mentor,admin',
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'string|max:40',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $user->fill($data);

        // Les permissions n'ont de sens que pour un administrateur.
        if ($user->role !== 'admin') {
            $user->permissions = null;
        }

        if (array_key_exists('prenom', $data) || array_key_exists('nom', $data)) {
            $user->name = trim($user->prenom.' '.$user->nom) ?: $user->name;
        }

        $user->save();

        return response()->json(['ok' => true, 'member' => $user->only([
            'id', 'prenom', 'nom', 'email', 'role', 'permissions', 'is_active',
        ])]);
    }

    public function deleteMember($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['ok' => false, 'message' => 'Impossible de supprimer un administrateur.'], 403);
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }

    // ── Demandes d'adhésion ───────────────────────────────────────────────────

    public function adhesions(Request $request)
    {
        $q = $request->query('q', '');
        $query = Member::orderBy('created_at', 'desc');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('prenom', 'like', "%{$q}%")
                   ->orWhere('nom', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return response()->json(['ok' => true, 'adhesions' => $query->get()]);
    }

    public function updateAdhesion(Request $request, $id)
    {
        $adhesion = Member::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,valide,rejete',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $adhesion->fill($validator->validated())->save();
        return response()->json(['ok' => true]);
    }

    // ── Messages de contact ───────────────────────────────────────────────────

    public function contacts(Request $request)
    {
        $query = Contact::orderBy('created_at', 'desc');
        return response()->json(['ok' => true, 'contacts' => $query->get()]);
    }

    public function markContactTraite($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->update(['traite' => true]);
        return response()->json(['ok' => true]);
    }

    // ── Documents ─────────────────────────────────────────────────────────────

    public function documents()
    {
        return response()->json(['ok' => true, 'documents' => Document::orderBy('category')->orderBy('title')->get()]);
    }

    public function createDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'category'    => 'required|string|max:100',
            'url'         => 'required|url|max:500',
            'size'        => 'nullable|string|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $doc = Document::create($validator->validated());
        return response()->json(['ok' => true, 'document' => $doc], 201);
    }

    public function updateDocument(Request $request, $id)
    {
        $doc = Document::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:500',
            'category'    => 'sometimes|string|max:100',
            'url'         => 'sometimes|url|max:500',
            'size'        => 'nullable|string|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $doc->fill($validator->validated())->save();
        return response()->json(['ok' => true, 'document' => $doc]);
    }

    public function deleteDocument($id)
    {
        Document::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Notifications broadcast ───────────────────────────────────────────────

    public function broadcastNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:150',
            'body'  => 'nullable|string|max:500',
            'link'  => 'nullable|string|max:300',
            'type'  => 'sometimes|in:info,message,alert',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        $members = User::where('role', 'member')->pluck('id');

        foreach ($members as $uid) {
            MemberNotification::create([
                'user_id' => $uid,
                'type'    => $d['type'] ?? 'info',
                'title'   => $d['title'],
                'body'    => $d['body'] ?? null,
                'link'    => $d['link'] ?? null,
            ]);
        }

        return response()->json(['ok' => true, 'sent_to' => count($members)]);
    }

    public function broadcastHistory()
    {
        $historique = MemberNotification::selectRaw('title, type, MIN(created_at) as created_at, COUNT(*) as destinataires')
            ->groupBy('title', 'type', 'body', 'link')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['ok' => true, 'historique' => $historique]);
    }

    // ── Newsletter ────────────────────────────────────────────────────────────

    public function newsletterSubscribers()
    {
        return response()->json(['ok' => true, 'subscribers' => NewsletterSubscriber::orderBy('created_at', 'desc')->get()]);
    }

    // ── Partenariats ──────────────────────────────────────────────────────────

    public function partnershipRequests()
    {
        return response()->json(['ok' => true, 'requests' => PartnershipRequest::orderBy('created_at', 'desc')->get()]);
    }

    public function updatePartnershipRequest(Request $request, $id)
    {
        $partnership = PartnershipRequest::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:nouveau,accepte,refuse',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $partnership->fill($validator->validated())->save();
        return response()->json(['ok' => true]);
    }
}
