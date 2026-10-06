<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmation d'inscription d'un invité (formulaire public), avec son billet. */
class InscriptionEvenementConfirmee extends Mailable
{
    public function __construct(
        public EventRegistration $inscription,
        public Event $event,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — Confirmation de votre inscription : '.$this->event->title);
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $date = $this->event->starts_at->locale('fr')->translatedFormat('l j F Y \à H\hi');
        $lien = rtrim(config('app.frontend_url'), '/').'/billet/'.$this->inscription->billet;

        $details = "<p><strong>📅 Date :</strong> {$date}</p>";
        if ($this->event->en_ligne) {
            $details .= '<p><strong>💻 En ligne :</strong> le lien de connexion vous sera communiqué avant le début.</p>';
        } elseif ($this->event->location) {
            $details .= "<p><strong>📍 Lieu :</strong> {$e($this->event->location)}</p>";
        }

        return new Content(htmlString: "
            <p>Bonjour {$e($this->inscription->prenom)},</p>
            <p>Votre inscription à <strong>{$e($this->event->title)}</strong> est bien confirmée.
            Nous avons hâte de vous accueillir !</p>
            {$details}
            <p><strong>🎫 Votre billet :</strong> {$this->inscription->billet}<br>
            Présentez son QR code à l'entrée : <a href=\"{$lien}\">{$lien}</a></p>
            <p>En cas d'empêchement, prévenez-nous afin de libérer votre place.</p>
            <p>À très bientôt,<br>L'équipe REJCC</p>
        ");
    }
}
