<?php

namespace App\Mail;

use App\Models\EventParticipant;
use App\Models\RegistrationEvent;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InscriptionEvenementConfirmee extends Mailable
{
    public function __construct(
        public EventParticipant $participant,
        public RegistrationEvent $event,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — Confirmation de votre inscription : '.$this->event->title);
    }

    public function content(): Content
    {
        $date = $this->event->starts_at
            ? $this->event->starts_at->locale('fr')->translatedFormat('l j F Y \à H\hi')
            : null;

        $details = '';
        if ($date) {
            $details .= "<p><strong>📅 Date :</strong> {$date}</p>";
        }
        if ($this->event->location) {
            $details .= "<p><strong>📍 Lieu :</strong> {$this->event->location}</p>";
        }

        return new Content(htmlString: "
            <p>Bonjour {$this->participant->prenom},</p>
            <p>Votre inscription à <strong>{$this->event->title}</strong> est bien confirmée.
            Nous avons hâte de vous accueillir !</p>
            {$details}
            <p>Pensez à noter la date. En cas d'empêchement, prévenez-nous afin de libérer votre place.</p>
            <p>À très bientôt,<br>L'équipe REJCC</p>
        ");
    }
}
