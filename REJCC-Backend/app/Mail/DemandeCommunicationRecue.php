<?php

namespace App\Mail;

use App\Models\DemandeCommunication;
use App\Support\MailLayout;
use App\Support\PlanDiffusion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

/** Nouvelle demande de communication, envoyée à l'équipe Communication. */
class DemandeCommunicationRecue extends Mailable
{
    public function __construct(public DemandeCommunication $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'REJCC — Nouvelle demande de communication : '.$this->demande->titre);
    }

    public function content(): Content
    {
        $d = $this->demande->loadMissing('demandeur');
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;
        $td = 'padding:6px 10px;border-top:1px solid #EDF0F5;font-size:13px';
        $demandeur = $d->demandeur ? trim("{$d->demandeur->prenom} {$d->demandeur->nom}") : '';
        $jours = PlanDiffusion::joursAvant($d->date_souhaitee);

        $lignes = [
            'Département' => $d->libelleDepartement(),
            'Demandé par' => $demandeur.' · '.$d->contact,
            'Type' => $d->libelleType(),
            'Publication souhaitée' => $d->date_souhaitee->locale('fr')->translatedFormat('l j F Y').' ('.($jours <= 0 ? 'aujourd\'hui ou dépassée' : "dans {$jours} jour".($jours > 1 ? 's' : '')).')',
            'Événement' => $d->date_evenement?->locale('fr')->translatedFormat('l j F Y'),
            'Canaux souhaités' => implode(', ', array_map(PlanDiffusion::libelleCanal(...), $d->canaux ?? [])) ?: 'À préciser',
            'Pièces jointes' => count($d->fichiers ?? []) ?: null,
        ];
        $tableau = collect($lignes)->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $l) => "<tr><td style=\"{$td};color:#5B677A;white-space:nowrap\">{$e($l)}</td><td style=\"{$td}\">{$e($v)}</td></tr>")->implode('');

        $urgence = PlanDiffusion::avertissementDelai($d->date_souhaitee)
            ? "<p style=\"{$p};padding:10px 12px;border-radius:8px;background:#FDEEEE;color:#8F0100\"><strong>Délai court :</strong> la publication est souhaitée dans moins de 7 jours.</p>"
            : '';

        return new Content(htmlString: MailLayout::html('Nouvelle demande de communication',
            "<p style=\"{$p}\">Le département <strong>{$e($d->libelleDepartement())}</strong> vous adresse une demande : <strong>{$e($d->titre)}</strong>.</p>{$urgence}"
            ."<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 16px\">{$tableau}</table>"
            ."<p style=\"{$p};white-space:pre-line;color:#3D4A60\">{$e(Str::limit($d->message, 600))}</p>",
            'Traiter la demande', rtrim((string) config('app.dashboard_url'), '/').'/admin/communication/demandes?fiche='.$d->id,
            'Notification interne : envoyée à l\'équipe Communication à chaque nouvelle demande.', signature: false));
    }
}
