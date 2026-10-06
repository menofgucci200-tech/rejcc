<?php

namespace App\Support;

use App\Models\Certificate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use TCPDF;

/**
 * PDF officiel d'un certificat (modèle « Prestige »), produit uniquement par
 * la plateforme. Sécurités : micro-texte nominatif le long du cadre,
 * filigrane diagonal (nom + référence), rosace guillochée unique calculée à
 * partir du code, QR code signé, PDF protégé contre la modification et,
 * si un certificat numérique est configuré, signature électronique.
 */
class CertificatPdf
{
    private const W = 297;

    private const H = 210;

    private const BLANC = [255, 255, 255];

    private const AZUR = [143, 163, 217];

    private const ROUGE = [172, 1, 0];

    private const MARINE = [3, 29, 89];

    /** @return array{0: string, 1: bool} contenu du PDF, signé électroniquement ou non */
    public static function generer(Certificate $c): array
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setCellHeightRatio(1);
        $pdf->SetCreator('Plateforme REJCC');
        $pdf->SetAuthor('REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques');
        $pdf->SetTitle($c->intitule.' — '.$c->nom);
        $pdf->SetSubject($c->titre);
        $pdf->SetKeywords($c->reference.' '.$c->codeLisible().' '.$c->urlVerification(false));

        $polices = resource_path('fonts/certificats').'/';
        foreach (['anton', 'caslon', 'manrope', 'manropesb', 'manropeb', 'manropexb'] as $f) {
            $pdf->AddFont($f, '', $polices.$f.'.php');
        }

        $pdf->AddPage();
        $pdf->Image(resource_path('certificats/prestige-fond.jpg'), 0, 0, self::W, self::H, 'JPG', '', '', false, 300);

        $graine = hash('sha256', $c->code.'|'.$c->reference, true);
        self::filigrane($pdf, $c);
        self::rosace($pdf, $graine);
        self::microTexte($pdf, $c);
        self::contenu($pdf, $c);
        self::pied($pdf, $c);

        // Signature électronique de certification (si configurée) : toute
        // modification ultérieure est signalée par les lecteurs PDF. Sinon,
        // protection par droits : impression seule, modification bloquée.
        $signe = self::signer($pdf, $c);
        if (! $signe) {
            $pdf->SetProtection(['modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'], '', bin2hex(random_bytes(16)), 2);
        }

