<?php

namespace App\Mail;

use App\Models\Certificate;
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
        $acces = $c->user_id
            ? "<p>Il est délivré au format PDF, signé électroniquement : seul ce fichier fait foi (une image ou une capture d'écran n'a pas valeur de certificat). Retrouvez-le dans votre espace membre : <a href=\"{$base}/espace-membre/certificats\">{$base}/espace-membre/certificats</a></p>"
            : "<p>Il est délivré au format PDF, signé électroniquement : seul ce fichier fait foi. Téléchargez-le depuis sa page officielle : <a href=\"{$verif}\">{$verif}</a></p>";
        $prenom = explode(' ', $c->nom)[0];

        return new Content(htmlString: "
            <p>Bonjour {$e($prenom)},</p>
            <p>".($this->reemission ? 'Votre document a été corrigé et délivré à nouveau' : 'Félicitations ! Le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques vous délivre')."
            votre <strong>{$e(mb_strtolower($c->intitule))}</strong> pour <strong>{$e($c->titre)}</strong>.</p>
            <p>Référence : <strong>{$e($c->reference)}</strong><br>Code de vérification : <strong>{$e($c->codeLisible())}</strong></p>
            {$acces}
            <p>Toute personne (recruteur, partenaire…) peut vérifier son authenticité sur <a href=\"{$verif}\">{$verif}</a>.</p>
            <p>L'équipe REJCC</p>
        ");
    }
}
