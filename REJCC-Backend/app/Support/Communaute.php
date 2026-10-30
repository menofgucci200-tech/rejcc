<?php

namespace App\Support;

use App\Models\MemberNotification;
use App\Models\Salon;
use App\Models\SalonMembre;
use App\Models\SalonMessage;
use App\Models\SalonModeration;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Règles de la Communauté REJCC : accès aux salons, droit d'écrire,
 * modération, mots interdits et notifications.
 *
 * - Tous les membres actifs lisent et écrivent dans les salons ouverts à
 *   tous (texte, réactions, réponses, notes vocales).
 * - L'envoi de photos et de documents est réservé aux membres à jour de leur
 *   abonnement (les administrateurs et mentors en sont dispensés).
 * - Le canal Annonces et un salon en lecture seule ne reçoivent que les
 *   messages de l'équipe : administrateurs (rubrique « salons ») et référents.
 */
class Communaute
{
    /** Rubrique d'administration de la Communauté (middleware auth.admin:salons). */
    public const RUBRIQUE = 'salons';

    public const CLE_MOTS_INTERDITS = 'communaute.mots_interdits';

    /** Anti-spam : messages par minute et par membre (hors modérateurs). */
    public const MAX_PAR_MINUTE = 10;

    public const TAILLE_MAX_FICHIER = 10 * 1024 * 1024;

    public const DUREE_MAX_VOCAL = 180;

    /** Administrateur de la Communauté : tous les salons, toutes les actions. */
    public static function estAdmin(?User $u): bool
    {
        return $u !== null && $u->role === 'admin'
            && ($u->permissions === null || in_array(self::RUBRIQUE, (array) $u->permissions, true));
    }

    /** Administrateur ou référent du salon. */
    public static function estModerateur(?User $u, Salon $salon, ?SalonMembre $etat = null): bool
    {
        if (self::estAdmin($u)) {
            return true;
        }
        $etat ??= $u ? SalonMembre::where('salon_id', $salon->id)->where('user_id', $u->id)->first() : null;

        return $etat !== null && $etat->role === 'moderateur';
    }

    /** Raison pour laquelle le membre ne peut pas ouvrir ce salon (null s'il le peut). */
    public static function refusAcces(User $u, Salon $salon, ?SalonMembre $etat = null): ?string
    {
        if (! $u->is_active || $u->anonymise_at) {
            return 'Votre compte ne permet pas d\'accéder à la Communauté.';
        }
        if (self::estAdmin($u)) {
            return null;
        }
        if ($salon->archive_at) {
            return 'Ce salon est archivé.';
        }
        if ($etat?->exclu_at) {
            return 'Vous avez été retiré de ce salon par l\'équipe de modération.';
        }
        if ($salon->acces === 'abonnes' && ! $u->hasActiveSubscription()) {
            return 'Ce salon est réservé aux membres à jour de leur abonnement annuel.';
        }

        return null;
    }

    /** Raison pour laquelle le membre ne peut pas écrire maintenant (null s'il le peut). */
    public static function refusEcriture(User $u, Salon $salon, SalonMembre $etat): ?string
    {
        if ($refus = self::refusAcces($u, $salon, $etat)) {
            return $refus;
        }
        if ($salon->archive_at) {
            return 'Ce salon est archivé : il n\'accepte plus de messages.';
        }
        $moderateur = self::estModerateur($u, $salon, $etat);
        if ($salon->estAnnonces() && ! $moderateur) {
            return 'Seule l\'équipe du REJCC publie dans les Annonces. Vous pouvez réagir aux messages.';
        }
        if ($salon->lecture_seule && ! $moderateur) {
            return 'Ce salon est en lecture seule pour le moment.';
        }
        if ($etat->estRenduMuet()) {
            return 'Vous ne pouvez pas écrire dans ce salon avant le '.$etat->silence_jusqua->locale('fr')->isoFormat('D MMMM [à] HH[h]mm').'.';
        }
        if ($salon->regles && ! $etat->regles_acceptees_at && ! $moderateur) {
            return 'Acceptez les règles du salon pour y écrire.';
        }

        return null;
    }

