<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NewsController;
use App\Livewire\Admin\Adhesions as AdminAdhesions;
use App\Livewire\Admin\Contacts as AdminContacts;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Documents as AdminDocuments;
use App\Livewire\Admin\Members as AdminMembers;
use App\Livewire\Admin\MembershipApplications as AdminMembershipApplications;
use App\Livewire\Admin\Notifications as AdminNotifications;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Member\Directory as MemberDirectory;
use App\Livewire\Member\Documents as MemberDocuments;
use App\Livewire\Member\Messaging as MemberMessaging;
use App\Livewire\Member\Notifications as MemberNotifications;
use App\Livewire\Member\ProfileEditor as MemberProfileEditor;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/a-propos', 'pages.a-propos');
Route::view('/activites', 'pages.activites');
Route::view('/domaines', 'pages.domaines');

Route::get('/evenements', [EventController::class, 'index']);
Route::get('/evenements/{slug}', [EventController::class, 'show']);

Route::get('/actualites', [NewsController::class, 'index']);
Route::get('/actualites/{slug}', [NewsController::class, 'show']);

Route::view('/partenaires', 'pages.partenaires');
Route::view('/adhesion', 'pages.adhesion');
Route::view('/contact', 'pages.contact');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('espace-membre')->name('espace-membre.')->group(function () {
    Route::get('/', MemberDashboard::class)->name('dashboard');
    Route::get('/annuaire', MemberDirectory::class)->name('directory');
    Route::get('/messagerie', MemberMessaging::class)->name('messaging');
    Route::get('/notifications', MemberNotifications::class)->name('notifications');
    Route::get('/documents', MemberDocuments::class)->name('documents');
    Route::get('/profil', MemberProfileEditor::class)->name('profile');
});

Route::middleware(['auth', 'admin.web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/membres', AdminMembers::class)->name('members');
    Route::get('/adhesions', AdminAdhesions::class)->name('adhesions');
    Route::get('/candidatures', AdminMembershipApplications::class)->name('candidatures');
    Route::get('/contacts', AdminContacts::class)->name('contacts');
    Route::get('/documents', AdminDocuments::class)->name('documents');
    Route::get('/notifications', AdminNotifications::class)->name('notifications');
});

require __DIR__.'/auth.php';
