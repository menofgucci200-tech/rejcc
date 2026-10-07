<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Délivrance (ou mise à jour) d'un certificat ou d'une attestation. */
class CertificatDelivre extends Mailable
{
    public function __construct(public Certificate $certificat, public bool $reemission = false)
    {
    }

    public function envelope(): Envelope
    {
        $quoi = $this->certificat->type === 'formation' ? 'Votre certificat' : 'Votre attestation';

        return new Envelope(subject: 'REJCC — '.$quoi.($this->reemission ? ' a été mis à jour' : ' est disponible'));
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $c = $this->certificat;
        $base = rtrim((string) config('app.frontend_url'), '/');
        $verif = $c->urlVerification(false);
        $p = MailLayout::P;
        $quoi = $c->type === 'formation' ? 'certificat' : 'attestation';
        $acces = $c->user_id
            ? "<p style=\"{$p}\">Il est délivré au format PDF, signé électroniquement : seul ce fichier fait foi (une image ou une capture d'écran n'a pas valeur de {$quoi}). Vous le retrouvez dans votre espace membre.</p>"
            : "<p style=\"{$p}\">Il est délivré au format PDF, signé électroniquement : seul ce fichier fait foi. Téléchargez-le depuis sa page officielle.</p>";
        $prenom = explode(' ', $c->nom)[0];
        $intro = $this->reemission
            ? 'Votre document a été corrigé et vous est délivré à nouveau :'
            : 'Le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques a le plaisir de vous délivrer';

        return new Content(htmlString: MailLayout::html(
            $this->reemission ? 'Votre document a été mis à jour' : 'Félicitations'.($prenom !== '' ? ', '.$prenom : '').' !',
            MailLayout::bonjour($prenom)."
<p style=\"{$p}\">{$intro} votre <strong>{$e(mb_strtolower($c->intitule))}</strong> pour <strong>{$e($c->titre)}</strong>.</p>
<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 14px;background:#F6F8FB;border-left:3px solid #AC0100;border-radius:6px\"><tr><td style=\"padding:12px 16px;font-size:13.5px\">
Référence : <strong>{$e($c->reference)}</strong><br>Code de vérification : <strong style=\"letter-spacing:1px\">{$e($c->codeLisible())}</strong></td></tr></table>
{$acces}
<p style=\"margin:0\">Toute personne (recruteur, partenaire…) peut vérifier son authenticité sur <a href=\"{$e($verif)}\" style=\"color:#4F6FBF\">{$e($verif)}</a>.</p>",
            $c->user_id ? 'Voir mon '.$quoi : 'Télécharger mon '.$quoi,
            $c->user_id ? $base.'/espace-membre/certificats' : $verif,
            'Vous recevez cet e-mail car le REJCC vous a délivré ce document.'));
    }
}
