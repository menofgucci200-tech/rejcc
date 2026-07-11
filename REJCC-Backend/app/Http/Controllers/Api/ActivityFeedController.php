<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\FormationEnrollment;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class ActivityFeedController extends Controller
{
    /**
     * Fil d'activité du membre courant : inscriptions aux formations et
     * événements, progression, annonces publiées — fusionnés par date.
     */
    public function mine(Request $request)
    {
        $userId = $request->user()->id;
        $items = collect();

        foreach (FormationEnrollment::with('formation')->where('user_id', $userId)->get() as $e) {
            if (! $e->formation) {
                continue;
            }

            $items->push([
                'text' => "Inscription à la formation « {$e->formation->title} »",
                'at' => $e->created_at,
                'color' => '#4F6FBF',
            ]);

            if ($e->completed_at) {
                $items->push([
                    'text' => "Formation « {$e->formation->title} » terminée 🎉",
                    'at' => $e->completed_at,
                    'color' => '#AC0100',
                ]);
            } elseif ($e->progress > 0) {
                $items->push([
                    'text' => "Progression {$e->progress} % — « {$e->formation->title} »",
                    'at' => $e->updated_at,
                    'color' => '#22A85A',
                ]);
            }
        }

        foreach (EventRegistration::with('event')->where('user_id', $userId)->get() as $r) {
            if (! $r->event) {
                continue;
            }

            $items->push([
                'text' => "Inscription confirmée : {$r->event->title}".($r->event->starts_at ? ' du '.$r->event->starts_at->translatedFormat('j F') : ''),
                'at' => $r->created_at,
                'color' => '#031D59',
            ]);
        }

        foreach (Opportunity::where('author_id', $userId)->get() as $o) {
            $items->push([
                'text' => "Votre annonce « {$o->title} » est en ligne",
                'at' => $o->created_at,
                'color' => '#F5A623',
            ]);
        }

        $feed = $items
            ->sortByDesc('at')
            ->take(8)
            ->values()
            ->map(fn (array $item) => [
                'text' => $item['text'],
                'at' => $item['at']?->toIso8601String(),
                'color' => $item['color'],
            ]);

        return response()->json(['ok' => true, 'activity' => $feed]);
    }
}