    /** Délai restant avant de pouvoir écrire à nouveau (mode lent), en secondes. */
    public static function attenteModeLent(User $u, Salon $salon, SalonMembre $etat): int
    {
        if ($salon->mode_lent_secondes <= 0 || ! $etat->dernier_message_at || self::estModerateur($u, $salon, $etat)) {
            return 0;
        }

        return max(0, $salon->mode_lent_secondes - (int) $etat->dernier_message_at->diffInSeconds(now()));
    }

    public static function peutEnvoyerFichier(User $u): bool
    {
        return $u->hasActiveSubscription();
    }

    /**
     * État du membre dans le salon, créé à sa première visite : les messages
     * antérieurs à son arrivée ne comptent pas comme non lus.
     */
    public static function etat(Salon $salon, User $u): SalonMembre
    {
        return SalonMembre::firstOrCreate(
            ['salon_id' => $salon->id, 'user_id' => $u->id],
            ['lu_jusqua' => (int) $salon->messages()->max('id')],
        );
    }

    /** Salons visibles par le membre, dans l'ordre d'affichage. */
    public static function salonsVisibles(User $u)
    {
        $etats = SalonMembre::where('user_id', $u->id)->get()->keyBy('salon_id');

        return Salon::query()
            ->when(! self::estAdmin($u), fn ($q) => $q->whereNull('archive_at'))
            ->orderBy('ordre')->orderBy('id')->get()
            ->filter(fn (Salon $s) => self::refusAcces($u, $s, $etats->get($s->id)) === null || ($s->acces === 'abonnes' && ! $etats->get($s->id)?->exclu_at))
            ->values();
    }

    /** Nombre total de messages non lus dans la Communauté (pastille du menu). */
    public static function nonLus(User $u): int
    {
        $total = 0;
        $etats = SalonMembre::where('user_id', $u->id)->get()->keyBy('salon_id');
        foreach (self::salonsVisibles($u) as $s) {
            $etat = $etats->get($s->id);
            if (! $etat || self::refusAcces($u, $s, $etat)) {
                continue;
            }
            $total += SalonMessage::where('salon_id', $s->id)->where('id', '>', $etat->lu_jusqua)
                ->whereNull('supprime_at')->where('user_id', '!=', $u->id)->count();
        }

        return $total;
    }

    // ── Mots interdits ───────────────────────────────────────────────────

    /** @return list<string> */
    public static function motsInterdits(): array
    {
        $brut = (string) SiteSetting::where('key', self::CLE_MOTS_INTERDITS)->value('value');

        return array_values(array_unique(array_filter(array_map(
            fn ($m) => mb_strtolower(trim($m)),
            preg_split('/[\n,;]+/', $brut) ?: []
        ))));
    }

    public static function enregistrerMotsInterdits(string $texte): void
    {
        SiteSetting::updateOrCreate(['key' => self::CLE_MOTS_INTERDITS], ['value' => trim($texte)]);
    }

    /** Premier mot interdit trouvé dans le texte (comparaison sans accents ni majuscules). */
    public static function motInterdit(?string $texte): ?string
    {
        if ($texte === null || trim($texte) === '') {
            return null;
        }
        $normal = ' '.Str::lower(Str::ascii($texte)).' ';
        foreach (self::motsInterdits() as $mot) {
            $m = Str::lower(Str::ascii($mot));
            if ($m !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($m, '/').'(?![\p{L}\p{N}])/u', $normal)) {
                return $mot;
            }
        }

