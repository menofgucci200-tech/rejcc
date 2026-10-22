<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Formateur;
use App\Models\User;
use App\Support\GoogleMeet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Formateurs des sessions live (département Formation) et accès des
 * formateurs à leurs sessions : lien personnel (sans compte) ou, pour un
 * membre du réseau, « Mes sessions » dans son espace.
 */
class FormateurController extends Controller
{
    private function payload(Formateur $f): array
    {
        return [
            ...$f->only(['id', 'user_id', 'prenom', 'nom', 'email', 'telephone', 'specialite', 'actif']),
            'nom_complet' => $f->nomComplet(),
            'exterieur' => $f->estExterieur(),
            'sessions_a_venir' => $f->sessions()->where('statut', 'publie')->where('starts_at', '>', now())->count(),
            'sessions_total' => $f->sessions()->count(),
        ];
    }

    /** GET /admin/formateurs */
    public function index()
    {
        return response()->json(['ok' => true, 'formateurs' => Formateur::orderByDesc('actif')->orderBy('prenom')->get()->map(fn ($f) => $this->payload($f))->values()]);
    }

    /** GET /admin/formateurs/membres?q= : membres du réseau à désigner comme formateurs. */
    public function chercherMembres(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'membres' => []]);
        }
        $membres = User::where('is_active', true)
            ->where(fn ($w) => $w->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->whereNotIn('id', Formateur::whereNotNull('user_id')->select('user_id'))
            ->orderBy('prenom')->limit(10)->get(['id', 'prenom', 'nom', 'email', 'telephone', 'secteur']);

        return response()->json(['ok' => true, 'membres' => $membres->map(fn ($u) => $u->only(['id', 'prenom', 'nom', 'email', 'telephone', 'secteur']))]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Formateur);
    }

    public function update(Request $request, int $id)
    {
        return $this->persist($request, Formateur::findOrFail($id));
    }

    private function persist(Request $request, Formateur $f)
    {
        $v = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('formateurs', 'user_id')->ignore($f->id)],
            'prenom' => 'required_without:user_id|nullable|string|max:80',
            'nom' => 'required_without:user_id|nullable|string|max:80',
            'email' => 'nullable|email|max:150',
            'telephone' => 'nullable|string|max:30',
            'specialite' => 'nullable|string|max:160',
            'actif' => 'nullable|boolean',
        ], ['user_id.unique' => 'Ce membre est déjà formateur.'], ['prenom' => 'prénom', 'specialite' => 'spécialité']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $membre = ! empty($d['user_id']) ? User::find($d['user_id']) : null;

        $f->fill([
            'user_id' => $membre?->id,
            'prenom' => $membre?->prenom ?? $d['prenom'],
            'nom' => $membre?->nom ?? $d['nom'],
            'email' => ($d['email'] ?? null) ?: $membre?->email,
            'telephone' => ($d['telephone'] ?? null) ?: $membre?->telephone,
            'specialite' => $d['specialite'] ?? null,
            'actif' => $d['actif'] ?? true,
        ])->save();

        return response()->json(['ok' => true, 'formateur' => $this->payload($f)]);
    }

    /** Ce que voit un formateur d'une de ses sessions. */
    private static function sessionPourFormateur(Event $e, Formateur $f, string $jeton): array
    {
        return [
            'title' => $e->title,
            'description' => $e->description,
            'formation' => $e->formation?->title,
            'starts_at' => $e->starts_at->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'statut' => $e->statut,
            'motif_annulation' => $e->motif_annulation,
            'en_cours' => $e->statut === 'publie' && $e->starts_at->isPast() && ! $e->estPasse(),
            'passe' => $e->estPasse(),
            // La visio s'ouvre à partir d'une heure avant le début.
            'lien_visio' => $e->statut === 'publie' && $e->starts_at->copy()->subHour()->isPast() && ! $e->estPasse() ? $e->lien_visio : null,
            'visio_google' => (bool) $e->google_event_id,
            'compte_google' => $e->google_event_id ? GoogleMeet::compte()['email'] ?? null : null,
            'formateur' => $f->nomComplet(),
            'co_animateurs' => $e->formateurs->where('id', '!=', $f->id)->map->nomComplet()->values(),
            'inscrits' => FormationLiveController::listeInscrits($e, avecContacts: false),
            'jeton' => $jeton,
        ];
    }

    /** GET /animer/{jeton} : espace animateur d'une session (lien personnel, sans compte). */
    public function espaceAnimateur(string $jeton)
    {
        $lien = \Illuminate\Support\Facades\DB::table('event_formateur')->where('jeton', $jeton)->first();
        $e = $lien ? Event::lives()->with(['formation:id,title', 'formateurs'])->find($lien->event_id) : null;
        $f = $lien ? Formateur::find($lien->formateur_id) : null;
        if (! $e || ! $f || ! $f->actif) {
            return response()->json(['ok' => false, 'message' => 'Ce lien d\'animateur n\'est plus valable.'], 404);
        }

        return response()->json(['ok' => true, 'session' => self::sessionPourFormateur($e, $f, $jeton)]);
    }

    /** GET /mes-sessions-animees : sessions d'un membre formateur (espace membre). */
    public function mesSessions(Request $request)
    {
        $f = Formateur::where('user_id', $request->user()->id)->where('actif', true)->first();
        if (! $f) {
            return response()->json(['ok' => true, 'formateur' => false, 'sessions' => []]);
        }
        $sessions = $f->sessions()->with(['formation:id,title', 'formateurs'])->orderBy('starts_at')->get()
            ->filter(fn (Event $e) => ! $e->estPasse() || $e->starts_at->isAfter(now()->subDays(30)))
            ->map(fn (Event $e) => self::sessionPourFormateur($e, $f, $e->pivot->jeton))->values();

        return response()->json(['ok' => true, 'formateur' => true, 'sessions' => $sessions]);
    }
}
