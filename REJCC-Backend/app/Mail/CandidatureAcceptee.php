<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CandidatureAcceptee extends Mailable
{
    public function __construct(public MembershipApplication $application)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenue au REJCC — votre candidature est acceptée !');
    }

    public function content(): Content
    {
        $prenom = e($this->application->prenom);
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(htmlString: \App\Support\MailLayout::html(
            "Bienvenue au REJCC !",
            "<p style=\"margin:0 0 12px\">Bonjour {$prenom},</p>
<p style=\"margin:0 0 12px\">Excellente nouvelle : votre demande d'adhésion a été <strong>acceptée</strong>. Bienvenue dans le réseau !</p>
<p style=\"margin:0\">Votre espace membre est prêt. Connectez-vous avec votre adresse e-mail et le mot de passe choisi lors de votre adhésion.</p>",
            'Accéder à mon espace membre',
            $base.'/connexion',
            "Vous recevez cet e-mail suite à votre demande d'adhésion au REJCC.",
        ));
    }
}
