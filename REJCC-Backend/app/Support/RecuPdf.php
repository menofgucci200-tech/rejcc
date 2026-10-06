<?php

namespace App\Support;

use App\Models\Payment;
use TCPDF;

/** Reçu de paiement de l'abonnement annuel (A4 portrait, à la charte REJCC). */
class RecuPdf
{
    private const MARINE = [3, 29, 89];

    private const ROUGE = [172, 1, 0];

    private const GRIS = [91, 103, 122];

    public static function generer(Payment $p): string
    {
        $p->loadMissing('user', 'beneficiaire');
        $benef = $p->beneficiaire ?? $p->user;
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetCreator('Plateforme REJCC');
        $pdf->SetAuthor('REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques');
        $pdf->SetTitle('Reçu '.$p->recu_numero);
        foreach (['anton', 'manrope', 'manropesb', 'manropeb', 'manropexb'] as $f) {
            $pdf->AddFont($f, '', resource_path('fonts/certificats').'/'.$f.'.php');
        }
        $pdf->AddPage();

        // En-tête bleu nuit.
        $pdf->Rect(0, 0, 210, 52, 'F', [], self::MARINE);
        $pdf->Rect(0, 52, 210, 1.6, 'F', [], self::ROUGE);
        $pdf->Image(resource_path('certificats/monogramme-blanc.png'), 18, 12, 0, 28, 'PNG');
        $txt = function (float $x, float $y, string $t, string $f, float $s, array $c, string $align = 'L', float $w = 0, float $esp = 0) use ($pdf) {
            $pdf->SetFont($f, '', $s);
            $pdf->setFontSpacing($esp);
            $pdf->SetTextColorArray($c);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, 0, $t, 0, 0, $align, false, '', 0, false, 'T', 'T');
            $pdf->setFontSpacing(0);
        };
        $txt(40, 15, 'REJCC', 'manropexb', 15, [255, 255, 255], 'L', 0, 1.2);
        $txt(40, 22, 'Réseau Entrepreneurial des Jeunes Chrétiens Catholiques', 'manrope', 8.5, [190, 200, 225]);
        $txt(40, 27, 'Abidjan, Côte d\'Ivoire · rejcc.site', 'manrope', 8.5, [190, 200, 225]);
        $txt(110, 15, 'REÇU DE PAIEMENT', 'anton', 20, [255, 255, 255], 'R', 82, 0.8);
        $txt(110, 26, 'N° '.$p->recu_numero, 'manropeb', 10, [255, 255, 255], 'R', 82);
        $txt(110, 32, 'Payé le '.$p->paye_at?->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm'), 'manrope', 8.5, [190, 200, 225], 'R', 82);

        // Montant.
        $pdf->RoundedRect(18, 66, 174, 34, 3, '1111', 'F', [], [244, 246, 248]);
        $txt(26, 73, 'MONTANT RÉGLÉ', 'manropeb', 8, self::GRIS, 'L', 0, 0.8);
        $txt(26, 80, number_format($p->amount, 0, ',', ' ').' F CFA', 'manropexb', 24, self::MARINE);
        $pdf->RoundedRect(148, 76, 36, 12, 6, '1111', 'F', [], [34, 168, 90]);
        $txt(148, 79, 'PAYÉ', 'manropexb', 10, [255, 255, 255], 'C', 36, 1.2);

        // Détails.
        $lignes = array_filter([
            'Objet' => 'Cotisation annuelle au REJCC'.($p->estOffert() ? ' (abonnement offert)' : ''),
            'Membre abonné' => trim($benef?->prenom.' '.$benef?->nom).($benef ? ' · N° '.$benef->memberNumber() : ''),
            'Offert par' => $p->estOffert() ? trim($p->user?->prenom.' '.$p->user?->nom) : null,
            'Période couverte' => $p->periode_debut && $p->periode_fin
                ? 'du '.$p->periode_debut->locale('fr')->isoFormat('D MMMM YYYY').' au '.$p->periode_fin->locale('fr')->isoFormat('D MMMM YYYY') : null,
            'Moyen de paiement' => ($p->moyen ?: 'Paiement en ligne').' · via CinetPay',
            'Référence de la transaction' => $p->reference,
            'Référence opérateur' => $p->transaction_id,
        ]);
        $y = 112;
        foreach ($lignes as $k => $v) {
            $txt(18, $y, mb_strtoupper($k), 'manropeb', 7.5, self::GRIS, 'L', 0, 0.6);
            $pdf->SetFont('manropesb', '', 11);
            $pdf->SetTextColorArray(self::MARINE);
            $pdf->SetXY(18, $y + 4.6);
            $pdf->MultiCell(174, 0, (string) $v, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            $y = max($y + 13, $pdf->GetY() + 5);
            $pdf->SetDrawColor(231, 235, 241);
            $pdf->Line(18, $y - 2.5, 192, $y - 2.5);
        }

        // Mentions.
        $pdf->SetFont('manrope', '', 8.5);
        $pdf->SetTextColorArray(self::GRIS);
        $pdf->SetXY(18, max($y + 6, 205));
        $pdf->MultiCell(174, 0, 'Ce reçu atteste du paiement de la cotisation annuelle donnant accès aux services réservés aux membres abonnés (carte de membre officielle, annuaire, messagerie, Marketplace, projets). Il est généré par la plateforme du REJCC à la confirmation du paiement par CinetPay et ne nécessite pas de signature.', 0, 'L');

        $pdf->Rect(0, 282, 210, 15, 'F', [], [244, 246, 248]);
        $txt(18, 287.5, 'REJCC · Réseau Entrepreneurial des Jeunes Chrétiens Catholiques · contact@rejcc.site', 'manrope', 7.5, self::GRIS);
        $txt(130, 287.5, 'Reçu n° '.$p->recu_numero, 'manropesb', 7.5, self::MARINE, 'R', 62);

        $pdf->SetProtection(['modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'], '', bin2hex(random_bytes(16)), 2);

        return $pdf->Output('recu.pdf', 'S');
    }
}
