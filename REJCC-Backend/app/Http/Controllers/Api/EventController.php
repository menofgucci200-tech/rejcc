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
    /** Données publiques d'un événement (vitrine) : jamais le lien de visio ni les inscrits. */
    private function payloadPublic(Event $e): array
    {
        $nb = $e->registrations_count ?? $e->nbInscrits();
        $restantes = $e->capacity === null ? null : max(0, $e->capacity - $nb);

        return [
            'id' => $e->id,
            'slug' => $e->slug,
            'title' => $e->title,
            'excerpt' => $e->excerpt,
            'description' => $e->description,
            'body' => $e->body,
            'category' => $e->category,
            'location' => $e->location,
            'en_ligne' => $e->en_ligne,
            'statut' => $e->statut,
            'motif_annulation' => $e->motif_annulation,
            'starts_at' => $e->starts_at?->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'time_label' => $e->time_label,
            'image' => $e->image,
            'capacity' => $e->capacity,
            'places_restantes' => $restantes,
            'complet' => $restantes === 0,
            'passe' => $e->estPasse(),
            'reserve_abonnes' => $e->reserve_abonnes,
            'inscription_publique' => $e->inscription_publique,
            // Un visiteur peut-il s'inscrire par le formulaire public ?
            'inscription_visiteur' => $e->inscription_publique && $e->raisonRefus(null) === null,
            // Les membres peuvent-ils encore s'inscrire (sans tenir compte de l'abonnement) ?
            'inscriptions_membres' => $e->statut === 'publie' && ! $e->starts_at->isPast() && $e->inscriptions_ouvertes
                && ($e->date_limite === null || $e->date_limite->isFuture()) && $restantes !== 0,
        ];
    }

    /** Liste publique des événements (vitrine) : publiés et annulés. */
    public function publicIndex()
    {
        $events = Event::visibles()->withCount('registrations')->orderBy('starts_at')->get()
            ->map(fn (Event $e) => $this->payloadPublic($e));

        return response()->json(['ok' => true, 'events' => $events->values()]);
    }

    /** Détail public d'un événement par son slug (vitrine). */
    public function publicShow(string $slug)
    {
        $event = Event::visibles()->withCount('registrations')->where('slug', $slug)->first();

        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'event' => $this->payloadPublic($event)]);
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
        $inscription = EventRegistration::where('event_id', $e->id)->where('user_id', $moi->id)->first();

        return response()->json(['ok' => true, 'event' => $this->payloadMembre($e, $moi, (bool) $inscription) + [
            'participants' => $this->participants($e, $moi),
            // Réservé aux inscrits : billet (QR de pointage) et lien de visio.
            'billet' => $inscription?->billet,
            'present' => (bool) $inscription?->present_at,
            'lien_visio' => $inscription && $e->en_ligne ? $e->lien_visio : null,
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
        $events = Event::withCount([
            'registrations',
            'registrations as presents_count' => fn ($q) => $q->whereNotNull('present_at'),
            'registrations as invites_count' => fn ($q) => $q->whereNull('user_id'),
        ])->orderByDesc('starts_at')->get()
            ->map(fn (Event $e) => $e->toArray() + ['passe' => $e->estPasse()]);

        return response()->json(['ok' => true, 'events' => $events]);
    }

    /** Une ligne de la liste unique des inscrits (membre ou invité). */
    private function ligneInscrit(EventRegistration $r): array
    {
        $u = $r->user;

        return [
            'id' => $r->id,
            'user_id' => $r->user_id,
            'type' => $u ? 'membre' : 'invite',
            'nom' => $r->nomComplet(),
            'prenom' => $u->prenom ?? $r->prenom,
            'nom_famille' => $u->nom ?? $r->nom,
            'email' => $u->email ?? $r->email,
            'telephone' => $u->telephone ?? $r->telephone,
            'ville' => $u->ville ?? null,
            'photo' => $u->photo ?? null,
            'role' => $u->role ?? null,
            'numero' => $u?->memberNumber(),
            'se_dit_membre' => $r->se_dit_membre,
            'reponses' => $r->reponses ?? [],
            'billet' => $r->billet,
            'inscrit_le' => $r->created_at?->toIso8601String(),
            'present_at' => $r->present_at?->toIso8601String(),
        ];
    }

    /**
     * GET /admin/events/{id}/inscrits — liste unique des inscrits (membres et
     * invités du formulaire public), recherche, présence le jour J.
     */
    public function inscrits(Request $request, int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        $q = trim((string) $request->query('q', ''));
        $query = EventRegistration::with('user:id,prenom,nom,email,telephone,ville,photo,role')
            ->where('event_id', $e->id)->orderBy('created_at');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('prenom', 'like', $like)->orWhere('nom', 'like', $like)
                ->orWhere('telephone', 'like', $like)->orWhere('email', 'like', $like)->orWhere('billet', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('prenom', 'like', $like)->orWhere('nom', 'like', $like)
                    ->orWhere('email', 'like', $like)->orWhere('telephone', 'like', $like)));
        }
        $inscrits = $query->get()->map(fn (EventRegistration $r) => $this->ligneInscrit($r));
        $tous = EventRegistration::where('event_id', $e->id);

        return response()->json([
            'ok' => true,
            'event' => [
                'id' => $e->id, 'title' => $e->title, 'slug' => $e->slug, 'statut' => $e->statut,
                'starts_at' => $e->starts_at?->toIso8601String(), 'capacity' => $e->capacity,
                'champs' => $e->champs ?? [], 'inscription_publique' => $e->inscription_publique, 'attestation' => (bool) $e->attestation,
            ],
            'inscrits' => $inscrits->values(),
            'total' => (clone $tous)->count(),
            'membres' => (clone $tous)->whereNotNull('user_id')->count(),
            'invites' => (clone $tous)->whereNull('user_id')->count(),
            'presents' => (clone $tous)->whereNotNull('present_at')->count(),
        ]);
    }

    /** DELETE /admin/events/{id}/inscrits/{inscription} — retire un inscrit (doublon, erreur). */
    public function retirerInscrit(int $id, int $inscription)
    {
        EventRegistration::where('event_id', $id)->whereKey($inscription)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * POST /admin/events/{id}/pointage — pointe un participant le jour J à
     * partir du QR de son billet (B-XXXXXXXX) ou de sa carte membre
     * (…/carte/0006). Avec sur_place=1, un membre non inscrit est inscrit et
     * pointé.
     */
    public function pointage(Request $request, int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        $code = strtoupper(trim((string) $request->input('code')));
        $inscription = null;
        $membre = null;

        if (preg_match('/B-[A-Z0-9]{8}/', $code, $m)) {
            $inscription = EventRegistration::with('user')->where('event_id', $e->id)->where('billet', $m[0])->first();
            if (! $inscription) {
                return response()->json(['ok' => false, 'message' => "Ce billet ne correspond pas à cet événement."], 404);
            }
            $membre = $inscription->user;
        } elseif (preg_match('#(?:/CARTE/)?0*(\d{1,9})$#', $code, $m)) {
            $membre = User::find((int) $m[1]);
            if (! $membre) {
                return response()->json(['ok' => false, 'message' => 'Carte membre inconnue.'], 404);
            }
            $inscription = EventRegistration::where('event_id', $e->id)->where('user_id', $membre->id)->first();
            if (! $inscription) {
                if (! $request->boolean('sur_place')) {
                    return response()->json([
                        'ok' => false, 'code' => 'non_inscrit',
                        'message' => trim($membre->prenom.' '.$membre->nom)." n'est pas inscrit(e) à cet événement.",
                        'membre' => ['id' => $membre->id, 'nom' => trim($membre->prenom.' '.$membre->nom), 'photo' => $membre->photo],
                    ], 404);
                }
                $inscription = EventRegistration::create(['event_id' => $e->id, 'user_id' => $membre->id]);
            }
        } else {
            return response()->json(['ok' => false, 'message' => 'Code non reconnu : scannez un billet ou une carte membre.'], 422);
        }

        $deja = $inscription->present_at !== null;
        if (! $deja) {
            $inscription->update(['present_at' => now()]);
        }

        return response()->json([
            'ok' => true,
            'deja' => $deja,
            'membre' => $membre
                ? ['id' => $membre->id, 'nom' => trim($membre->prenom.' '.$membre->nom), 'photo' => $membre->photo, 'role' => $membre->role]
                : ['id' => null, 'nom' => $inscription->nomComplet(), 'photo' => null, 'role' => 'invite'],
            'present_at' => $inscription->present_at->toIso8601String(),
            'presents' => EventRegistration::where('event_id', $e->id)->whereNotNull('present_at')->count(),
            'inscrits' => EventRegistration::where('event_id', $e->id)->count(),
        ]);
    }

    /** POST /admin/events/{id}/annuler — annule l'événement et prévient les inscrits. */
    public function annuler(Request $request, int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        $motif = trim((string) $request->input('motif'));
        if (mb_strlen($motif) < 5) {
            return response()->json(['ok' => false, 'message' => "Indiquez le motif de l'annulation : il est transmis aux inscrits."], 422);
        }
        if ($e->statut === 'annule') {
            return response()->json(['ok' => false, 'message' => 'Cet événement est déjà annulé.'], 422);
        }
        $e->update(['statut' => 'annule', 'motif_annulation' => mb_substr($motif, 0, 500)]);
        $prevenus = $e->prevenirInscrits("Événement annulé : {$e->title}", "Nous sommes au regret d'annuler « {$e->title} ». Motif : {$motif}");

        return response()->json(['ok' => true, 'prevenus' => $prevenus]);
    }

    /** POST /admin/events/{id}/retablir — remet en ligne un événement annulé par erreur. */
    public function retablir(int $id)
    {
        $e = Event::find($id);
        if (! $e || $e->statut !== 'annule') {
            return response()->json(['ok' => false, 'message' => "Cet événement n'est pas annulé."], 422);
        }
        $e->update(['statut' => 'publie', 'motif_annulation' => null]);
        $prevenus = $e->prevenirInscrits("Événement maintenu : {$e->title}", "Bonne nouvelle : « {$e->title} » est finalement maintenu, {$this->quand($e)}. Votre inscription reste valable.");

        return response()->json(['ok' => true, 'prevenus' => $prevenus]);
    }

    /** POST /admin/events/{id}/message — message de l'équipe à tous les inscrits. */
    public function message(Request $request, int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        $texte = trim((string) $request->input('message'));
        if (mb_strlen($texte) < 5) {
            return response()->json(['ok' => false, 'message' => 'Écrivez le message à envoyer aux inscrits.'], 422);
        }
        if (! $e->registrations()->exists()) {
            return response()->json(['ok' => false, 'message' => "Personne n'est encore inscrit à cet événement."], 422);
        }
        $prevenus = $e->prevenirInscrits("Message : {$e->title}", mb_substr($texte, 0, 1000));

        return response()->json(['ok' => true, 'prevenus' => $prevenus]);
    }

    private function quand(Event $e): string
    {
        return $e->starts_at->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm');
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
            'statut' => 'nullable|in:brouillon,publie',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'time_label' => 'nullable|string|max:60',
            'location' => 'nullable|string|max:160',
            'en_ligne' => 'nullable|boolean',
            'lien_visio' => 'nullable|required_if:en_ligne,true|url|max:500',
            'excerpt' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:3000',
            'capacity' => 'nullable|integer|min:1|max:1000000',
            'image' => 'nullable|url|max:500',
            'inscriptions_ouvertes' => 'nullable|boolean',
            'date_limite' => 'nullable|date|before_or_equal:starts_at',
            'reserve_abonnes' => 'nullable|boolean',
            'inscription_publique' => 'nullable|boolean',
            'attestation' => 'nullable|boolean',
            'annoncer' => 'nullable|boolean',
            // Questions du formulaire d'inscription publique.
            'champs' => 'nullable|array|max:20',
            'champs.*.label' => 'required|string|max:120',
            'champs.*.type' => 'required|in:text,textarea,select,checkbox,file',
            'champs.*.required' => 'nullable|boolean',
            'champs.*.options' => 'nullable|array|max:30',
            'champs.*.options.*' => 'nullable|string|max:120',
        ], [
            'ends_at.after' => 'La fin doit être après le début.',
            'date_limite.before_or_equal' => "La date limite d'inscription doit précéder le début de l'événement.",
            'lien_visio.required_if' => 'Indiquez le lien de connexion (Zoom, Meet…) de l\'événement en ligne.',
            'lien_visio.url' => 'Le lien de connexion doit être une adresse web complète (https://…).',
            'champs.*.label.required' => 'Chaque question doit avoir un intitulé.',
        ], [
            'title' => 'titre', 'category' => 'catégorie', 'starts_at' => 'date de début', 'capacity' => 'capacité',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        if (($data['reserve_abonnes'] ?? false) && ($data['inscription_publique'] ?? false)) {
            return response()->json(['ok' => false, 'message' => "Un événement réservé aux abonnés ne peut pas être ouvert à l'inscription publique."], 422);
        }
        $annoncer = (bool) ($data['annoncer'] ?? false);
        unset($data['annoncer']);
        if (array_key_exists('champs', $data)) {
            $data['champs'] = $this->normaliserChamps($data['champs'] ?? []);
        }
        if (! ($data['en_ligne'] ?? $event->en_ligne)) {
            $data['lien_visio'] = null;
        }
        // Un événement annulé garde son statut (le rétablir passe par « Rétablir »).
        if ($event->statut === 'annule') {
            unset($data['statut']);
        }

        // Slug généré à la création puis stable (les URLs publiques ne changent pas).
        if (! $event->exists) {
            $base = Str::slug($data['title']) ?: 'evenement';
            $slug = $base;
            for ($i = 2; Event::where('slug', $slug)->orWhere('ancien_slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $data['slug'] = $slug;
            $data['statut'] ??= 'publie';
        }

        $etaitPublie = $event->exists && $event->statut === 'publie';
        $ancienneDate = $event->starts_at;
        $ancienLieu = $event->en_ligne ? 'en ligne' : $event->location;

        $event->fill($data)->save();

        // Report ou changement de lieu d'un événement publié : les inscrits sont prévenus.
        $prevenus = null;
        $nouveauLieu = $event->en_ligne ? 'en ligne' : $event->location;
        if ($etaitPublie && $event->statut === 'publie' && ($ancienneDate && ! $ancienneDate->equalTo($event->starts_at) || $ancienLieu !== $nouveauLieu)) {
            $changeDate = ! $ancienneDate->equalTo($event->starts_at);
            $prevenus = $event->prevenirInscrits(
                ($changeDate ? 'Date modifiée' : 'Lieu modifié')." : {$event->title}",
                "« {$event->title} » aura lieu {$this->quand($event)}".($event->en_ligne ? ' en ligne' : ($event->location ? " — {$event->location}" : '')).'. Votre inscription reste valable ; si vous ne pouvez plus venir, annulez-la pour libérer votre place.'
            );
        }

        // Annonce aux membres à la publication (case à cocher).
        $annonces = 0;
        if ($annoncer && $event->statut === 'publie' && ! $event->annonce_at) {
            $annonces = $this->annoncer($event);
        }

        return response()->json(['ok' => true, 'event' => $event->fresh(), 'prevenus' => $prevenus, 'annonces' => $annonces]);
    }

    /** Notifie tous les membres actifs d'un nouvel événement (une seule fois). */
    private function annoncer(Event $e): int
    {
        $body = "{$this->quand($e)}".($e->en_ligne ? ' — en ligne' : ($e->location ? " — {$e->location}" : ''))
            .($e->reserve_abonnes ? '. Réservé aux membres à jour de leur abonnement.' : '. Inscrivez-vous depuis la fiche de l\'événement.');
        $n = 0;
        User::where('is_active', true)->whereIn('role', ['member', 'mentor'])->select('id')
            ->chunkById(500, function ($users) use ($e, $body, &$n) {
                $now = now();
                MemberNotification::insert($users->map(fn ($u) => [
                    'user_id' => $u->id, 'type' => 'info', 'title' => "Nouvel événement : {$e->title}",
                    'body' => ucfirst($body), 'link' => "/espace-membre/evenements?evenement={$e->id}",
                    'created_at' => $now, 'updated_at' => $now,
                ])->all());
                $n += $users->count();
            });
        $e->forceFill(['annonce_at' => now()])->save();

        return $n;
    }

    /** Génère une clé stable par question et nettoie sa définition. */
    private function normaliserChamps(array $champs): array
    {
        $out = [];
        $used = [];
        foreach ($champs as $f) {
            $label = trim($f['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $base = Str::slug($label) ?: 'champ';
            $key = $base;
            for ($i = 2; in_array($key, $used, true); $i++) {
                $key = $base.'-'.$i;
            }
            $used[] = $key;
            $field = ['key' => $key, 'label' => $label, 'type' => $f['type'], 'required' => (bool) ($f['required'] ?? false)];
            if ($f['type'] === 'select') {
                $field['options'] = array_values(array_filter(array_map('trim', $f['options'] ?? [])));
            }
            $out[] = $field;
        }

        return $out;
    }

    /** Ancienne bascule (compatibilité) : inscrit ou désinscrit selon l'état actuel. */
    public function register(Request $request, int $id)
    {
        $inscrit = EventRegistration::where('event_id', $id)->where('user_id', $request->user()->id)->exists();

        return $inscrit ? $this->desinscrire($request, $id) : $this->inscrire($request, $id);
    }
}
