<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\SalonMembre;
use App\Models\SalonMessage;
use App\Models\SalonReaction;
use App\Models\SalonSignalement;
use App\Models\User;
use App\Support\Communaute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Communauté REJCC côté membre : salons, fil de discussion, envoi de
 * messages (texte, photo, document, note vocale), réactions, signalements
 * et réglages. Les référents d'un salon y disposent aussi de leurs outils
 * de modération (silence, exclusion, lecture seule, mode lent).
 */
class CommunauteController extends Controller
{
    private const PAR_PAGE = 50;

    private const MIMES_IMAGES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const MIMES_DOCUMENTS = ['application/pdf'];

    private const MIMES_VOCAUX = ['audio/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg', 'audio/aac', 'audio/x-m4a', 'video/webm'];

    /** GET /communaute — salons du membre avec messages non lus et dernier message. */
    public function index(Request $request)
    {
        $moi = $request->user();
        if ($refus = $this->refusCompte($moi)) {
            return $refus;
        }
        $etats = SalonMembre::where('user_id', $moi->id)->get()->keyBy('salon_id');

        $salons = Communaute::salonsVisibles($moi)->map(function (Salon $s) use ($moi, $etats) {
            $etat = $etats->get($s->id);
            $refus = Communaute::refusAcces($moi, $s, $etat);
            $dernier = $refus ? null : SalonMessage::with('user:id,prenom,nom')->where('salon_id', $s->id)->latest('id')->first();
            $nonLus = (! $refus && $etat)
                ? SalonMessage::where('salon_id', $s->id)->where('id', '>', $etat->lu_jusqua)->whereNull('supprime_at')->where('user_id', '!=', $moi->id)->count()
                : 0;

            return [
                'id' => $s->id,
                'slug' => $s->slug,
                'nom' => $s->nom,
                'emoji' => $s->emoji,
                'description' => $s->description,
                'type' => $s->type,
                'acces' => $s->acces,
                'archive' => $s->archive_at !== null,
                'lecture_seule' => $s->lecture_seule,
                'verrou' => $refus,
                'non_lus' => $nonLus,
                'notifications' => $etat?->notifications ?? $s->notificationsParDefaut(),
                'dernier' => $dernier ? [
                    'auteur' => $dernier->user ? $dernier->user->prenom : null,
                    'moi' => $dernier->user_id === $moi->id,
                    'extrait' => $dernier->estSupprime() ? 'Message supprimé' : Communaute::extrait($dernier),
                    'date' => $dernier->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'salons' => $salons,
            'peut_fichiers' => Communaute::peutEnvoyerFichier($moi),
            'admin' => Communaute::estAdmin($moi),
            'reactions' => SalonReaction::EMOJIS,
        ]);
    }

    /**
     * GET /communaute/salons/{slug}?after=&before= — fil du salon. Sans
     * paramètre : les derniers messages ; after : les nouveaux (rafraîchissement) ;
     * before : l'historique plus ancien. Marque le salon comme lu.
     */
    public function salon(Request $request, string $slug)
    {
        $moi = $request->user();
        [$salon, $etat, $refus] = $this->ouvrir($moi, $slug);
        if ($refus) {
            return $refus;
        }
        $moderateur = Communaute::estModerateur($moi, $salon, $etat);
        $after = (int) $request->query('after', 0);
        $before = (int) $request->query('before', 0);

        $requete = SalonMessage::with(['user:id,prenom,nom,photo,role', 'reponseA.user:id,prenom,nom'])->where('salon_id', $salon->id);
        if ($after > 0) {
            $messages = $requete->where('id', '>', $after)->orderBy('id')->limit(200)->get();
        } else {
            $messages = $requete->when($before > 0, fn ($q) => $q->where('id', '<', $before))
                ->latest('id')->limit(self::PAR_PAGE)->get()->sortBy('id')->values();
        }

        // Messages modifiés ou supprimés depuis le dernier rafraîchissement.
        $depuis = $request->query('depuis');
        $modifies = [];
        if ($after > 0 && $depuis) {
            try {
                $modifies = SalonMessage::with(['user:id,prenom,nom,photo,role', 'reponseA.user:id,prenom,nom'])->where('salon_id', $salon->id)
                    ->where('id', '<=', $after)->where('updated_at', '>', \Illuminate\Support\Carbon::parse($depuis))
                    ->limit(100)->get();
            } catch (\Throwable) {
                $modifies = [];
            }
        }

        $tous = collect($messages)->concat($modifies);
        $reactions = $this->reactions($tous->pluck('id')->all(), $moi->id);
        $referents = SalonMembre::where('salon_id', $salon->id)->where('role', 'moderateur')->pluck('user_id')->all();
        $presenter = fn (SalonMessage $m) => Communaute::presenter($m, $moi, $moderateur, $reactions[$m->id] ?? [], $referents);

        $dernierId = (int) SalonMessage::where('salon_id', $salon->id)->max('id');
        if ($dernierId > $etat->lu_jusqua) {
            $etat->forceFill(['lu_jusqua' => $dernierId])->save();
        }
        Communaute::marquerPresent($moi->id, $salon->id);
        // Les notifications de ce salon sont lues puisque le membre l'a ouvert.
        \App\Models\MemberNotification::where('user_id', $moi->id)->where('type', 'communaute')
            ->where('link', Communaute::lien($salon))->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'salon' => [
                'id' => $salon->id,
                'slug' => $salon->slug,
                'nom' => $salon->nom,
                'emoji' => $salon->emoji,
                'description' => $salon->description,
                'type' => $salon->type,
                'acces' => $salon->acces,
                'regles' => $salon->regles,
                'lecture_seule' => $salon->lecture_seule,
                'mode_lent_secondes' => $salon->mode_lent_secondes,
                'archive' => $salon->archive_at !== null,
            ],
            'moi' => [
                'moderateur' => $moderateur,
                'admin' => Communaute::estAdmin($moi),
                'regles_acceptees' => $etat->regles_acceptees_at !== null || $moderateur,
                'refus_ecriture' => Communaute::refusEcriture($moi, $salon, $etat),
                'attente' => Communaute::attenteModeLent($moi, $salon, $etat),
                'peut_fichiers' => Communaute::peutEnvoyerFichier($moi),
                'peut_mention_tous' => $moderateur,
                'notifications' => $etat->notifications ?? $salon->notificationsParDefaut(),
            ],
            'messages' => collect($messages)->map($presenter)->values(),
            'modifies' => collect($modifies)->map($presenter)->values(),
            'plus_anciens' => $after === 0 && collect($messages)->isNotEmpty()
                && SalonMessage::where('salon_id', $salon->id)->where('id', '<', collect($messages)->first()->id)->exists(),
            'horodatage' => now()->toIso8601String(),
        ]);
    }

