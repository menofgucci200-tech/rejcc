<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function download(string $dataset): StreamedResponse
    {
        $result = Api::get('/admin/export/'.rawurlencode($dataset), [], Api::token());

        abort_unless($result['ok'] ?? false, 404);

        $filename = ($result['filename'] ?? 'export').'.csv';

        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour qu'Excel affiche correctement les accents.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $result['columns'] ?? [], ';');
            foreach ($result['rows'] ?? [] as $row) {
                fputcsv($out, array_map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v, $row), ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
