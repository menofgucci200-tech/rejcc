<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use App\Support\MailLayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Paiement d'abonnement confirmé (au membre, au bénéficiaire d'un cadeau, ou à celui qui l'offre). */
class AbonnementConfirme extends Mailable
{
    public function __construct(public Payment $paiement, public User $beneficiaire, public string $pour = 'membre')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->pour) {
            'beneficiaire' => 'REJCC — Un abonnement vous a été offert',
            'payeur' => 'REJCC — Merci pour l\'abonnement offert',
            default => 'REJCC — Votre abonnement est actif',
        });
    }

    public function content(): Content
    {
        $e = fn ($v) => e((string) $v);
        $p = $this->paiement;
        $fin = $p->periode_fin?->locale('fr')->isoFormat('D MMMM YYYY');
        $lien = rtrim((string) config('app.frontend_url'), '/').'/espace-membre/abonnement';
        $nom = trim($this->beneficiaire->prenom.' '.$this->beneficiaire->nom);
        $payeur = trim(($p->user?->prenom ?? '').' '.($p->user?->nom ?? ''));
        $montant = number_format($p->amount, 0, ',', ' ').' F CFA';

        $p_ = MailLayout::P;
        $corps = match ($this->pour) {
            'beneficiaire' => "<p style=\"{$p_}\">Bonjour {$e($this->beneficiaire->prenom)},</p><p style=\"{$p_}\"><strong>{$e($payeur)}</strong> vous offre votre abonnement annuel au Réseau Entrepreneurial des Jeunes Chrétiens Catholiques. Il est valable jusqu'au <strong>{$e($fin)}</strong>.</p>",
            'payeur' => "<p style=\"{$p_}\">Bonjour {$e($p->user?->prenom)},</p><p style=\"{$p_}\">Merci ! Votre paiement de {$e($montant)} est confirmé : <strong>{$e($nom)}</strong> est abonné(e) jusqu'au <strong>{$e($fin)}</strong>. Il ou elle en a été prévenu(e).</p>",
            default => "<p style=\"{$p_}\">Bonjour {$e($this->beneficiaire->prenom)},</p><p style=\"{$p_}\">Votre paiement de {$e($montant)} est confirmé : votre abonnement annuel au REJCC est actif jusqu'au <strong>{$e($fin)}</strong>.</p>",
        };
        $titre = match ($this->pour) {
            'beneficiaire' => 'Un abonnement vous a été offert',
            'payeur' => 'Merci pour votre cadeau',
            default => 'Votre abonnement est actif',
        };

        return new Content(htmlString: MailLayout::html($titre,
            $corps.($this->pour === 'beneficiaire' ? '' : "<p style=\"margin:0\">Reçu n° <strong>{$e($p->recu_numero)}</strong>, téléchargeable à tout moment dans votre espace membre.</p>"),
            $this->pour === 'beneficiaire' ? 'Voir mon abonnement' : 'Voir mon abonnement et mon reçu', $lien,
            'E-mail envoyé automatiquement suite à un paiement sur la plateforme du REJCC.'));
    }
}
