<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Carbon\Carbon;

class CardController extends Controller
{
    public function show(string $code)
    {
        $result = Api::get('/member-card/'.rawurlencode($code));

        if (! ($result['ok'] ?? false)) {
            abort(404);
        }

        $card = (object) $result['card'];
        $card->membre_depuis = ($card->membre_depuis ?? null) ? Carbon::parse($card->membre_depuis)->translatedFormat('d F Y') : null;

        return view('pages.carte', ['card' => $card]);
    }

    /**
     * Fiche contact (vCard) du membre, à enregistrer dans le téléphone depuis
     * sa page publique. Le téléphone et l'e-mail n'y figurent que si le membre
     * a choisi de les rendre publics.
     */
    public function vcard(string $code)
    {
        $result = Api::get('/member-card/'.rawurlencode($code));
        $card = $result['card'] ?? null;

        if (! ($result['ok'] ?? false) || ! $card || ($card['locked'] ?? false)) {
            abort(404);
        }

        $esc = fn (?string $v) => str_replace(["\\", "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], trim((string) $v));
        $nom = trim(($card['prenom'] ?? '').' '.($card['nom'] ?? '')) ?: ($card['name'] ?? 'Membre REJCC');

        $lignes = array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$esc($card['nom'] ?? '').';'.$esc($card['prenom'] ?? '').';;;',
            'FN:'.$esc($nom),
            ($card['organisation'] ?? null) ? 'ORG:'.$esc($card['organisation']) : null,
            ($card['titre'] ?? null) ? 'TITLE:'.$esc($card['titre']) : null,
            ($card['telephone'] ?? null) ? 'TEL;TYPE=CELL:'.$esc($card['telephone']) : null,
            ($card['email'] ?? null) ? 'EMAIL;TYPE=INTERNET:'.$esc($card['email']) : null,
            ($card['ville'] ?? null) ? 'ADR;TYPE=WORK:;;;'.$esc($card['ville']).';;;Côte d\'Ivoire' : null,
            'URL:'.url('/carte/'.$card['code']),
            'NOTE:'.$esc(($card['role_label'] ?? 'Membre').' du REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques. N° '.($card['numero'] ?? '')),
            'END:VCARD',
        ]);

        $fichier = 'rejcc-'.\Illuminate\Support\Str::slug($nom).'.vcf';

        return response(implode("\r\n", $lignes)."\r\n", 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fichier.'"',
        ]);
    }
}
