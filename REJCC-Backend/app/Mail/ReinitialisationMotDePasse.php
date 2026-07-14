<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReinitialisationMotDePasse extends Mailable
{
    public function __construct(public User $user, public string $token)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — Réinitialisation de votre mot de passe');
    }

    public function content(): Content
    {
        $lien = rtrim(config('app.frontend_url'), '/')
            .'/reinitialiser-mot-de-passe?token='.$this->token
            .'&email='.urlencode($this->user->email);

        return new Content(htmlString: "
            <p>Bonjour {$this->user->prenom},</p>
            <p>Vous avez demandé la réinitialisation du mot de passe de votre espace membre REJCC.</p>
            <p><a href=\"{$lien}\" style=\"display:inline-block;background:#031D59;color:#ffffff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:bold\">Choisir un nouveau mot de passe</a></p>
            <p>Ce lien est valable 1 heure. Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :<br>
            <a href=\"{$lien}\">{$lien}</a></p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail : votre mot de passe reste inchangé.</p>
            <p>Fraternellement,<br>L'équipe REJCC</p>
        ");
    }
}
