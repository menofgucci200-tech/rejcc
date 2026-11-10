<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tour de rôle de la Communication : un responsable pour un bloc de jours. */
class CommunicationTour extends Model
{
    protected $table = 'communication_tours';

    protected $fillable = ['du', 'au', 'responsable_id', 'responsable', 'cree_par'];

    protected $casts = ['du' => 'date', 'au' => 'date'];

    /** Dates enregistrées en « AAAA-MM-JJ » (comparaisons fiables sur SQLite comme sur MySQL). */
    public function setDuAttribute($valeur): void
    {
        $this->attributes['du'] = \Illuminate\Support\Carbon::parse($valeur)->toDateString();
    }

    public function setAuAttribute($valeur): void
    {
        $this->attributes['au'] = \Illuminate\Support\Carbon::parse($valeur)->toDateString();
    }

    public function responsableCompte(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function nomResponsable(): ?string
    {
        if ($this->responsableCompte) {
            return trim("{$this->responsableCompte->prenom} {$this->responsableCompte->nom}") ?: $this->responsableCompte->email;
        }

        return $this->responsable ?: null;
    }
}
