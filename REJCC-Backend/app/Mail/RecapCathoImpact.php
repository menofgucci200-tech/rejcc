<?php

namespace App\Mail;

use App\Support\CathoImpact;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** Récapitulatif hebdomadaire CathoImpact pour l'équipe Communication (chaque lundi). */
class RecapCathoImpact extends Mailable
{
    /**
     * @param  Collection<int, \App\Models\CathoImpactCandidature>  $nouvelles  réponses des 7 derniers jours
     * @param  array<string, int>  $attente  réponses en attente, par statut
     */
    public function __construct(public Collection $nouvelles, public array $attente)
    {
    }

    public function envelope(): Envelope
    {
        $n = $this->nouvelles->count();

        return new Envelope(subject: 'REJCC — CathoImpact : '.($n ? "{$n} nouvelle".($n > 1 ? 's' : '').' réponse'.($n > 1 ? 's' : '').' cette semaine' : 'réponses en attente'));
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = MailLayout::P;
        $td = 'padding:6px 10px;border-top:1px solid #EDF0F5;font-size:13px';

        if ($this->nouvelles->isEmpty()) {
            $nouvelles = "<p style=\"{$p}\">Aucune nouvelle réponse cette semaine.</p>";
        } else {
            $lignes = $this->nouvelles->map(fn ($c) => "<tr><td style=\"{$td}\"><strong>{$e($c->nom_complet)}</strong><br><span style=\"color:#5B677A\">{$e($c->fonction)} · {$e($c->entreprise)}</span></td>"
                ."<td style=\"{$td};color:#5B677A\">{$e($c->secteur)}<br>{$e($c->ville)}</td>"
                ."<td style=\"{$td};color:#5B677A;white-space:nowrap\">{$e($c->created_at->format('d/m'))}</td></tr>")->implode('');
            $nouvelles = "<p style=\"{$p}\"><strong>Nouvelles réponses ({$this->nouvelles->count()})</strong></p>"
                ."<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 18px\">{$lignes}</table>";
        }

        $attente = '';
        if ($this->attente !== []) {
            $lignes = collect($this->attente)->map(fn ($n, $s) => "<tr><td style=\"{$td}\">{$e(CathoImpact::libelleStatut($s))}</td><td style=\"{$td};text-align:right\"><strong>{$n}</strong></td></tr>")->implode('');
            $attente = "<p style=\"{$p}\"><strong>En attente, par étape</strong></p>"
                ."<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0 0 6px\">{$lignes}</table>";
        }

        return new Content(htmlString: MailLayout::html('Votre récapitulatif CathoImpact',
            "<p style=\"{$p}\">Voici le point de la semaine sur les réponses au formulaire CathoImpact.</p>{$nouvelles}{$attente}",
            'Ouvrir CathoImpact', rtrim((string) config('app.dashboard_url'), '/').'/admin/cathoimpact',
            'Notification interne : récapitulatif envoyé chaque lundi au département Communication.', signature: false));
    }
}
