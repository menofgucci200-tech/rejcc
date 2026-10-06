<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MessageCompte;
use App\Models\AccountEvent;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\NewsletterSubscriber;
use App\Models\PersonalDocument;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Client;
use App\Support\Journal;
use App\Support\Mailer;
use App\Support\MailLayout;
use App\Support\Notifications;
use App\Support\WebPush;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Paramètres du compte d'un membre : notifications, appareils connectés,
 * mot de passe, adresse e-mail, export des données, clôture du compte et
 * journal du compte.
 */
class CompteController extends Controller
{
    /** Jours entre la demande de clôture et l'anonymisation définitive. */
    public const DELAI_CLOTURE_JOURS = 30;

    private static function alerte(User $u, string $sujet, string $titre, string $texte, ?string $email = null): void
    {
        $e = fn ($v) => e((string) $v);
        $base = rtrim((string) config('app.frontend_url'), '/');
        Mailer::send($email ?? $u->email, new MessageCompte('REJCC — '.$sujet, MailLayout::html($titre,
            "<p style=\"margin:0 0 10px\">Bonjour {$e($u->prenom)},</p><p style=\"margin:0 0 10px\">{$texte}</p>"
            .'<p style="margin:0;color:#5B677A;font-size:13px">Ce n\'est pas vous ? Réinitialisez immédiatement votre mot de passe depuis la page de connexion et prévenez l\'équipe du REJCC.</p>',
            'Ouvrir mes paramètres', $base.'/espace-membre/profil?onglet=securite',
            'E-mail de sécurité envoyé automatiquement : il ne peut pas être désactivé.')));
    }

    // ── Notifications ───────────────────────────────────────────────────

    public function notifications(Request $request)
    {
        return response()->json(['ok' => true, 'reglages' => Notifications::reglages($request->user()),
            'appareils_push' => PushSubscription::where('user_id', $request->user()->id)->count(), 'categories' => collect(Notifications::CATEGORIES)
                ->map(fn ($c, $k) => ['cle' => $k, 'libelle' => $c[0], 'detail' => $c[1]])->values(), 'frequences' => Notifications::FREQUENCES]);
    }

