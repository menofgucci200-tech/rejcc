<?php

namespace App\Mail;

use App\Models\Event;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rappel envoyé une heure avant une session en ligne, avec le lien vers la session. */
class RappelSessionLive extends Mailable
{
    public function __construct(
        public ?string $prenom,
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

        return new Content(htmlString: MailLayout::html($this->titre,
            MailLayout::bonjour($this->prenom).'<p style="'.MailLayout::P.'">'.$e($this->texte).'</p>',
            'Ouvrir la session', $this->lien,
            'Vous recevez cet e-mail car vous êtes inscrit(e) à cette session du REJCC.'));
    }
}