        return null;
    }

    // ── Journal ──────────────────────────────────────────────────────────

    public static function journal(?Salon $salon, ?User $acteur, string $action, ?string $detail = null, ?int $cibleId = null, ?int $messageId = null): void
    {
        SalonModeration::create([
            'salon_id' => $salon?->id,
            'acteur_id' => $acteur?->id,
            'cible_id' => $cibleId,
            'salon_message_id' => $messageId,
            'action' => $action,
            'detail' => $detail ? Str::limit($detail, 490) : null,
        ]);
    }

    // ── Présence et notifications ────────────────────────────────────────

    public static function clePresence(int $userId, int $salonId): string
    {
        return "communaute:ouvert:{$userId}:{$salonId}";
    }

    /** Le membre a ce salon sous les yeux (rafraîchi par l'écran toutes les quelques secondes). */
    public static function marquerPresent(int $userId, int $salonId): void
    {
        Cache::put(self::clePresence($userId, $salonId), true, now()->addSeconds(20));
    }

    public static function lien(Salon $salon): string
    {
        return '/espace-membre/communaute?salon='.$salon->slug;
    }

    /**
     * Notifie les membres concernés par un nouveau message, avec une seule
     * notification par salon et par membre (mise à jour à chaque message) :
     *  - Annonces : tous les membres, sauf ceux qui ont mis le salon en sourdine ;
     *  - autres salons : les membres mentionnés, l'auteur du message cité, ceux
     *    qui suivent « tous les messages » ; @tous (équipe) prévient tout le monde.
     * Personne n'est notifié pour un salon qu'il a sous les yeux.
     */
    public static function notifier(SalonMessage $m): void
    {
        $salon = $m->salon;
        $auteur = $m->user;
        if (! $salon || ! $auteur) {
            return;
        }
        $nomAuteur = trim($auteur->prenom.' '.$auteur->nom);
        $mentionnes = array_map('intval', (array) $m->mentions);
        $citeA = $m->reponseA?->user_id;
        $etats = SalonMembre::where('salon_id', $salon->id)->get()->keyBy('user_id');
        $niveau = fn (int $userId) => $etats->get($userId)?->notifications ?? $salon->notificationsParDefaut();

        if ($salon->estAnnonces() || $m->mention_tous) {
            $destinataires = User::query()->where('is_active', true)->whereNull('anonymise_at')->where('id', '!=', $auteur->id)->pluck('id')->all();
        } else {
            $suivent = $etats->filter(fn (SalonMembre $e) => $e->notifications === 'tout')->keys()->all();
            $destinataires = array_unique(array_merge($mentionnes, $citeA ? [$citeA] : [], $suivent));
        }

        $extrait = self::extrait($m);
        foreach ($destinataires as $userId) {
            $userId = (int) $userId;
            if ($userId === $auteur->id || $niveau($userId) === 'rien' || $etats->get($userId)?->exclu_at) {
                continue;
            }
            if (Cache::has(self::clePresence($userId, $salon->id))) {
                continue;
            }
            $personnel = in_array($userId, $mentionnes, true) || $userId === $citeA;
            if (! $salon->estAnnonces() && ! $m->mention_tous && ! $personnel && $niveau($userId) !== 'tout') {
                continue;
            }
            if ($salon->acces === 'abonnes') {
                $u = User::find($userId);
                if (! $u || self::refusAcces($u, $salon, $etats->get($userId))) {
                    continue;
                }
            }

            $titre = match (true) {
                in_array($userId, $mentionnes, true) => $nomAuteur.' vous a mentionné dans « '.$salon->nom.' »',
                $userId === $citeA => $nomAuteur.' vous a répondu dans « '.$salon->nom.' »',
                $salon->estAnnonces() => 'Nouvelle annonce du REJCC',
                default => 'Nouveau message dans « '.$salon->nom.' »',
            };
            $corps = ($salon->estAnnonces() ? '' : $nomAuteur.' : ').$extrait;

            $notif = MemberNotification::where('user_id', $userId)->where('type', 'communaute')
                ->where('link', self::lien($salon))->whereNull('read_at')->latest('id')->first();
            if ($notif) {
                // Une mention reste visible : un message ordinaire ne l'écrase pas.
                $garderTitre = ! $personnel && str_contains((string) $notif->title, 'vous a ');
                $notif->forceFill([
                    'title' => $garderTitre ? $notif->title : $titre,
                    'body' => $corps,
                    'created_at' => now(),
                ])->save();
            } else {
                MemberNotification::create([
                    'user_id' => $userId,
                    'type' => 'communaute',
                    'title' => $titre,
                    'body' => $corps,
                    'link' => self::lien($salon),
                ]);
            }
        }
    }

    public static function extrait(SalonMessage $m): string
    {
        return match ($m->type) {
            'image' => '📷 Photo'.($m->body ? ' · '.Str::limit(preg_replace('/\s+/', ' ', $m->body), 80) : ''),
            'fichier' => '📄 '.($m->fichier_nom ?: 'Document'),
            'vocal' => '🎤 Note vocale',
            'sondage' => '📊 Sondage : '.Str::limit((string) ($m->sondage?->question ?? ''), 90),
            'carte' => '🔗 '.(CommunauteCartes::TYPES[$m->carte_type] ?? 'Partage').($m->body ? ' · '.Str::limit(preg_replace('/\s+/', ' ', $m->body), 70) : ''),
            default => Str::limit(preg_replace('/\s+/', ' ', (string) $m->body), 100),
        };
    }

    /** Sondage tel qu'affiché : résultats, votes du membre, droits. */
    public static function sondage(\App\Models\SalonSondage $s, User $moi, bool $moderateur, ?int $auteurId): array
    {
        $votes = \App\Models\SalonSondageVote::with('user:id,prenom,nom')->where('salon_sondage_id', $s->id)->get();
        $votants = $votes->pluck('user_id')->unique()->count();
        $clos = $s->estClos();

        return [
            'id' => $s->id,
            'question' => $s->question,
            'choix_multiple' => $s->choix_multiple,
            'anonyme' => $s->anonyme,
            'clos' => $clos,
            'fin_at' => $s->fin_at?->toIso8601String(),
            'votants' => $votants,
            'a_vote' => $votes->contains('user_id', $moi->id),
            'peut_clore' => ! $clos && ($moderateur || $auteurId === $moi->id),
            'options' => $s->options->map(function ($o) use ($votes, $votants, $moi, $s) {
                $pour = $votes->where('salon_sondage_option_id', $o->id);

                return [
                    'id' => $o->id,
                    'libelle' => $o->libelle,
                    'votes' => $pour->count(),
                    'pourcentage' => $votants ? (int) round($pour->count() * 100 / $votants) : 0,
                    'moi' => $pour->contains('user_id', $moi->id),
                    'votants' => $s->anonyme ? [] : $pour->take(6)->map(fn ($v) => $v->user?->prenom)->filter()->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    // ── Présentation ─────────────────────────────────────────────────────

    public static function auteur(?User $u): ?array
    {
        if (! $u) {
            return null;
        }

        return [
            'id' => $u->id,
            'prenom' => $u->prenom,
            'nom' => $u->nom,
            'photo' => $u->photo,
            'role' => $u->role,
        ];
    }

    /** Message tel qu'affiché dans le fil. */
    public static function presenter(SalonMessage $m, User $moi, bool $moderateur, array $reactions = [], array $referents = []): array
    {
        $supprime = $m->estSupprime();
        $cite = $m->reponseA;

        return [
            'id' => $m->id,
            'type' => $supprime ? 'supprime' : $m->type,
            'body' => $supprime ? null : $m->body,
            'date' => $m->created_at?->toIso8601String(),
            'edite' => ! $supprime && $m->edite_at !== null,
            'supprime_par_equipe' => $supprime && $m->supprime_par && $m->supprime_par !== $m->user_id,
            'auteur' => self::auteur($m->user) + ['referent' => in_array($m->user_id, $referents, true)],
            'moi' => $m->user_id === $moi->id,
            'mentions' => $supprime ? [] : array_map('intval', (array) $m->mentions),
            'mention_tous' => ! $supprime && $m->mention_tous,
            'fichier' => (! $supprime && $m->fichier) ? [
                'nom' => $m->fichier_nom,
                'mime' => $m->fichier_mime,
                'taille' => $m->fichier_taille,
                'duree' => $m->duree,
            ] : null,
            'sondage' => (! $supprime && $m->type === 'sondage' && $m->sondage) ? self::sondage($m->sondage, $moi, $moderateur, $m->user_id) : null,
            'carte' => (! $supprime && $m->type === 'carte') ? (CommunauteCartes::carte($m->carte_type, $m->carte_id) ?? ['indisponible' => true, 'type_libelle' => CommunauteCartes::TYPES[$m->carte_type] ?? 'Partage']) : null,
            'epingle' => ! $supprime && $m->epingle_at !== null,
            'reponse_a' => $cite ? [
                'id' => $cite->id,
                'auteur' => $cite->user ? trim($cite->user->prenom.' '.$cite->user->nom) : 'Membre',
                'extrait' => $cite->estSupprime() ? 'Message supprimé' : self::extrait($cite),
            ] : null,
            'reactions' => $reactions,
            'peut_modifier' => ! $supprime && $m->user_id === $moi->id && $m->type === 'texte'
                && $m->created_at?->gt(now()->subMinutes(SalonMessage::MINUTES_MODIFICATION)),
            'peut_supprimer' => ! $supprime && ($m->user_id === $moi->id || $moderateur),
            'peut_signaler' => ! $supprime && $m->user_id !== $moi->id,
            'peut_epingler' => ! $supprime && $moderateur,
        ];
    }
}
