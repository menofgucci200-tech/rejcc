<?php

namespace App\Mail;

use App\Models\DemandeCommunication;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Suivi d'une demande de communication, envoyé au demandeur : changement de
 * statut (acceptée, planifiée, publiée, refusée…) ou réponse de la Communication.
 */
class DemandeCommunicationSuivi extends Mailable
{
    /**
     * @param  string  $evenement  statut atteint, ou « commentaire »
     * @param  string|null  $texte  motif de refus ou message de la Communication
     */
    public function __construct(public DemandeCommunication $demande, public string $evenement, public ?string $texte = null) {}

    private const PHRASES = [
        'acceptee' => 'a été acceptée par la Communication. Elle va être préparée.',
        'planifiee' => 'est planifiée dans le calendrier de la Communication.',
        'publiee' => 'a été publiée. Merci d\'avoir fait appel à la Communication.',
        'refusee' => 'n\'a pas pu être retenue par la Communication.',
        'nouvelle' => 'est de nouveau en attente de traitement.',
        'annulee' => 'a été annulée.',
    ];

    public function envelope(): Envelope
    {
        $objet = $this->evenement === 'commentaire'
            ? 'message de la Communication'
            : 'demande '.mb_strtolower($this->demande->libelleStatut());

        return new Envelope(subject: 'REJCC — '.ucfirst($objet).' : '.$this->demande->titre);
    }

    public function content(): Content
    {
        $d = $this->demande->loadMissing('demandeur');
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;

        $corps = MailLayout::bonjour($d->demandeur?->prenom);
        if ($this->evenement === 'commentaire') {
            $corps .= "<p style=\"{$p}\">La Communication a répondu à votre demande <strong>« {$e($d->titre)} »</strong> :</p>";
        } else {
            $corps .= "<p style=\"{$p}\">Votre demande <strong>« {$e($d->titre)} »</strong> ".$e(self::PHRASES[$this->evenement] ?? 'a changé de statut.').'</p>';
            if ($this->evenement === 'planifiee' && $d->date_publication_prevue) {
                $corps .= "<p style=\"{$p}\">Publication prévue le <strong>{$e($d->date_publication_prevue->locale('fr')->translatedFormat('l j F Y'))}</strong>.</p>";
            }
            if ($this->evenement === 'publiee' && $d->lien_publication) {
                $corps .= "<p style=\"{$p}\">Voir la publication : <a href=\"{$e($d->lien_publication)}\" style=\"color:#4F6FBF\">{$e($d->lien_publication)}</a></p>";
            }
        }
        if ($this->texte) {
            $libelle = $this->evenement === 'refusee' ? 'Motif' : 'Message';
            $corps .= "<p style=\"{$p};padding:10px 12px;border-left:3px solid #4F6FBF;background:#F4F6F8;white-space:pre-line\"><strong>{$libelle} :</strong> {$e($this->texte)}</p>";
        }

        return new Content(htmlString: MailLayout::html('Votre demande de communication', $corps,
            'Suivre mes demandes', rtrim((string) config('app.dashboard_url'), '/').'/admin/communication/mes-demandes?fiche='.$d->id,
            'Notification interne : vous avez adressé cette demande à la Communication depuis le dashboard du REJCC.'));
    }
}
