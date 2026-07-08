<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('api.guest')->group(function () {
    Volt::route('connexion', 'pages.auth.login')
        ->name('login');
});
