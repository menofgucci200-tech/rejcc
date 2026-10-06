<?php

namespace App\Support;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Notifications sur le téléphone ou l'ordinateur (protocole Web Push),
 * sans bibliothèque externe : chiffrement du message (RFC 8291, aes128gcm)
 * et identification du serveur (VAPID, RFC 8292) avec OpenSSL seul, ce qui
 * fonctionne sur un hébergement mutualisé.
 */
class WebPush
{
    /** En-tête DER d'une clé publique EC P-256 (SubjectPublicKeyInfo) avant le point de 65 octets. */
    private const ENTETE_CLE_P256 = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public static function b64(string $b): string
    {
        return rtrim(strtr(base64_encode($b), '+/', '-_'), '=');
    }

    public static function deb64(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    // ── Clés VAPID du serveur ───────────────────────────────────────────

    /** @return array{prive: string, public: string} PEM privé + point public brut (65 octets) */
    public static function cles(): array
    {
        static $cles = null;
        if ($cles) {
            return $cles;
        }
        $pem = config('services.webpush.private_key');
        if (! $pem) {
            // Paire créée une fois puis conservée (à sauvegarder avec storage/app/private).
            $fichier = 'webpush/vapid.pem';
            if (! Storage::disk('local')->exists($fichier)) {
                $k = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
                openssl_pkey_export($k, $nouveau);
                Storage::disk('local')->put($fichier, $nouveau);
            }
            $pem = Storage::disk('local')->get($fichier);
        }
        $pem = str_contains($pem, 'BEGIN') ? $pem : base64_decode($pem);
        $cle = openssl_pkey_get_private($pem) ?: throw new RuntimeException('Clé VAPID invalide.');

        return $cles = ['prive' => $pem, 'public' => self::pointPublic($cle)];
    }

    public static function clePublique(): string
    {
        return self::b64(self::cles()['public']);
    }

    private static function pointPublic($cle): string
    {
        $d = openssl_pkey_get_details($cle)['ec'];

        return "\x04".str_pad($d['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    }

    private static function clePubliquePem(string $point): string
    {
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin(self::ENTETE_CLE_P256).$point), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    /** Jeton VAPID (JWT ES256) pour le service de notifications du navigateur. */
    public static function jetonVapid(string $endpoint): string
    {
        $u = parse_url($endpoint);
        $entete = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $corps = self::b64(json_encode([
            'aud' => $u['scheme'].'://'.$u['host'],
            'exp' => time() + 12 * 3600,
            'sub' => 'mailto:'.(config('mail.admin_email') ?: config('mail.from.address') ?: 'contact@rejcc.site'),
        ]));
        openssl_sign("{$entete}.{$corps}", $der, self::cles()['prive'], OPENSSL_ALGO_SHA256);

        return "{$entete}.{$corps}.".self::b64(self::derVersBrut($der));
    }

    /** Signature ECDSA DER → r‖s (2 × 32 octets) attendu par JWT. */
    private static function derVersBrut(string $der): string
    {
        $pos = 2;
        $lire = function () use ($der, &$pos) {
            $long = ord($der[$pos + 1]);
            $v = substr($der, $pos + 2, $long);
            $pos += 2 + $long;

            return str_pad(ltrim($v, "\0"), 32, "\0", STR_PAD_LEFT);
        };

        return $lire().$lire();
    }

    // ── Chiffrement du message (RFC 8291) ───────────────────────────────

    /**
     * @param  string  $p256dh  clé publique de l'abonné (65 octets)
     * @param  string  $auth  secret d'authentification de l'abonné (16 octets)
     * @param  array|null  $essai  [clé privée PEM éphémère, sel] imposés (tests)
     */
    public static function chiffrer(string $message, string $p256dh, string $auth, ?array $essai = null): string
    {
        $ephemere = $essai ? openssl_pkey_get_private($essai[0]) : openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $sel = $essai[1] ?? random_bytes(16);
        $asPublic = self::pointPublic($ephemere);

        $secret = openssl_pkey_derive(openssl_pkey_get_public(self::clePubliquePem($p256dh)), $ephemere, 32);
        if ($secret === false) {
            throw new RuntimeException('Clé de l\'abonné invalide.');
        }
        $ikm = hash_hkdf('sha256', $secret, 32, "WebPush: info\0".$p256dh.$asPublic, $auth);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sel);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sel);

        $chiffre = openssl_encrypt($message."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $sel.pack('N', 4096).chr(strlen($asPublic)).$asPublic.$chiffre.$tag;
    }

    // ── Envoi ───────────────────────────────────────────────────────────

    /** Envoie à un appareil ; false si l'abonnement n'existe plus (à supprimer). */
    public static function envoyer(PushSubscription $s, array $donnees): bool
    {
        try {
            $corps = self::chiffrer(json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::deb64($s->p256dh), self::deb64($s->auth));
            $r = Http::withHeaders([
                'Authorization' => 'vapid t='.self::jetonVapid($s->endpoint).', k='.self::clePublique(),
                'Content-Encoding' => 'aes128gcm',
                'TTL' => '86400',
                'Urgency' => 'normal',
            ])->withBody($corps, 'application/octet-stream')->timeout(10)->post($s->endpoint);
        } catch (Throwable $e) {
            Log::warning('Push non envoyé : '.$e->getMessage());

            return true;
        }
        if (in_array($r->status(), [404, 410], true)) {
            return false;
        }
        if ($r->successful()) {
            $s->forceFill(['last_success_at' => now()])->save();
        } else {
            Log::warning('Push refusé ('.$r->status().') : '.mb_substr($r->body(), 0, 200));
        }

        return true;
    }
}
