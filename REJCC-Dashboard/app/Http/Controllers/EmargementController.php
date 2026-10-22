<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Liste d'émargement d'une session live, en CSV (Excel) : inscrits, clic « Rejoindre », présence. */
class EmargementController extends Controller
{
    public function __invoke(int $id)
    {
        $r = Api::get("/admin/formations-live/{$id}/presences", [], Api::token());
        abort_unless($r['ok'] ?? false, 404, $r['message'] ?? 'Session introuvable.');
        $s = $r['session'];
        $heure = fn (?string $iso) => $iso ? Carbon::parse($iso)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') : '';
        // Protection contre les formules dans Excel.
        $cellule = fn ($v) => '"'.str_replace('"', '""', preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v).'"';

        $lignes = [['Session', $s['title']], ['Date', $heure($s['starts_at'])], ['Formateur(s)', $s['animateur'] ?? ''], [],
            ['Nom', 'E-mail', 'Téléphone', 'Inscrit le', 'A cliqué « Rejoindre »', 'Présent']];
        foreach ($r['inscrits'] as $i) {
            $lignes[] = [$i['nom'], $i['email'], $i['telephone'], $heure($i['inscrit_le']), $heure($i['rejoint_at']), $i['present'] ? 'Oui' : 'Non'];
        }
        $csv = "\xEF\xBB\xBF".implode("\r\n", array_map(fn ($l) => implode(';', array_map($cellule, $l)), $lignes))."\r\n";
        $nom = 'emargement-'.Str::slug($s['title']).'-'.Carbon::parse($s['starts_at'])->format('Y-m-d').'.csv';

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$nom.'"']);
    }
}
