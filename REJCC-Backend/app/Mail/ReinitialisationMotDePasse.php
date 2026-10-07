<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MailLayout;
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

        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;

        return new Content(htmlString: MailLayout::html('Choisissez un nouveau mot de passe',
            MailLayout::bonjour($this->user->prenom)."
<p style=\"{$p}\">Vous avez demandé la réinitialisation du mot de passe de votre espace membre REJCC. Ce lien est valable <strong>1 heure</strong>.</p>
<p style=\"margin:0;color:#5B677A;font-size:13px\">Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :<br><a href=\"{$e($lien)}\" style=\"color:#4F6FBF;word-break:break-all\">{$e($lien)}</a></p>",
            'Choisir un nouveau mot de passe', $lien,
            "E-mail de sécurité. Si vous n'êtes pas à l'origine de cette demande, ignorez-le : votre mot de passe reste inchangé."));
    }
}
