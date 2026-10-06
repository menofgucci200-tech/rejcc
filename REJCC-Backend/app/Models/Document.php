<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public const ACCES = [
        'tous' => 'Tous les membres',
        'abonnes' => 'Membres abonnés',
        'mentors' => 'Mentors',
        'groupe' => 'Un groupe sectoriel',
    ];

    protected $fillable = [
        'title', 'description', 'category', 'category_id', 'url', 'size', 'fichier', 'fichier_nom', 'mime', 'octets',
        'acces', 'group_id', 'statut', 'motif', 'auteur_id', 'vues', 'telechargements', 'fichier_maj_at', 'publie_at',
    ];

    protected $casts = ['fichier_maj_at' => 'datetime', 'publie_at' => 'datetime'];

    public function categorie()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function groupe()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function scopePublies(Builder $q): Builder
    {
        return $q->where('statut', 'publie');
    }

    /** Raison pour laquelle ce membre ne peut pas ouvrir le document (null s'il le peut). */
    public function raisonRefus(User $u): ?string
    {
        if ($u->role === 'admin' || $this->auteur_id === $u->id) {
            return null;
        }

        return match ($this->acces) {
            'abonnes' => $u->hasActiveSubscription() ? null : 'Document réservé aux membres à jour de leur abonnement annuel.',
            'mentors' => $u->role === 'mentor' ? null : 'Document réservé aux mentors du réseau.',
            'groupe' => $this->group_id && $u->groups()->whereKey($this->group_id)->exists()
                ? null : 'Document réservé aux membres du groupe « '.($this->groupe?->name ?? 'sectoriel').' ».',
            default => null,
        };
    }

    /** Type lisible à partir du type MIME ou de l'extension. */
    public function typeLabel(): string
    {
        $ext = strtolower(pathinfo((string) ($this->fichier_nom ?: parse_url((string) $this->url, PHP_URL_PATH)), PATHINFO_EXTENSION));
        $mime = (string) $this->mime;

        return match (true) {
            $ext === 'pdf' || $mime === 'application/pdf' => 'PDF',
            in_array($ext, ['doc', 'docx', 'odt'], true) => 'Word',
            in_array($ext, ['xls', 'xlsx', 'ods', 'csv'], true) => 'Excel',
            in_array($ext, ['ppt', 'pptx', 'odp'], true) => 'PowerPoint',
            str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'Image',
            str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov'], true) => 'Vidéo',
            str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true) => 'Audio',
            ! $this->fichier && $this->url && str_starts_with($this->url, 'http') => 'Lien',
            default => 'Fichier',
        };
    }

    public function tailleLisible(): ?string
    {
        if (! $this->octets) {
            // Ancienne taille saisie à la main : gardée seulement si un fichier est joint.
            return $this->fichier && preg_match('/\d/', (string) $this->size) ? $this->size : null;
        }
        $o = $this->octets;

        return $o >= 1048576 ? number_format($o / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($o / 1024)).' Ko';
    }
}
