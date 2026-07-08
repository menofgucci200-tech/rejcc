<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /** Liste publique des événements (vitrine, pas d'inscription/membre). */
    public function publicIndex()
    {
        return response()->json(['ok' => true, 'events' => Event::orderBy('starts_at')->get()]);
    }

    /** Détail public d'un événement par son slug (vitrine). */
    public function publicShow(string $slug)
    {
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'event' => $event]);
    }

    /** Liste des événements à venir + statut d'inscription du membre courant. */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $events = Event::orderBy('starts_at')->withCount('registrations')->get();
        $registered = EventRegistration::where('user_id', $me)->pluck('event_id')->all();

        $out = $events->map(fn ($e) => [
            'id' => $e->id,
            'slug' => $e->slug,
            'title' => $e->title,
            'description' => $e->description,
            'location' => $e->location,
            'category' => $e->category,
            'starts_at' => $e->starts_at,
            'image' => $e->image,
            'attendees_count' => $e->registrations_count,
            'registered' => in_array($e->id, $registered),
        ]);

        return response()->json(['ok' => true, 'events' => $out]);
    }

    /** Bascule l'inscription du membre courant à un événement. */
    public function register(Request $request, int $id)
    {
        $me = $request->user()->id;

        $event = Event::find($id);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        $existing = EventRegistration::where('event_id', $id)->where('user_id', $me)->first();
        if ($existing) {
            $existing->delete();
            $registered = false;
        } else {
            EventRegistration::create(['event_id' => $id, 'user_id' => $me]);
            $registered = true;
        }

        return response()->json([
            'ok' => true,
            'registered' => $registered,
            'attendees_count' => EventRegistration::where('event_id', $id)->count(),
        ]);
    }
}
