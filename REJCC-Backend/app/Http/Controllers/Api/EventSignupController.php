<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InscriptionEvenementConfirmee;
use App\Models\EventParticipant;
use App\Models\RegistrationEvent;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Inscription publique à un événement (cible des QR codes). N'importe qui
 * peut s'inscrire — membre ou non — pour permettre d'estimer le nombre de
 * participants et de constituer une base de données solide.
 */
class EventSignupController extends Controller
{
    /** Détails de l'événement + état des inscriptions (page scannée). */
    public function show(string $slug)
    {
        $event = RegistrationEvent::where('slug', $slug)->first();
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'event' => $this->payload($event)]);
    }

    /** Enregistre un participant. */
    public function register(Request $request, string $slug)
    {
        $event = RegistrationEvent::where('slug', $slug)->first();
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        if (! $event->is_open) {
            return response()->json(['ok' => false, 'message' => 'Les inscriptions pour cet événement sont fermées.'], 422);
        }

        if ($event->isFull()) {
            return response()->json(['ok' => false, 'message' => 'Toutes les places ont été réservées. Les inscriptions sont complètes.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'telephone' => 'required|string|min:8|max:30',
            'email' => 'nullable|email|max:150',
            'is_member' => 'nullable|boolean',
        ], [
            'prenom.required' => 'Indiquez votre prénom.',
            'nom.required' => 'Indiquez votre nom.',
            'telephone.required' => 'Indiquez votre numéro de téléphone / WhatsApp.',
            'telephone.min' => 'Le numéro de téléphone est trop court.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();

        // Anti-doublon : une même personne (par téléphone) ne s'inscrit qu'une fois.
        $exists = EventParticipant::where('registration_event_id', $event->id)
            ->where('telephone', $d['telephone'])
            ->exists();
        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Ce numéro est déjà inscrit à cet événement.'], 422);
        }

        // Nouvelle vérification de capacité juste avant l'insertion (anti-course).
        if ($event->isFull()) {
            return response()->json(['ok' => false, 'message' => 'Toutes les places ont été réservées entre-temps.'], 422);
        }

        $participant = EventParticipant::create([
            'registration_event_id' => $event->id,
            'prenom' => $d['prenom'],
            'nom' => $d['nom'],
            'telephone' => $d['telephone'],
            'email' => $d['email'] ?? null,
            'is_member' => (bool) ($d['is_member'] ?? false),
        ]);

        // E-mail de confirmation (uniquement si une adresse a été fournie).
        // Envoi best-effort APRÈS la réponse HTTP (cf. App\Support\Mailer).
        Mailer::send($participant->email, new InscriptionEvenementConfirmee($participant, $event));

        return response()->json([
            'ok' => true,
            'message' => 'Votre inscription est confirmée. À bientôt !',
            'event' => $this->payload($event->fresh()),
        ]);
    }

    private function payload(RegistrationEvent $event): array
    {
        $count = $event->participants()->count();

        return [
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'capacity' => $event->capacity,
            'count' => $count,
            'remaining' => $event->capacity !== null ? max(0, $event->capacity - $count) : null,
            'is_open' => $event->is_open,
            'is_full' => $event->isFull(),
            'accepts' => $event->acceptsRegistrations(),
        ];
    }
}
