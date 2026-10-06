<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Information envoyée aux invités d'un événement (sans compte membre) :
 * message de l'équipe, rappel, changement de date ou annulation. Les membres
 * la reçoivent en notification sur la plateforme.
 */
class InfoEvenement extends Mailable
{
    public function __construct(
        public EventRegistration $inscription,
        public Event $event,
        public string $titre,
        public string $texte,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — '.$this->titre);
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $lien = rtrim(config('app.frontend_url'), '/').'/billet/'.$this->inscription->billet;
        $texte = nl2br($e($this->texte));

        return new Content(htmlString: "
            <p>Bonjour {$e($this->inscription->prenom)},</p>
            <p>À propos de <strong>{$e($this->event->title)}</strong> :</p>
            <p>{$texte}</p>
            <p>Votre billet : <a href=\"{$lien}\">{$lien}</a></p>
            <p>L'équipe REJCC</p>
        ");
    }
}
