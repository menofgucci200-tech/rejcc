<?php

namespace App\Support;

use App\Mail\MessageCompte;
use App\Models\MemberNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Notifications de l'espace membre relayées par e-mail (et sur le téléphone,
 * voir Push) selon les réglages du membre, par catégorie : tout de suite,
 * en résumé quotidien ou jamais. Un e-mail n'est envoyé que pour une
 * notification restée non lue : le membre connecté n'est pas dérangé.
 */
class Notifications
{
    /** clé => [libellé, détail, rythme e-mail par défaut] */
    public const CATEGORIES = [
        'messagerie' => ['Messages privés', 'Messages des membres, mentors, vendeurs et recruteurs', 'immediat'],
        'mentorat' => ['Mentorat', 'Demandes, réponses, séances et suivi avec votre mentor ou vos mentorés', 'immediat'],
        'evenements' => ['Événements', 'Nouveaux événements, changements de programme, attestations', 'quotidien'],
        'formations' => ['Formations & certificats', 'Formations, parcours, badges et certificats délivrés', 'quotidien'],
        'reseau' => ['Projets, emploi & marketplace', 'Candidatures, contributions, annonces, avis et groupes sectoriels', 'quotidien'],
        'compte' => ['Mon compte & annonces', 'Documents, carte membre et annonces de l\'équipe du REJCC', 'quotidien'],
    ];

    public const FREQUENCES = ['immediat' => 'Tout de suite', 'quotidien' => 'Résumé quotidien', 'jamais' => 'Jamais'];

    /** Pas d'e-mail pour une notification lue dans ce délai (membre en ligne). */
    public const DELAI_MINUTES = 10;

    /** Une même conversation ne renvoie pas d'e-mail avant ce délai. */
    public const RELANCE_HEURES = 6;

    public static function categorie(?string $link, ?string $type = null): string
    {
        if ($type === 'message') {
            return 'messagerie';
        }
        $section = explode('/', trim((string) parse_url((string) $link, PHP_URL_PATH), '/'))[1] ?? '';

        return match ($section) {
            'messagerie' => 'messagerie',
            'mentorat', 'mentors' => 'mentorat',
            'evenements' => 'evenements',
            'formations', 'catalogue', 'parcours', 'certificats' => 'formations',
            'projets', 'emplois', 'marketplace', 'groupes', 'annuaire' => 'reseau',
            default => 'compte',
        };
    }

    /** Réglages effectifs (valeurs par défaut pour les catégories jamais réglées). */
    public static function reglages(User $u): array
    {
        $p = $u->preferences ?? [];
        $email = [];
        $push = [];
        foreach (self::CATEGORIES as $cle => [, , $defaut]) {
            $f = $p['email'][$cle] ?? $defaut;
            $email[$cle] = isset(self::FREQUENCES[$f]) ? $f : $defaut;
            $push[$cle] = (bool) ($p['push'][$cle] ?? in_array($cle, ['messagerie', 'mentorat', 'evenements'], true));
        }
        $pause = $p['pause_emails'] ?? null;

        return [
            'email' => $email,
            'push' => $push,
            'pause_emails' => $pause && Carbon::parse($pause)->endOfDay()->isFuture() ? Carbon::parse($pause)->toDateString() : null,
        ];
    }

    public static function enPause(User $u): bool
    {
        return self::reglages($u)['pause_emails'] !== null;
    }

    private static function eligibles(Carbon $depuis)
    {
        return MemberNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<=', now()->subMinutes(self::DELAI_MINUTES))
            ->where('created_at', '>=', $depuis)
            ->where(fn ($q) => $q->whereNull('email_at')
                ->orWhere(fn ($w) => $w->whereColumn('email_at', '<', 'created_at')->where('email_at', '<', now()->subHours(self::RELANCE_HEURES))));
    }

    /** Tâche fréquente : e-mails « tout de suite ». */
    public static function envoyerImmediats(): int
    {
        return self::envoyer(self::eligibles(now()->subDays(3)), 'immediat');
    }

    /** Tâche quotidienne : résumé des nouveautés non lues des catégories « résumé quotidien ». */
    public static function envoyerResumes(): int
    {
        return self::envoyer(self::eligibles(now()->subHours(36)), 'quotidien');
    }

