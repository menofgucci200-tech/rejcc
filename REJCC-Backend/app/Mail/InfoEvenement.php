<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\MailLayout;
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

        $p = MailLayout::P;

        return new Content(htmlString: MailLayout::html($this->titre,
            MailLayout::bonjour($this->inscription->prenom)."
<p style=\"{$p}\">À propos de <strong>{$e($this->event->title)}</strong> :</p>
<p style=\"margin:0\">{$texte}</p>",
            'Voir mon billet', $lien,
            'Vous recevez cet e-mail car vous êtes inscrit(e) à cet événement du REJCC.'));
    }
}
