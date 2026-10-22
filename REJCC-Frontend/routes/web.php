<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NewsController;
use App\Livewire\Member\Abonnement as MemberAbonnement;
use App\Livewire\Member\Carte as MemberCarte;
use App\Livewire\Member\Catalogue as MemberCatalogue;
use App\Livewire\Member\Certificats as MemberCertificats;
use App\Livewire\Member\Marketplace as MemberMarketplace;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Member\Directory as MemberDirectory;
use App\Livewire\Member\Documents as MemberDocuments;
use App\Livewire\Member\Emplois as MemberEmplois;
use App\Livewire\Member\Evenements as MemberEvenements;
use App\Livewire\Member\Formations as MemberFormations;
use App\Livewire\Member\Mentorat as MemberMentorat;
use App\Livewire\Member\Messaging as MemberMessaging;
use App\Livewire\Member\Notifications as MemberNotifications;
use App\Livewire\Member\Parcours as MemberParcours;
use App\Livewire\Member\ProfileEditor as MemberProfileEditor;
use App\Livewire\Member\Projets as MemberProjets;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class);
Route::view('/a-propos', 'pages.a-propos');
Route::view('/activites', 'pages.activites');
Route::view('/domaines', 'pages.domaines');

Route::get('/evenements', [EventController::class, 'index']);
Route::get('/evenements/{slug}', [EventController::class, 'show']);
Route::get('/projets', [\App\Http\Controllers\ProjetPublicController::class, 'index']);
Route::get('/emplois', [\App\Http\Controllers\EmploiPublicController::class, 'index']);
Route::get('/emplois/{id}', [\App\Http\Controllers\EmploiPublicController::class, 'show'])->whereNumber('id');
Route::get('/projets/{id}', [\App\Http\Controllers\ProjetPublicController::class, 'show'])->whereNumber('id');

Route::get('/actualites', [NewsController::class, 'index']);
Route::get('/actualites/{slug}', [NewsController::class, 'show']);

Route::view('/partenaires', 'pages.partenaires');
Route::get('/galerie', [\App\Http\Controllers\GaleriePublicController::class, 'index']);
Route::get('/galerie/{slug}', [\App\Http\Controllers\GaleriePublicController::class, 'show']);
// Pages légales (contenu rédigé depuis l'admin)
Route::get('/{slug}', \App\Http\Controllers\LegalPageController::class)
    ->whereIn('slug', \App\Support\Content\LegalPages::SLUGS)->name('legal');
