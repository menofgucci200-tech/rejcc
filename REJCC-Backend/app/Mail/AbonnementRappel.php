<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rappel d'échéance de l'abonnement annuel. */
class AbonnementRappel extends Mailable
{
    public function __construct(public User $membre, public string $titre, public string $texte)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — '.$this->titre);
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $lien = rtrim((string) config('app.frontend_url'), '/').'/espace-membre/abonnement';

        $p = MailLayout::P;

        return new Content(htmlString: MailLayout::html($this->titre,
            MailLayout::bonjour($this->membre->prenom)."
<p style=\"{$p}\">{$e($this->texte)}</p>
<p style=\"margin:0;color:#5B677A;font-size:13px\">Paiement sécurisé sur la plateforme : Wave, Orange Money, MTN, Moov ou carte bancaire.</p>",
            'Renouveler mon abonnement', $lien,
            'Rappel lié à votre abonnement au REJCC.'));
    }
}
