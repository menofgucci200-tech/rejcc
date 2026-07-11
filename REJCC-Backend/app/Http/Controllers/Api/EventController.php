<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
