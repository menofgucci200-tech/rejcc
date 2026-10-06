<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /** Liste publique des événements (vitrine, pas d'inscription/membre). */
    public function publicIndex()
    {
        return response()->json(['ok' => true, 'events' => Event::where('statut', 'publie')->orderBy('starts_at')->get()]);
    }

    /** Détail public d'un événement par son slug (vitrine). */
    public function publicShow(string $slug)
    {
        $event = Event::visibles()->where('slug', $slug)->first();

        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'event' => $event]);
    }

    /** Données d'un événement pour l'espace membre. */
    private function payloadMembre(Event $e, User $moi, bool $inscrit): array
    {
        $nb = $e->registrations_count ?? $e->nbInscrits();
        $restantes = $e->capacity === null ? null : max(0, $e->capacity - $nb);

        return [
            'id' => $e->id,
            'slug' => $e->slug,
            'title' => $e->title,
            'excerpt' => $e->excerpt,
            'description' => $e->description,
            'location' => $e->location,
            'en_ligne' => $e->en_ligne,
            'category' => $e->category,
            'statut' => $e->statut,
            'motif_annulation' => $e->motif_annulation,
            'starts_at' => $e->starts_at?->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'time_label' => $e->time_label,
            'image' => $e->image,
            'capacity' => $e->capacity,
            'attendees_count' => $nb,
            'places_restantes' => $restantes,
            'complet' => $restantes === 0,
            'reserve_abonnes' => $e->reserve_abonnes,
            'date_limite' => $e->date_limite?->toIso8601String(),
            'passe' => $e->estPasse(),
            'registered' => $inscrit,
            // Raison pour laquelle l'inscription est impossible (null : possible).
            'refus' => $inscrit ? null : $e->raisonRefus($moi),
        ];
    }

    /**
     * « Ils participent » : membres inscrits visibles dans l'annuaire (comptes
     * actifs, sans masquage). La liste nominative est réservée aux membres à
     * jour de leur abonnement, comme l'annuaire ; les autres voient le nombre.
     */
    private function participants(Event $e, User $moi): array
    {
        $visibles = User::query()
            ->join('event_registrations', 'event_registrations.user_id', '=', 'users.id')
            ->where('event_registrations.event_id', $e->id)
            ->where('users.is_active', true)
            ->where('users.id', '!=', $moi->id)
            ->where(fn ($w) => $w->whereNull('users.preferences')
                ->orWhereNull('users.preferences->apparaitre_annuaire')
                ->orWhere('users.preferences->apparaitre_annuaire', true));

        $total = (clone $visibles)->count();
        $voir = $moi->hasActiveSubscription();

        return [
            'total' => $total,
            'visible' => $voir,
            'membres' => $voir ? $visibles->orderByDesc('event_registrations.created_at')->limit(30)
                ->get(['users.id', 'users.prenom', 'users.nom', 'users.photo', 'users.role', 'users.titre', 'users.ville'])
                ->map(fn (User $u) => $u->only(['id', 'prenom', 'nom', 'photo', 'role', 'titre', 'ville']))->values()->all() : [],
        ];
    }

    /** GET /events — événements publiés (et annulés) avec l'état d'inscription du membre. */
    public function index(Request $request)
    {
        $moi = $request->user();
        $inscrits = EventRegistration::where('user_id', $moi->id)->pluck('event_id')->flip();

        $events = Event::visibles()->withCount('registrations')->orderBy('starts_at')->get()
            ->map(fn (Event $e) => $this->payloadMembre($e, $moi, isset($inscrits[$e->id])));

        return response()->json(['ok' => true, 'events' => $events->values()]);
    }

    /** GET /events/{id} — fiche complète d'un événement. */
    public function show(Request $request, int $id)
    {
        $moi = $request->user();
        $e = Event::visibles()->withCount('registrations')->find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        $inscrit = EventRegistration::where('event_id', $e->id)->where('user_id', $moi->id)->exists();

        return response()->json(['ok' => true, 'event' => $this->payloadMembre($e, $moi, $inscrit) + [
            'participants' => $this->participants($e, $moi),
        ]]);
    }

    /** POST /events/{id}/inscription — s'inscrire (règles : statut, date, capacité, abonnement). */
    public function inscrire(Request $request, int $id)
    {
        $moi = $request->user();
        $e = Event::visibles()->find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        if (EventRegistration::where('event_id', $e->id)->where('user_id', $moi->id)->exists()) {
            return response()->json(['ok' => true, 'registered' => true, 'attendees_count' => $e->nbInscrits()]);
        }
        if ($raison = $e->raisonRefus($moi)) {
            return response()->json(['ok' => false, 'message' => $raison], 422);
        }

        EventRegistration::create(['event_id' => $e->id, 'user_id' => $moi->id]);

        $date = $e->starts_at->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm');
        MemberNotification::create([
            'user_id' => $moi->id,
            'type' => 'info',
            'title' => 'Inscription confirmée',
            'body' => "Vous êtes inscrit(e) à « {$e->title} », {$date}".($e->en_ligne ? ' (en ligne)' : ($e->location ? " — {$e->location}" : '')).'.',
            'link' => "/espace-membre/evenements?evenement={$e->id}",
        ]);

        return response()->json(['ok' => true, 'registered' => true, 'attendees_count' => $e->nbInscrits()]);
    }

    /** DELETE /events/{id}/inscription — se désinscrire (avant le début de l'événement). */
    public function desinscrire(Request $request, int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        if ($e->starts_at->isPast()) {
            return response()->json(['ok' => false, 'message' => "L'événement a commencé : l'inscription ne peut plus être annulée."], 422);
        }
        EventRegistration::where('event_id', $e->id)->where('user_id', $request->user()->id)->delete();

        return response()->json(['ok' => true, 'registered' => false, 'attendees_count' => $e->nbInscrits()]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex()
    {
        $events = Event::withCount('registrations')->orderByDesc('starts_at')->get();

        return response()->json(['ok' => true, 'events' => $events]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Event());
    }

    public function update(Request $request, int $id)
    {
        $event = Event::find($id);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return $this->persist($request, $event);
    }

    public function destroy(int $id)
    {
        Event::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    private function persist(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:160',
            'category' => 'required|string|min:2|max:60',
            'starts_at' => 'required|date',
            'time_label' => 'nullable|string|max:60',
            'location' => 'nullable|string|max:160',
            'excerpt' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:3000',
            'capacity' => 'nullable|integer|min:1',
            'image' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        // Slug généré à la création puis stable (les URLs publiques ne changent pas).
        if (! $event->exists) {
            $base = Str::slug($data['title']) ?: 'evenement';
            $slug = $base;
            for ($i = 2; Event::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $data['slug'] = $slug;
        }

        $event->fill($data)->save();

        return response()->json(['ok' => true, 'event' => $event]);
    }

    /** Ancienne bascule (compatibilité) : inscrit ou désinscrit selon l'état actuel. */
    public function register(Request $request, int $id)
    {
        $inscrit = EventRegistration::where('event_id', $id)->where('user_id', $request->user()->id)->exists();

        return $inscrit ? $this->desinscrire($request, $id) : $this->inscrire($request, $id);
    }
}
