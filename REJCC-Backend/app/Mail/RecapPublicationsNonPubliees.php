<?php

namespace App\Mail;

use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Récapitulatif du matin à l'équipe Communication : publications de la veille restées non publiées. */
class RecapPublicationsNonPubliees extends Mailable
{
    /** @param  Collection<int, array>  $publications  lignes de CalendrierEditorial::ligne() */
    public function __construct(public Collection $publications, public Carbon $date)
    {
    }

    public function envelope(): Envelope
    {
        $n = $this->publications->count();

        return new Envelope(subject: "REJCC — Calendrier éditorial : {$n} publication".($n > 1 ? 's' : '').' non publiée'.($n > 1 ? 's' : '').' hier');
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;
        $td = 'padding:8px 10px;border-top:1px solid #EDF0F5;font-size:13px;vertical-align:top';
        $jour = $this->date->copy()->locale('fr')->isoFormat('dddd D MMMM');

        $lignes = $this->publications->map(fn ($l) => "<tr><td style=\"{$td}\"><strong>{$e($l['titre'])}</strong><br><span style=\"color:#5B677A\">{$e($l['type_label'])}"
            .($l['canaux_labels'] ? ' · '.$e(implode(', ', $l['canaux_labels'])) : '').'</span></td>'
            ."<td style=\"{$td};color:#AC0100;white-space:nowrap\">".$e($l['responsable_effectif']['nom'] ?? 'Sans responsable').'</td></tr>')->implode('');

        return new Content(htmlString: MailLayout::html('Publications non publiées hier',
            "<p style=\"{$p}\">Ces publications étaient prévues hier, {$e($jour)}, et ne sont pas marquées « publié » :</p>"
            ."<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 14px\">{$lignes}</table>"
            ."<p style=\"{$p}\">Si elles sont en ligne, marquez-les « publié » avec leur lien. Sinon, publiez-les aujourd'hui ou reprogrammez-les.</p>",
            'Ouvrir le calendrier', rtrim((string) config('app.dashboard_url'), '/').'/admin/communication/calendrier',
            "Notification interne : envoyée chaque matin à l'équipe Communication, seulement s'il reste des publications en retard.", signature: false));
    }
}
