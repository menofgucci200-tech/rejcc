<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Formateur;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Message au formateur d'une session live : affectation, report ou
 * annulation, avec son lien personnel d'animateur.
 */
class SessionFormateur extends Mailable
{
    public function __construct(
        public Formateur $formateur,
        public Event $event,
        public string $titre,
        public string $texte,
        public string $lien,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — '.$this->titre);
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;

        return new Content(htmlString: MailLayout::html($this->titre,
            MailLayout::bonjour($this->formateur->prenom)."
<p style=\"{$p}\">{$e($this->texte)}</p>
<p style=\"{$p}\">Gardez ce lien : il est personnel et vous permet d'ouvrir la visio le jour de la session.</p>",
            'Ouvrir mon espace animateur', $this->lien,
            'Vous recevez cet e-mail car vous êtes formateur d\'une session live du REJCC.'));
    }
}
