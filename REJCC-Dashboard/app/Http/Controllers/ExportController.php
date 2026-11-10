<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function download(string $dataset): StreamedResponse
    {
        // Paramètres de filtrage éventuels (participants d'un événement, membres d'un groupe, type de contact média).
        $query = request()->only('event', 'group', 'type');

        $result = Api::get('/admin/export/'.rawurlencode($dataset), $query, Api::token());

        abort_unless($result['ok'] ?? false, 404);

        $filename = ($result['filename'] ?? 'export').'.csv';

        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour qu'Excel affiche correctement les accents.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $result['columns'] ?? [], ';');
            foreach ($result['rows'] ?? [] as $row) {
                fputcsv($out, array_map(fn ($v) => self::cellule(is_array($v) ? implode(', ', $v) : (string) $v), $row), ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Injection de formules : une cellule venue d'un formulaire public qui commence
     * par = @ (ou + - suivi d'autre chose qu'un nombre, pour garder « +225 07… »)
     * serait exécutée par Excel. Elle est préfixée d'une apostrophe.
     */
    public static function cellule(string $v): string
    {
        return preg_match('/^(?:[=@\t\r]|[+\-](?!\s*\d))/', $v) ? "'".$v : $v;
    }
}