    /** POST /communaute/salons/{slug}/regles — le membre accepte les règles du salon. */
    public function accepterRegles(Request $request, string $slug)
    {
        [, $etat, $refus] = $this->ouvrir($request->user(), $slug);
        if ($refus) {
            return $refus;
        }
        $etat->forceFill(['regles_acceptees_at' => now()])->save();

        return response()->json(['ok' => true]);
    }

    /** PUT /communaute/salons/{slug}/notifications — tout, mentions ou sourdine. */
    public function notifications(Request $request, string $slug)
    {
        [, $etat, $refus] = $this->ouvrir($request->user(), $slug);
        if ($refus) {
            return $refus;
        }
        $niveau = (string) $request->input('niveau');
        if (! isset(SalonMembre::NOTIFICATIONS[$niveau])) {
            return response()->json(['ok' => false, 'message' => 'Choisissez un réglage de notifications valide.'], 422);
        }
        $etat->forceFill(['notifications' => $niveau])->save();

        return response()->json(['ok' => true, 'niveau' => $niveau]);
    }

    /** POST /communaute/salons/{slug}/messages — publier un message. */
    public function envoyer(Request $request, string $slug)
    {
        $moi = $request->user();
        [$salon, $etat, $refus] = $this->ouvrir($moi, $slug);
        if ($refus) {
            return $refus;
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:texte,image,fichier,vocal',
            'body' => 'nullable|string|max:4000',
            'reponse_a_id' => 'nullable|integer',
            'mentions' => 'nullable|array|max:20',
            'mentions.*' => 'integer',
            'mention_tous' => 'nullable|boolean',
            'fichier' => 'nullable|string|max:255',
            'fichier_nom' => 'nullable|string|max:160',
            'fichier_mime' => 'nullable|string|max:100',
            'fichier_taille' => 'nullable|integer|min:1',
            'duree' => 'nullable|integer|min:1',
        ], [
            'body.max' => 'Votre message est trop long (4 000 caractères maximum).',
            'mentions.max' => 'Vous ne pouvez pas mentionner plus de 20 personnes dans un message.',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        if ($raison = Communaute::refusEcriture($moi, $salon, $etat)) {
            return response()->json(['ok' => false, 'code' => 'ecriture_refusee', 'message' => $raison], 403);
        }
        $moderateur = Communaute::estModerateur($moi, $salon, $etat);
        if ($attente = Communaute::attenteModeLent($moi, $salon, $etat)) {
            return response()->json(['ok' => false, 'code' => 'mode_lent', 'attente' => $attente,
                'message' => 'Mode lent activé : vous pourrez écrire à nouveau dans '.self::duree($attente).'.'], 429);
        }
        if (! $moderateur && SalonMessage::where('user_id', $moi->id)->where('created_at', '>', now()->subMinute())->count() >= Communaute::MAX_PAR_MINUTE) {
            return response()->json(['ok' => false, 'message' => 'Vous envoyez beaucoup de messages : patientez une minute avant de continuer.'], 429);
        }

        $type = (string) $request->input('type');
        $body = trim((string) $request->input('body', ''));
        $body = $body === '' ? null : $body;

        if ($type === 'texte' && $body === null) {
            return response()->json(['ok' => false, 'message' => 'Écrivez votre message avant de l\'envoyer.'], 422);
        }
        if ($body !== null && ($mot = Communaute::motInterdit($body))) {
            return response()->json(['ok' => false, 'code' => 'mot_interdit', 'message' => "Votre message contient un terme non autorisé dans la Communauté (« {$mot} »). Reformulez-le."], 422);
        }
        if ($type === 'texte' && SalonMessage::where('salon_id', $salon->id)->where('user_id', $moi->id)
            ->where('created_at', '>', now()->subMinute())->where('body', $body)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Vous venez d\'envoyer ce même message.'], 422);
        }

