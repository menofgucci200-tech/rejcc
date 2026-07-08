<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        return view('pages.evenements.index');
    }

    public function show(string $slug)
    {
        $result = Api::get("/public-events/{$slug}");

        if (! ($result['ok'] ?? false)) {
            abort(404);
        }

        $event = (object) $result['event'];
        $event->starts_at = Carbon::parse($event->starts_at);

        return view('pages.evenements.show', ['event' => $event]);
    }
}
