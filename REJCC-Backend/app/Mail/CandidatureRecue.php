<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CandidatureRecue extends Mailable
{
    public function __construct(public MembershipApplication $application)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre candidature au REJCC a bien été reçue');
    }

    public function content(): Content
    {
        return new Content(htmlString: "
            <p>Bonjour {$this->application->prenom},</p>
            <p>Nous avons bien reçu votre candidature d'adhésion au REJCC.
            Elle sera examinée par notre équipe dans les meilleurs délais.</p>
            <p>Vous pouvez suivre son avancement à tout moment sur
            <a href=\"https://rejcc.site/suivre-ma-candidature\">rejcc.site/suivre-ma-candidature</a>
            avec votre adresse e-mail.</p>
            <p>Fraternellement,<br>L'équipe REJCC</p>
        ");
    }
}
