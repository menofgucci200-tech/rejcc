<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\Message;
use App\Models\User;
use App\Models\MessageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * Messagerie privée entre membres : une conversation par interlocuteur.
 */
class MessageController extends Controller
{
    /** Messages envoyés au plus par minute. */
    public const MAX_PAR_MINUTE = 20;

    /** Nouvelles conversations démarrées au plus en 24 h. */
    public const MAX_NOUVELLES_PAR_JOUR = 20;

    /** Message renvoyé à un non-abonné qui veut démarrer une conversation. */
    private const RESERVE_ABONNES = "Démarrer une conversation est réservé aux membres abonnés. Vous pouvez lire et répondre aux messages que l'on vous adresse.";

    /** L'interlocuteur m'a-t-il déjà écrit ? (condition pour un non-abonné) */
    private function aEcritA(int $auteur, int $destinataire): bool
    {
        return Message::where('sender_id', $auteur)->where('recipient_id', $destinataire)->exists();
    }

    private function refusNonAbonne()
    {
        return response()->json(['ok' => false, 'code' => 'subscription_required', 'message' => self::RESERVE_ABONNES], 402);
    }

    private function bloque(int $auteur, int $cible): bool
    {
        return DB::table('message_blocks')->where('user_id', $auteur)->where('blocked_id', $cible)->exists();
    }

    /** Informations affichées sur l'interlocuteur (liste et en-tête du fil). */
    private function interlocuteur(User $u): array
    {
        return [
            'id' => $u->id,
            'prenom' => $u->prenom,
            'nom' => $u->nom,
            'photo' => $u->photo,
            'role' => $u->role,
            'role_label' => $u->roleLabel(),
            'titre' => $u->titre,
            'ville' => $u->ville,
            'actif' => (bool) $u->is_active,
        ];
    }

    /** GET /messages?q= — conversations, la plus récente d'abord. */
    public function conversations(Request $request)
    {
        $me = $request->user()->id;

        $lignes = Message::query()
            ->selectRaw('CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END as autre, MAX(id) as dernier', [$me])
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->groupBy('autre')
            ->orderByDesc('dernier')
            ->get();

        $derniers = Message::whereIn('id', $lignes->pluck('dernier'))->get()->keyBy('id');
        $nonLus = Message::where('recipient_id', $me)->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as n')->groupBy('sender_id')->pluck('n', 'sender_id');
        $users = User::whereIn('id', $lignes->pluck('autre'))->get()->keyBy('id');

        // Un non-abonné ne voit que les conversations qu'on lui a adressées.
        $abonne = $request->user()->hasActiveSubscription();
        $mEcrivent = $abonne ? null : Message::where('recipient_id', $me)->distinct()->pluck('sender_id')->flip();

        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $voirArchives = $request->boolean('archives');
        $archives = DB::table('conversation_archives')->where('user_id', $me)->pluck('archived_at', 'other_id');
        $bloques = DB::table('message_blocks')->where('user_id', $me)->pluck('blocked_id')->flip();

        $out = [];
        $nbArchives = 0;
        foreach ($lignes as $l) {
            $u = $users[$l->autre] ?? null;
            $m = $derniers[$l->dernier] ?? null;
            if (! $u || ! $m) {
                continue;
            }
            if ($mEcrivent !== null && ! isset($mEcrivent[$u->id])) {
                continue;
            }
            // Archivée tant qu'aucun message n'est arrivé depuis l'archivage.
            $archivee = isset($archives[$u->id]) && $m->created_at <= \Illuminate\Support\Carbon::parse($archives[$u->id]);
            $nbArchives += $archivee ? 1 : 0;
            if ($archivee !== $voirArchives) {
                continue;
            }
            if ($q !== '' && ! str_contains(mb_strtolower($u->prenom.' '.$u->nom), $q)) {
                continue;
            }
            $out[] = $this->interlocuteur($u) + [
                'archivee' => $archivee,
                'bloque' => isset($bloques[$u->id]),
                'user_id' => $u->id,
                'last' => $m->body,
                'last_moi' => $m->sender_id === $me,
                'last_vu' => $m->sender_id === $me && $m->read_at !== null,
                'at' => $m->created_at?->toIso8601String(),
                'unread' => (int) ($nonLus[$u->id] ?? 0),
            ];
        }

        return response()->json(['ok' => true, 'conversations' => $out, 'archives' => $nbArchives, 'restreint' => ! $abonne]);
    }

