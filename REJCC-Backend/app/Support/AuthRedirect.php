<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AuthRedirect
{
    public static function target(User $user): string
    {
        if ($user->role === 'admin' && Route::has('admin.dashboard')) {
            return route('admin.dashboard', absolute: false);
        }

        if (Route::has('espace-membre.dashboard')) {
            return route('espace-membre.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }
}
