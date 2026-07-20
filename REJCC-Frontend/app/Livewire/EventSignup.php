<?php

namespace App\Livewire;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Page publique d'inscription à un événement, cible des QR codes. Toute
 * personne (membre ou non) peut s'inscrire tant que les inscriptions sont
 * ouvertes et qu'il reste des places.
 */
#[Layout('layouts.site')]
#[Title('Inscription à l\'événement')]
class EventSignup extends Component
{
    use WithFileUploads;

    public string $slug = '';

    /** Données de l'événement (titre, date, lieu, affiche, champs, état…). */
    public array $event = [];

    public string $prenom = '';

    public string $nom = '';

    public string $telephone = '';

    public string $email = '';

    public bool $is_member = false;

    /** Réponses aux champs personnalisés, par clé de champ. */
    public array $answers = [];

    /** Fichiers uploadés (champs de type « file »), par clé de champ. */
    public array $uploads = [];

    public bool $submitted = false;

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $result = Api::get('/event-signup/'.rawurlencode($slug));
        if (! ($result['ok'] ?? false)) {
            abort(404);
        }

        $this->event = $result['event'];
    }

    public function register(): void
    {
        $fields = $this->event['fields'] ?? [];

        // 1. Upload des fichiers → URL stockée dans les réponses.
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'file' && ! empty($this->uploads[$f['key']])) {
                $this->validate([
                    "uploads.{$f['key']}" => 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp4,webm,mov',
                ], [
                    "uploads.{$f['key']}.max" => 'Le fichier ne doit pas dépasser 20 Mo.',
                    "uploads.{$f['key']}.mimes" => 'Format non pris en charge (image, PDF, Word ou vidéo).',
                ], ["uploads.{$f['key']}" => $f['label']]);

                $path = $this->uploads[$f['key']]->store('evenements/'.date('Y/m'), 'uploads');
                $this->answers[$f['key']] = Storage::disk('uploads')->url($path);
            }
        }

        // 2. Règles de base + règles dynamiques.
        $rules = [
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'telephone' => 'required|string|min:8|max:30',
            'email' => 'nullable|email|max:150',
        ];
        $names = [];
        foreach ($fields as $f) {
            $key = 'answers.'.$f['key'];
            $required = $f['required'] ?? false;
            if (($f['type'] ?? '') === 'checkbox') {
                $rules[$key] = $required ? 'accepted' : 'nullable|boolean';
            } elseif (($f['type'] ?? '') === 'file') {
                $rules[$key] = ($required ? 'required' : 'nullable').'|string';
            } else {
                $rules[$key] = ($required ? 'required' : 'nullable').'|string|max:2000';
            }
            $names[$key] = $f['label'];
        }

        $this->validate($rules, [
            'prenom.required' => 'Indiquez votre prénom.',
            'nom.required' => 'Indiquez votre nom.',
            'telephone.required' => 'Indiquez votre numéro de téléphone / WhatsApp.',
            'telephone.min' => 'Le numéro de téléphone est trop court.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
        ], $names);

        $result = Api::post('/event-signup/'.rawurlencode($this->slug), [
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'email' => $this->email ?: null,
            'is_member' => $this->is_member,
            'answers' => $this->answers,
        ]);

        if (! ($result['ok'] ?? false)) {
            // Événement complet / fermé / doublon : on rafraîchit l'état et on affiche le message.
            if (isset($result['event'])) {
                $this->event = $result['event'];
            }
            $this->addError('telephone', $result['message'] ?? 'L\'inscription a échoué. Réessayez.');

            return;
        }

        $this->event = $result['event'] ?? $this->event;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.event-signup');
    }
}
