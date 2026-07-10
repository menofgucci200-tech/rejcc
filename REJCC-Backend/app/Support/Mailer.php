<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi d'email « best effort » : un problème SMTP ne doit jamais faire
 * échouer l'action métier (candidature, message de contact…).
 */
class Mailer
{
    public static function send(?string $to, Mailable $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Envoi d\'email échoué vers '.$to.' : '.$e->getMessage());
        }
    }
}