Route::get('/carte/{code}', [\App\Http\Controllers\CardController::class, 'show'])->name('carte');
Route::get('/carte/{code}/contact.vcf', [\App\Http\Controllers\CardController::class, 'vcard'])->name('carte.vcard');
Route::get('/participer/{slug}', \App\Livewire\EventSignup::class)->name('event.signup');
Route::get('/billet/{code}', \App\Livewire\Billet::class)->name('billet');
// Espace animateur d'une session live (lien personnel du formateur).
Route::get('/animer/{jeton}', \App\Livewire\Animer::class)->where('jeton', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1')->name('animer');

// Vérification publique des certificats et attestations (le registre fait foi)
Route::get('/verifier/{code?}', \App\Livewire\VerifierCertificat::class)->name('verifier');
Route::get('/verifier/{code}/pdf', [\App\Http\Controllers\CertificatPdfController::class, 'public'])->name('verifier.pdf');
// Confirmation d'une nouvelle adresse e-mail (lien reçu par e-mail)
Route::get('/confirmer-email/{jeton}', [\App\Http\Controllers\CompteController::class, 'confirmerEmail'])->where('jeton', '[A-Za-z0-9]{32,64}')->middleware('throttle:10,1')->name('confirmer-email');
Route::get('/adhesion', \App\Livewire\AdhesionApplicationForm::class)->name('adhesion');
Route::get('/suivre-ma-candidature', \App\Livewire\AdhesionStatusCheck::class)->name('adhesion.status');
Route::redirect('/inscription', '/adhesion');
Route::view('/contact', 'pages.contact');

Route::post('/logout', LogoutController::class)->middleware('api.auth')->name('logout');
Route::post('/retour-testeur', \App\Http\Controllers\RetourTesteurController::class)->middleware(['api.auth', 'throttle:20,1'])->name('retour-testeur');

Route::get('/mon-espace', function () {
    $user = session('api_user');

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect('/espace-membre');
})->name('mon-espace');

Route::middleware('api.auth')->prefix('espace-membre')->name('espace-membre.')->group(function () {
    Route::get('/', MemberDashboard::class)->name('dashboard');
    Route::get('/annuaire', MemberDirectory::class)->name('directory');
    Route::get('/groupes', \App\Livewire\Member\Groupes::class)->name('groupes');
    Route::get('/groupes/{groupId}', \App\Livewire\Member\GroupeMembres::class)->name('groupes.membres');
    Route::get('/messagerie', MemberMessaging::class)->name('messaging');
    Route::get('/notifications', MemberNotifications::class)->name('notifications');
    Route::get('/documents', MemberDocuments::class)->name('documents');
    // Fichier d'un document : servi après contrôle d'accès (jamais en accès public).
    Route::get('/documents/{id}/fichier/{nom?}', \App\Http\Controllers\DocumentFichierController::class)->whereNumber('id')->name('documents.fichier');
    Route::get('/mes-documents/{id}/fichier/{nom?}', [\App\Http\Controllers\CoffreFichierController::class, 'membre'])->whereNumber('id')->name('mes-documents.fichier');
    Route::get('/profil', MemberProfileEditor::class)->name('profile');
    Route::get('/mes-donnees', [\App\Http\Controllers\CompteController::class, 'exporter'])->middleware('throttle:5,1')->name('mes-donnees');

    Route::get('/formations', MemberFormations::class)->name('formations');
    Route::get('/formations/{formationId}', \App\Livewire\Member\FormationDetail::class)->name('formations.detail');
    Route::get('/catalogue', MemberCatalogue::class)->name('catalogue');
    Route::get('/catalogue/{formationId}', \App\Livewire\Member\FormationFiche::class)->whereNumber('formationId')->name('catalogue.fiche');
    Route::get('/parcours', MemberParcours::class)->name('parcours');
    Route::get('/parcours/{pathId}', \App\Livewire\Member\ParcoursDetail::class)->name('parcours.detail');
    Route::get('/mentorat', MemberMentorat::class)->name('mentorat');
    Route::get('/mentorat/{mentorshipId}', \App\Livewire\Member\MentoratSuivi::class)->whereNumber('mentorshipId')->name('mentorat.suivi');
    Route::get('/marketplace', MemberMarketplace::class)->name('marketplace');
    Route::redirect('/communaute', '/espace-membre/marketplace');
    Route::get('/evenements', MemberEvenements::class)->name('evenements');
    Route::get('/evenements/{id}/agenda.ics', \App\Http\Controllers\EvenementAgendaController::class)->whereNumber('id')->name('evenements.agenda');
    Route::get('/evenements/{id}/rejoindre', \App\Http\Controllers\RejoindreVisioController::class)->whereNumber('id')->name('evenements.rejoindre');
    Route::get('/projets', MemberProjets::class)->name('projets');
    Route::get('/emplois', MemberEmplois::class)->name('emplois');
    Route::get('/certificats', MemberCertificats::class)->name('certificats');
    Route::get('/mes-sessions', \App\Livewire\Member\SessionsAnimees::class)->name('sessions-animees');
    Route::get('/certificats/{id}/pdf', [\App\Http\Controllers\CertificatPdfController::class, 'membre'])->whereNumber('id')->name('certificats.pdf');
    Route::get('/carte', MemberCarte::class)->name('carte');
    Route::get('/abonnement', MemberAbonnement::class)->name('abonnement');
    Route::get('/abonnement/recus/{ref}', [\App\Http\Controllers\RecuController::class, 'membre'])->where('ref', '[A-Za-z0-9\-]+')->name('abonnement.recu');
});

require __DIR__.'/auth.php';