    /**
     * GET /messages/{userId}?after= — fil de discussion (ou seulement les
     * messages postérieurs à `after`), et marque les messages reçus comme lus.
     */
    public function thread(Request $request, int $userId)
    {
        $me = $request->user()->id;
        $partner = User::find($userId);
        if (! $partner || $partner->id === $me) {
            return response()->json(['ok' => false, 'message' => 'Ce membre est introuvable.'], 404);
        }
        if (! $request->user()->hasActiveSubscription() && ! $this->aEcritA($userId, $me)) {
            return $this->refusNonAbonne();
        }

        Message::where('sender_id', $userId)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);
        // La notification de cette conversation est lue elle aussi.
        MemberNotification::where('user_id', $me)->where('type', 'message')
            ->where('link', self::lien($userId))->whereNull('read_at')->update(['read_at' => now()]);
        // Fil ouvert : pas de notification pour les messages qui arrivent pendant la lecture.
        Cache::put(self::cleFilOuvert($me, $userId), true, now()->addSeconds(15));

        $query = Message::where(fn ($q) => $q->where(fn ($w) => $w->where('sender_id', $me)->where('recipient_id', $userId))
            ->orWhere(fn ($w) => $w->where('sender_id', $userId)->where('recipient_id', $me)));

        $after = (int) $request->query('after', 0);
        $messages = (clone $query)->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->with('listing:id,title,photo,price,type,statut', 'project:id,title,image,statut')
            ->orderBy('id')->get(['id', 'sender_id', 'recipient_id', 'body', 'listing_id', 'project_id', 'created_at', 'read_at'])
            ->map(fn (Message $m) => $m->only(['id', 'sender_id', 'recipient_id', 'body', 'created_at', 'read_at']) + [
                'annonce' => $m->listing ? $m->listing->only(['id', 'title', 'photo', 'price', 'type', 'statut']) : null,
                'projet' => $m->project ? $m->project->only(['id', 'title', 'image', 'statut']) : null,
            ]);

