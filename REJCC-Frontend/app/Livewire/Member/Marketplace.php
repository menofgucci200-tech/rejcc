<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Marketplace des membres : consulter les services/produits proposés par le
 * réseau, contacter un vendeur, et soumettre ses propres annonces (validées
 * par l'administration avant publication).
 */
#[Layout('layouts.member-light')]
class Marketplace extends Component
{
    use HandlesMedia;

    public string $onglet = 'catalogue'; // catalogue | mes-annonces

    public string $recherche = '';

    public string $filtreType = 'tous'; // tous | service | produit

    public string $filtreCategorie = 'toutes';

    public bool $showForm = false;

    // Formulaire d'annonce
    public string $type = 'service';

    public string $title = '';

    public string $category = '';

    public string $description = '';

    public string $price = '';

    public string $contact = '';

    public ?string $message = null;

    public function mount(): void
    {
        $this->contact = Api::user()->telephone ?? '';
    }

    public function setOnglet(string $onglet): void
    {
        if (in_array($onglet, ['catalogue', 'mes-annonces'], true)) {
            $this->onglet = $onglet;
            $this->message = null;
        }
    }

    public function setFiltreType(string $type): void
    {
        $this->filtreType = $type;
    }

    public function setFiltreCategorie(string $categorie): void
    {
        $this->filtreCategorie = $categorie;
    }

    public function openForm(): void
    {
        $this->reset(['type', 'title', 'category', 'description', 'price']);
        $this->type = 'service';
        $this->contact = Api::user()->telephone ?? '';
        $this->clearMedia();
        $this->resetValidation();
        $this->showForm = true;
        $this->message = null;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function soumettre(): void
    {
        $this->validate([
            'type' => 'required|in:service,produit',
            'title' => 'required|string|min:3|max:120',
            'category' => 'required|string|max:60',
            'description' => 'required|string|min:20|max:2000',
            'price' => 'nullable|string|max:80',
            'contact' => 'nullable|string|max:60',
        ], [
            'title.required' => 'Donnez un titre à votre annonce.',
            'category.required' => 'Choisissez une catégorie.',
            'description.min' => 'Décrivez votre offre en quelques phrases (20 caractères minimum).',
        ]);

        $result = Api::post('/marketplace', [
            'type' => $this->type,
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description,
            'price' => $this->price ?: null,
            'contact' => $this->contact ?: null,
            'photo' => $this->mediaUrl ?: null,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('title', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->showForm = false;
        $this->onglet = 'mes-annonces';
        $this->message = 'Annonce soumise ! Elle sera visible sur la Marketplace dès validation par l\'administration.';
    }

    public function retirer(int $id): void
    {
        $result = Api::delete("/marketplace/{$id}", Api::token());

        if ($result['ok'] ?? false) {
            $this->message = 'Annonce retirée.';
        }
    }

    public function render()
    {
        $me = Api::user()->id;

        $data = Api::get('/marketplace', [], Api::token());
        $categories = $data['categories'] ?? [];

        $listings = collect($data['listings'] ?? [])
            ->filter(function (array $l) {
                if ($this->filtreType !== 'tous' && $l['type'] !== $this->filtreType) {
                    return false;
                }
                if ($this->filtreCategorie !== 'toutes' && $l['category'] !== $this->filtreCategorie) {
                    return false;
                }
                if ($this->recherche !== '') {
                    $q = mb_strtolower($this->recherche);

                    return str_contains(mb_strtolower($l['title'].' '.$l['description'].' '.$l['category'].' '.($l['seller']['prenom'] ?? '').' '.($l['seller']['nom'] ?? '')), $q);
                }

                return true;
            })
            ->values();

        // Catégories effectivement présentes (pour le filtre)
        $categoriesActives = collect($data['listings'] ?? [])->pluck('category')->unique()->sort()->values();

        $mesAnnonces = $this->onglet === 'mes-annonces'
            ? collect(Api::get('/marketplace/mine', [], Api::token())['listings'] ?? [])
            : collect();

        return view('livewire.member.marketplace', [
            'listings' => $listings,
            'categories' => $categories,
            'categoriesActives' => $categoriesActives,
            'mesAnnonces' => $mesAnnonces,
            'me' => $me,
        ]);
    }
}
