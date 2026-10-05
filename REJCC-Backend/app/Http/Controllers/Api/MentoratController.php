<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\Mentorship;
use App\Models\User;
use App\Support\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    /** Nombre maximum de demandes en attente + mentorats en cours par membre. */
    public const MAX_OUVERTS_PAR_MEMBRE = 2;

    /** Places restantes chez un mentor (capacité − mentorats en cours). */
    private function placesRestantes(User $mentor): int
    {
        $enCours = Mentorship::where('mentor_id', $mentor->id)->where('statut', 'accepte')->count();

        return max(0, (int) $mentor->mentor_capacite - $enCours);
    }

    private function carteMentor(User $m, ?Mentorship $relation = null): array
    {
        $places = $this->placesRestantes($m);

        return [
            'id' => $m->id,
            'prenom' => $m->prenom,
            'nom' => $m->nom,
            'photo' => $m->photo,
            'titre' => $m->titre,
            'secteur' => $m->secteur,
            'ville' => $m->ville,
            'organisation' => $m->organisation,
            'mentor' => MemberProfile::mentor($m),
            'places_restantes' => $places,
            'disponible' => $m->mentor_accepte && $places > 0,
            'ma_relation' => $relation ? ['id' => $relation->id, 'statut' => $relation->statut] : null,
        ];
    }

    /** GET /mentors — mentors du réseau (recherche par nom, expertise, secteur, ville). */
    public function mentors(Request $request)
    {
        $me = $request->user();
        $q = trim((string) $request->query('q', ''));

        $query = User::where('role', 'mentor')->where('is_active', true)->where('id', '!=', $me->id)
            ->orderByDesc('mentor_accepte')->orderBy('prenom')->orderBy('nom');

        // Recherche sans accents ni majuscules (« levee » trouve « Levée de fonds ») ;
        // faite ici car les expertises sont stockées en JSON.
        $norm = fn ($v) => Str::lower(Str::ascii((string) $v));
        $mots = array_map($norm, preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY));

        $relations = Mentorship::where('mentore_id', $me->id)->ouverts()->get()->keyBy('mentor_id');
        $mentors = $query->get()->filter(function (User $m) use ($mots, $norm) {
            $texte = $norm(implode(' ', [$m->prenom, $m->nom, $m->secteur, $m->ville, $m->organisation, $m->mentor_bio, implode(' ', $m->mentor_expertises ?? [])]));

            return collect($mots)->every(fn ($mot) => str_contains($texte, $mot));
        });

        return response()->json([
            'ok' => true,
            'mentors' => $mentors->map(fn (User $m) => $this->carteMentor($m, $relations->get($m->id)))->values(),
            // Domaines proposés (pour les filtres).
            'expertises' => User::where('role', 'mentor')->where('is_active', true)->pluck('mentor_expertises')
                ->flatten()->filter()->unique(fn ($e) => mb_strtolower($e))->sort()->values(),
        ]);
    }

    /** POST /mentors/{id}/demande — le membre demande un mentorat. */
    public function demander(Request $request, int $id)
    {
        $me = $request->user();
        $mentor = User::where('role', 'mentor')->where('is_active', true)->find($id);
        if (! $mentor || $mentor->id === $me->id) {
            return response()->json(['ok' => false, 'message' => 'Mentor introuvable.'], 404);
        }
        if ($me->role !== 'member') {
            return response()->json(['ok' => false, 'message' => 'Les demandes de mentorat sont réservées aux membres.'], 403);
        }
        if (! $me->hasActiveSubscription()) {
            return response()->json(['ok' => false, 'code' => 'subscription_required', 'message' => 'Le mentorat est réservé aux membres à jour de leur abonnement annuel : activez votre abonnement pour envoyer une demande.'], 402);
        }

        $validator = Validator::make($request->all(), [
            'objectif' => 'required|string|min:10|max:200',
            'besoin' => 'nullable|string|max:2000',
        ], [
            'objectif.required' => 'Indiquez votre objectif pour ce mentorat.',
            'objectif.min' => 'Décrivez votre objectif en quelques mots (10 caractères au moins).',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (Mentorship::where('mentore_id', $me->id)->where('mentor_id', $mentor->id)->ouverts()->exists()) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà une demande ou un mentorat en cours avec ce mentor.'], 422);
        }
        if (Mentorship::where('mentore_id', $me->id)->ouverts()->count() >= self::MAX_OUVERTS_PAR_MEMBRE) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà '.self::MAX_OUVERTS_PAR_MEMBRE.' demandes ou mentorats en cours : attendez une réponse ou terminez un mentorat avant d\'en demander un autre.'], 422);
        }
        if (! $mentor->mentor_accepte || $this->placesRestantes($mentor) === 0) {
            return response()->json(['ok' => false, 'message' => 'Ce mentor n\'accepte pas de nouveaux mentorés pour le moment.'], 422);
        }

        $m = Mentorship::create([
            'mentor_id' => $mentor->id,
            'mentore_id' => $me->id,
            'objectif' => trim($request->input('objectif')),
            'besoin' => $request->input('besoin'),
        ]);

        MemberNotification::create([
            'user_id' => $mentor->id,
            'type' => 'info',
            'title' => 'Nouvelle demande de mentorat',
            'body' => trim("{$me->prenom} {$me->nom}")." vous demande un accompagnement : « {$m->objectif} ».",
            'link' => '/espace-membre/mentorat',
        ]);

        return response()->json(['ok' => true, 'mentorship' => $this->relation($m->fresh(['mentor', 'mentore']), $me)]);
    }

    /** Relation vue par l'utilisateur courant (l'« autre » est le mentor ou le mentoré). */
    private function relation(Mentorship $m, User $me): array
    {
        $autre = $m->mentor_id === $me->id ? $m->mentore : $m->mentor;

        return [
            'id' => $m->id,
            'statut' => $m->statut,
            'statut_label' => Mentorship::STATUTS[$m->statut] ?? $m->statut,
            'objectif' => $m->objectif,
            'besoin' => $m->besoin,
            'reponse' => $m->reponse,
            'cree_le' => $m->created_at?->toIso8601String(),
            'repondu_le' => $m->repondu_at?->toIso8601String(),
            'termine_le' => $m->termine_at?->toIso8601String(),
            'je_suis' => $m->mentor_id === $me->id ? 'mentor' : 'mentore',
            'autre' => $autre ? [
                'id' => $autre->id,
                'prenom' => $autre->prenom,
                'nom' => $autre->nom,
                'photo' => $autre->photo,
                'titre' => $autre->titre,
                'secteur' => $autre->secteur,
                'ville' => $autre->ville,
            ] : null,
        ];
    }

    /** GET /mentorat — mes demandes et mentorats (comme mentoré et, pour un mentor, comme mentor). */
    public function mesMentorats(Request $request)
    {
        $me = $request->user();
        $liste = Mentorship::with(['mentor', 'mentore'])
            ->where(fn ($w) => $w->where('mentore_id', $me->id)->orWhere('mentor_id', $me->id))
            ->where('statut', '!=', 'annule') // demandes retirées : sans intérêt dans la liste
            ->orderByRaw("CASE statut WHEN 'accepte' THEN 0 WHEN 'en_attente' THEN 1 ELSE 2 END")
            ->latest()
            ->get()
            ->map(fn (Mentorship $m) => $this->relation($m, $me));

        return response()->json(['ok' => true, 'mentorats' => $liste->values()]);
    }

    /** POST /mentorat/{id}/annuler — le membre retire sa demande en attente. */
    public function annuler(Request $request, int $id)
    {
        $m = Mentorship::where('mentore_id', $request->user()->id)->where('statut', 'en_attente')->find($id);
        if (! $m) {
            return response()->json(['ok' => false, 'message' => 'Demande introuvable ou déjà traitée.'], 404);
        }
        $m->update(['statut' => 'annule']);

        return response()->json(['ok' => true]);
    }
}
