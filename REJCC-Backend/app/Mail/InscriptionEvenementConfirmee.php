<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\MailLayout;
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

        $p = MailLayout::P;
        $ligne = fn ($label, $valeur) => "<tr><td style=\"padding:4px 14px 4px 0;color:#5B677A;font-size:13px;white-space:nowrap;vertical-align:top\">{$label}</td><td style=\"padding:4px 0;font-weight:700;color:#031D59\">{$valeur}</td></tr>";
        $details = $ligne('Date', $e(ucfirst($date)));
        if ($this->event->en_ligne) {
            $details .= $ligne('Lieu', 'En ligne : le lien de connexion vous sera communiqué avant le début');
        } elseif ($this->event->location) {
            $details .= $ligne('Lieu', $e($this->event->location));
        }
        $details .= $ligne('Billet', $e($this->inscription->billet));

        return new Content(htmlString: MailLayout::html('Votre inscription est confirmée',
            MailLayout::bonjour($this->inscription->prenom)."
<p style=\"{$p}\">Votre inscription à <strong>{$e($this->event->title)}</strong> est bien confirmée. Nous avons hâte de vous accueillir !</p>
<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 14px;background:#F6F8FB;border-left:3px solid #AC0100;border-radius:6px\"><tr><td style=\"padding:10px 16px\"><table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\">{$details}</table></td></tr></table>
<p style=\"{$p}\">Présentez le QR code de votre billet à l'entrée.</p>
<p style=\"margin:0;color:#5B677A;font-size:13px\">En cas d'empêchement, prévenez-nous afin de libérer votre place.</p>",
            'Voir mon billet', $lien,
            'Vous recevez cet e-mail suite à votre inscription à un événement du REJCC.'));
    }
}
