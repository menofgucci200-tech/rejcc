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
        $card->membre_depuis = $card->membre_depuis ? Carbon::parse($card->membre_depuis)->translatedFormat('d F Y') : null;

        return view('pages.carte', ['card' => $card]);
    }
}