        return response()->json([
            'ok' => true,
            'me' => $me,
            'partner' => $this->interlocuteur($partner),
            'messages' => $messages,
            // Dernier de mes messages lu par l'interlocuteur (« Vu »).
            'vu_jusqua' => (int) (clone $query)->where('sender_id', $me)->whereNotNull('read_at')->max('id'),
            'peut_ecrire' => $partner->is_active && ! $this->bloque($me, $partner->id) && ! $this->bloque($partner->id, $me),
            'bloque' => $this->bloque($me, $partner->id),
            'archivee' => DB::table('conversation_archives')->where('user_id', $me)->where('other_id', $partner->id)->exists(),
            'signalee' => MessageReport::where('reporter_id', $me)->where('reported_id', $partner->id)->where('statut', 'nouveau')->exists(),
        ]);
    }

    /** POST /messages — envoyer un message. */
    public function send(Request $request)
    {
        $me = $request->user();

        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|integer',
            'body' => 'required|string|max:2000',
            'listing_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
        ], [
            'body.required' => 'Écrivez votre message avant de l\'envoyer.',
            'body.max' => 'Votre message est trop long (2000 caractères maximum).',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $body = trim((string) $request->input('body'));
        if ($body === '') {
            return response()->json(['ok' => false, 'message' => 'Écrivez votre message avant de l\'envoyer.'], 422);
        }

        $destinataire = User::find((int) $request->input('recipient_id'));
        if (! $destinataire || $destinataire->id === $me->id) {
            return response()->json(['ok' => false, 'message' => 'Ce destinataire est introuvable.'], 422);
        }
        if (! $destinataire->is_active) {
            return response()->json(['ok' => false, 'message' => "Ce compte est suspendu : il ne peut pas recevoir de messages."], 422);
        }

        if (! $me->hasActiveSubscription() && ! $this->aEcritA($destinataire->id, $me->id)) {
            return $this->refusNonAbonne();
        }
        if ($this->bloque($me->id, $destinataire->id)) {
            return response()->json(['ok' => false, 'message' => 'Vous avez bloqué ce membre. Débloquez-le pour lui écrire.'], 422);
        }
        if ($this->bloque($destinataire->id, $me->id)) {
            return response()->json(['ok' => false, 'message' => 'Ce membre ne reçoit plus vos messages.'], 403);
        }

        // Anti-spam : rythme d'envoi et nombre de nouvelles conversations par jour.
        if (Message::where('sender_id', $me->id)->where('created_at', '>', now()->subMinute())->count() >= self::MAX_PAR_MINUTE) {
            return response()->json(['ok' => false, 'message' => 'Vous envoyez beaucoup de messages : patientez une minute avant de continuer.'], 429);
        }
        $dejaEchange = Message::where(fn ($q) => $q->where('sender_id', $me->id)->where('recipient_id', $destinataire->id))
            ->orWhere(fn ($q) => $q->where('sender_id', $destinataire->id)->where('recipient_id', $me->id))->exists();
        if (! $dejaEchange) {
            $nouvelles = Message::where('sender_id', $me->id)->where('created_at', '>', now()->subDay())
                ->whereNotExists(fn ($q) => $q->from('messages as avant')
                    ->whereColumn('avant.id', '<', 'messages.id')
                    ->where(fn ($w) => $w->where(fn ($x) => $x->whereColumn('avant.sender_id', 'messages.sender_id')->whereColumn('avant.recipient_id', 'messages.recipient_id'))
                        ->orWhere(fn ($x) => $x->whereColumn('avant.sender_id', 'messages.recipient_id')->whereColumn('avant.recipient_id', 'messages.sender_id'))))
                ->count();
            if ($nouvelles >= self::MAX_NOUVELLES_PAR_JOUR) {
                return response()->json(['ok' => false, 'message' => 'Vous avez démarré '.self::MAX_NOUVELLES_PAR_JOUR.' nouvelles conversations en 24 h : réessayez demain.'], 429);
            }
        }

        // Message « À propos de » une annonce en ligne du destinataire.
        $annonce = $request->filled('listing_id')
            ? \App\Models\MarketplaceListing::where('statut', 'approuve')->where('user_id', $destinataire->id)->find((int) $request->input('listing_id'))
            : null;
        if ($annonce && ! Message::where('sender_id', $me->id)->where('listing_id', $annonce->id)->exists()) {
            $annonce->increment('contacts'); // un contact par membre intéressé
        }

        // Message « À propos » d'un projet validé porté par le destinataire (ou son équipe).
        $projet = $request->filled('project_id')
            ? \App\Models\Project::where('statut', 'valide')->find((int) $request->input('project_id'))
            : null;
        if ($projet && ! $projet->estDeLEquipe($destinataire)) {
            $projet = null;
        }

        $message = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $destinataire->id,
            'body' => $body,
            'listing_id' => $annonce?->id,
            'project_id' => $projet?->id,
        ]);

        $this->notifier($me, $destinataire, $body, $annonce?->title, $projet?->title);

        return response()->json(['ok' => true, 'message' => $message->only(['id', 'sender_id', 'recipient_id', 'body', 'listing_id', 'project_id', 'created_at', 'read_at'])]);
    }

    private static function lien(int $autreId): string
    {
        return "/espace-membre/messagerie?to={$autreId}";
    }

    private static function cleFilOuvert(int $lecteur, int $autre): string
    {
        return "messagerie:fil-ouvert:{$lecteur}:{$autre}";
    }

    /**
     * Une seule notification par conversation non lue : elle est mise à jour
     * (extrait du dernier message, nombre de messages non lus) au lieu d'en
     * créer une par message. Aucune si le destinataire a le fil sous les yeux.
     */
    private function notifier(User $expediteur, User $destinataire, string $body, ?string $annonce = null, ?string $projet = null): void
    {
        if (Cache::has(self::cleFilOuvert($destinataire->id, $expediteur->id))) {
            return;
        }

        $nonLus = Message::where('sender_id', $expediteur->id)->where('recipient_id', $destinataire->id)->whereNull('read_at')->count();
        $extrait = Str::limit(preg_replace('/\s+/', ' ', $body), 90);
        $donnees = [
            'title' => 'Message de '.trim($expediteur->prenom.' '.$expediteur->nom),
            'body' => ($annonce ? "À propos de votre annonce « {$annonce} » : " : '').($projet ? "À propos du projet « {$projet} » : " : '')."« {$extrait} »".($nonLus > 1 ? " · {$nonLus} messages non lus" : ''),
        ];

        $notif = MemberNotification::where('user_id', $destinataire->id)->where('type', 'message')
            ->where('link', self::lien($expediteur->id))->whereNull('read_at')->latest('id')->first();

        if ($notif) {
            $notif->forceFill($donnees + ['created_at' => now()])->save();
        } else {
            MemberNotification::create($donnees + [
                'user_id' => $destinataire->id,
                'type' => 'message',
                'link' => self::lien($expediteur->id),
            ]);
        }
    }

    private function interlocuteurValide(Request $request, int $id): ?User
    {
        $u = User::find($id);

        return $u && $u->id !== $request->user()->id ? $u : null;
    }

    /** POST /messages/{id}/bloquer — le membre ne peut plus vous écrire (il le constatera s'il essaie). */
    public function bloquer(Request $request, int $id)
    {
        if (! $this->interlocuteurValide($request, $id)) {
            return response()->json(['ok' => false, 'message' => 'Ce membre est introuvable.'], 404);
        }
        DB::table('message_blocks')->updateOrInsert(
            ['user_id' => $request->user()->id, 'blocked_id' => $id],
            ['created_at' => now(), 'updated_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    /** DELETE /messages/{id}/bloquer */
    public function debloquer(Request $request, int $id)
    {
        DB::table('message_blocks')->where('user_id', $request->user()->id)->where('blocked_id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /messages/{id}/archiver — masque la conversation de sa liste jusqu'au prochain message. */
    public function archiver(Request $request, int $id)
    {
        if (! $this->interlocuteurValide($request, $id)) {
            return response()->json(['ok' => false, 'message' => 'Ce membre est introuvable.'], 404);
        }
        DB::table('conversation_archives')->updateOrInsert(
            ['user_id' => $request->user()->id, 'other_id' => $id],
            ['archived_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    /** DELETE /messages/{id}/archiver */
    public function desarchiver(Request $request, int $id)
    {
        DB::table('conversation_archives')->where('user_id', $request->user()->id)->where('other_id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * POST /messages/{id}/signaler — signale la conversation à
     * l'administration, qui pourra alors la consulter.
     */
    public function signaler(Request $request, int $id)
    {
        $me = $request->user();
        if (! $this->interlocuteurValide($request, $id)) {
            return response()->json(['ok' => false, 'message' => 'Ce membre est introuvable.'], 404);
        }
        $aRecu = Message::where('sender_id', $id)->where('recipient_id', $me->id)->exists();
        if (! $aRecu) {
            return response()->json(['ok' => false, 'message' => "Ce membre ne vous a pas encore écrit : il n'y a rien à signaler."], 422);
        }

        MessageReport::updateOrCreate(
            ['reporter_id' => $me->id, 'reported_id' => $id, 'statut' => 'nouveau'],
            ['motif' => mb_substr(trim((string) $request->input('motif')), 0, 500) ?: null],
        );
        if ($request->boolean('bloquer')) {
            $this->bloquer($request, $id);
        }

        return response()->json(['ok' => true, 'message' => "Merci, la conversation a été signalée à l'administration."]);
    }
}
