<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rappel d'échéance de l'abonnement annuel. */
class AbonnementRappel extends Mailable
{
    public function __construct(public User $membre, public string $titre, public string $texte)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — '.$this->titre);
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $lien = rtrim((string) config('app.frontend_url'), '/').'/espace-membre/abonnement';

        return new Content(htmlString: "
            <p>Bonjour {$e($this->membre->prenom)},</p>
            <p>{$e($this->texte)}</p>
            <p><a href=\"{$lien}\">Renouveler mon abonnement</a> (paiement sécurisé : Wave, Orange Money, MTN, Moov ou carte).</p>
            <p>L'équipe REJCC</p>");
    }
}