    private static function envoyer($requete, string $rythme): int
    {
        $n = 0;
        $requete->orderBy('created_at')->get()->groupBy('user_id')->each(function (Collection $notifs, $userId) use ($rythme, &$n) {
            $u = User::find($userId);
            if (! $u || ! $u->is_active || $u->anonymise_at || ! $u->email) {
                return;
            }
            $r = self::reglages($u);
            $retenues = $notifs->filter(fn ($x) => $r['email'][self::categorie($x->link, $x->type)] === $rythme)->values();
            if ($retenues->isEmpty() || $r['pause_emails']) {
                return;
            }
            Mailer::send($u->email, self::mail($u, $retenues, $rythme));
            MemberNotification::whereIn('id', $retenues->pluck('id'))->update(['email_at' => now()]);
            $n++;
        });

        return $n;
    }

    /**
     * Tâche à la minute : notifications récentes envoyées sur les appareils
     * (téléphone, ordinateur) où le membre a activé les notifications.
     */
    public static function envoyerPush(): int
    {
        $n = 0;
        MemberNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->where(fn ($q) => $q->whereNull('push_at')->orWhereColumn('push_at', '<', 'created_at'))
            ->whereIn('user_id', PushSubscription::select('user_id'))
            ->orderBy('created_at')->limit(500)->get()
            ->groupBy('user_id')->each(function (Collection $notifs, $userId) use (&$n) {
                $u = User::find($userId);
                MemberNotification::whereIn('id', $notifs->pluck('id'))->update(['push_at' => now()]);
                if (! $u || ! $u->is_active) {
                    return;
                }
                $r = self::reglages($u);
                $base = rtrim((string) config('app.frontend_url'), '/');
                foreach ($notifs->filter(fn ($x) => $r['push'][self::categorie($x->link, $x->type)])->take(5) as $x) {
                    foreach (PushSubscription::where('user_id', $u->id)->get() as $s) {
                        $ok = WebPush::envoyer($s, [
                            'titre' => $x->title,
                            'texte' => mb_strimwidth((string) $x->body, 0, 180, '…'),
                            'lien' => $base.($x->link && str_starts_with($x->link, '/') ? $x->link : '/espace-membre/notifications'),
                            'tag' => 'rejcc-'.$x->id,
                        ]);
                        $ok ? $n++ : $s->delete();
                    }
                }
            });

        return $n;
    }

    private static function mail(User $u, Collection $notifs, string $rythme): MessageCompte
    {
        $e = fn ($v) => e((string) $v);
        $base = rtrim((string) config('app.frontend_url'), '/');
        $url = fn ($x) => $base.($x->link && str_starts_with($x->link, '/') ? $x->link : '/espace-membre/notifications');
        $prenom = $u->prenom ?: 'cher membre';

        if ($notifs->count() === 1) {
            $x = $notifs->first();

            return new MessageCompte('REJCC — '.$x->title, MailLayout::html($x->title,
                "<p style=\"margin:0 0 10px\">Bonjour {$e($prenom)},</p><p style=\"margin:0\">{$e($x->body)}</p>",
                'Voir sur la plateforme', $url($x)));
        }

        $lignes = $notifs->take(15)->map(fn ($x) => "<tr><td style=\"padding:11px 0;border-top:1px solid #EDF0F5\">
            <a href=\"{$e($url($x))}\" style=\"color:#031D59;font-weight:700;text-decoration:none\">{$e($x->title)}</a>
            <div style=\"color:#5B677A;font-size:13px;margin-top:2px\">{$e(mb_strimwidth((string) $x->body, 0, 160, '…'))}</div></td></tr>")->join('');
        $plus = $notifs->count() > 15 ? '<p style="margin:10px 0 0;color:#5B677A;font-size:13px">… et '.($notifs->count() - 15).' autre(s) sur la plateforme.</p>' : '';
        $titre = $rythme === 'quotidien' ? 'Votre résumé du jour' : $notifs->count().' nouveautés vous attendent';

        return new MessageCompte('REJCC — '.$titre.' ('.$notifs->count().')', MailLayout::html($titre,
            "<p style=\"margin:0 0 6px\">Bonjour {$e($prenom)}, voici ce qui s'est passé pour vous sur la plateforme :</p>
            <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">{$lignes}</table>{$plus}",
            'Ouvrir mon espace membre', $base.'/espace-membre/notifications'));
    }
}
