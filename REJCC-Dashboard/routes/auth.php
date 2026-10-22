<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Connexion propre au dashboard : réservée aux comptes d'administration
// (administrateur général et comptes des départements). Mot de passe oublié :
// sur le site (même compte), voir la page de connexion.
Route::middleware('api.guest')->group(function () {
    Volt::route('connexion', 'pages.auth.login')->name('login');
});
