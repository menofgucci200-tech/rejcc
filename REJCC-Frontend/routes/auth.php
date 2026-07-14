<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('api.guest')->group(function () {
    Volt::route('connexion', 'pages.auth.login')
        ->name('login');

    Volt::route('mot-de-passe-oublie', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reinitialiser-mot-de-passe', 'pages.auth.reset-password')
        ->name('password.reset');
});
