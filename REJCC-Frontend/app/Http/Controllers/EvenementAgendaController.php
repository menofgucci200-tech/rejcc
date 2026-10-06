<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Fichier .ics d'un événement, pour l'ajouter à son agenda (téléphone,
 * Google Agenda, Outlook…).
 */
class EvenementAgendaController extends Controller
{
    private static function echapper(string $texte): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $texte);
    }

    public function __invoke(int $id)
    {
        $result = Api::get("/events/{$id}", [], Api::token());
        abort_unless($result['ok'] ?? false, 404);
        $e = $result['event'];

        $debut = Carbon::parse($e['starts_at'])->utc();
        $fin = $e['ends_at'] ? Carbon::parse($e['ends_at'])->utc() : $debut->copy()->addHours(2);
        $lien = route('espace-membre.evenements', ['evenement' => $e['id']]);
        $description = trim(($e['excerpt'] ?: Str::limit((string) $e['description'], 400))."\n\n".$lien);

        $lignes = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//REJCC//Evenements//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:evenement-'.$e['id'].'@rejcc',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$debut->format('Ymd\THis\Z'),
            'DTEND:'.$fin->format('Ymd\THis\Z'),
            'SUMMARY:'.self::echapper($e['title']),
            'DESCRIPTION:'.self::echapper($description),
            'LOCATION:'.self::echapper($e['en_ligne'] ? 'En ligne' : (string) $e['location']),
            'URL:'.$lien,
            'BEGIN:VALARM',
            'TRIGGER:-PT2H',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::echapper($e['title']),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lignes)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($e['title']).'.ics"',
        ]);
    }
}
