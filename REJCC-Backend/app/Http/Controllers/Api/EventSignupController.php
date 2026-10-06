<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InscriptionEvenementConfirmee;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Inscription publique à un événement (cible des QR codes), ouverte quand
 * l'admin a activé « Inscription publique ». Les invités rejoignent la même
 * liste que les membres : même capacité, même billet, même pointage.
 */
class EventSignupController extends Controller
{
    /** Détails de l'événement + état des inscriptions (page scannée). */
    public function show(string $slug)
    {
        $event = Event::parSlug($slug);
        if (! $event || $event->statut === 'brouillon' && ! $event->inscription_publique) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'event' => $this->payload($event)]);
    }

    /** Enregistre un invité. */
    public function register(Request $request, string $slug)
    {
        $event = Event::parSlug($slug);
        if (! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        if ($raison = $event->raisonRefus(null)) {
            return response()->json(['ok' => false, 'message' => $raison, 'event' => $this->payload($event)], 422);
        }

        // Règles de base + règles dynamiques issues des questions de l'événement.
        $rules = [
            'prenom' => ['required', 'string', 'min:2', 'max:80'],
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'telephone' => ['required', 'string', 'min:8', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'is_member' => ['nullable', 'boolean'],
        ];
        $names = [];
        foreach ($event->champs ?? [] as $f) {
            $key = 'answers.'.$f['key'];
            $required = $f['required'] ?? false;

            if ($f['type'] === 'checkbox') {
                $rules[$key] = $required ? ['accepted'] : ['nullable', 'boolean'];
            } elseif ($f['type'] === 'file') {
                $rules[$key] = [$required ? 'required' : 'nullable', 'url', 'max:500'];
            } else {
                $r = [$required ? 'required' : 'nullable', 'string', 'max:2000'];
                if ($f['type'] === 'select' && ! empty($f['options'])) {
                    $r[] = Rule::in($f['options']);
                }
                $rules[$key] = $r;
            }
            $names[$key] = $f['label'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'prenom.required' => 'Indiquez votre prénom.',
            'nom.required' => 'Indiquez votre nom.',
            'telephone.required' => 'Indiquez votre numéro de téléphone.',
            'telephone.min' => 'Le numéro de téléphone est trop court.',
        ]);
        $validator->setAttributeNames($names);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        $telephone = preg_replace('/\s+/', '', $d['telephone']);

        // Anti-doublon : une même personne (par téléphone) ne s'inscrit qu'une fois.
        $doublon = EventRegistration::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('telephone', $telephone)->orWhereHas('user', fn ($u) => $u->where('telephone', $telephone)))
            ->exists();
        if ($doublon) {
            return response()->json(['ok' => false, 'message' => 'Ce numéro est déjà inscrit à cet événement.'], 422);
        }

        // Nouvelle vérification de capacité juste avant l'insertion (anti-course).
        if ($event->placesRestantes() === 0) {
            return response()->json(['ok' => false, 'message' => 'Toutes les places ont été réservées entre-temps.', 'event' => $this->payload($event)], 422);
        }

        $inscription = EventRegistration::create([
            'event_id' => $event->id,
            'prenom' => $d['prenom'],
            'nom' => $d['nom'],
            'telephone' => $telephone,
            'email' => $d['email'] ?? null,
            'se_dit_membre' => (bool) ($d['is_member'] ?? false),
            'reponses' => $this->collectAnswers($event, $request) ?: null,
        ]);

        // E-mail de confirmation avec le billet (si une adresse a été fournie).
        Mailer::send($inscription->email, new InscriptionEvenementConfirmee($inscription, $event));

        return response()->json([
            'ok' => true,
            'message' => 'Votre inscription est confirmée. À bientôt !',
            'billet' => $inscription->billet,
            'event' => $this->payload($event),
        ]);
    }

    /** GET /billet/{code} — billet d'un invité (QR présenté à l'entrée). */
    public function billet(string $code)
    {
        $r = EventRegistration::with('event', 'user:id,prenom,nom')->where('billet', strtoupper($code))->first();
        if (! $r || ! $r->event) {
            return response()->json(['ok' => false, 'message' => 'Billet introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'billet' => [
            'code' => $r->billet,
            'nom' => $r->nomComplet(),
            'present' => (bool) $r->present_at,
            'event' => $this->payload($r->event),
        ]]);
    }

    /** Ne conserve que les réponses aux questions définies, typées proprement. */
    private function collectAnswers(Event $event, Request $request): array
    {
        $answers = [];
        foreach ($event->champs ?? [] as $f) {
            $val = $request->input('answers.'.$f['key']);
            if ($f['type'] === 'checkbox') {
                if ($request->has('answers.'.$f['key'])) {
                    $answers[$f['key']] = (bool) $val;
                }

                continue;
            }
            if ($val !== null && $val !== '') {
                $answers[$f['key']] = $val;
            }
        }

        return $answers;
    }

    private function payload(Event $event): array
    {
        $count = $event->nbInscrits();
        $refus = $event->raisonRefus(null);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'poster' => $event->image,
            'fields' => $event->champs ?? [],
            'location' => $event->en_ligne ? 'En ligne' : $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'time_label' => $event->time_label,
            'registration_deadline' => $event->date_limite?->toIso8601String(),
            'capacity' => $event->capacity,
            'count' => $count,
            'remaining' => $event->capacity !== null ? max(0, $event->capacity - $count) : null,
            'statut' => $event->statut,
            'motif_annulation' => $event->motif_annulation,
            'is_open' => $event->inscriptions_ouvertes,
            'is_full' => $event->capacity !== null && $count >= $event->capacity,
            'is_past_deadline' => $event->date_limite !== null && $event->date_limite->isPast(),
            'inscription_publique' => $event->inscription_publique,
            'accepts' => $refus === null,
            'refus' => $refus,
        ];
    }
}
