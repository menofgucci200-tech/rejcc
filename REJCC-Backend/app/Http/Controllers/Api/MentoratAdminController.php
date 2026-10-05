<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\MentorApplication;
use App\Models\Mentorship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Pilotage du programme de mentorat par l'administration : vue d'ensemble,
 * candidatures « Devenir mentor », attribution manuelle d'un mentor.
 */
class MentoratAdminController extends Controller
{
    private function nom(?User $u): ?string
    {
        return $u ? trim("{$u->prenom} {$u->nom}") : null;
    }

    /** GET /admin/mentorat — mentors, relations, candidatures. */
    public function index()
    {
        $relations = Mentorship::with(['mentor:id,prenom,nom', 'mentore:id,prenom,nom'])->withCount('seances')->latest()->get();

        $mentors = User::where('role', 'mentor')->orderBy('prenom')->orderBy('nom')->get()->map(function (User $m) use ($relations) {
            $siennes = $relations->where('mentor_id', $m->id);

            return [
                'id' => $m->id,
                'nom' => $this->nom($m) ?: $m->email,
                'email' => $m->email,
                'telephone' => $m->telephone,
                'ville' => $m->ville,
                'secteur' => $m->secteur,
                'actif' => (bool) $m->is_active,
                'depuis' => $m->created_at?->toDateString(),
                'expertises' => $m->mentor_expertises ?? [],
                'capacite' => (int) $m->mentor_capacite,
                'accepte' => (bool) $m->mentor_accepte,
                'en_cours' => $siennes->where('statut', 'accepte')->count(),
                'en_attente' => $siennes->where('statut', 'en_attente')->count(),
                'termines' => $siennes->where('statut', 'termine')->count(),
                'stats' => Mentorship::statsMentor($m->id),
            ];
        });

        return response()->json([
            'ok' => true,
            'stats' => [
                'mentors' => $mentors->count(),
                'en_cours' => $relations->where('statut', 'accepte')->count(),
                'en_attente' => $relations->where('statut', 'en_attente')->count(),
                // Demandes sans réponse depuis plus de 7 jours : à relancer.
                'en_souffrance' => $relations->where('statut', 'en_attente')->filter(fn ($r) => $r->created_at->lt(now()->subDays(7)))->count(),
                'termines' => $relations->where('statut', 'termine')->count(),
                'candidatures' => MentorApplication::where('statut', 'en_attente')->count(),
            ],
            'mentors' => $mentors->values(),
            'relations' => $relations->where('statut', '!=', 'annule')->map(fn (Mentorship $r) => [
                'id' => $r->id,
                'mentor' => $this->nom($r->mentor),
                'mentore' => $this->nom($r->mentore),
                'objectif' => $r->objectif,
                'statut' => $r->statut,
                'statut_label' => Mentorship::STATUTS[$r->statut] ?? $r->statut,
                'cree_le' => $r->created_at?->toDateString(),
                'en_souffrance' => $r->statut === 'en_attente' && $r->created_at->lt(now()->subDays(7)),
                'seances' => $r->seances_count,
                'note' => $r->note,
                'cree_par_admin' => $r->cree_par_admin,
            ])->values(),
            'candidatures' => MentorApplication::with('user:id,prenom,nom,email,ville,secteur,titre')->latest()->get()->map(fn (MentorApplication $c) => [
                'id' => $c->id,
                'nom' => $this->nom($c->user),
                'email' => $c->user?->email,
                'ville' => $c->user?->ville,
                'titre' => $c->user?->titre,
                'expertises' => $c->expertises,
                'experience' => $c->experience,
                'motivation' => $c->motivation,
                'disponibilites' => $c->disponibilites,
                'statut' => $c->statut,
                'statut_label' => MentorApplication::STATUTS[$c->statut] ?? $c->statut,
                'reponse' => $c->reponse,
                'cree_le' => $c->created_at?->toDateString(),
            ])->values(),
            // Membres actifs pouvant recevoir un mentor (attribution manuelle).
            'membres' => User::where('role', 'member')->where('is_active', true)->orderBy('prenom')->orderBy('nom')
                ->get(['id', 'prenom', 'nom', 'email'])->map(fn ($u) => ['id' => $u->id, 'nom' => $this->nom($u).' — '.$u->email])->values(),
        ]);
    }

