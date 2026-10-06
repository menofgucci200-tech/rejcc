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
        $prenom = e($this->application->prenom);
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(htmlString: \App\Support\MailLayout::html(
            "Votre demande d'adhésion est bien reçue",
            "<p style=\"margin:0 0 12px\">Bonjour {$prenom},</p>
<p style=\"margin:0 0 12px\">Nous avons bien reçu votre demande d'adhésion au REJCC. Elle va être étudiée par le Bureau exécutif dans les meilleurs délais.</p>
<p style=\"margin:0\">Vous pouvez suivre son avancement à tout moment avec votre adresse e-mail.</p>",
            'Suivre ma candidature',
            $base.'/suivre-ma-candidature',
            "Vous recevez cet e-mail suite à votre demande d'adhésion au REJCC.",
        ));
    }
}
