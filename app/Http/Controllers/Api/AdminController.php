<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // ── Stats générales ──────────────────────────────────────────────────────

    public function stats()
    {
        return response()->json([
            'ok' => true,
            'stats' => [
                'membres'    => User::where('role', 'member')->count(),
                'admins'     => User::where('role', 'admin')->count(),
                'adhesions'  => Member::count(),
                'contacts'   => Contact::count(),
                'documents'  => Document::count(),
                'non_traites'=> Contact::where('traite', false)->count(),
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
            'ville', 'profil', 'secteur', 'role', 'created_at',
        ]);

        return response()->json(['ok' => true, 'members' => $members]);
    }

    public function updateMember(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:member,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user->fill($validator->validated())->save();

        return response()->json(['ok' => true, 'member' => $user->only([
            'id', 'prenom', 'nom', 'email', 'role',
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
}