    public function enregistrerNotifications(Request $request)
    {
        $cats = implode(',', array_keys(Notifications::CATEGORIES));
        $v = Validator::make($request->all(), [
            'email' => 'sometimes|array',
            'email.*' => 'in:'.implode(',', array_keys(Notifications::FREQUENCES)),
            'push' => 'sometimes|array',
            'push.*' => 'boolean',
            'pause_emails' => 'nullable|date|after_or_equal:today|before:'.now()->addMonths(6)->toDateString(),
        ], ['pause_emails.before' => 'La pause ne peut pas dépasser 6 mois.', 'pause_emails.after_or_equal' => 'Choisissez une date à venir.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $u = $request->user();
        $p = $u->preferences ?? [];
        $actuel = Notifications::reglages($u);
        foreach (['email', 'push'] as $canal) {
            if ($request->has($canal)) {
                $p[$canal] = array_merge($actuel[$canal], array_intersect_key((array) $request->input($canal), Notifications::CATEGORIES));
            }
        }
        if ($request->exists('pause_emails')) {
            $p['pause_emails'] = $request->input('pause_emails') ?: null;
        }
        $u->preferences = $p;
        $u->save();
        Journal::noter($u, 'notifications', null, $request);

        return response()->json(['ok' => true, 'reglages' => Notifications::reglages($u)]);
    }

    // ── Appareils connectés ─────────────────────────────────────────────

    public function appareils(Request $request)
    {
        $courant = $request->attributes->get('api_token_id');

        return response()->json(['ok' => true, 'appareils' => ApiToken::where('user_id', $request->user()->id)
            ->orderByDesc('last_used_at')->get()->map(fn (ApiToken $t) => [
                'id' => $t->id,
                'appareil' => Client::appareil($t->agent),
                'ip' => $t->ip,
                'connecte_le' => $t->created_at?->toIso8601String(),
                'actif_le' => ($t->last_used_at ?? $t->created_at)?->toIso8601String(),
                'courant' => $t->id === $courant,
            ])->values()]);
    }

    public function deconnecterAppareil(Request $request, int $id)
    {
        $t = ApiToken::where('user_id', $request->user()->id)->find($id);
        if (! $t || $t->id === $request->attributes->get('api_token_id')) {
            return response()->json(['ok' => false, 'message' => 'Appareil introuvable.'], 404);
        }
        $nom = Client::appareil($t->agent);
        $t->delete();
        Journal::noter($request->user(), 'deconnexion_appareil', $nom, $request);

        return response()->json(['ok' => true, 'message' => "« {$nom} » a été déconnecté."]);
    }

    public function deconnecterAutres(Request $request)
    {
        $n = ApiToken::where('user_id', $request->user()->id)->where('id', '!=', $request->attributes->get('api_token_id'))->delete();
        if ($n) {
            Journal::noter($request->user(), 'deconnexion_appareil', $n.' autre(s) appareil(s)', $request);
        }

        return response()->json(['ok' => true, 'message' => $n ? "{$n} appareil(s) déconnecté(s)." : 'Aucun autre appareil n\'était connecté.']);
    }

    // ── Mot de passe ────────────────────────────────────────────────────

    /** Règle commune : 8 caractères minimum, au moins une lettre et un chiffre. */
    public static function regleMotDePasse(): array
    {
        return ['required', 'string', 'min:8', 'max:100', 'confirmed', 'regex:/[A-Za-zÀ-ÿ]/', 'regex:/[0-9]/'];
    }

    public const MESSAGES_MOT_DE_PASSE = [
        'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password.regex' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
        'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        'password.required' => 'Choisissez un nouveau mot de passe.',
    ];

    public function motDePasse(Request $request)
    {
        $u = $request->user();
        $v = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => self::regleMotDePasse(),
            'deconnecter_autres' => 'sometimes|boolean',
        ], self::MESSAGES_MOT_DE_PASSE + ['current_password.required' => 'Saisissez votre mot de passe actuel.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first(), 'champ' => $v->errors()->keys()[0]], 422);
        }
        if (! Hash::check($request->current_password, $u->password)) {
            return response()->json(['ok' => false, 'message' => 'Mot de passe actuel incorrect.', 'champ' => 'current_password'], 422);
        }
        if (Hash::check($request->password, $u->password)) {
            return response()->json(['ok' => false, 'message' => 'Le nouveau mot de passe doit être différent de l\'actuel.', 'champ' => 'password'], 422);
        }

        $u->password = $request->password;
        $u->save();
        $n = 0;
        if ($request->boolean('deconnecter_autres', true)) {
            $n = ApiToken::where('user_id', $u->id)->where('id', '!=', $request->attributes->get('api_token_id'))->delete();
        }
        Journal::noter($u, 'mot_de_passe', $n ? $n.' autre(s) appareil(s) déconnecté(s)' : null, $request);
        self::alerte($u, 'Votre mot de passe a été modifié', 'Mot de passe modifié',
            'Le mot de passe de votre compte REJCC vient d\'être modifié depuis '.e(Client::appareil(Client::agent($request))).', le '.now()->locale('fr')->isoFormat('D MMMM YYYY [à] HH[h]mm').'.');

        return response()->json(['ok' => true, 'deconnectes' => $n]);
    }

    // ── Adresse e-mail ──────────────────────────────────────────────────

    public function demanderEmail(Request $request)
    {
        $u = $request->user();
        $v = Validator::make($request->all(), [
            'email' => 'required|email:rfc|max:150',
            'password' => 'required|string',
        ], ['email.email' => 'Saisissez une adresse e-mail valide.', 'password.required' => 'Confirmez avec votre mot de passe.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $email = mb_strtolower(trim($request->email));
        if (! Hash::check($request->password, $u->password)) {
            return response()->json(['ok' => false, 'message' => 'Mot de passe incorrect.'], 422);
        }
        if ($email === mb_strtolower($u->email)) {
            return response()->json(['ok' => false, 'message' => 'C\'est déjà votre adresse actuelle.'], 422);
        }
        if (User::where('email', $email)->where('id', '!=', $u->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Cette adresse est déjà utilisée par un autre compte.'], 422);
        }

        $jeton = Str::random(48);
        $u->forceFill(['email_nouveau' => $email, 'email_jeton' => hash('sha256', $jeton), 'email_jeton_at' => now()])->save();
        $lien = rtrim((string) config('app.frontend_url'), '/').'/confirmer-email/'.$jeton;
        $e = fn ($x) => e((string) $x);
        Mailer::send($email, new MessageCompte('REJCC — Confirmez votre nouvelle adresse e-mail', MailLayout::html('Confirmez votre nouvelle adresse',
            "<p style=\"margin:0 0 10px\">Bonjour {$e($u->prenom)},</p><p style=\"margin:0\">Vous avez demandé à utiliser cette adresse pour votre compte REJCC. Confirmez-la en cliquant sur le bouton ci-dessous (lien valable 48 heures). Votre adresse actuelle reste active d'ici là.</p>",
            'Confirmer cette adresse', $lien, 'Vous n\'êtes pas à l\'origine de cette demande ? Ignorez simplement cet e-mail.')));
        self::alerte($u, 'Changement d\'adresse e-mail demandé', 'Changement d\'adresse demandé',
            'Une demande de changement de l\'adresse e-mail de votre compte vers <strong>'.$e($email).'</strong> a été faite. Elle ne prendra effet qu\'après confirmation depuis cette nouvelle adresse.');
        Journal::noter($u, 'email_demande', $email, $request);

        return response()->json(['ok' => true, 'message' => "Un lien de confirmation a été envoyé à {$email}. Votre adresse actuelle reste active en attendant."]);
    }

    public function annulerEmail(Request $request)
    {
        $request->user()->forceFill(['email_nouveau' => null, 'email_jeton' => null, 'email_jeton_at' => null])->save();

        return response()->json(['ok' => true]);
    }

    /** POST /auth/email/confirmer — public : le lien peut être ouvert sans être connecté. */
    public function confirmerEmail(Request $request)
    {
        $jeton = (string) $request->input('jeton');
        $u = strlen($jeton) >= 32 ? User::where('email_jeton', hash('sha256', $jeton))->first() : null;
        if (! $u || ! $u->email_nouveau || ! $u->email_jeton_at || $u->email_jeton_at->lt(now()->subHours(48))) {
            return response()->json(['ok' => false, 'message' => 'Ce lien n\'est plus valable. Refaites la demande depuis vos paramètres.'], 422);
        }
        if (User::where('email', $u->email_nouveau)->where('id', '!=', $u->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Cette adresse est désormais utilisée par un autre compte.'], 422);
        }
        $ancienne = $u->email;
        $u->forceFill(['email' => $u->email_nouveau, 'email_nouveau' => null, 'email_jeton' => null, 'email_jeton_at' => null])->save();
        NewsletterSubscriber::where('email', $ancienne)->update(['email' => $u->email]);
        Journal::noter($u, 'email', $ancienne.' → '.$u->email, $request);
        self::alerte($u, 'Votre adresse e-mail a été modifiée', 'Adresse e-mail modifiée',
            'L\'adresse de connexion de votre compte REJCC est désormais <strong>'.e($u->email).'</strong>. Cette adresse-ci ('.e($ancienne).') ne recevra plus nos e-mails.', $ancienne);

        return response()->json(['ok' => true, 'email' => $u->email, 'message' => 'Votre nouvelle adresse est confirmée : utilisez-la désormais pour vous connecter.']);
    }

    // ── Mes données ─────────────────────────────────────────────────────

    /** Lignes d'une table liées au membre, sans les colonnes techniques ou secrètes. */
    private static function lignes(string $table, array $cles, int $id, array $exclues = []): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        $exclues = array_merge(['password', 'token', 'remember_token', 'email_jeton', 'fichier', 'endpoint', 'p256dh', 'auth'], $exclues);

        return DB::table($table)->where(fn ($q) => collect($cles)->each(fn ($c) => $q->orWhere($c, $id)))
            ->when(Schema::hasColumn($table, 'id'), fn ($q) => $q->orderBy('id'))->get()->map(fn ($r) => array_diff_key((array) $r, array_flip($exclues)))->all();
    }

    public function export(Request $request)
    {
        $u = $request->user();
        $id = $u->id;
        $profil = array_diff_key($u->toArray(), array_flip(['password', 'remember_token', 'email_jeton', 'permissions']));
        $donnees = [
            'avertissement' => 'Export des données personnelles de votre compte REJCC (Réseau Entrepreneurial des Jeunes Chrétiens Catholiques), conformément à la loi ivoirienne n° 2013-450 relative à la protection des données à caractère personnel.',
            'exporte_le' => now()->toIso8601String(),
            'profil' => $profil + ['numero_membre' => $u->memberNumber()],
            'candidature_adhesion' => self::lignes('membership_applications', ['user_id'], $id),
            'abonnement_paiements' => self::lignes('payments', ['user_id', 'beneficiaire_id'], $id, ['recu_fichier']),
            'formations' => self::lignes('formation_enrollments', ['user_id'], $id),
            'badges_parcours' => self::lignes('path_badges', ['user_id'], $id),
            'certificats' => self::lignes('certificates', ['user_id'], $id, ['empreinte', 'signature']),
            'evenements' => self::lignes('event_registrations', ['user_id'], $id),
            'groupes_sectoriels' => self::lignes('group_user', ['user_id'], $id),
            'messages_groupes' => self::lignes('group_messages', ['user_id'], $id),
            'messages_prives' => self::lignes('messages', ['sender_id', 'recipient_id'], $id),
            'mentorat' => self::lignes('mentorships', ['mentor_id', 'mentore_id'], $id),
            'candidature_mentor' => self::lignes('mentor_applications', ['user_id'], $id),
            'projets' => self::lignes('projects', ['user_id'], $id),
            'equipes_projets' => self::lignes('project_members', ['user_id'], $id),
            'projets_suivis' => self::lignes('project_follows', ['user_id'], $id),
            'annonces_marketplace' => self::lignes('marketplace_listings', ['user_id'], $id),
            'offres_emploi_publiees' => self::lignes('opportunities', ['author_id'], $id),
            'candidatures_emploi' => self::lignes('opportunity_applications', ['user_id'], $id),
            'alertes_emploi' => self::lignes('job_alerts', ['user_id'], $id),
            'avis' => self::lignes('member_reviews', ['reviewer_id', 'reviewed_id'], $id),
            'documents_personnels' => self::lignes('personal_documents', ['user_id'], $id),
            'notifications' => self::lignes('member_notifications', ['user_id'], $id),
            'appareils_connectes' => self::lignes('api_tokens', ['user_id'], $id),
            'journal_du_compte' => self::lignes('account_events', ['user_id'], $id),
        ];
        Journal::noter($u, 'export', null, $request);

        return response()->json(['ok' => true, 'donnees' => $donnees]);
    }

    // ── Clôture du compte ───────────────────────────────────────────────

    public function cloturer(Request $request)
    {
        $u = $request->user();
        $v = Validator::make($request->all(), ['password' => 'required|string', 'motif' => 'nullable|string|max:500'],
            ['password.required' => 'Confirmez avec votre mot de passe.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        if (! Hash::check($request->password, $u->password)) {
            return response()->json(['ok' => false, 'message' => 'Mot de passe incorrect.'], 422);
        }
        if ($u->role === 'admin') {
            return response()->json(['ok' => false, 'message' => 'Un compte administrateur ne peut pas être clôturé ainsi : demandez à un autre administrateur de retirer vos droits.'], 422);
        }

        $date = now()->addDays(self::DELAI_CLOTURE_JOURS);
        $u->forceFill([
            'suppression_prevue_at' => $date, 'suppression_motif' => $request->input('motif') ?: null,
            'is_active' => false, 'piece_identite' => null, 'photo' => null,
        ])->save();
        // Le coffre-fort est vidé tout de suite (les fichiers chiffrés sont supprimés par le site).
        $fichiers = PersonalDocument::where('user_id', $u->id)->pluck('fichier')->filter()->values();
        PersonalDocument::where('user_id', $u->id)->delete();
        DB::table('push_subscriptions')->where('user_id', $u->id)->delete();
        ApiToken::where('user_id', $u->id)->delete();
        Journal::noter($u, 'cloture', 'Suppression prévue le '.$date->locale('fr')->isoFormat('D MMMM YYYY'), $request);
        AuditLog::create(['user_id' => $u->id, 'actor' => trim($u->prenom.' '.$u->nom).' ('.$u->email.')', 'action' => 'Clôture demandée',
            'target' => "Compte membre #{$u->id} — suppression le ".$date->toDateString().($u->suppression_motif ? ' — motif : '.$u->suppression_motif : ''),
            'method' => 'POST', 'path' => '/'.$request->path(), 'ip' => Client::ip($request)]);

        $dateTxt = $date->locale('fr')->isoFormat('D MMMM YYYY');
        Mailer::send($u->email, new MessageCompte('REJCC — Clôture de votre compte', MailLayout::html('Votre compte sera supprimé le '.$dateTxt,
            '<p style="margin:0 0 10px">Bonjour '.e($u->prenom).',</p><p style="margin:0 0 10px">Votre demande de clôture est bien enregistrée. Votre compte est désactivé dès maintenant et sera définitivement supprimé le <strong>'.$dateTxt.'</strong>.</p>'
            .'<p style="margin:0 0 10px">Vous changez d\'avis ? <strong>Reconnectez-vous simplement avant cette date</strong> : la clôture sera annulée.</p>'
            .'<p style="margin:0;color:#5B677A;font-size:13px">Conformément à nos obligations légales, l\'historique de vos paiements et le registre des certificats délivrés sont conservés.</p>',
            'Me reconnecter', rtrim((string) config('app.frontend_url'), '/').'/connexion', 'E-mail envoyé automatiquement suite à votre demande.')));
        if ($admin = config('mail.admin_email')) {
            Mailer::send($admin, new MessageCompte('REJCC — Clôture de compte demandée', MailLayout::html('Clôture de compte demandée',
                '<p style="margin:0">'.e(trim($u->prenom.' '.$u->nom)).' ('.e($u->email).') a demandé la clôture de son compte. Suppression prévue le '.$dateTxt.'.'
                .($u->suppression_motif ? '<br>Motif : « '.e($u->suppression_motif).' »' : '').'</p>', null, null, 'Notification interne.')));
        }

        return response()->json(['ok' => true, 'suppression_le' => $date->toDateString(), 'fichiers' => $fichiers]);
    }

    // ── Notifications sur les appareils (Web Push) ──────────────────────

    public function abonnerPush(Request $request)
    {
        $v = Validator::make($request->all(), [
            'endpoint' => 'required|url|max:500|starts_with:https://',
            'keys.p256dh' => 'required|string|max:200',
            'keys.auth' => 'required|string|max:100',
        ]);
        if ($v->fails() || strlen(WebPush::deb64($request->input('keys.p256dh'))) !== 65) {
            return response()->json(['ok' => false, 'message' => 'Abonnement aux notifications invalide.'], 422);
        }
        PushSubscription::updateOrCreate(['endpoint_hash' => hash('sha256', $request->endpoint)], [
            'user_id' => $request->user()->id, 'endpoint' => $request->endpoint,
            'p256dh' => $request->input('keys.p256dh'), 'auth' => $request->input('keys.auth'),
            'agent' => Client::agent($request),
        ]);

        return response()->json(['ok' => true, 'appareils' => PushSubscription::where('user_id', $request->user()->id)->count()]);
    }

    public function desabonnerPush(Request $request)
    {
        PushSubscription::where('user_id', $request->user()->id)
            ->when($request->filled('endpoint'), fn ($q) => $q->where('endpoint_hash', hash('sha256', (string) $request->endpoint)))->delete();

        return response()->json(['ok' => true]);
    }

    /** Notification d'essai sur les appareils du membre. */
    public function essaiPush(Request $request)
    {
        $abos = PushSubscription::where('user_id', $request->user()->id)->get();
        if ($abos->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Activez d\'abord les notifications sur cet appareil.'], 422);
        }
        foreach ($abos as $s) {
            WebPush::envoyer($s, ['titre' => 'Notifications activées ✓', 'texte' => 'Vous serez prévenu(e) ici des nouveautés du REJCC.',
                'lien' => rtrim((string) config('app.frontend_url'), '/').'/espace-membre', 'tag' => 'rejcc-essai']) || $s->delete();
        }

        return response()->json(['ok' => true, 'message' => 'Notification d\'essai envoyée.']);
    }

    // ── Journal ─────────────────────────────────────────────────────────

    public function journal(Request $request)
    {
        return response()->json(['ok' => true, 'evenements' => AccountEvent::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(60)->get()->map(fn (AccountEvent $e) => [
                'type' => $e->type,
                'libelle' => AccountEvent::TYPES[$e->type] ?? $e->type,
                'detail' => $e->detail,
                'appareil' => $e->agent ? Client::appareil($e->agent) : null,
                'ip' => $e->ip,
                'le' => $e->created_at?->toIso8601String(),
            ])->values()]);
    }
}
