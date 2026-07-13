<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * Champ média réutilisable pour les formulaires de création :
 * upload d'un fichier (image, PDF, bureautique, audio, vidéo) OU lien collé.
 * Le fichier est stocké sur le disque « uploads » (public/uploads, sans
 * symlink) et son URL publique est renvoyée à l'API.
 */
trait HandlesMedia
{
    use WithFileUploads;

    /** Fichier temporaire en cours d'upload (wire:model). */
    public $mediaFile = null;

    /** URL finale du média (résultat de l'upload, ou lien saisi manuellement). */
    public string $mediaUrl = '';

    /** Libellé lisible (nom de fichier). */
    public string $mediaName = '';

    /** Taille lisible (ex. « 2.4 Mo »). */
    public string $mediaSize = '';

    public function updatedMediaFile(): void
    {
        $this->validate([
            'mediaFile' => 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,mp4,webm,mov,mp3,wav',
        ], [], ['mediaFile' => 'fichier']);

        $path = $this->mediaFile->store('media/'.date('Y/m'), 'uploads');

        $this->mediaUrl = Storage::disk('uploads')->url($path);
        $this->mediaName = $this->mediaFile->getClientOriginalName();
        $this->mediaSize = $this->humanSize($this->mediaFile->getSize());
        $this->mediaFile = null;
    }

    public function clearMedia(): void
    {
        $this->mediaFile = null;
        $this->mediaUrl = '';
        $this->mediaName = '';
        $this->mediaSize = '';
    }

    protected function fillMedia(?string $url, ?string $name = null, ?string $size = null): void
    {
        $this->mediaUrl = $url ?? '';
        $this->mediaName = $name ?? ($url ? basename(parse_url($url, PHP_URL_PATH) ?: $url) : '');
        $this->mediaSize = $size ?? '';
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1).' Mo';
        }

        return max(1, round($bytes / 1024)).' Ko';
    }
}
