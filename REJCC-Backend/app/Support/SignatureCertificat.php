<?php

namespace App\Support;

use App\Models\Certificate;

/**
 * Signature numérique (Ed25519) des données d'un certificat, inscrite dans son
 * QR code. Un QR fabriqué ou recopié d'un autre certificat est détecté : la
 * signature ne correspond plus au titulaire, au titre ou à la date.
 *
 * La clé vient de CERTIFICATS_CLE_SIGNATURE (64 caractères hexadécimaux) ou,
 * à défaut, est dérivée de APP_KEY. La clé publique est publiée sur la page
 * de vérification pour un contrôle indépendant.
 */
class SignatureCertificat
{
    private static function paire(): string
    {
        $graine = config('services.certificats.cle_signature')
            ? hex2bin((string) config('services.certificats.cle_signature'))
            : hash('sha256', 'rejcc-certificats|'.config('app.key'), true);

        return sodium_crypto_sign_seed_keypair($graine);
    }

    public static function signer(Certificate $c): string
    {
        $sig = sodium_crypto_sign_detached($c->donneesSignees(), sodium_crypto_sign_secretkey(self::paire()));

        return rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    }

    public static function verifier(Certificate $c, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }
        $brut = base64_decode(strtr($signature, '-_', '+/'), true);
        if ($brut === false || strlen($brut) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($brut, $c->donneesSignees(), sodium_crypto_sign_publickey(self::paire()));
    }

    public static function clePublique(): string
    {
        return bin2hex(sodium_crypto_sign_publickey(self::paire()));
    }
}
