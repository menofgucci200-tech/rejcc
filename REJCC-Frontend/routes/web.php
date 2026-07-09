<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NewsController;
use App\Livewire\Admin\Actualites as AdminActualites;
use App\Livewire\Admin\Adhesions as AdminAdhesions;
use App\Livewire\Admin\Audit as AdminAudit;
use App\Livewire\Admin\Certificats as AdminCertificats;
use App\Livewire\Admin\Communaute as AdminCommunaute;
use App\Livewire\Admin\Contacts as AdminContacts;
use App\Livewire\Admin\Contenu as AdminContenu;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Documents as AdminDocuments;
use App\Livewire\Admin\Emplois as AdminEmplois;
use App\Livewire\Admin\Evenements as AdminEvenements;
use App\Livewire\Admin\Formations as AdminFormations;
use App\Livewire\Admin\Members as AdminMembers;
use App\Livewire\Admin\Mentors as AdminMentors;
use App\Livewire\Admin\Newsletter as AdminNewsletter;
use App\Livewire\Admin\Notifications as AdminNotifications;
use App\Livewire\Admin\Partenariats as AdminPartenariats;
use App\Livewire\Admin\Projets as AdminProjets;
use App\Livewire\Admin\Ressources as AdminRessources;
use App\Livewire\Member\Catalogue as MemberCatalogue;
use App\Livewire\Member\Certificats as MemberCertificats;
use App\Livewire\Member\Communaute as MemberCommunaute;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Member\Directory as MemberDirectory;
use App\Livewire\Member\Documents as MemberDocuments;
use App\Livewire\Member\Emplois as MemberEmplois;
use App\Livewire\Member\Evenements as MemberEvenements;
use App\Livewire\Member\Formations as MemberFormations;
use App\Livewire\Member\Incubateur as MemberIncubateur;
use App\Livewire\Member\Mentorat as MemberMentorat;
use App\Livewire\Member\Messaging as MemberMessaging;
use App\Livewire\Member\Notifications as MemberNotifications;
use App\Livewire\Member\Parcours as MemberParcours;
use App\Livewire\Member\ProfileEditor as MemberProfileEditor;
use App\Livewire\Member\Projets as MemberProjets;
use App\Livewire\Member\Ressources as MemberRessources;
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
Route::view('/adhesion', 'pages.adhesion')->name('adhesion');
Route::get('/suivre-ma-candidature', \App\Livewire\AdhesionStatusCheck::class)->name('adhesion.status');
Route::redirect('/inscription', '/adhesion');
Route::view('/contact', 'pages.contact');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::post('/logout', LogoutController::class)->middleware('api.auth')->name('logout');

Route::get('/mon-espace', function () {
    $user = session('api_user');

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect(($user['role'] ?? null) === 'admin' ? '/admin' : '/espace-membre');
})->name('mon-espace');

Route::middleware('api.auth')->prefix('espace-membre')->name('espace-membre.')->group(function () {
    Route::get('/', MemberDashboard::class)->name('dashboard');
    Route::get('/annuaire', MemberDirectory::class)->name('directory');
    Route::get('/messagerie', MemberMessaging::class)->name('messaging');
    Route::get('/notifications', MemberNotifications::class)->name('notifications');
    Route::get('/documents', MemberDocuments::class)->name('documents');
    Route::get('/profil', MemberProfileEditor::class)->name('profile');

    Route::get('/formations', MemberFormations::class)->name('formations');
    Route::get('/catalogue', MemberCatalogue::class)->name('catalogue');
    Route::get('/parcours', MemberParcours::class)->name('parcours');
    Route::get('/mentorat', MemberMentorat::class)->name('mentorat');
    Route::get('/communaute', MemberCommunaute::class)->name('communaute');
    Route::get('/evenements', MemberEvenements::class)->name('evenements');
    Route::get('/projets', MemberProjets::class)->name('projets');
    Route::get('/incubateur', MemberIncubateur::class)->name('incubateur');
    Route::get('/emplois', MemberEmplois::class)->name('emplois');
    Route::get('/ressources', MemberRessources::class)->name('ressources');
    Route::get('/certificats', MemberCertificats::class)->name('certificats');
});

Route::middleware(['api.auth', 'admin.web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/membres', AdminMembers::class)->name('members');
    Route::get('/adhesions', AdminAdhesions::class)->name('adhesions');
    Route::get('/mentors', AdminMentors::class)->name('mentors');
    Route::get('/formations', AdminFormations::class)->name('formations');
    Route::get('/evenements', AdminEvenements::class)->name('evenements');
    Route::get('/projets', AdminProjets::class)->name('projets');
    Route::get('/communaute', AdminCommunaute::class)->name('communaute');
    Route::get('/ressources', AdminRessources::class)->name('ressources');
    Route::get('/certificats', AdminCertificats::class)->name('certificats');
    Route::get('/emplois', AdminEmplois::class)->name('emplois');
    Route::get('/actualites', AdminActualites::class)->name('actualites');
    Route::get('/contenu', AdminContenu::class)->name('contenu');
    Route::get('/newsletter', AdminNewsletter::class)->name('newsletter');
    Route::get('/documents', AdminDocuments::class)->name('documents');
    Route::get('/contacts', AdminContacts::class)->name('contacts');
    Route::get('/partenariats', AdminPartenariats::class)->name('partenariats');
    Route::get('/notifications', AdminNotifications::class)->name('notifications');
    Route::get('/audit', AdminAudit::class)->name('audit');
});

require __DIR__.'/auth.php';
