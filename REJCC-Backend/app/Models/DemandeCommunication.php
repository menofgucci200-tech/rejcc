<?php

namespace App\Models;

use App\Support\DemandesCommunication;
use App\Support\PlanDiffusion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Demande de communication d'un département, et son traitement par la Communication. */
class DemandeCommunication extends Model
{
    protected $table = 'demandes_communication';

    protected $fillable = [
        'demandeur_id', 'departement', 'type', 'titre', 'message', 'date_souhaitee', 'date_evenement',
        'canaux', 'fichiers', 'contact', 'statut', 'statut_at', 'historique', 'motif_refus',
        'date_publication_prevue', 'publication_id', 'lien_publication',
    ];

    protected function casts(): array
    {
        return [
            'canaux' => 'array',
            'fichiers' => 'array',
            'historique' => 'array',
            'date_souhaitee' => 'date',
            'date_evenement' => 'date',
            'date_publication_prevue' => 'date',
            'statut_at' => 'datetime',
        ];
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    private static function nom(?User $u): ?string
    {
        return $u ? (trim("{$u->prenom} {$u->nom}") ?: $u->email) : null;
    }

    /** Change le statut, horodaté et ajouté à l'historique (avec le motif ou le commentaire). */
    public function changerStatut(string $statut, ?User $par = null, ?string $texte = null): void
    {
        $historique = $this->historique ?? [];
        $historique[] = array_filter([
            'type' => 'statut',
            'statut' => $statut,
            'texte' => $texte,
            'at' => now()->toIso8601String(),
            'par' => self::nom($par),
        ], fn ($v) => $v !== null);
        $this->forceFill(['statut' => $statut, 'statut_at' => now(), 'historique' => $historique]);
    }

    /** Réponse ou commentaire de la Communication au demandeur. */
    public function commenter(string $texte, ?User $par = null): void
    {
        $historique = $this->historique ?? [];
        $historique[] = ['type' => 'commentaire', 'texte' => $texte, 'at' => now()->toIso8601String(), 'par' => self::nom($par)];
        $this->forceFill(['historique' => $historique]);
    }

    /** Dernière réponse adressée au demandeur (commentaire, motif de refus…). */
    public function derniereReponse(): ?array
    {
        return collect($this->historique ?? [])->reverse()->first(fn ($h) => ! empty($h['texte']));
    }

    public function libelleStatut(): string
    {
        return DemandesCommunication::libelleStatut($this->statut);
    }

    public function libelleDepartement(): string
    {
        return DemandesCommunication::libelleDepartement($this->departement);
    }

    public function libelleType(): string
    {
        return PlanDiffusion::libelleType($this->type);
    }
}
