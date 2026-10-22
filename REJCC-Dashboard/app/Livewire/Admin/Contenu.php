<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Contenu extends Component
{
    use HandlesMedia;

    /** Onglets : type côté API => libellé. */
    public const ONGLETS = [
        'sectors' => 'Secteurs',
        'testimonials' => 'Témoignages',
        'partners' => 'Partenaires',
        'stats' => 'Chiffres clés',
        'steps' => "Étapes d'adhésion",
        'albums' => 'Albums photos',
        'gallery' => 'Galerie photos',
    ];

    public string $onglet = 'sectors';

    public bool $showForm = false;

    public ?int $editingId = null;

    // Champs (superset de tous les types ; chaque onglet utilise les siens).
    public string $title = '';

    public string $blurb = '';

    public string $items = ''; // un élément par ligne

    public string $icon = '';

    public string $name = '';

    public string $role = '';

    public string $quote = '';

    public string $sector = '';

    public string $label = '';

    public ?int $value = null;

    public string $suffix = '';

    public string $text = '';

    public string $caption = '';

    public string $site_url = '';

    // Albums de la galerie
    public string $dateAlbum = '';

    public string $lieu = '';

    public string $descriptionAlbum = '';

    public bool $publie = true;

    /** Photos envoyées en une fois dans l'album (fichiers temporaires). */
    public array $photosAlbum = [];

    /** Album d'une photo (onglet Galerie photos). */
    public ?int $albumId = null;

    /** Filtre de la liste des photos par album ('' = toutes, 'aucun' = hors album). */
    public string $filtreAlbum = '';

    public function voirPhotosAlbum(int $id): void
    {
        $this->setOnglet('gallery');
        $this->filtreAlbum = (string) $id;
    }

    protected function albums(): Collection
    {
        return Collection::make(Api::get('/admin/site-content/albums', [], Api::token())['items'] ?? []);
    }

    public function setOnglet(string $onglet): void
    {
        if (isset(self::ONGLETS[$onglet])) {
            $this->onglet = $onglet;
            $this->filtreAlbum = '';
            $this->closeForm();
        }
    }

    protected function contenu(): Collection
    {
        return Collection::make(Api::get("/admin/site-content/{$this->onglet}", [], Api::token())['items'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'blurb', 'items', 'icon', 'name', 'role', 'quote', 'sector', 'label', 'value', 'suffix', 'text', 'caption', 'site_url', 'dateAlbum', 'lieu', 'descriptionAlbum', 'publie', 'photosAlbum']);
        // Nouvelle photo : rangée par défaut dans l'album filtré.
        $this->albumId = ctype_digit($this->filtreAlbum) ? (int) $this->filtreAlbum : null;
        $this->clearMedia();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $item = $this->contenu()->firstWhere('id', $id);
        if (! $item) {
            return;
        }

        $this->openCreate();
        $this->editingId = $id;
        $this->title = $item['title'] ?? '';
        $this->blurb = $item['blurb'] ?? '';
        $this->items = implode("\n", $item['items'] ?? []);
        $this->icon = $item['icon'] ?? '';
        $this->name = $item['name'] ?? '';
        $this->role = $item['role'] ?? '';
        $this->quote = $item['quote'] ?? '';
        $this->sector = $item['sector'] ?? '';
        $this->label = $item['label'] ?? '';
        $this->value = $item['value'] ?? null;
        $this->suffix = $item['suffix'] ?? '';
        $this->text = $item['text'] ?? '';
        $this->caption = $item['caption'] ?? '';
        $this->site_url = $item['site_url'] ?? '';
        if ($this->onglet === 'gallery') {
            $this->fillMedia($item['url'] ?? null);
            $this->albumId = $item['album_id'] ?? null;
        }
        if ($this->onglet === 'albums') {
            $this->title = $item['titre'] ?? '';
            $this->dateAlbum = $item['date_evenement'] ?? '';
            $this->lieu = $item['lieu'] ?? '';
            $this->descriptionAlbum = $item['description'] ?? '';
            $this->publie = (bool) ($item['publie'] ?? true);
            $this->fillMedia($item['couverture'] ?? null);
        }
        if ($this->onglet === 'partners') {
            $this->fillMedia($item['logo'] ?? null);
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        switch ($this->onglet) {
            case 'sectors':
                $this->validate([
                    'icon' => 'required|string|max:40',
                    'title' => 'required|string|min:2|max:120',
                    'blurb' => 'required|string|min:5|max:300',
                    'items' => 'required|string|min:2',
                ]);
                $data = [
                    'icon' => $this->icon,
                    'title' => $this->title,
                    'blurb' => $this->blurb,
                    'items' => array_values(array_filter(array_map('trim', explode("\n", $this->items)))),
                ];
                break;

            case 'testimonials':
                $this->validate([
                    'name' => 'required|string|min:2|max:120',
                    'role' => 'required|string|min:2|max:120',
                    'quote' => 'required|string|min:10|max:600',
                ]);
                $data = ['name' => $this->name, 'role' => $this->role, 'quote' => $this->quote];
                break;

            case 'partners':
                $this->validate([
                    'name' => 'required|string|min:2|max:120',
                    'sector' => 'nullable|string|max:120',
                    'site_url' => 'nullable|url|max:500',
                ], [
                    'name.required' => "Indiquez le nom de l'entreprise / organisation.",
                    'site_url.url' => 'Le lien du site doit être une adresse valide (https://…).',
                ]);
                // Logo et site web optionnels : sans logo la vitrine affiche le
                // nom, sans site web le logo n'est pas cliquable.
                $data = [
                    'name' => $this->name,
                    'sector' => $this->sector ?: null,
                    'logo' => $this->mediaUrl ?: null,
                    'site_url' => $this->site_url ?: null,
                ];
                break;

            case 'stats':
                $this->validate([
                    'label' => 'required|string|min:2|max:120',
                    'value' => 'required|integer|min:0',
                    'suffix' => 'nullable|string|max:10',
                ]);
                $data = ['label' => $this->label, 'value' => $this->value, 'suffix' => $this->suffix ?: null];
                break;

            case 'steps':
                $this->validate([
                    'icon' => 'required|string|max:40',
                    'title' => 'required|string|min:2|max:160',
                    'text' => 'required|string|min:5|max:400',
                ]);
                $data = ['icon' => $this->icon, 'title' => $this->title, 'text' => $this->text];
                break;

            case 'gallery':
                if (! $this->mediaUrl) {
                    $this->addError('mediaFile', 'Ajoutez une photo (fichier ou lien).');

                    return;
                }
                $this->validate(['caption' => 'nullable|string|max:200']);
                $data = ['url' => $this->mediaUrl, 'caption' => $this->caption ?: null, 'album_id' => $this->albumId ?: null];
                break;

            case 'albums':
                $this->validate([
                    'title' => 'required|string|min:3|max:160',
                    'dateAlbum' => 'nullable|date',
                    'lieu' => 'nullable|string|max:160',
                    'descriptionAlbum' => 'nullable|string|max:2000',
                    'photosAlbum' => 'array|max:60',
                    'photosAlbum.*' => 'image|max:10240',
                ], [
                    'title.required' => "Donnez un titre à l'album (ex : Assemblée générale 2026).",
                    'photosAlbum.max' => 'Envoyez au plus 60 photos à la fois.',
                    'photosAlbum.*.image' => 'Seules les images (JPG, PNG, WebP) peuvent être ajoutées.',
                    'photosAlbum.*.max' => 'Chaque photo doit faire moins de 10 Mo.',
                ]);
                $data = [
                    'titre' => $this->title,
                    'date_evenement' => $this->dateAlbum ?: null,
                    'lieu' => $this->lieu ?: null,
                    'description' => $this->descriptionAlbum ?: null,
                    'couverture' => $this->mediaUrl ?: null,
                    'publie' => $this->publie,
                ];
                break;

            default:
                return;
        }

        $token = Api::token();
        $res = $this->editingId
            ? Api::put("/admin/site-content/{$this->onglet}/{$this->editingId}", $data, $token)
            : Api::post("/admin/site-content/{$this->onglet}", $data, $token);

        if (! ($res['ok'] ?? false)) {
            $this->dispatch('rj-toast', type: 'erreur', message: $res['message'] ?? "L'enregistrement a échoué.");

            return;
        }

        if ($this->onglet === 'albums' && $this->photosAlbum) {
            $albumId = $res['item']['id'];
            $ajoutees = 0;
            foreach ($this->photosAlbum as $fichier) {
                $path = $fichier->store('galerie/'.date('Y/m'), 'uploads');
                $ok = Api::post('/admin/site-content/gallery', [
                    'album_id' => $albumId,
                    'url' => Storage::disk('uploads')->url($path),
                ], $token)['ok'] ?? false;
                $ajoutees += $ok ? 1 : 0;
            }
            $this->dispatch('rj-toast', type: 'succes', message: $ajoutees.' photo'.($ajoutees > 1 ? 's ajoutées' : ' ajoutée')." à l'album.");
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/site-content/{$this->onglet}/{$id}", Api::token());
    }

    public function render()
    {
        return view('livewire.admin.contenu', [
            'onglets' => self::ONGLETS,
            // « elements » et non « items » : la propriété publique $items
            // (textarea des filières) écraserait la variable de vue.
            'elements' => $this->elements(),
            'listeAlbums' => in_array($this->onglet, ['gallery', 'albums'], true) ? $this->albums() : collect(),
        ]);
    }

    protected function elements(): Collection
    {
        $elements = $this->contenu();
        if ($this->onglet === 'gallery' && $this->filtreAlbum !== '') {
            $elements = $elements->filter(fn ($p) => $this->filtreAlbum === 'aucun'
                ? empty($p['album_id'])
                : (string) ($p['album_id'] ?? '') === $this->filtreAlbum)->values();
        }

        return $elements;
    }
}
