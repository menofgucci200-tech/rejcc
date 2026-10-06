<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Abonnement aux notifications sur un appareil (Web Push). */
class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'agent', 'last_success_at'];

    protected $casts = ['last_success_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
