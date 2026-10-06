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
        $prenom = e($this->application->prenom);
        $base = rtrim((string) config('app.frontend_url'), '/');
        $motif = $this->application->reject_reason
            ? '<p style="margin:0 0 12px"><strong>Motif :</strong> '.e($this->application->reject_reason).'</p>'
            : '';

        return new Content(htmlString: \App\Support\MailLayout::html(
            'Suite de votre demande d\'adhésion',
            "<p style=\"margin:0 0 12px\">Bonjour {$prenom},</p>
<p style=\"margin:0 0 12px\">Après examen, nous ne sommes malheureusement pas en mesure de donner une suite favorable à votre demande d'adhésion au REJCC.</p>
{$motif}
<p style=\"margin:0\">Cette décision ne remet pas en cause la qualité de votre parcours : vous pourrez déposer une nouvelle demande ultérieurement. Écrivez-nous si vous souhaitez en savoir plus.</p>",
            'Nous écrire',
            $base.'/contact',
            "Vous recevez cet e-mail suite à votre demande d'adhésion au REJCC.",
        ));
    }
}
