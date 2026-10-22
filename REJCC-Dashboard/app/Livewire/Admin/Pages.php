<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Content\PageCatalog;
use App\Support\Content\SiteRemote;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pages du site vitrine : édition des textes de chaque section, affichage /
 * masquage des sections et référencement (SEO) par page. Publié immédiatement.
 */
#[Layout('layouts.admin-light')]
class Pages extends Component
{
    public string $page = 'home';

    public ?string $editingSection = null;

    /** Valeurs du formulaire de la section en cours d'édition. */
    public array $fields = [];

    public string $seoTitle = '';

    public string $seoDescription = '';

    public ?string $message = null;

    public function mount(): void
    {
        $this->loadSeo();
    }

    public function selectPage(string $page): void
    {
        if (! isset(PageCatalog::pages()[$page])) {
            return;
        }

        $this->page = $page;
        $this->editingSection = null;
        $this->message = null;
        $this->loadSeo();
    }

    public function toggleSection(string $section): void
    {
        $def = PageCatalog::pages()[$this->page]['sections'][$section] ?? null;
        if (! $def || ($def['locked'] ?? false)) {
            return;
        }

        $visible = SiteRemote::visible($this->page, $section);

        $result = Api::put("/admin/page-sections/{$this->page}/{$section}", [
            'visible' => ! $visible,
        ], Api::token());

        if ($result['ok'] ?? false) {
            SiteRemote::clear();
            $this->message = $visible ? 'Section masquée sur le site.' : 'Section de nouveau visible.';
        }
    }

    public function openEdit(string $section): void
    {
        $def = PageCatalog::pages()[$this->page]['sections'][$section] ?? null;
        if (! $def || $def['fields'] === []) {
            return;
        }

        $this->fields = [];
        foreach ($def['fields'] as $key => $field) {
            $this->fields[$key] = (string) SiteRemote::field($this->page, $section, $key, $field['default']);
        }

        $this->editingSection = $section;
        $this->message = null;
    }

    public function closeEdit(): void
    {
        $this->editingSection = null;
        $this->fields = [];
    }

    public function saveSection(): void
    {
        if (! $this->editingSection) {
            return;
        }

        $def = PageCatalog::pages()[$this->page]['sections'][$this->editingSection] ?? null;
        if (! $def) {
            return;
        }

        // Un champ identique au texte d'origine n'est pas stocké : la vitrine
        // garde sa valeur codée et l'admin peut « revenir au texte d'origine »
        // simplement en vidant le champ.
        $content = [];
        foreach ($def['fields'] as $key => $field) {
            $value = trim((string) ($this->fields[$key] ?? ''));
            if ($value !== '' && $value !== $field['default']) {
                $content[$key] = $value;
            }
        }

        $result = Api::put("/admin/page-sections/{$this->page}/{$this->editingSection}", [
            'content' => $content,
        ], Api::token());

        if ($result['ok'] ?? false) {
            SiteRemote::clear();
            $this->message = 'Section publiée.';
            $this->closeEdit();
        } else {
            $this->addError('fields', $result['message'] ?? 'Une erreur est survenue.');
        }
    }

    public function saveSeo(): void
    {
        $this->validate([
            'seoTitle' => 'nullable|string|max:70',
            'seoDescription' => 'nullable|string|max:170',
        ]);

        $result = Api::put('/admin/site-settings', ['settings' => [
            "seo.{$this->page}.title" => $this->seoTitle,
            "seo.{$this->page}.description" => $this->seoDescription,
        ]], Api::token());

        if ($result['ok'] ?? false) {
            SiteRemote::clear();
            $this->message = 'Référencement publié.';
        }
    }

    private function loadSeo(): void
    {
        $this->seoTitle = (string) SiteRemote::setting("seo.{$this->page}.title", '');
        $this->seoDescription = (string) SiteRemote::setting("seo.{$this->page}.description", '');
    }

    public function render()
    {
        $pages = PageCatalog::pages();
        $current = $pages[$this->page];

        // État effectif de chaque section (visibilité + textes modifiés ?)
        $sections = [];
        foreach ($current['sections'] as $key => $def) {
            $overridden = false;
            foreach (array_keys($def['fields']) as $fieldKey) {
                if (SiteRemote::field($this->page, $key, $fieldKey) !== null) {
                    $overridden = true;
                    break;
                }
            }

            $sections[] = [
                'key' => $key,
                'label' => $def['label'],
                'hint' => $def['hint'] ?? null,
                'locked' => $def['locked'] ?? false,
                'editable' => $def['fields'] !== [],
                'fields' => $def['fields'],
                'visible' => SiteRemote::visible($this->page, $key),
                'overridden' => $overridden,
            ];
        }

        return view('livewire.admin.pages', [
            'pages' => $pages,
            'current' => $current,
            'sections' => $sections,
        ]);
    }
}
