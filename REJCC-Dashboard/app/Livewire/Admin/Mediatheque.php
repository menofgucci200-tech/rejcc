<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Médiathèque : tous les fichiers uploadés sur le site (contenus, annonces,
 * bibliothèque) en un seul endroit — aperçu, copie du lien pour réutilisation
 * dans n'importe quel formulaire, suppression, upload direct.
 * Les documents des membres (photos de profil, pièces d'identité) sont
 * volontairement exclus : ils se gèrent depuis les profils.
 */
#[Layout('layouts.admin-light')]
class Mediatheque extends Component
{
    use WithFileUploads;

    /** @var array<int, \Illuminate\Http\UploadedFile> */
    public array $fichiers = [];

    public string $filtre = 'tous'; // tous | images | videos | documents

    public ?string $message = null;

    public function updatedFichiers(): void
    {
        $this->validate([
            'fichiers.*' => 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,mp4,webm,mov,mp3,wav',
        ], [], ['fichiers.*' => 'fichier']);

        foreach ($this->fichiers as $fichier) {
            $fichier->store('bibliotheque/'.date('Y/m'), 'uploads');
        }

        $count = count($this->fichiers);
        $this->fichiers = [];
        $this->message = $count > 1 ? "{$count} fichiers ajoutés à la médiathèque." : 'Fichier ajouté à la médiathèque.';
    }

    public function setFiltre(string $filtre): void
    {
        if (in_array($filtre, ['tous', 'images', 'videos', 'documents'], true)) {
            $this->filtre = $filtre;
        }
    }

    public function supprimer(string $path): void
    {
        // Sécurité : uniquement dans les dossiers de contenu, jamais membres/.
        if (str_starts_with($path, 'membres/') || str_contains($path, '..')) {
            return;
        }

        Storage::disk('uploads')->delete($path);
        $this->message = 'Fichier supprimé. Pensez à retirer les contenus qui l\'utilisaient.';
    }

    public function render()
    {
        $disk = Storage::disk('uploads');

        $files = collect($disk->allFiles())
            ->reject(fn (string $p) => str_starts_with($p, 'membres/') || str_starts_with(basename($p), '.'))
            ->map(function (string $p) use ($disk) {
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));

                return [
                    'path' => $p,
                    'name' => basename($p),
                    'url' => $disk->url($p),
                    'size' => $this->humanSize($disk->size($p)),
                    'modified' => $disk->lastModified($p),
                    'kind' => match (true) {
                        in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true) => 'images',
                        in_array($ext, ['mp4', 'webm', 'mov', 'm4v'], true) => 'videos',
                        default => 'documents',
                    },
                ];
            })
            ->sortByDesc('modified')
            ->values();

        $compteurs = [
            'tous' => $files->count(),
            'images' => $files->where('kind', 'images')->count(),
            'videos' => $files->where('kind', 'videos')->count(),
            'documents' => $files->where('kind', 'documents')->count(),
        ];

        if ($this->filtre !== 'tous') {
            $files = $files->where('kind', $this->filtre)->values();
        }

        return view('livewire.admin.mediatheque', [
            'files' => $files->take(96),
            'compteurs' => $compteurs,
        ]);
    }

    private function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_048_576 => round($bytes / 1_048_576, 1).' Mo',
            $bytes >= 1_024 => round($bytes / 1_024).' Ko',
            default => $bytes.' o',
        };
    }
}
