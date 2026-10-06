<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-mail déjà mis en forme (MailLayout) : notifications, alertes de sécurité, confirmations. */
class MessageCompte extends Mailable
{
    public function __construct(public string $sujet, public string $contenu) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sujet);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->contenu);
    }
}