        return [$pdf->Output('certificat.pdf', 'S'), $signe];
    }

    // ── Textes ──────────────────────────────────────────────────────────

    private static function texte(TCPDF $pdf, float $y, string $txt, string $police, float $taille, array $couleur, float $alpha = 1, float $espacement = 0, float $largeur = 237, ?float $x = null): void
    {
        $pdf->SetFont($police, '', $taille);
        $pdf->setFontSpacing($espacement);
        $pdf->SetTextColorArray($couleur);
        $pdf->SetAlpha($alpha);
        $x ??= (self::W - $largeur) / 2;
        // L'espacement s'ajoute aussi après la dernière lettre : on recentre.
        $pdf->SetXY($x + $espacement / 2, $y);
        $pdf->Cell($largeur, 0, $txt, 0, 0, 'C', false, '', 0, false, 'T', 'T');
        $pdf->SetAlpha(1);
        $pdf->setFontSpacing(0);
    }

    /** Taille réduite si le texte dépasse la largeur disponible (noms et titres longs). */
    private static function tailleAjustee(TCPDF $pdf, string $txt, string $police, float $taille, float $max, float $min): float
    {
        while ($taille > $min) {
            $pdf->SetFont($police, '', $taille);
            if ($pdf->GetStringWidth($txt) <= $max) {
                break;
            }
            $taille -= 0.5;
        }

        return $taille;
    }

    private static function date(?string $d): string
    {
        return $d ? Carbon::parse($d)->locale('fr')->isoFormat('D MMMM YYYY') : '';
    }

    private static function contenu(TCPDF $pdf, Certificate $c): void
    {
        $d = $c->details ?? [];
        [$titre, $sous, $decerne, $phrase] = match ($c->type) {
            'evenement' => ['ATTESTATION', 'DE PARTICIPATION', 'Le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques atteste que', "a participé à l'événement"],
            'parcours' => ['ATTESTATION', 'DE PARCOURS', 'Le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques atteste que', "a suivi et achevé avec succès l'ensemble du parcours"],
            default => ['CERTIFICAT', 'DE RÉUSSITE', 'Le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques décerne ce certificat à', "pour avoir suivi avec succès et validé par l'examen final la formation"],
        };

        self::texte($pdf, 44.2, $titre, 'anton', 34.5, self::BLANC, 1, 2.9);
        self::texte($pdf, 62.6, $sous, 'manropeb', 8.6, self::AZUR, 1, 1.5);
        self::texte($pdf, 74.6, $decerne, 'caslon', 12, self::BLANC, 0.72);

        $taille = self::tailleAjustee($pdf, $c->nom, 'caslon', 45, 225, 26);
        self::texte($pdf, 81.5 + (45 - $taille) * 0.18, $c->nom, 'caslon', $taille, self::BLANC);

        // Trait fin + point rouge sous le nom.
        $pdf->SetLineWidth(0.15);
        $pdf->SetDrawColorArray(self::AZUR);
        $pdf->SetAlpha(0.6);
        $pdf->Line(105, 101.8, 145.6, 101.8);
        $pdf->Line(151.4, 101.8, 192, 101.8);
        $pdf->SetAlpha(0.35);
        $pdf->Circle(148.5, 101.8, 1.4, 0, 360, 'F', [], self::ROUGE);
        $pdf->SetAlpha(1);
        $pdf->Circle(148.5, 101.8, 0.75, 0, 360, 'F', [], self::ROUGE);

        self::texte($pdf, 107.6, $phrase, 'manrope', 9.75, self::BLANC, 0.78);
        $tTitre = self::tailleAjustee($pdf, $c->titre, 'manropexb', 13.5, 225, 9);
        self::texte($pdf, 114.6, $c->titre, 'manropexb', $tTitre, self::BLANC);

        $infos = match ($c->type) {
            'evenement' => array_filter([
                ! empty($d['date_evenement']) ? 'le '.self::date($d['date_evenement']) : null,
                $d['lieu_evenement'] ?? null, $d['duree'] ?? null,
                'délivrée à '.$c->lieu.', le '.self::date($c->delivre_le->toDateString()),
            ]),
            'parcours' => [count($d['formations'] ?? []).' formation'.(count($d['formations'] ?? []) > 1 ? 's' : '').' validée'.(count($d['formations'] ?? []) > 1 ? 's' : ''), $c->lieu.', le '.self::date($c->delivre_le->toDateString())],
            default => array_filter([
                $d['duree'] ?? null,
                ! empty($d['modules']) ? $d['modules'].' module'.($d['modules'] > 1 ? 's' : '') : null,
                isset($d['score']) ? "résultat à l'examen : ".$d['score'].' %' : null,
                $c->lieu.', le '.self::date($c->delivre_le->toDateString()),
            ]),
        };
        self::texte($pdf, 123.4, implode(' · ', $infos), 'manrope', 9.75, self::BLANC, 0.78);

        [$label, $items] = $c->type === 'parcours'
            ? ['FORMATIONS DU PARCOURS', $d['formations'] ?? []]
            : ['COMPÉTENCES VALIDÉES', $d['competences'] ?? []];
        if ($items) {
            self::texte($pdf, 133.6, $label, 'manropeb', 7.1, self::AZUR, 1, 0.85);
            self::liste($pdf, 139.0, $items);
        }
    }

    /** Éléments séparés par de petits losanges rouges, centrés, sur une ou deux lignes. */
    private static function liste(TCPDF $pdf, float $y, array $items): void
    {
        $taille = 9.75;
        $pdf->SetFont('manropesb', '', $taille);
        $ecart = 6.5;
        $lignes = [[]];
        $largeurs = [0];
        foreach ($items as $it) {
            $w = $pdf->GetStringWidth($it);
            $l = count($lignes) - 1;
            if ($lignes[$l] && $largeurs[$l] + $ecart + $w > 225) {
                $lignes[] = [];
                $largeurs[] = 0;
                $l++;
            }
            $largeurs[$l] += ($lignes[$l] ? $ecart : 0) + $w;
            $lignes[$l][] = [$it, $w];
        }
        foreach (array_slice($lignes, 0, 2) as $i => $ligne) {
            $x = (self::W - $largeurs[$i]) / 2;
            $yy = $y + $i * 5.6;
            foreach ($ligne as $j => [$it, $w]) {
                if ($j > 0) {
                    $cx = $x + $ecart / 2;
                    $cy = $yy + 1.95;
                    $pdf->Polygon([$cx, $cy - 0.75, $cx + 0.75, $cy, $cx, $cy + 0.75, $cx - 0.75, $cy], 'F', [], self::ROUGE);
                    $x += $ecart;
                }
                $pdf->SetTextColorArray(self::BLANC);
                $pdf->SetXY($x, $yy);
                $pdf->Cell($w, 0, $it, 0, 0, 'L', false, '', 0, false, 'T', 'T');
                $x += $w;
            }
        }
    }

    private static function pied(TCPDF $pdf, Certificate $c): void
    {
        // Référence et code de vérification (en haut à gauche).
        foreach ([[15.2, 'N° '.$c->reference, 0.6], [19.4, 'CODE DE VÉRIFICATION  '.$c->codeLisible(), 0.45]] as [$y, $t, $a]) {
            $pdf->SetFont('manropesb', '', 6.6);
            $pdf->setFontSpacing(0.55);
            $pdf->SetTextColorArray(self::BLANC);
            $pdf->SetAlpha($a);
            $pdf->Text(17, $y, $t);
        }
        $pdf->SetAlpha(1);
        $pdf->setFontSpacing(0);

        // Signataires (instantané au moment de la délivrance).
        $sig = array_values($c->signataires ?? []);
        foreach ([78.0, 219.0] as $i => $cx) {
            $s = $sig[$i] ?? null;
            if (! $s) {
                continue;
            }
            $pdf->SetLineWidth(0.2);
            $pdf->SetDrawColorArray(self::BLANC);
            $pdf->SetAlpha(0.55);
            $pdf->Line($cx - 41.5, 185.3, $cx + 41.5, 185.3);
            $pdf->SetAlpha(1);
            if (! empty($s['signature']) && Storage::disk('local')->exists($s['signature'])) {
                $pdf->Image(Storage::disk('local')->path($s['signature']), $cx - 25, 170.8, 50, 13.5, '', '', '', false, 300, '', false, false, 0, 'CM');
            }
            $nom = trim((string) ($s['nom'] ?? ''));
            self::texte($pdf, 187.4, $nom ?: (string) $s['fonction'], 'manropeb', 8.6, self::BLANC, 1, 0, 83, $cx - 41.5);
            self::texte($pdf, 191.2, $nom ? (string) $s['fonction'] : 'REJCC', 'manrope', 7.9, self::BLANC, 0.62, 0, 83, $cx - 41.5);
        }

        // Cachet officiel (facultatif) près du second signataire.
        $cachet = $c->details['cachet'] ?? null;
        if ($cachet && Storage::disk('local')->exists($cachet)) {
            $pdf->SetAlpha(0.92);
            $pdf->Image(Storage::disk('local')->path($cachet), 238, 163, 24, 24, '', '', '', false, 300, '', false, false, 0, 'CM');
            $pdf->SetAlpha(1);
        }

        // QR code signé (vers la page de vérification).
        $pdf->RoundedRect(138, 167.4, 21, 21, 1.8, '1111', 'F', [], self::BLANC);
        $pdf->write2DBarcode($c->urlVerification(true), 'QRCODE,M', 139.6, 169.0, 17.8, 17.8, [
            'border' => false, 'padding' => 0, 'fgcolor' => self::MARINE, 'bgcolor' => false,
        ], 'N');
        self::texte($pdf, 190.2, 'Vérifier : rejcc.site/verifier', 'manrope', 6.4, self::BLANC, 0.62, 0, 60);
        self::texte($pdf, 193.3, "L'authenticité se vérifie uniquement en ligne", 'manrope', 5.4, self::BLANC, 0.45, 0, 70);
    }

    // ── Sécurités ───────────────────────────────────────────────────────

    /** Micro-texte continu entre les deux filets du cadre : nom, référence et code répétés. */
    private static function microTexte(TCPDF $pdf, Certificate $c): void
    {
        $motif = 'REJCC · '.mb_strtoupper($c->nom).' · '.$c->reference.' · '.$c->codeLisible().' · AUTHENTIQUE · ';
        $pdf->SetFont('manropesb', '', 1.75);
        $pdf->SetTextColorArray(self::AZUR);
        $pdf->SetAlpha(0.75);
        $unite = max(1, $pdf->GetStringWidth($motif));
        $ligne = fn (float $longueur) => str_repeat($motif, (int) ceil($longueur / $unite) + 1);
        $m = 9.1; // milieu entre les filets (8 mm et 10,2 mm)

        $pdf->StartTransform();
        $pdf->Rect($m, 0, self::W - 2 * $m, self::H, 'CNZ');
        $pdf->Text($m, $m - 0.35, $ligne(self::W));
        $pdf->Text($m, self::H - $m - 0.35, $ligne(self::W));
        $pdf->StopTransform();

        foreach ([[$m - 0.35, self::H - $m, 90], [self::W - $m + 0.35, $m, -90]] as [$x, $y, $angle]) {
            $pdf->StartTransform();
            $pdf->Rotate($angle, $x, $y);
            $pdf->Rect($x, $y - 1, self::H - 2 * $m, 2, 'CNZ');
            $pdf->Text($x, $y, $ligne(self::H));
            $pdf->StopTransform();
        }
        $pdf->SetAlpha(1);
    }

    /** Filigrane diagonal très discret : nom et référence du titulaire sur toute la page. */
    private static function filigrane(TCPDF $pdf, Certificate $c): void
    {
        $motif = mb_strtoupper($c->nom).'   ·   '.$c->reference.'   ·   ';
        $pdf->SetFont('manropesb', '', 6);
        $pdf->SetTextColorArray(self::AZUR);
        $pdf->SetAlpha(0.06);
        $pas = $pdf->GetStringWidth($motif);
        $a = deg2rad(24);
        [$ux, $uy] = [cos($a), -sin($a)];   // direction des lignes (vers le haut à droite)
        [$nx, $ny] = [sin($a), cos($a)];    // écart entre deux lignes
        $pdf->StartTransform();
        $pdf->Rect(10.2, 10.2, self::W - 20.4, self::H - 20.4, 'CNZ');
        // Chaque ligne part de son entrée dans la page : toutes les coordonnées
        // restent positives (TCPDF lit une abscisse négative depuis la droite).
        for ($k = -40; $k <= 40; $k++) {
            $ox = self::W / 2 + $k * 11 * $nx;
            $oy = self::H / 2 + $k * 11 * $ny;
            $tMin = -INF;
            $tMax = INF;
            foreach ([[$ox, $ux, 0.5, self::W - 0.5], [$oy, $uy, 0.5, self::H - 0.5]] as [$o, $u, $min, $max]) {
                $t1 = ($min - $o) / $u;
                $t2 = ($max - $o) / $u;
                $tMin = max($tMin, min($t1, $t2));
                $tMax = min($tMax, max($t1, $t2));
            }
            if ($tMin >= $tMax) {
                continue;
            }
            $decalage = fmod(($k % 3 + 3) % 3 * $pas / 3, $pas);
            for ($t = $tMin - $decalage; $t < $tMax; $t += $pas) {
                $tt = max($t, $tMin);
                $x = $ox + $tt * $ux;
                $y = $oy + $tt * $uy;
                $pdf->StartTransform();
                $pdf->Rotate(24, $x, $y);
                $pdf->Text($x, $y, $t < $tMin ? mb_substr($motif, (int) round(($tMin - $t) / $pas * mb_strlen($motif))) : $motif);
                $pdf->StopTransform();
            }
        }
        $pdf->StopTransform();
        $pdf->SetAlpha(1);
    }

    /**
     * Rosace guillochée derrière le nom, comme sur les billets de banque : ses
     * paramètres sont tirés du code du certificat, elle est donc différente
     * sur chaque exemplaire et toute retouche de la zone du nom la brise.
     */
    private static function rosace(TCPDF $pdf, string $graine): void
    {
        $o = array_values(unpack('C*', $graine));
        $cx = self::W / 2;
        $cy = 89;
        $R = 52 + $o[0] % 10;              // grand rayon
        $r = 3 + $o[1] % 6;                // petit cercle roulant
        $dd = 9 + $o[2] % 9;               // décalage du point traceur
        $echelle = 46 / ($R + $dd - $r);   // rayon visible ~46 mm
        $pdf->SetLineWidth(0.09);
        $pdf->SetDrawColorArray(self::AZUR);

        foreach ([[0, 0.16, 1.0], [$o[3] / 40, 0.1, 0.72]] as [$phase, $alpha, $k]) {
            $pdf->SetAlpha($alpha);
            $pts = [];
            $tours = $r * 2 * M_PI;
            for ($i = 0; $i <= 2400; $i++) {
                $t = $tours * $i / 2400 + $phase;
                // Hypotrochoïde : x = (R−r)cos t + d cos((R−r)t/r)
                $x = ($R - $r) * cos($t) + $dd * cos(($R - $r) / $r * $t);
                $y = ($R - $r) * sin($t) - $dd * sin(($R - $r) / $r * $t);
                $pts[] = $cx + $x * $echelle * $k * 1.55;
                $pts[] = $cy + $y * $echelle * $k * 0.62;
            }
            $pdf->PolyLine($pts, 'D');
        }
        $pdf->SetAlpha(1);
    }

    /** Signature électronique (si un certificat numérique est configuré). */
    private static function signer(TCPDF $pdf, Certificate $c): bool
    {
        $cert = config('services.certificats.pdf_certificat');
        $cle = config('services.certificats.pdf_cle');
        if (! $cert || ! $cle || ! is_file($cert) || ! is_file($cle)) {
            return false;
        }
        $pdf->setSignature('file://'.realpath($cert), 'file://'.realpath($cle), (string) config('services.certificats.pdf_mot_de_passe', ''), '', 1, [
            'Name' => 'REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques',
            'Location' => $c->lieu,
            'Reason' => 'Certificat officiel '.$c->reference,
            'ContactInfo' => rtrim((string) config('app.frontend_url'), '/').'/verifier',
        ]);

        return true;
    }
}
