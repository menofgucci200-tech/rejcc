<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi d'email « best effort » : un problème SMTP ne doit jamais faire
 * échouer l'action métier (candidature, message de contact…).
 *
 * L'envoi est différé APRÈS la réponse HTTP (dispatchAfterResponse) : la
 * personne obtient une réponse instantanée (le temps du SMTP ne bloque plus
 * la requête), et le mail part juste après dans le même processus — sans
 * worker ni cron, ce qui convient à l'hébergement mutualisé. Utile lors des
 * pics d'inscriptions (ouverture à la communauté).
 */
class Mailer
{
    public static function send(?string $to, Mailable $mailable): void
    {
        if (! $to) {
            return;
        }

        dispatch(function () use ($to, $mailable) {
            try {
                Mail::to($to)->send($mailable);
            } catch (Throwable $e) {
                Log::warning('Envoi d\'email échoué vers '.$to.' : '.$e->getMessage());
            }
        })->afterResponse();
    }
}
