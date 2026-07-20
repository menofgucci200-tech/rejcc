<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    protected $fillable = ['registration_event_id', 'prenom', 'nom', 'telephone', 'email', 'is_member'];

    protected $casts = ['is_member' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvent::class, 'registration_event_id');
    }
}
