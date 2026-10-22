<?php

namespace App\Support;

use App\Models\Event;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Réunions Google Meet des sessions live, créées par le compte Google du
 * REJCC (ex. contact.rejcc@gmail.com). Le compte est autorisé une fois depuis
 * le dashboard (OAuth : aucun mot de passe ne transite par la plateforme) ;
 * seul un jeton d'actualisation chiffré est gardé. La personne connectée à ce
 * compte pendant la session admet et gère les participants.
 */
class GoogleMeet
{
    private const CLE = 'google_meet';

    private const SCOPES = 'openid email https://www.googleapis.com/auth/calendar.events';

    public static function estConfigure(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /** @return array{email: ?string, connecte_le: ?string}|null */
    public static function compte(): ?array
    {
        $v = SiteSetting::where('key', self::CLE)->value('value');

        return is_array($v) && ! empty($v['refresh_token']) ? ['email' => $v['email'] ?? null, 'connecte_le' => $v['connecte_le'] ?? null] : null;
    }

    public static function estConnecte(): bool
    {
        return self::estConfigure() && self::compte() !== null;
    }

    /** Adresse de la page Google où le titulaire du compte autorise la plateforme. */
    public static function urlAutorisation(string $retour): string
    {
        $etat = Crypt::encryptString(json_encode(['retour' => $retour, 'expire' => now()->addMinutes(15)->timestamp]));

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $etat,
        ]);
    }

    /** Retour de Google : garde le jeton d'actualisation, renvoie l'adresse de retour du dashboard. */
    public static function terminerConnexion(string $code, string $etat): string
    {
        $e = json_decode(Crypt::decryptString($etat), true);
        if (! is_array($e) || ($e['expire'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('La demande de connexion a expiré : recommencez depuis le dashboard.');
        }

        $r = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful() || ! $r->json('refresh_token')) {
            throw new RuntimeException('Google n\'a pas accordé l\'accès au calendrier. Recommencez et acceptez toutes les autorisations.');
        }

        $email = Http::withToken($r->json('access_token'))->get('https://openidconnect.googleapis.com/v1/userinfo')->json('email');
        SiteSetting::updateOrCreate(['key' => self::CLE], ['value' => [
            'email' => $email,
            'refresh_token' => Crypt::encryptString($r->json('refresh_token')),
            'connecte_le' => now()->toIso8601String(),
        ]]);
        Cache::forget('google_meet_acces');

        return (string) ($e['retour'] ?? '');
    }

    public static function deconnecter(): void
    {
        $v = SiteSetting::where('key', self::CLE)->value('value');
        if (is_array($v) && ! empty($v['refresh_token'])) {
            rescue(fn () => Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => Crypt::decryptString($v['refresh_token'])]), report: false);
        }
        SiteSetting::where('key', self::CLE)->delete();
        Cache::forget('google_meet_acces');
    }

    private static function jetonAcces(): string
    {
        return Cache::remember('google_meet_acces', now()->addMinutes(50), function () {
            $v = SiteSetting::where('key', self::CLE)->value('value');
            if (! is_array($v) || empty($v['refresh_token'])) {
                throw new RuntimeException('Le compte Google du REJCC n\'est pas connecté.');
            }
            $r = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => Crypt::decryptString($v['refresh_token']),
                'grant_type' => 'refresh_token',
            ]);
            if (! $r->successful()) {
                throw new RuntimeException('Le compte Google ne répond plus : reconnectez-le depuis la page Formations live.');
            }

            return (string) $r->json('access_token');
        });
    }

    private static function corps(Event $e): array
    {
        return [
            'summary' => 'REJCC · '.$e->title,
            'description' => trim(($e->animateur ? "Animée par {$e->animateur}.\n" : '').'Session live du REJCC.'),
            'start' => ['dateTime' => $e->starts_at->toRfc3339String(), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => ($e->ends_at ?? $e->starts_at->copy()->addHour())->toRfc3339String(), 'timeZone' => config('app.timezone')],
        ];
    }

    /**
     * Crée la réunion (ou met à jour celle de la session) et renvoie son lien Meet.
     *
     * @throws RuntimeException compte non connecté ou refus de Google
     */
    public static function planifier(Event $e): string
    {
        $client = Http::withToken(self::jetonAcces())->acceptJson()->baseUrl('https://www.googleapis.com/calendar/v3/calendars/primary/');

        if ($e->google_event_id) {
            $r = $client->patch('events/'.rawurlencode($e->google_event_id).'?conferenceDataVersion=1', self::corps($e));
            if ($r->successful() && $r->json('hangoutLink')) {
                return (string) $r->json('hangoutLink');
            }
        }

        $r = $client->post('events?conferenceDataVersion=1', self::corps($e) + [
            'conferenceData' => ['createRequest' => [
                'requestId' => (string) Str::uuid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
            ]],
        ]);
        if (! $r->successful() || ! $r->json('hangoutLink')) {
            throw new RuntimeException('Google n\'a pas pu créer la réunion Meet. Réessayez, ou collez un lien Meet.');
        }
        $e->forceFill(['google_event_id' => $r->json('id')])->save();

        return (string) $r->json('hangoutLink');
    }

    /** Supprime la réunion de l'agenda Google (session annulée ou passée à un autre lien). */
    public static function supprimer(Event $e): void
    {
        if (! $e->google_event_id) {
            return;
        }
        rescue(fn () => Http::withToken(self::jetonAcces())->delete('https://www.googleapis.com/calendar/v3/calendars/primary/events/'.rawurlencode($e->google_event_id)), report: false);
        $e->forceFill(['google_event_id' => null])->save();
    }
}