        $piece = [];
        if ($type !== 'texte') {
            $chemin = (string) $request->input('fichier');
            $mime = strtolower((string) $request->input('fichier_mime'));
            $taille = (int) $request->input('fichier_taille');
            if ($chemin === '' || ! str_starts_with($chemin, 'communaute/') || str_contains($chemin, '..')) {
                return response()->json(['ok' => false, 'message' => 'Le fichier envoyé est introuvable. Réessayez.'], 422);
            }
            if ($taille > Communaute::TAILLE_MAX_FICHIER) {
                return response()->json(['ok' => false, 'message' => 'Ce fichier dépasse 10 Mo.'], 422);
            }
            if (in_array($type, ['image', 'fichier'], true) && ! Communaute::peutEnvoyerFichier($moi)) {
                return response()->json(['ok' => false, 'code' => 'subscription_required', 'message' => 'L\'envoi de photos et de documents est réservé aux membres à jour de leur abonnement annuel.'], 402);
            }
            $mimesAutorises = match ($type) {
                'image' => self::MIMES_IMAGES,
                'fichier' => self::MIMES_DOCUMENTS,
                default => self::MIMES_VOCAUX,
            };
            if (! in_array(explode(';', $mime)[0], $mimesAutorises, true)) {
                return response()->json(['ok' => false, 'message' => match ($type) {
                    'image' => 'Seules les photos JPG, PNG, WebP ou GIF sont acceptées.',
                    'fichier' => 'Seuls les documents PDF sont acceptés.',
                    default => 'Format de note vocale non reconnu.',
                }], 422);
            }
            $duree = $type === 'vocal' ? (int) $request->input('duree', 0) : null;
            if ($type === 'vocal' && ($duree < 1 || $duree > Communaute::DUREE_MAX_VOCAL)) {
                return response()->json(['ok' => false, 'message' => 'Une note vocale dure 3 minutes au maximum.'], 422);
            }
            $piece = [
                'fichier' => $chemin,
                'fichier_nom' => mb_substr(str_replace(['/', '\\', "\0"], '-', (string) $request->input('fichier_nom', 'fichier')), 0, 160),
                'fichier_mime' => explode(';', $mime)[0],
                'fichier_taille' => $taille ?: null,
                'duree' => $duree,
            ];
        }

