<?php

namespace App\Models;

use App\Support\CathoImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Réponse au formulaire CathoImpact et son suivi par la Communication. */
class CathoImpactCandidature extends Model
{
    protected $table = 'cathoimpact_candidatures';

    protected $fillable = [
        'profil', 'nom_complet', 'email', 'whatsapp', 'entreprise', 'fonction', 'secteur', 'ville',
        'parcours', 'inspiration', 'lien', 'photo', 'consentement_texte', 'consentement_at',
        'statut', 'statut_at', 'historique', 'community_manager_id', 'community_manager',
        'lien_questionnaire', 'lien_publication', 'note_interne',
    ];

    protected function casts(): array
    {
        return [
            'historique' => 'array',
            'consentement_at' => 'datetime',
            'statut_at' => 'datetime',
        ];
    }

    public function communityManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'community_manager_id');
    }

    /** Change le statut, horodaté et ajouté à l'historique. */
    public function changerStatut(string $statut, ?User $par = null): void
    {
        if ($statut === $this->statut && $this->statut_at) {
            return;
        }
        $historique = $this->historique ?? [];
        $historique[] = [
            'statut' => $statut,
            'at' => now()->toIso8601String(),
            'par' => $par ? (trim("{$par->prenom} {$par->nom}") ?: $par->email) : null,
        ];
        $this->forceFill(['statut' => $statut, 'statut_at' => now(), 'historique' => $historique]);
    }

    /** Nom du community manager attribué (compte de l'équipe ou texte libre). */
    public function nomCommunityManager(): ?string
    {
        if ($this->communityManager) {
            return trim("{$this->communityManager->prenom} {$this->communityManager->nom}") ?: $this->communityManager->email;
        }

        return $this->community_manager ?: null;
    }

    public function libelleStatut(): string
    {
        return CathoImpact::libelleStatut($this->statut);
    }
}
