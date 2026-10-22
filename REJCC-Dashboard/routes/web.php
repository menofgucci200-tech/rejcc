<?php

/*
 * Dashboard du REJCC (dashboard.rejcc.site) : administration générale et
 * espaces des départements du Bureau. Le site public et l'espace membre sont
 * une autre application (REJCC-Frontend, rejcc.site).
 */

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Admin\Actualites as AdminActualites;
use App\Livewire\Admin\Adhesions as AdminAdhesions;
use App\Livewire\Admin\Audit as AdminAudit;
use App\Livewire\Admin\Certificats as AdminCertificats;
use App\Livewire\Admin\Marketplace as AdminMarketplace;
use App\Livewire\Admin\Contacts as AdminContacts;
use App\Livewire\Admin\Contenu as AdminContenu;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Documents as AdminDocuments;
use App\Livewire\Admin\Emplois as AdminEmplois;
use App\Livewire\Admin\Evenements as AdminEvenements;
use App\Livewire\Admin\Formations as AdminFormations;
use App\Livewire\Admin\Parcours as AdminParcours;
use App\Livewire\Admin\Members as AdminMembers;
use App\Livewire\Admin\Mentors as AdminMentors;
use App\Livewire\Admin\Newsletter as AdminNewsletter;
use App\Livewire\Admin\Notifications as AdminNotifications;
use App\Livewire\Admin\Partenariats as AdminPartenariats;
use App\Livewire\Admin\Projets as AdminProjets;
use App\Support\Espace;
use Illuminate\Support\Facades\Route;

// Accueil : l'espace de la personne connectée, sinon la page de connexion.
Route::get('/', fn () => redirect(Espace::accueil(session('api_user'))))->name('home');

Route::post('/logout', LogoutController::class)->middleware('api.auth')->name('logout');
Route::post('/retour-testeur', \App\Http\Controllers\RetourTesteurController::class)->middleware(['api.auth', 'throttle:20,1'])->name('retour-testeur');

// Espaces des départements du Bureau.
Route::middleware(['api.auth', 'admin.web'])->group(function () {
    Route::get('/departements', \App\Livewire\Departements\Index::class)->name('departements.index');
    Route::get('/departements/{slug}', \App\Livewire\Departements\Show::class)->where('slug', '[a-z-]+')->name('departements.show');
});

Route::middleware(['api.auth', 'admin.web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/membres', AdminMembers::class)->name('members');
    // Fichier d'un document de la bibliothèque (stockage partagé avec le site).
    Route::get('/documents/{id}/fichier/{nom?}', \App\Http\Controllers\DocumentFichierController::class)->whereNumber('id')->name('documents.fichier');
    Route::get('/membres/documents/{id}/fichier/{nom?}', [\App\Http\Controllers\CoffreFichierController::class, 'equipe'])->whereNumber('id')->name('members.document');
    Route::get('/export/{dataset}', [\App\Http\Controllers\ExportController::class, 'download'])->name('export');
    Route::get('/inscription', \App\Livewire\Admin\Inscription::class)->name('inscription');
    Route::get('/adhesions', AdminAdhesions::class)->name('adhesions');
    Route::get('/mentors', AdminMentors::class)->name('mentors');
    Route::get('/formations', AdminFormations::class)->name('formations');
    Route::get('/parcours', AdminParcours::class)->name('parcours');
    Route::get('/formations-live', \App\Livewire\Admin\FormationsLive::class)->name('lives');
    Route::get('/formateurs', \App\Livewire\Admin\Formateurs::class)->name('formateurs');
    Route::get('/evenements', AdminEvenements::class)->name('evenements');
    Route::get('/evenements/{id}/pointage', \App\Livewire\Admin\EvenementPointage::class)->whereNumber('id')->name('evenements.pointage');
    // Ancien module « Inscriptions (QR) », fusionné dans les événements.
    Route::redirect('/inscriptions', '/admin/evenements')->name('inscriptions');
    Route::get('/projets', AdminProjets::class)->name('projets');
    Route::get('/marketplace', AdminMarketplace::class)->name('marketplace');
    Route::redirect('/communaute', '/admin/marketplace');
    Route::get('/abonnements', \App\Livewire\Admin\Abonnements::class)->name('abonnements');
    Route::get('/abonnements/recus/{ref}', [\App\Http\Controllers\RecuController::class, 'admin'])->where('ref', '[A-Za-z0-9\-]+')->name('abonnements.recu');
    Route::get('/certificats', AdminCertificats::class)->name('certificats');
    Route::get('/certificats/{id}/pdf', [\App\Http\Controllers\CertificatPdfController::class, 'admin'])->whereNumber('id')->name('certificats.pdf');
    Route::get('/certificats/apercu', [\App\Http\Controllers\CertificatPdfController::class, 'apercu'])->name('certificats.apercu');
    Route::get('/emplois', AdminEmplois::class)->name('emplois');
    Route::get('/groupes', \App\Livewire\Admin\Groupes::class)->name('groupes');
    Route::get('/signalements', \App\Livewire\Admin\Signalements::class)->name('signalements');
    Route::get('/actualites', AdminActualites::class)->name('actualites');
    Route::get('/contenu', AdminContenu::class)->name('contenu');
    Route::get('/pages', \App\Livewire\Admin\Pages::class)->name('pages');
    Route::get('/reglages', \App\Livewire\Admin\Reglages::class)->name('reglages');
    Route::get('/pages-legales', \App\Livewire\Admin\PagesLegales::class)->name('legal');
    Route::get('/mediatheque', \App\Livewire\Admin\Mediatheque::class)->name('mediatheque');
    Route::get('/newsletter', AdminNewsletter::class)->name('newsletter');
    Route::get('/documents', AdminDocuments::class)->name('documents');
    Route::get('/contacts', AdminContacts::class)->name('contacts');
    Route::get('/partenariats', AdminPartenariats::class)->name('partenariats');
    Route::get('/notifications', AdminNotifications::class)->name('notifications');
    Route::get('/audit', AdminAudit::class)->name('audit');
    Route::get('/suggestions', \App\Livewire\Admin\Suggestions::class)->name('suggestions'); // retours des comptes de test
});

require __DIR__.'/auth.php';
