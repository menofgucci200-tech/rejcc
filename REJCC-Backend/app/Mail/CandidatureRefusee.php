<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CandidatureRefusee extends Mailable
{
    public function __construct(public MembershipApplication $application)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Suite de votre candidature au REJCC');
    }

    public function content(): Content
    {
        $motif = $this->application->reject_reason
            ? '<p><strong>Motif :</strong> '.e($this->application->reject_reason).'</p>'
            : '';

        return new Content(htmlString: "
            <p>Bonjour {$this->application->prenom},</p>
            <p>Après examen, nous ne sommes malheureusement pas en mesure de donner
            une suite favorable à votre candidature d'adhésion au REJCC.</p>
            {$motif}
            <p>Cette décision ne remet pas en cause la qualité de votre parcours, et vous
            pourrez soumettre une nouvelle candidature ultérieurement. N'hésitez pas à nous
            écrire via <a href=\"https://rejcc.site/contact\">rejcc.site/contact</a>
            si vous souhaitez en savoir plus.</p>
            <p>Fraternellement,<br>L'équipe REJCC</p>
        ");
    }
}
