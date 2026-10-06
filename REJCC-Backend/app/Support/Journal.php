<?php

namespace App\Support;

use App\Models\AccountEvent;
use App\Models\User;
use Illuminate\Http\Request;

/** Journal du compte : ce qui touche à la sécurité et aux données du membre. */
class Journal
{
    /** $r = false : sans adresse ni appareil (action de l'équipe, pas du membre). */
    public static function noter(User|int $user, string $type, ?string $detail = null, Request|false|null $r = null): void
    {
        $r = $r === false ? null : ($r ?? request());
        AccountEvent::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type' => $type,
            'detail' => $detail ? mb_substr($detail, 0, 255) : null,
            'ip' => $r ? Client::ip($r) : null,
            'agent' => $r ? Client::agent($r) : null,
            'created_at' => now(),
        ]);
    }
}
