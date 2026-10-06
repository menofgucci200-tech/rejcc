<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'user_id', 'billet', 'present_at', 'rappel_at'];

    protected $casts = ['present_at' => 'datetime', 'rappel_at' => 'datetime'];

    protected static function booted(): void
    {
        // Chaque inscription reçoit un billet (code de son QR) pour le pointage.
        static::creating(function (self $r) {
            if (! $r->billet) {
                do {
                    $code = 'B-'.strtoupper(Str::random(8));
                } while (static::where('billet', $code)->exists());
                $r->billet = $code;
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
