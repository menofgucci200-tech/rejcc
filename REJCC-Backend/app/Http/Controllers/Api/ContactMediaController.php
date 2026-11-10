<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMedia;
use App\Models\User;
use App\Support\OutilsCommunication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Fichier des contacts médias du département Communication (journalistes,
 * radios, influenceurs, institutions…). Données personnelles : rubrique
 * « communication » seulement, et un compte de test ne voit que les
 * contacts qu'il a lui-même saisis.
 */
class ContactMediaController extends Controller
{
    /** Contacts visibles par ce compte (tous, sauf pour un compte de test). */
    public static function visibles(User $u): Builder
    {
        return ContactMedia::query()->when($u->estTesteur(), fn ($q) => $q->where('auteur_id', $u->id));
    }

    private function payload(ContactMedia $c): array
    {
        return [
            ...$c->only(['id', 'nom', 'media', 'type', 'fonction', 'email', 'telephone', 'ville', 'notes']),
            'type_label' => OutilsCommunication::TYPES_CONTACTS[$c->type] ?? 'Autre',
            'dernier_contact' => $c->dernier_contact?->toDateString(),
            'updated_at' => $c->updated_at?->toIso8601String(),
        ];
    }

    /** GET /admin/communication/contacts-medias?type=&q= */
    public function index(Request $request)
    {
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $contacts = self::visibles($request->user())
            ->when(isset(OutilsCommunication::TYPES_CONTACTS[$type]), fn ($r) => $r->where('type', $type))
            ->when($q !== '', fn ($r) => $r->where(fn ($w) => $w->where('nom', 'like', "%{$q}%")->orWhere('media', 'like', "%{$q}%")
                ->orWhere('fonction', 'like', "%{$q}%")->orWhere('ville', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%")))
            ->orderBy('nom')->get();

        return response()->json([
            'ok' => true,
            'contacts' => $contacts->map(fn ($c) => $this->payload($c))->values(),
            'types' => OutilsCommunication::TYPES_CONTACTS,
            'compteurs' => self::visibles($request->user())->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type'),
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $c = ContactMedia::create($d + ['auteur_id' => $request->user()->id]);

        return response()->json(['ok' => true, 'contact' => $this->payload($c)], 201);
    }

    public function update(Request $request, int $id)
    {
        $c = self::visibles($request->user())->find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Contact introuvable.'], 404);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $c->fill($d)->save();

        return response()->json(['ok' => true, 'contact' => $this->payload($c)]);
    }

    public function destroy(Request $request, int $id)
    {
        $c = self::visibles($request->user())->find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Contact introuvable.'], 404);
        }
        $c->delete();

        return response()->json(['ok' => true]);
    }

    private function valider(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'media' => 'nullable|string|max:150',
            'type' => ['required', Rule::in(array_keys(OutilsCommunication::TYPES_CONTACTS))],
            'fonction' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'telephone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\s-]{6,40}$/'],
            'ville' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:3000',
            'dernier_contact' => 'nullable|date|before_or_equal:today',
        ], [
            'nom.required' => 'Indiquez le nom du contact.',
            'nom.max' => 'Le nom ne doit pas dépasser 150 caractères.',
            'type.required' => 'Choisissez un type de contact.',
            'type.in' => 'Type de contact inconnu.',
            'email.email' => "L'adresse e-mail n'est pas valide.",
            'telephone.regex' => 'Le numéro de téléphone ne doit contenir que des chiffres, espaces, +, - ou parenthèses.',
            'dernier_contact.date' => 'La date du dernier contact est invalide.',
            'dernier_contact.before_or_equal' => 'La date du dernier contact ne peut pas être dans le futur.',
            'notes.max' => 'Les notes ne doivent pas dépasser 3 000 caractères.',
            '*.max' => 'Un des champs est trop long.',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }

        return collect($v->validated())->map(fn ($x) => is_string($x) ? (trim($x) ?: null) : $x)->all();
    }
}
