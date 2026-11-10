<?php

namespace App\Mail;

use App\Support\CalendrierEditorial;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rappel au responsable de ses publications du calendrier éditorial :
 * la veille à 18 h (publications du lendemain) et le matin à 8 h (du jour).
 */
class RappelPublications extends Mailable
{
    /**
     * @param  Collection<int, \App\Models\CommunicationPublication>  $publications
     * @param  string  $moment  veille | jour
     */
    public function __construct(public ?string $prenom, public Collection $publications, public string $moment, public Carbon $date)
    {
    }

    public function envelope(): Envelope
    {
        $n = $this->publications->count();
        $quand = $this->moment === 'veille' ? 'demain' : "aujourd'hui";

        return new Envelope(subject: "REJCC — Calendrier éditorial : {$n} publication".($n > 1 ? 's' : '')." à faire {$quand}");
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;
        $td = 'padding:8px 10px;border-top:1px solid #EDF0F5;font-size:13px;vertical-align:top';
        $jour = $this->date->copy()->locale('fr')->isoFormat('dddd D MMMM');

        $lignes = $this->publications->map(function ($pub) use ($e, $td) {
            $canaux = collect($pub->canaux ?? [])->map(fn ($c) => CalendrierEditorial::CANAUX[$c] ?? $c)->implode(', ');
            $detail = collect([CalendrierEditorial::TYPES[$pub->type]['label'] ?? null, $pub->format])->filter()->implode(' · ');

            return "<tr><td style=\"{$td};white-space:nowrap;color:#5B677A\">".($pub->heure ? $e(str_replace(':', ' h ', $pub->heure)) : 'Dans la journée').'</td>'
                ."<td style=\"{$td}\"><strong>{$e($pub->titre)}</strong>".($detail ? "<br><span style=\"color:#5B677A\">{$e($detail)}</span>" : '')
                .($canaux ? "<br><span style=\"color:#4F6FBF\">{$e($canaux)}</span>" : '')
                .($pub->notes ? "<br><span style=\"color:#5B677A;font-style:italic\">{$e(mb_strimwidth($pub->notes, 0, 240, '…'))}</span>" : '').'</td></tr>';
        })->implode('');

        $intro = $this->moment === 'veille'
            ? "Demain, {$jour}, c'est à vous de publier. Préparez dès ce soir vos visuels et vos textes :"
            : "C'est votre tour aujourd'hui, {$jour}. Voici ce qui est prévu :";

        return new Content(htmlString: MailLayout::html(
            $this->moment === 'veille' ? 'Vos publications de demain' : 'Vos publications du jour',
            MailLayout::bonjour($this->prenom)
            ."<p style=\"{$p}\">{$e($intro)}</p>"
            ."<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 14px\">{$lignes}</table>"
            ."<p style=\"{$p}\">Une fois en ligne, ouvrez le calendrier, cliquez sur « Marquer publié » et collez le lien de la publication : toute l'équipe saura que c'est fait.</p>",
            'Ouvrir le calendrier', rtrim((string) config('app.dashboard_url'), '/').'/admin/communication/calendrier',
            'Notification interne : rappel du calendrier éditorial du département Communication.', signature: false));
    }
}
