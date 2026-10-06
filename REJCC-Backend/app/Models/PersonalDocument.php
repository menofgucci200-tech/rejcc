<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Document personnel d'un membre (coffre-fort privé). */
class PersonalDocument extends Model
{
    public const TYPES = [
        'cni' => "Carte nationale d'identité",
        'passeport' => 'Passeport',
        'extrait' => "Extrait d'acte de naissance",
        'casier' => 'Casier judiciaire',
        'residence' => 'Certificat de résidence',
        'diplome' => 'Diplôme',
        'cv' => 'CV',
        'attestation' => 'Attestation',
        'autre' => 'Autre document',
    ];

    /** Nombre maximal de documents par membre. */
    public const MAX = 40;

    protected $fillable = [
        'user_id', 'type', 'titre', 'fichier', 'fichier_nom', 'mime', 'octets', 'delivre_le', 'expire_le',
        'partage', 'partage_at', 'consulte_equipe_at', 'rappel_at',
    ];

    protected $casts = [
        'delivre_le' => 'date', 'expire_le' => 'date', 'partage' => 'boolean',
        'partage_at' => 'datetime', 'consulte_equipe_at' => 'datetime', 'rappel_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function libelle(): string
    {
        return $this->titre ?: (self::TYPES[$this->type] ?? 'Document');
    }

    /** valide | bientot (moins de 30 jours) | expire | null (sans date). */
    public function etat(): ?string
    {
        if (! $this->expire_le) {
            return null;
        }

        return match (true) {
            $this->expire_le->lt(today()) => 'expire',
            $this->expire_le->lte(today()->addDays(30)) => 'bientot',
            default => 'valide',
        };
    }

    public function payload(): array
    {
        $o = (int) $this->octets;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? 'Document',
            'titre' => $this->titre,
            'libelle' => $this->libelle(),
            'fichier_nom' => $this->fichier_nom,
            'mime' => $this->mime,
            'apercu' => str_starts_with((string) $this->mime, 'image/') ? 'image' : ($this->mime === 'application/pdf' ? 'pdf' : null),
            'taille' => $o ? ($o >= 1048576 ? number_format($o / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($o / 1024)).' Ko') : null,
            'delivre_le' => $this->delivre_le?->toDateString(),
            'expire_le' => $this->expire_le?->toDateString(),
            'etat' => $this->etat(),
            'partage' => $this->partage,
            'partage_at' => $this->partage_at?->toIso8601String(),
            'consulte_equipe_at' => $this->consulte_equipe_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** Rappel au membre 30 jours avant l'expiration d'une pièce (une seule fois par date). */
    public static function rappelsExpiration(): int
    {
        $n = 0;
        foreach (self::whereNull('rappel_at')->whereNotNull('expire_le')
            ->whereDate('expire_le', '<=', today()->addDays(30))->whereDate('expire_le', '>=', today())->get() as $d) {
            MemberNotification::create([
                'user_id' => $d->user_id, 'type' => 'info',
                'title' => "Votre document expire bientôt : {$d->libelle()}",
                'body' => 'Il expire le '.$d->expire_le->locale('fr')->isoFormat('D MMMM YYYY').'. Pensez à le renouveler puis à remplacer le fichier dans « Mes documents personnels ».',
                'link' => '/espace-membre/documents?onglet=personnels',
            ]);
            $d->update(['rappel_at' => now()]);
            $n++;
        }

        return $n;
    }
}
