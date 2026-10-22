<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SessionFormateur;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Formateur;
use App\Models\Formation;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Support\GoogleMeet;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sessions live des formations (espace du département Formation). Chaque
 * session est un événement en ligne : elle apparaît dans le calendrier du
 * public visé, avec inscription, rappels et ajout à l'agenda. La visio est une
 * réunion Google Meet créée par le compte Google du REJCC, ou un lien collé.
 * Chaque formateur reçoit un lien personnel d'animateur pour la session.
 */
class FormationLiveController extends Controller
{
    private function payload(Event $e): array
    {
        $lien = (string) $e->lien_visio;

        return [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'formation_id' => $e->formation_id,
            'formation' => $e->formation?->title,
            'animateur' => $e->animateur,
            'formateurs' => $e->formateurs->map(fn (Formateur $f) => [
                'id' => $f->id, 'nom' => $f->nomComplet(), 'exterieur' => $f->estExterieur(),
                'lien' => self::lienAnimateur($f->pivot->jeton),
            ])->values(),
            'starts_at' => $e->starts_at?->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'duree' => $e->ends_at ? (int) $e->starts_at->diffInMinutes($e->ends_at) : null,
            'lien_visio' => $lien,
            'visio' => $e->google_event_id ? 'meet' : (str_starts_with($lien, 'https://meet.jit.si/') ? 'auto' : 'lien'),
            'cible_formation' => (bool) $e->cible_formation,
            'cible_groupes' => array_map('intval', $e->cible_groupes ?? []),
            'cible_profils' => $e->cible_profils ?? [],
            'public' => $this->libellePublic($e),
            'capacity' => $e->capacity,
            'statut' => $e->statut,
            'motif_annulation' => $e->motif_annulation,
            'inscrits' => $e->registrations_count ?? $e->nbInscrits(),
            'annonce' => $e->annonce_at !== null,
            'en_cours' => $e->statut === 'publie' && $e->starts_at->isPast() && ! $e->estPasse(),
            'passe' => $e->estPasse(),
        ];
    }

