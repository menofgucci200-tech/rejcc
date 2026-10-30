<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Réaction (émoji) d'un membre à un message de salon. */
class SalonReaction extends Model
{
    /** Réactions proposées, comme sur WhatsApp. */
    public const EMOJIS = ['👍', '🙏', '❤️', '😂', '😮', '👏'];

    protected $fillable = ['salon_message_id', 'user_id', 'emoji'];
}
