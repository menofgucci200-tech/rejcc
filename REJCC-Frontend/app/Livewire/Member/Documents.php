<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Documents & ressources : bibliothèque du réseau (recherche, catégories,
 * aperçu intégré, téléchargement contrôlé).
 */
#[Layout('layouts.member-light')]
class Documents extends Component
{
    #[Url(except: 'bibliotheque')]
    public string $onglet = 'bibliotheque';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: '')]
    public string $categorie = '';

    #[Url(except: 'recents')]
    public string $tri = 'recents';

    /** Document ouvert dans la visionneuse (lien partageable, notification). */
    #[Url(as: 'document', except: null)]
    public ?int $ouvert = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['bibliotheque', 'propositions', 'personnels'], true) ? $o : 'bibliotheque';
        $this->ouvert = null;
    }

    public function setCategorie(string $id): void
    {
        $this->categorie = $this->categorie === $id ? '' : $id;
    }

    public function ouvrir(int $id): void
    {
        $this->ouvert = $id;
    }

    public function fermer(): void
    {
        $this->ouvert = null;
    }

    public function render()
    {
        $data = Api::get('/documents', array_filter([
            'q' => trim($this->recherche), 'categorie' => $this->categorie, 'tri' => $this->tri !== 'recents' ? $this->tri : null,
        ]), Api::token());
        $docs = Collection::make($data['documents'] ?? []);

        // Document ouvert : pris dans la liste, sinon recherché sans filtre (lien direct).
        $doc = $this->ouvert ? $docs->firstWhere('id', $this->ouvert) : null;
        if ($this->ouvert && ! $doc) {
            $doc = collect(Api::get('/documents', [], Api::token())['documents'] ?? [])->firstWhere('id', $this->ouvert);
        }

        return view('livewire.member.documents', [
            'docs' => $docs,
            'categories' => Collection::make($data['categories'] ?? []),
            'propositions' => Collection::make($data['mes_propositions'] ?? []),
            'peutProposer' => (bool) ($data['peut_proposer'] ?? false),
            'doc' => $doc,
            'filtresActifs' => trim($this->recherche) !== '' || $this->categorie !== '',
        ]);
    }
}