    public static function lienAnimateur(string $jeton): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/animer/'.$jeton;
    }

    private function libellePublic(Event $e): string
    {
        if (! $e->estCiblee()) {
            return 'Tous les membres';
        }
        $parts = [];
        if ($e->cible_formation) {
            $parts[] = 'inscrits à la formation';
        }
        if (! empty($e->cible_groupes)) {
            $parts[] = Group::whereIn('id', $e->cible_groupes)->orderBy('name')->pluck('name')->implode(', ');
        }
        if (! empty($e->cible_profils)) {
            $parts[] = collect($e->cible_profils)->map(fn ($p) => mb_strtolower(Event::PROFILS[$p] ?? $p))->implode(', ');
        }

        return ucfirst(implode(' · ', $parts));
    }

    /** GET /admin/formations-live : sessions, et de quoi les programmer. */
    public function index()
    {
        $sessions = Event::lives()->with(['formation:id,title', 'formateurs'])->withCount('registrations')
            ->orderBy('starts_at')->get()->map(fn (Event $e) => $this->payload($e))->values();

        return response()->json([
            'ok' => true,
            'sessions' => $sessions,
            'formations' => Formation::orderBy('title')->get(['id', 'title'])->map(fn ($f) => $f->only(['id', 'title'])),
            'groupes' => Group::orderBy('ordre')->orderBy('name')->get(['id', 'name'])->map(fn ($g) => $g->only(['id', 'name'])),
            'profils' => Event::PROFILS,
            'formateurs' => Formateur::where('actif', true)->orderBy('prenom')->get()->map(fn (Formateur $f) => [
                'id' => $f->id, 'nom' => $f->nomComplet(), 'specialite' => $f->specialite, 'exterieur' => $f->estExterieur(),
            ]),
            'google' => ['configure' => GoogleMeet::estConfigure(), 'compte' => GoogleMeet::compte()],
        ]);
    }

    /** POST /admin/formations-live/public : nombre de membres visés par ces critères. */
    public function compterPublic(Request $request)
    {
        $e = new Event([
            'formation_id' => $request->integer('formation_id') ?: null,
            'cible_formation' => $request->boolean('cible_formation'),
            'cible_groupes' => array_map('intval', (array) $request->input('cible_groupes', [])),
            'cible_profils' => array_values(array_intersect((array) $request->input('cible_profils', []), array_keys(Event::PROFILS))),
        ]);

        return response()->json(['ok' => true, 'total' => $e->publicVise()->count()]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Event);
    }

    public function update(Request $request, int $id)
    {
        return $this->persist($request, $this->session($id));
    }

    /** POST /admin/formations-live/{id}/annuler : les inscrits et les formateurs sont prévenus. */
    public function annuler(Request $request, int $id)
    {
        $e = $this->session($id);
        $motif = trim((string) $request->input('motif', ''));
        $e->forceFill(['statut' => 'annule', 'motif_annulation' => $motif ?: null])->save();
        GoogleMeet::supprimer($e);
        $texte = "La session live « {$e->title} » prévue {$e->quand()} est annulée.".($motif ? " Motif : {$motif}." : '');
        $prevenus = $e->prevenirInscrits("Session annulée : {$e->title}", $texte);
        foreach ($e->formateurs as $f) {
            $this->prevenirFormateur($f, $e, "Session annulée : {$e->title}", $texte);
        }

        return response()->json(['ok' => true, 'prevenus' => $prevenus]);
    }

    /** GET /admin/formations-live/{id}/inscrits */
    public function inscrits(int $id)
    {
        $e = $this->session($id);

        return response()->json(['ok' => true, 'inscrits' => self::listeInscrits($e, avecContacts: true)]);
    }

    /** Inscrits d'une session (les formateurs n'en voient que les noms). */
    public static function listeInscrits(Event $e, bool $avecContacts): array
    {
        return EventRegistration::where('event_id', $e->id)->with('user:id,prenom,nom,email,telephone')->latest()->get()
            ->map(fn (EventRegistration $r) => [
                'nom' => $r->user ? trim($r->user->prenom.' '.$r->user->nom) : (trim($r->prenom.' '.$r->nom) ?: 'Invité'),
                'email' => $avecContacts ? ($r->user?->email ?? $r->email) : null,
                'telephone' => $avecContacts ? ($r->user?->telephone ?? $r->telephone) : null,
                'inscrit_le' => $r->created_at?->toIso8601String(),
            ])->all();
    }

    private function session(int $id): Event
    {
        return Event::lives()->with('formateurs')->findOrFail($id);
    }

    private function persist(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:160',
            'formation_id' => 'nullable|integer|exists:formations,id',
            'description' => 'nullable|string|max:3000',
            'starts_at' => 'required|date|after:now',
            'duree' => 'required|integer|min:15|max:600',
            'visio' => 'required|in:meet,lien,auto',
            'lien_visio' => 'nullable|required_if:visio,lien|url:https|max:500',
            'capacity' => 'nullable|integer|min:1|max:100000',
            'annoncer' => 'nullable|boolean',
            'cible_formation' => 'nullable|boolean',
            'cible_groupes' => 'nullable|array',
            'cible_groupes.*' => 'integer|exists:groups,id',
            'cible_profils' => 'nullable|array',
            'cible_profils.*' => 'in:'.implode(',', array_keys(Event::PROFILS)),
            'formateurs' => 'nullable|array|max:10',
            'formateurs.*' => 'integer|exists:formateurs,id',
        ], [
            'starts_at.after' => 'Choisissez une date et une heure à venir.',
            'lien_visio.required_if' => 'Collez le lien de la visio (Google Meet, Zoom, Teams…).',
            'lien_visio.url' => 'Le lien de la visio doit être une adresse complète (https://…).',
        ], ['title' => 'titre', 'starts_at' => 'date', 'duree' => 'durée', 'capacity' => 'nombre de places']);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $d = $validator->validated();
        if (($d['cible_formation'] ?? false) && empty($d['formation_id'])) {
            return response()->json(['ok' => false, 'message' => 'Choisissez la formation dont les inscrits sont visés.'], 422);
        }
        if ($d['visio'] === 'meet' && ! GoogleMeet::estConnecte()) {
            return response()->json(['ok' => false, 'message' => 'Le compte Google du REJCC n\'est pas connecté : connectez-le, ou collez le lien de la réunion Meet.'], 422);
        }

        $formateurs = Formateur::whereIn('id', $d['formateurs'] ?? [])->get();
        $debut = Carbon::parse($d['starts_at']);
        $ancienneDate = $event->starts_at;

        $event->fill([
            'title' => $d['title'],
            'formation_id' => $d['formation_id'] ?? null,
            'animateur' => $formateurs->map->nomComplet()->implode(', ') ?: null,
            'description' => $d['description'] ?? null,
            'excerpt' => Str::limit((string) ($d['description'] ?? ''), 280),
            'category' => Event::CATEGORIE_LIVE,
            'en_ligne' => true,
            'location' => null,
            'starts_at' => $debut,
            'ends_at' => $debut->copy()->addMinutes((int) $d['duree']),
            'capacity' => $d['capacity'] ?? null,
            'inscriptions_ouvertes' => true,
            'cible_formation' => (bool) ($d['cible_formation'] ?? false),
            'cible_groupes' => array_values($d['cible_groupes'] ?? []) ?: null,
            'cible_profils' => array_values($d['cible_profils'] ?? []) ?: null,
        ]);
        if (! $event->exists) {
            $base = Str::slug($d['title']) ?: 'session-live';
            $slug = $base;
            for ($i = 2; Event::where('slug', $slug)->orWhere('ancien_slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $event->slug = $slug;
            $event->statut = 'publie';
            $event->lien_visio = $d['visio'] === 'lien' ? $d['lien_visio'] : 'https://meet.google.com/';
        }
        $event->save();

        // Visio : réunion Meet du compte REJCC, lien collé, ou salle Jitsi (sessions plus anciennes).
        try {
            $lien = match ($d['visio']) {
                'meet' => GoogleMeet::planifier($event),
                'lien' => $d['lien_visio'],
                default => str_starts_with((string) $event->getOriginal('lien_visio'), 'https://meet.jit.si/') ? $event->getOriginal('lien_visio')
                    : 'https://meet.jit.si/REJCC-'.Str::limit(Str::slug($d['title']), 40, '').'-'.Str::lower(Str::random(10)),
            };
        } catch (RuntimeException $ex) {
            if (! $event->wasRecentlyCreated) {
                return response()->json(['ok' => false, 'message' => $ex->getMessage()], 422);
            }
            $event->delete();

            return response()->json(['ok' => false, 'message' => $ex->getMessage()], 422);
        }
        if ($d['visio'] !== 'meet') {
            GoogleMeet::supprimer($event);
        }
        $event->forceFill(['lien_visio' => $lien])->save();

        // Formateurs : lien personnel par session ; les nouveaux reçoivent le leur.
        $deja = $event->formateurs()->pluck('formateurs.id')->all();
        $event->formateurs()->detach(array_diff($deja, $formateurs->pluck('id')->all()));
        foreach ($formateurs as $f) {
            if (! in_array($f->id, $deja, true)) {
                $event->formateurs()->attach($f->id, ['jeton' => Str::random(48)]);
            }
        }
        $event->load('formateurs');
        foreach ($event->formateurs as $f) {
            if (! in_array($f->id, $deja, true)) {
                $this->prevenirFormateur($f, $event, "Vous animez : {$event->title}",
                    "Vous animez la session live « {$event->title} », {$event->quand()}. Votre lien d'animateur donne l'accès à la visio et à la liste des inscrits.");
            }
        }

        // Session reportée : inscrits et formateurs prévenus.
        if ($ancienneDate && ! $ancienneDate->equalTo($event->starts_at)) {
            $texte = "La session live « {$event->title} » aura lieu {$event->quand()}. Votre inscription reste valable.";
            $event->prevenirInscrits("Date modifiée : {$event->title}", $texte);
            foreach ($event->formateurs as $f) {
                if (in_array($f->id, $deja, true)) {
                    $this->prevenirFormateur($f, $event, "Date modifiée : {$event->title}", "La session live « {$event->title} » aura lieu {$event->quand()}.");
                }
            }
        }

        $annonces = ($d['annoncer'] ?? false) && ! $event->annonce_at ? $event->annoncer() : 0;

        return response()->json(['ok' => true, 'session' => $this->payload($event->fresh(['formation', 'formateurs'])), 'annonces' => $annonces]);
    }

    /** Lien personnel par e-mail, et notification sur la plateforme s'il est membre. */
    private function prevenirFormateur(Formateur $f, Event $e, string $titre, string $texte): void
    {
        $lien = self::lienAnimateur($f->pivot->jeton);
        Mailer::send($f->email ?: $f->user?->email, new SessionFormateur($f, $e, $titre, $texte, $lien));
        if ($f->user_id) {
            MemberNotification::create([
                'user_id' => $f->user_id, 'type' => 'info', 'title' => $titre, 'body' => $texte,
                'link' => '/animer/'.$f->pivot->jeton,
            ]);
        }
    }
}
