<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Administration des événements à inscription publique (module « Inscriptions »).
 */
class RegistrationEventController extends Controller
{
    public function index()
    {
        $events = RegistrationEvent::withCount('participants')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RegistrationEvent $e) => $this->payload($e));

        return response()->json(['ok' => true, 'events' => $events]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $data['slug'] = $this->uniqueSlug($data['title']);
        $event = RegistrationEvent::create($data);

        return response()->json(['ok' => true, 'event' => $this->payload($event->loadCount('participants'))], 201);
    }

    public function update(Request $request, int $id)
    {
        $event = RegistrationEvent::find($id);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        $data = $this->validated($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $event->update($data);

        return response()->json(['ok' => true, 'event' => $this->payload($event->loadCount('participants'))]);
    }

    /** Ouvre / ferme les inscriptions. */
    public function toggle(int $id)
    {
        $event = RegistrationEvent::find($id);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        $event->update(['is_open' => ! $event->is_open]);

        return response()->json(['ok' => true, 'is_open' => $event->is_open]);
    }

    public function destroy(int $id)
    {
        RegistrationEvent::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /** Liste paginée + recherche des participants d'un événement. */
    public function participants(Request $request, int $id)
    {
        $event = RegistrationEvent::find($id);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        $q = trim((string) $request->query('q', ''));
        $query = $event->participants()->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('prenom', 'like', "%{$q}%")
                   ->orWhere('nom', 'like', "%{$q}%")
                   ->orWhere('telephone', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $page = $query->paginate(30);

        return response()->json([
            'ok' => true,
            'participants' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    private function validated(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:160',
            'description' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:200',
            'starts_at' => 'nullable|date',
            'capacity' => 'nullable|integer|min:1|max:1000000',
            'is_open' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        return $validator->validated();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'evenement';
        $slug = $base;
        $i = 2;
        while (RegistrationEvent::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function payload(RegistrationEvent $event): array
    {
        $count = $event->participants_count ?? $event->participants()->count();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'capacity' => $event->capacity,
            'count' => $count,
            'remaining' => $event->capacity !== null ? max(0, $event->capacity - $count) : null,
            'is_open' => $event->is_open,
            'is_full' => $event->capacity !== null && $count >= $event->capacity,
            'created_at' => $event->created_at?->toIso8601String(),
        ];
    }
}
