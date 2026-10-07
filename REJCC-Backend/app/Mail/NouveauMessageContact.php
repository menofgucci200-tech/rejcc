<?php

namespace App\Mail;

use App\Models\Contact;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Message du formulaire de contact, transmis à l'administration (« Répondre » écrit directement à l'expéditeur). */
class NouveauMessageContact extends Mailable
{
    public function __construct(public Contact $contact)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'REJCC — Nouveau message de contact : '.$this->contact->sujet,
            replyTo: filter_var($this->contact->email, FILTER_VALIDATE_EMAIL) ? [new Address($this->contact->email, $this->contact->nom)] : [],
        );
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;
        $c = $this->contact;
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(htmlString: MailLayout::html('Nouveau message de contact',
            "<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 14px;font-size:13.5px\">
<tr><td style=\"padding:3px 14px 3px 0;color:#5B677A\">De</td><td style=\"padding:3px 0\"><strong>{$e($c->nom)}</strong> ({$e($c->email)})</td></tr>
<tr><td style=\"padding:3px 14px 3px 0;color:#5B677A\">Sujet</td><td style=\"padding:3px 0\"><strong>{$e($c->sujet)}</strong></td></tr>
</table>
<div style=\"{$p};padding:14px 16px;background:#F6F8FB;border-left:3px solid #AC0100;border-radius:6px\">".nl2br($e($c->message))."</div>
<p style=\"margin:0;color:#5B677A;font-size:13px\">Répondez directement à cet e-mail pour écrire à l'expéditeur.</p>",
            "Traiter dans l'administration", $base.'/admin/contacts',
            'Notification interne : message reçu via le formulaire de contact du site.', signature: false));
    }
}