    /** POST /admin/mentorat/candidatures/{id}/accepter — le membre devient mentor. */
    public function accepterCandidature(Request $request, int $id)
    {
        $c = MentorApplication::with('user')->where('statut', 'en_attente')->find($id);
        if (! $c || ! $c->user) {
            return response()->json(['ok' => false, 'message' => 'Candidature introuvable ou déjà traitée.'], 404);
        }

        $c->user->update([
            'role' => 'mentor',
            'mentor_expertises' => $c->expertises,
            'mentor_bio' => $c->experience,
            'mentor_disponibilites' => $c->disponibilites,
            'mentor_accepte' => true,
        ]);
        $c->update(['statut' => 'acceptee', 'reponse' => trim((string) $request->input('reponse')) ?: null, 'traite_par' => $request->user()->id, 'traite_at' => now()]);

        MemberNotification::create([
            'user_id' => $c->user_id,
            'type' => 'success',
            'title' => 'Bienvenue parmi les mentors du REJCC !',
            'body' => 'Votre candidature est acceptée. Complétez votre fiche de mentor : les membres peuvent désormais vous demander un accompagnement.',
            'link' => '/espace-membre/mentorat',
        ]);

        return response()->json(['ok' => true]);
    }

    /** POST /admin/mentorat/candidatures/{id}/refuser — avec un message au membre. */
    public function refuserCandidature(Request $request, int $id)
    {
        $c = MentorApplication::where('statut', 'en_attente')->find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Candidature introuvable ou déjà traitée.'], 404);
        }
        $validator = Validator::make($request->all(), ['reponse' => 'required|string|min:5|max:1000'], [
            'reponse.required' => 'Expliquez au membre pourquoi sa candidature n\'est pas retenue.',
            'reponse.min' => 'Expliquez au membre pourquoi sa candidature n\'est pas retenue.',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $c->update(['statut' => 'refusee', 'reponse' => trim($request->input('reponse')), 'traite_par' => $request->user()->id, 'traite_at' => now()]);
        MemberNotification::create([
            'user_id' => $c->user_id,
            'type' => 'info',
            'title' => 'Votre candidature de mentor',
            'body' => 'Votre candidature n\'a pas été retenue pour le moment. Consultez le message de l\'équipe dans « Mentorat ».',
            'link' => '/espace-membre/mentorat',
        ]);

        return response()->json(['ok' => true]);
    }

    /** POST /admin/mentorat/attribuer — l'administration met en relation un membre et un mentor. */
    public function attribuer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mentor_id' => 'required|integer',
            'mentore_id' => 'required|integer',
            'objectif' => 'required|string|min:10|max:200',
        ], [
            'mentor_id.required' => 'Choisissez un mentor.',
            'mentore_id.required' => 'Choisissez le membre à accompagner.',
            'objectif.required' => 'Indiquez l\'objectif du mentorat.',
            'objectif.min' => 'Décrivez l\'objectif en quelques mots (10 caractères au moins).',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $mentor = User::where('role', 'mentor')->where('is_active', true)->find($request->input('mentor_id'));
        $membre = User::where('role', 'member')->where('is_active', true)->find($request->input('mentore_id'));
        if (! $mentor || ! $membre) {
            return response()->json(['ok' => false, 'message' => 'Mentor ou membre introuvable.'], 404);
        }
        if (Mentorship::where('mentor_id', $mentor->id)->where('mentore_id', $membre->id)->ouverts()->exists()) {
            return response()->json(['ok' => false, 'message' => 'Ce membre a déjà une demande ou un mentorat en cours avec ce mentor.'], 422);
        }
        $enCours = Mentorship::where('mentor_id', $mentor->id)->where('statut', 'accepte')->count();
        if ($enCours >= (int) $mentor->mentor_capacite) {
            return response()->json(['ok' => false, 'message' => "Ce mentor a atteint sa capacité ({$mentor->mentor_capacite} mentorat(s) en cours)."], 422);
        }

        $m = Mentorship::create([
            'mentor_id' => $mentor->id, 'mentore_id' => $membre->id, 'statut' => 'accepte',
            'objectif' => trim($request->input('objectif')), 'repondu_at' => now(), 'cree_par_admin' => true,
        ]);

        foreach ([[$membre->id, 'Un mentor vous accompagne', $this->nom($mentor).' devient votre mentor (mise en relation par l\'équipe REJCC).'], [$mentor->id, 'Nouveau mentorat attribué', 'L\'équipe REJCC vous confie l\'accompagnement de '.$this->nom($membre).'.']] as [$uid, $titre, $texte]) {
            MemberNotification::create(['user_id' => $uid, 'type' => 'info', 'title' => $titre, 'body' => $texte." Objectif : « {$m->objectif} ».", 'link' => "/espace-membre/mentorat/{$m->id}"]);
        }

        return response()->json(['ok' => true, 'id' => $m->id]);
    }
}
