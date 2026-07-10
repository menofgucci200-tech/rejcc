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
        return new Content(htmlString: "
            <p>Bonjour {$this->application->prenom},</p>
            <p>Excellente nouvelle : votre candidature d'adhésion au REJCC a été <strong>acceptée</strong>.
            Bienvenue dans le réseau !</p>
            <p>Votre espace membre est prêt. Connectez-vous sur
            <a href=\"https://rejcc.site/connexion\">rejcc.site/connexion</a>
            avec votre adresse e-mail et le mot de passe choisi lors de votre inscription.</p>
            <p>Fraternellement,<br>L'équipe REJCC</p>
        ");
    }
}
