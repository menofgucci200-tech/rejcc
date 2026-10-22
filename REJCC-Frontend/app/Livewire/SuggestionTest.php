<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Component;

/**
 * Comptes de test : bouton flottant « Signaler un bug ou suggérer ». Le
 * retour est rattaché au département du compte, à la page et au navigateur
 * d'où il part. Consultation : Administration → Retours des testeurs.
 */
class SuggestionTest extends Component
{
    public const CATEGORIES = [
        'bug' => 'Bug ou erreur',
        'amelioration' => 'Amélioration',
        'besoin' => 'Besoin du département',
        'question' => 'Question',
    ];

    public string $page = '';

    public string $categorie = 'bug';

    public string $message = '';

    public ?string $erreur = null;

    public bool $envoyee = false;

    public int $envoyees = 0;

    public function mount(): void
    {
        $this->page = mb_substr(request()->getRequestUri(), 0, 500);
    }

    public function envoyer(): void
    {
        $this->erreur = null;
        $r = Api::post('/suggestions', [
            'categorie' => $this->categorie,
            'message' => trim($this->message),
            'page' => $this->page,
        ], Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Le retour n\'a pas pu être envoyé. Réessayez dans un instant.';

            return;
        }

        $this->reset('message');
        $this->categorie = 'bug';
        $this->envoyee = true;
        $this->envoyees++;
    }

    public function nouvelle(): void
    {
        $this->envoyee = false;
    }

    public function render()
    {
        return view('livewire.suggestion-test', [
            'connecte' => (bool) Api::token(),
            'departement' => session('api_user.departement'),
        ]);
    }
}
