<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Content\LegalPages;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rédaction des pages légales (Markdown) : brouillon, aperçu, publication
 * d'une nouvelle version datée, mise hors ligne.
 */
#[Layout('layouts.admin-light')]
class PagesLegales extends Component
{
    #[Url(as: 'page', except: 'mentions-legales')]
    public string $slug = 'mentions-legales';

    public string $titre = '';

    public string $resume = '';

    public string $contenu = '';

    public bool $apercu = false;

    public ?string $message = null;

    public ?string $erreur = null;

    protected function pages(): Collection
    {
        return Collection::make(Api::get('/admin/legal-pages', [], Api::token())['pages'] ?? []);
    }

    public function mount(): void
    {
        $this->charger($this->slug);
    }

    public function charger(string $slug): void
    {
        $page = $this->pages()->firstWhere('slug', $slug) ?? $this->pages()->first();
        if (! $page) {
            return;
        }
        $this->slug = $page['slug'];
        $this->titre = $page['titre'];
        $this->resume = (string) $page['resume'];
        $this->contenu = (string) $page['contenu'];
        $this->apercu = false;
        $this->message = $this->erreur = null;
    }

    protected function enregistrer(array $extra, string $succes): void
    {
        $this->message = $this->erreur = null;
        $result = Api::put("/admin/legal-pages/{$this->slug}", [
            'titre' => trim($this->titre),
            'resume' => trim($this->resume) ?: null,
            'contenu' => $this->contenu,
        ] + $extra, Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Enregistrement impossible, réessayez.';

            return;
        }
        LegalPages::clear();
        $this->message = str_replace(':version', (string) ($result['version'] ?? ''), $succes);
    }

    public function sauvegarder(): void
    {
        $this->enregistrer([], 'Brouillon enregistré : la version en ligne reste inchangée.');
    }

    public function publier(): void
    {
        $this->enregistrer(['publier' => true], 'Version :version publiée sur le site.');
    }

    public function depublier(): void
    {
        $this->enregistrer(['depublier' => true], 'Page retirée : le site affiche « en cours de rédaction ».');
    }

    public function render()
    {
        $pages = $this->pages();

        return view('livewire.admin.pages-legales', [
            'pages' => $pages,
            'courante' => $pages->firstWhere('slug', $this->slug),
            'rendu' => $this->apercu && trim($this->contenu) !== '' ? LegalPages::rendu($this->contenu) : null,
        ]);
    }
}