        $reponseA = $request->filled('reponse_a_id')
            ? SalonMessage::where('salon_id', $salon->id)->whereNull('supprime_at')->find((int) $request->input('reponse_a_id'))
            : null;

        // Mentions : membres actifs uniquement, et @tous réservé à l'équipe.
        $mentions = array_values(array_unique(array_map('intval', (array) $request->input('mentions', []))));
        $mentions = $mentions ? User::whereIn('id', $mentions)->where('is_active', true)->where('id', '!=', $moi->id)->pluck('id')->all() : [];
        $mentionTous = $request->boolean('mention_tous') && $moderateur;

        $message = DB::transaction(function () use ($salon, $moi, $type, $body, $reponseA, $mentions, $mentionTous, $piece, $etat) {
            $m = SalonMessage::create([
                'salon_id' => $salon->id,
                'user_id' => $moi->id,
                'type' => $type,
                'body' => $body,
                'reponse_a_id' => $reponseA?->id,
                'mentions' => $mentions ?: null,
                'mention_tous' => $mentionTous,
            ] + $piece);
            $etat->forceFill(['dernier_message_at' => now(), 'lu_jusqua' => $m->id])->save();

            return $m;
        });

        dispatch(fn () => Communaute::notifier($message->fresh(['salon', 'user', 'reponseA'])))->afterResponse();

        $message->load(['user:id,prenom,nom,photo,role', 'reponseA.user:id,prenom,nom']);
        $referents = SalonMembre::where('salon_id', $salon->id)->where('role', 'moderateur')->pluck('user_id')->all();

