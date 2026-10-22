<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Sessions live des formations (espace du département Formation). Chaque
 * session est un événement en ligne : elle apparaît dans le calendrier des
 * membres, avec inscription, rappels et ajout à leur agenda. La visio est une
 * salle Jitsi créée automatiquement, ou un lien Meet / Zoom / Teams collé.
 */
class FormationLiveController extends Controller
{
    private function payload(Event $e): array
    {
        return [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'formation_id' => $e->formation_id,
            'formation' => $e->formation?->title,
            'animateur' => $e->animateur,
            'starts_at' => $e->starts_at?->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'duree' => $e->ends_at ? (int) $e->starts_at->diffInMinutes($e->ends_at) : null,
            'lien_visio' => $e->lien_visio,
            'visio_auto' => str_starts_with((string) $e->lien_visio, 'https://meet.jit.si/'),
            'capacity' => $e->capacity,
            'statut' => $e->statut,
            'motif_annulation' => $e->motif_annulation,
            'inscrits' => $e->registrations_count ?? $e->nbInscrits(),
            'annonce' => $e->annonce_at !== null,
            'en_cours' => $e->statut === 'publie' && $e->starts_at->isPast() && ! $e->estPasse(),
            'passe' => $e->estPasse(),
        ];
    }

    /** GET /admin/formations-live : les sessions live et les formations auxquelles les rattacher. */
    public function index()
    {
        $sessions = Event::lives()->with('formation:id,title')->withCount('registrations')
            ->orderBy('starts_at')->get()->map(fn (Event $e) => $this->payload($e))->values();

        return response()->json([
            'ok' => true,
            'sessions' => $sessions,
            'formations' => Formation::orderBy('title')->get(['id', 'title'])->map(fn ($f) => $f->only(['id', 'title'])),
        ]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Event);
    }

    public function update(Request $request, int $id)
    {
        return $this->persist($request, $this->session($id));
    }

    /** POST /admin/formations-live/{id}/annuler : les inscrits sont prévenus. */
    public function annuler(Request $request, int $id)
    {
        $e = $this->session($id);
        $motif = trim((string) $request->input('motif', ''));
        $e->forceFill(['statut' => 'annule', 'motif_annulation' => $motif ?: null])->save();
        $prevenus = $e->prevenirInscrits("Session annulée : {$e->title}",
            "La session live « {$e->title} » prévue {$e->quand()} est annulée.".($motif ? " Motif : {$motif}." : ''));

        return response()->json(['ok' => true, 'prevenus' => $prevenus]);
    }

    /** GET /admin/formations-live/{id}/inscrits */
    public function inscrits(int $id)
    {
        $e = $this->session($id);
        $inscrits = EventRegistration::where('event_id', $e->id)->with('user:id,prenom,nom,email,telephone')->latest()->get()
            ->map(fn (EventRegistration $r) => [
                'nom' => $r->user ? trim($r->user->prenom.' '.$r->user->nom) : (trim($r->prenom.' '.$r->nom) ?: 'Invité'),
                'email' => $r->user?->email ?? $r->email,
                'telephone' => $r->user?->telephone ?? $r->telephone,
                'inscrit_le' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'inscrits' => $inscrits]);
    }

    private function session(int $id): Event
    {
        return Event::lives()->findOrFail($id);
    }

    private function persist(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:160',
            'formation_id' => 'nullable|integer|exists:formations,id',
            'animateur' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:3000',
            'starts_at' => 'required|date|after:now',
            'duree' => 'required|integer|min:15|max:600',
            'visio' => 'required|in:auto,lien',
            'lien_visio' => 'nullable|required_if:visio,lien|url:https|max:500',
            'capacity' => 'nullable|integer|min:1|max:100000',
            'annoncer' => 'nullable|boolean',
        ], [
            'starts_at.after' => 'Choisissez une date et une heure à venir.',
            'lien_visio.required_if' => 'Collez le lien de la visio (Google Meet, Zoom, Teams…).',
            'lien_visio.url' => 'Le lien de la visio doit être une adresse complète (https://…).',
        ], ['title' => 'titre', 'starts_at' => 'date', 'duree' => 'durée', 'capacity' => 'nombre de places']);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $d = $validator->validated();

        // Salle Jitsi : créée une fois, gardée ensuite (le lien reste celui partagé aux inscrits).
        $lien = $d['visio'] === 'lien'
            ? $d['lien_visio']
            : (str_starts_with((string) $event->lien_visio, 'https://meet.jit.si/') ? $event->lien_visio
                : 'https://meet.jit.si/REJCC-'.Str::limit(Str::slug($d['title']), 40, '').'-'.Str::lower(Str::random(10)));

        $debut = \Illuminate\Support\Carbon::parse($d['starts_at']);
        $ancienneDate = $event->starts_at;
        $event->fill([
            'title' => $d['title'],
            'formation_id' => $d['formation_id'] ?? null,
            'animateur' => $d['animateur'] ?? null,
            'description' => $d['description'] ?? null,
            'excerpt' => Str::limit((string) ($d['description'] ?? ''), 280),
            'category' => Event::CATEGORIE_LIVE,
            'en_ligne' => true,
            'location' => null,
            'lien_visio' => $lien,
            'starts_at' => $debut,
            'ends_at' => $debut->copy()->addMinutes((int) $d['duree']),
            'capacity' => $d['capacity'] ?? null,
            'inscriptions_ouvertes' => true,
        ]);
        if (! $event->exists) {
            $base = Str::slug($d['title']) ?: 'session-live';
            $slug = $base;
            for ($i = 2; Event::where('slug', $slug)->orWhere('ancien_slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $event->slug = $slug;
            $event->statut = 'publie';
        }
        $event->save();

        // Session reportée : les inscrits sont prévenus.
        if ($ancienneDate && ! $ancienneDate->equalTo($event->starts_at)) {
            $event->prevenirInscrits("Date modifiée : {$event->title}",
                "La session live « {$event->title} » aura lieu {$event->quand()}. Votre inscription reste valable.");
        }

        $annonces = ($d['annoncer'] ?? false) && ! $event->annonce_at ? $event->annoncer() : 0;

        return response()->json(['ok' => true, 'session' => $this->payload($event->fresh(['formation'])), 'annonces' => $annonces]);
    }
}
