<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NouveauMessageContact extends Mailable
{
    public function __construct(public Contact $contact)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Nouveau message de contact : {$this->contact->sujet}");
    }

    public function content(): Content
    {
        $message = nl2br(e($this->contact->message));

        return new Content(htmlString: "
            <p><strong>De :</strong> {$this->contact->nom} ({$this->contact->email})</p>
            <p><strong>Sujet :</strong> {$this->contact->sujet}</p>
            <p>{$message}</p>
            <p><a href=\"https://rejcc.site/admin/contacts\">Traiter dans l'admin</a></p>
        ");
    }
}