        return response()->json(['ok' => true, 'message' => Communaute::presenter($message, $moi, $moderateur, [], $referents)]);
    }

    /** PUT /communaute/messages/{id} — modifier son message texte (15 minutes). */
    public function modifier(Request $request, int $id)
    {
        $moi = $request->user();
        $m = SalonMessage::with('salon')->find($id);
        if (! $m || $m->estSupprime() || $m->user_id !== $moi->id || $m->type !== 'texte') {
            return response()->json(['ok' => false, 'message' => 'Ce message ne peut pas être modifié.'], 404);
        }
        if ($m->created_at->lt(now()->subMinutes(SalonMessage::MINUTES_MODIFICATION))) {
            return response()->json(['ok' => false, 'message' => 'Un message ne peut être modifié que dans les '.SalonMessage::MINUTES_MODIFICATION.' minutes qui suivent son envoi.'], 422);
        }
        $body = trim((string) $request->input('body', ''));
        if ($body === '' || mb_strlen($body) > 4000) {
            return response()->json(['ok' => false, 'message' => 'Le message doit contenir entre 1 et 4 000 caractères.'], 422);
        }
        if ($mot = Communaute::motInterdit($body)) {
            return response()->json(['ok' => false, 'code' => 'mot_interdit', 'message' => "Votre message contient un terme non autorisé dans la Communauté (« {$mot} »). Reformulez-le."], 422);
        }
        $m->forceFill(['body' => $body, 'edite_at' => now()])->save();

        return response()->json(['ok' => true]);
    }

    /** DELETE /communaute/messages/{id} — supprimer son message (ou en tant que modérateur). */
    public function supprimer(Request $request, int $id)
    {
        $moi = $request->user();
        $m = SalonMessage::with('salon')->find($id);
        if (! $m || $m->estSupprime()) {
            return response()->json(['ok' => false, 'message' => 'Message introuvable.'], 404);
        }
        $moderateur = Communaute::estModerateur($moi, $m->salon);
        if ($m->user_id !== $moi->id && ! $moderateur) {
            return response()->json(['ok' => false, 'message' => 'Vous ne pouvez supprimer que vos propres messages.'], 403);
        }
        $m->forceFill(['supprime_at' => now(), 'supprime_par' => $moi->id])->save();
        if ($m->user_id !== $moi->id) {
            Communaute::journal($m->salon, $moi, 'message_supprime', Communaute::extrait($m), $m->user_id, $m->id);
            SalonSignalement::where('salon_message_id', $m->id)->where('statut', 'nouveau')
                ->update(['statut' => 'traite', 'decision' => 'supprime', 'traite_par' => $moi->id, 'traite_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    /** POST /communaute/messages/{id}/reactions — ajoute ou retire une réaction. */
    public function reagir(Request $request, int $id)
    {
        $moi = $request->user();
        $m = SalonMessage::with('salon')->find($id);
        if (! $m || $m->estSupprime() || ! $m->salon) {
            return response()->json(['ok' => false, 'message' => 'Message introuvable.'], 404);
        }
        $etat = SalonMembre::where('salon_id', $m->salon_id)->where('user_id', $moi->id)->first();
        if ($raison = Communaute::refusAcces($moi, $m->salon, $etat)) {
            return response()->json(['ok' => false, 'message' => $raison], 403);
        }
        $emoji = (string) $request->input('emoji');
        if (! in_array($emoji, SalonReaction::EMOJIS, true)) {
            return response()->json(['ok' => false, 'message' => 'Réaction non disponible.'], 422);
        }
        $existante = SalonReaction::where('salon_message_id', $m->id)->where('user_id', $moi->id)->where('emoji', $emoji)->first();
        $existante ? $existante->delete() : SalonReaction::create(['salon_message_id' => $m->id, 'user_id' => $moi->id, 'emoji' => $emoji]);
        $m->touch(); // le fil des autres membres se met à jour

        return response()->json(['ok' => true, 'reactions' => $this->reactions([$m->id], $moi->id)[$m->id] ?? []]);
    }

    /** POST /communaute/messages/{id}/signaler — signaler un message à l'équipe. */
    public function signaler(Request $request, int $id)
    {
        $moi = $request->user();
        $m = SalonMessage::with('salon')->find($id);
        if (! $m || $m->estSupprime() || $m->user_id === $moi->id) {
            return response()->json(['ok' => false, 'message' => 'Ce message ne peut pas être signalé.'], 404);
        }
        $etat = SalonMembre::where('salon_id', $m->salon_id)->where('user_id', $moi->id)->first();
        if ($raison = Communaute::refusAcces($moi, $m->salon, $etat)) {
            return response()->json(['ok' => false, 'message' => $raison], 403);
        }
        $motif = trim((string) $request->input('motif', ''));
        SalonSignalement::updateOrCreate(
            ['salon_message_id' => $m->id, 'user_id' => $moi->id],
            ['motif' => $motif !== '' ? mb_substr($motif, 0, 500) : null, 'statut' => 'nouveau', 'decision' => null, 'traite_par' => null, 'traite_at' => null],
        );

        return response()->json(['ok' => true, 'message' => 'Merci, le message a été signalé à l\'équipe de modération.']);
    }

    /** GET /communaute/messages/{id}/fichier — pièce jointe (contrôle d'accès avant service par le site). */
    public function fichier(Request $request, int $id)
    {
        $moi = $request->user();
        $m = SalonMessage::with('salon')->find($id);
        if (! $m || ! $m->fichier || ! $m->salon) {
            return response()->json(['ok' => false, 'message' => 'Fichier introuvable.'], 404);
        }
        $admin = Communaute::estAdmin($moi);
        if ($m->estSupprime() && ! $admin) {
            return response()->json(['ok' => false, 'message' => 'Ce message a été supprimé.'], 404);
        }
        $etat = SalonMembre::where('salon_id', $m->salon_id)->where('user_id', $moi->id)->first();
        if (! $admin && ($raison = Communaute::refusAcces($moi, $m->salon, $etat))) {
            return response()->json(['ok' => false, 'message' => $raison], 403);
        }

        return response()->json(['ok' => true, 'fichier' => [
            'chemin' => $m->fichier,
            'nom' => $m->fichier_nom,
            'mime' => $m->fichier_mime,
        ]]);
    }

    /** GET /communaute/membres?q= — suggestions pour les mentions @Prénom. */
    public function membres(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $membres = User::query()->where('is_active', true)->whereNull('anonymise_at')->where('id', '!=', $request->user()->id)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('prenom', 'like', $q.'%')->orWhere('nom', 'like', $q.'%')
                ->orWhereRaw((DB::connection()->getDriverName() === 'sqlite' ? "(prenom || ' ' || nom)" : "CONCAT(prenom, ' ', nom)").' like ?', [$q.'%'])))
            ->orderBy('prenom')->limit(8)->get(['id', 'prenom', 'nom', 'photo', 'role']);

        return response()->json(['ok' => true, 'membres' => $membres->map(fn ($u) => Communaute::auteur($u))->values()]);
    }

    // ── Outils des référents (et administrateurs) dans le salon ──────────

    /** PUT /communaute/salons/{slug}/reglages — lecture seule et mode lent. */
    public function reglages(Request $request, string $slug)
    {
        $moi = $request->user();
        [$salon, $etat, $refus] = $this->ouvrir($moi, $slug);
        if ($refus) {
            return $refus;
        }
        if (! Communaute::estModerateur($moi, $salon, $etat)) {
            return response()->json(['ok' => false, 'message' => 'Réservé aux référents du salon.'], 403);
        }
        if ($request->has('lecture_seule')) {
            $salon->lecture_seule = $request->boolean('lecture_seule');
            Communaute::journal($salon, $moi, 'lecture_seule', $salon->lecture_seule ? 'Activée' : 'Désactivée');
        }
        if ($request->has('mode_lent_secondes')) {
            $secondes = (int) $request->input('mode_lent_secondes');
            if (! array_key_exists($secondes, Salon::MODES_LENTS)) {
                return response()->json(['ok' => false, 'message' => 'Durée de mode lent non disponible.'], 422);
            }
            $salon->mode_lent_secondes = $secondes;
            Communaute::journal($salon, $moi, 'mode_lent', Salon::MODES_LENTS[$secondes]);
        }
        $salon->save();

        return response()->json(['ok' => true]);
    }

    /** POST /communaute/salons/{slug}/membres/{userId}/{action} — silence, levée, exclusion, réintégration. */
    public function sanctionner(Request $request, string $slug, int $userId, string $action)
    {
        $moi = $request->user();
        [$salon, $etat, $refus] = $this->ouvrir($moi, $slug);
        if ($refus) {
            return $refus;
        }
        if (! Communaute::estModerateur($moi, $salon, $etat)) {
            return response()->json(['ok' => false, 'message' => 'Réservé aux référents du salon.'], 403);
        }

        return self::appliquerSanction($moi, $salon, $userId, $action, $request->input('duree'));
    }

    /**
     * Silence (24 h ou 7 jours), levée du silence, exclusion du salon ou
     * réintégration. Un référent ne peut pas sanctionner un autre référent ni
     * un administrateur.
     */
    public static function appliquerSanction(User $acteur, Salon $salon, int $userId, string $action, mixed $duree = null)
    {
        $cible = User::find($userId);
        if (! $cible || $cible->id === $acteur->id) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable.'], 404);
        }
        $etatCible = Communaute::etat($salon, $cible);
        if (! Communaute::estAdmin($acteur) && (Communaute::estAdmin($cible) || $etatCible->role === 'moderateur')) {
            return response()->json(['ok' => false, 'message' => 'Seul un administrateur peut sanctionner un référent ou un membre de l\'équipe.'], 403);
        }
        $nom = trim($cible->prenom.' '.$cible->nom);

        switch ($action) {
            case 'silence':
                $heures = match ((string) $duree) {
                    '1h' => 1,
                    '7j' => 24 * 7,
                    default => 24,
                };
                $etatCible->forceFill(['silence_jusqua' => now()->addHours($heures)])->save();
                Communaute::journal($salon, $acteur, 'silence', $nom.' · '.($heures === 1 ? '1 heure' : ($heures === 24 ? '24 heures' : '7 jours')), $cible->id);
                $texte = $nom.' ne pourra pas écrire dans ce salon pendant '.($heures === 1 ? '1 heure' : ($heures === 24 ? '24 heures' : '7 jours')).'.';
                break;
            case 'lever':
                $etatCible->forceFill(['silence_jusqua' => null])->save();
                Communaute::journal($salon, $acteur, 'silence_leve', $nom, $cible->id);
                $texte = $nom.' peut de nouveau écrire.';
                break;
            case 'exclure':
                $etatCible->forceFill(['exclu_at' => now(), 'role' => 'membre'])->save();
                Communaute::journal($salon, $acteur, 'exclusion', $nom, $cible->id);
                $texte = $nom.' a été retiré du salon.';
                break;
            case 'reintegrer':
                $etatCible->forceFill(['exclu_at' => null, 'silence_jusqua' => null])->save();
                Communaute::journal($salon, $acteur, 'reintegration', $nom, $cible->id);
                $texte = $nom.' a été réintégré dans le salon.';
                break;
            default:
                return response()->json(['ok' => false, 'message' => 'Action inconnue.'], 422);
        }

        return response()->json(['ok' => true, 'message' => $texte]);
    }

    // ── Outils internes ──────────────────────────────────────────────────

    /** @return array{0: ?Salon, 1: ?SalonMembre, 2: ?\Illuminate\Http\JsonResponse} */
    private function ouvrir(User $moi, string $slug): array
    {
        if ($refus = $this->refusCompte($moi)) {
            return [null, null, $refus];
        }
        $salon = Salon::where('slug', $slug)->first();
        if (! $salon || ($salon->archive_at && ! Communaute::estAdmin($moi))) {
            return [null, null, response()->json(['ok' => false, 'message' => 'Ce salon est introuvable.'], 404)];
        }
        $etat = Communaute::etat($salon, $moi);
        if ($raison = Communaute::refusAcces($moi, $salon, $etat)) {
            $code = $salon->acces === 'abonnes' && ! $moi->hasActiveSubscription() ? 'subscription_required' : 'acces_refuse';

            return [$salon, $etat, response()->json(['ok' => false, 'code' => $code, 'message' => $raison], $code === 'subscription_required' ? 402 : 403)];
        }

        return [$salon, $etat, null];
    }

    private function refusCompte(User $moi)
    {
        return (! $moi->is_active || $moi->anonymise_at)
            ? response()->json(['ok' => false, 'message' => 'Votre compte ne permet pas d\'accéder à la Communauté.'], 403)
            : null;
    }

    /** Réactions groupées par message : [id => [[emoji, nombre, moi], ...]]. */
    private function reactions(array $ids, int $moi): array
    {
        if (! $ids) {
            return [];
        }
        $resultat = [];
        SalonReaction::whereIn('salon_message_id', $ids)->get()->groupBy('salon_message_id')
            ->each(function ($liste, $id) use (&$resultat, $moi) {
                $resultat[$id] = $liste->groupBy('emoji')->map(fn ($r, $emoji) => [
                    'emoji' => $emoji,
                    'nombre' => $r->count(),
                    'moi' => $r->contains('user_id', $moi),
                ])->sortBy(fn ($x) => array_search($x['emoji'], SalonReaction::EMOJIS, true))->values()->all();
            });

        return $resultat;
    }

    private static function duree(int $secondes): string
    {
        return $secondes >= 60 ? ceil($secondes / 60).' min' : $secondes.' s';
    }
}
