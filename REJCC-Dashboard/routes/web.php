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
    Route::get('/formations-live/{id}/emargement.csv', \App\Http\Controllers\EmargementController::class)->whereNumber('id')->name('lives.emargement');
    Route::get('/formations/{formationId}/apprenants', \App\Livewire\Admin\Apprenants::class)->whereNumber('formationId')->name('apprenants');
    Route::get('/evenements', AdminEvenements::class)->name('evenements');
    Route::get('/evenements/{id}/pointage', \App\Livewire\Admin\EvenementPointage::class)->whereNumber('id')->name('evenements.pointage');
    // Ancien module « Inscriptions (QR) », fusionné dans les événements.
    Route::redirect('/inscriptions', '/admin/evenements')->name('inscriptions');
    Route::get('/projets', AdminProjets::class)->name('projets');
    Route::get('/marketplace', AdminMarketplace::class)->name('marketplace');
    Route::get('/communaute', \App\Livewire\Admin\Communaute::class)->name('communaute');
    Route::get('/communaute/fichiers/{id}', \App\Http\Controllers\CommunauteFichierController::class)->whereNumber('id')->name('communaute.fichier');
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
    // CathoImpact (espace Communication) : réponses au formulaire du site et galerie « Ils ont inspiré ».
    Route::get('/cathoimpact', \App\Livewire\Admin\CathoImpact::class)->name('cathoimpact');
    Route::get('/cathoimpact/{id}/photo', \App\Http\Controllers\CathoImpactPhotoController::class)->whereNumber('id')->name('cathoimpact.photo');
    // Boîte à outils de la Communication : marque (fichiers privés), visuels, textes, contacts médias.
    Route::get('/communication/marque', \App\Livewire\Admin\BoiteMarque::class)->name('marque');
    Route::get('/communication/marque/{id}/fichier/{nom?}', \App\Http\Controllers\MarqueFichierController::class)->whereNumber('id')->name('marque.fichier');
    Route::get('/communication/visuels', \App\Livewire\Admin\GenerateurVisuels::class)->name('visuels');
    Route::get('/communication/textes', \App\Livewire\Admin\TextesCommunication::class)->name('textes');
    Route::get('/communication/contacts-medias', \App\Livewire\Admin\ContactsMedias::class)->name('contacts-medias');
    // Mesure d'audience de la Communication : statistiques du site (fichiers partagés) et liens de suivi.
    Route::get('/communication/statistiques', \App\Livewire\Admin\StatistiquesDuSite::class)->name('statistiques-site');
    Route::get('/communication/liens', \App\Livewire\Admin\LiensSuivi::class)->name('liens-suivi');
    // Calendrier éditorial (publications, tours de rôle, import/export CSV) et bilan mensuel de la Communication.
    Route::get('/communication/calendrier', \App\Livewire\Admin\CalendrierEditorial::class)->name('calendrier-editorial');
    Route::get('/communication/calendrier/{quoi}.csv', \App\Http\Controllers\CalendrierCsvController::class)->whereIn('quoi', ['modele', 'export'])->name('calendrier-editorial.csv');
    Route::get('/communication/bilan', \App\Livewire\Admin\BilanCommunication::class)->name('bilan-communication');
    // Demandes de communication : traitement par la Com (rubrique « communication »), suivi par chaque demandeur (tout compte).
    Route::get('/communication/demandes', \App\Livewire\Admin\DemandesCommunication::class)->name('demandes-communication');
    Route::get('/communication/mes-demandes', \App\Livewire\Admin\MesDemandesCommunication::class)->name('mes-demandes-communication');
    Route::get('/communication/demandes/{id}/fichiers/{n}', \App\Http\Controllers\DemandeCommunicationFichierController::class)->whereNumber(['id', 'n'])->name('demandes-communication.fichier');
});

require __DIR__.'/auth.php';
