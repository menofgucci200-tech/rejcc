<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdhesionController;
use App\Http\Controllers\Api\ActivityFeedController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\HomeContentController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartenariatController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\SectorController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'rejcc-api']));

// Contenu vitrine (lecture seule, public)
Route::get('/sectors', [SectorController::class, 'index']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/home-content', [HomeContentController::class, 'index']);
Route::get('/site-settings', [\App\Http\Controllers\Api\SiteSettingsController::class, 'index']);
Route::get('/legal-pages', [\App\Http\Controllers\Api\LegalPageController::class, 'index']);
Route::get('/legal-pages/{slug}', [\App\Http\Controllers\Api\LegalPageController::class, 'show']);
Route::get('/gallery', fn () => response()->json(['ok' => true, 'photos' => \App\Models\GalleryPhoto::orderBy('ordre')->orderBy('id')->get()]));
Route::get('/news', [NewsArticleController::class, 'index']);
Route::get('/news/{slug}', [NewsArticleController::class, 'show']);
Route::get('/public-events', [EventController::class, 'publicIndex']);
Route::get('/public-events/{slug}', [EventController::class, 'publicShow']);

// Carte membre publique (cible des QR codes), limitée contre l'énumération
Route::get('/member-card/{code}', [\App\Http\Controllers\Api\MemberCardController::class, 'show'])
    ->middleware('throttle:30,1');

// Inscription publique à un événement (cible des QR codes de lancement/événements)
Route::get('/event-signup/{slug}', [\App\Http\Controllers\Api\EventSignupController::class, 'show'])
    ->middleware('throttle:60,1');
Route::post('/event-signup/{slug}', [\App\Http\Controllers\Api\EventSignupController::class, 'register'])
    ->middleware('throttle:20,1');

// Webhook CinetPay (notification serveur à serveur du paiement d'abonnement)
Route::post('/subscription/notify', [\App\Http\Controllers\Api\SubscriptionController::class, 'notify'])
    ->middleware('throttle:60,1');

// Formulaires publics (throttle anti-spam, par IP)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/adhesion', [AdhesionController::class, 'store']);
    Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);
    Route::post('/membership-applications/status', [MembershipApplicationController::class, 'status']);
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/newsletter', [NewsletterController::class, 'store']);
    Route::post('/partenariat', [PartenariatController::class, 'store']);
});

// Authentification — espace membre (throttle anti-brute-force, par IP)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth.token')->group(function () {
    // Compte
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);
    Route::put('/auth/preferences', [AuthController::class, 'updatePreferences']);

    // Abonnement annuel (10 000 F) — statut consultable par tout membre connecté
    Route::get('/subscription/status', [\App\Http\Controllers\Api\SubscriptionController::class, 'status']);
    Route::post('/subscription/pay', [\App\Http\Controllers\Api\SubscriptionController::class, 'initiate']);

    // Aperçu chiffré de l'annuaire, ouvert à tout membre connecté
    Route::get('/members-apercu', [AuthController::class, 'apercuAnnuaire']);

    // Annuaire & messagerie — réservés aux abonnés à jour
    Route::middleware('sub.active')->group(function () {
        Route::get('/members', [AuthController::class, 'directory']);
        Route::get('/members/{id}', [AuthController::class, 'show']);

        // Messagerie
        Route::get('/messages', [MessageController::class, 'conversations']);
        Route::get('/messages/{userId}', [MessageController::class, 'thread']);
        Route::post('/messages', [MessageController::class, 'send']);
    });

    // Mentorat
    Route::put('/mentorat/profil', [\App\Http\Controllers\Api\MentoratController::class, 'updateProfil']);
    Route::get('/mentors', [\App\Http\Controllers\Api\MentoratController::class, 'mentors']);
    Route::get('/nav-compteurs', [\App\Http\Controllers\Api\MentoratController::class, 'compteurs']);
    Route::get('/mentorat/candidature', [\App\Http\Controllers\Api\MentoratController::class, 'maCandidature']);
    Route::post('/mentorat/candidature', [\App\Http\Controllers\Api\MentoratController::class, 'candidater']);
    Route::post('/mentors/{id}/demande', [\App\Http\Controllers\Api\MentoratController::class, 'demander'])->whereNumber('id');
    Route::get('/mentorat', [\App\Http\Controllers\Api\MentoratController::class, 'mesMentorats']);
    Route::post('/mentorat/{id}/annuler', [\App\Http\Controllers\Api\MentoratController::class, 'annuler'])->whereNumber('id');
    Route::post('/mentorat/{id}/accepter', [\App\Http\Controllers\Api\MentoratController::class, 'accepter'])->whereNumber('id');
    Route::post('/mentorat/{id}/refuser', [\App\Http\Controllers\Api\MentoratController::class, 'refuser'])->whereNumber('id');
    Route::get('/mentorat/prochaine-seance', [\App\Http\Controllers\Api\SeanceController::class, 'prochaine']);
    Route::get('/mentorat/{id}', [\App\Http\Controllers\Api\SeanceController::class, 'show'])->whereNumber('id');
    Route::post('/mentorat/{id}/terminer', [\App\Http\Controllers\Api\SeanceController::class, 'terminer'])->whereNumber('id');
    Route::post('/mentorat/{id}/evaluer', [\App\Http\Controllers\Api\SeanceController::class, 'evaluer'])->whereNumber('id');
    Route::post('/mentorat/{id}/seances', [\App\Http\Controllers\Api\SeanceController::class, 'proposer'])->whereNumber('id');
    Route::post('/seances/{id}/confirmer', [\App\Http\Controllers\Api\SeanceController::class, 'confirmer'])->whereNumber('id');
    Route::post('/seances/{id}/annuler', [\App\Http\Controllers\Api\SeanceController::class, 'annuler'])->whereNumber('id');
    Route::post('/seances/{id}/compte-rendu', [\App\Http\Controllers\Api\SeanceController::class, 'compteRendu'])->whereNumber('id');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Documents & ressources
    Route::get('/documents', [DocumentController::class, 'index']);

    // Événements
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events/{id}/register', [EventController::class, 'register']);

    // Formations
    Route::get('/formations', [FormationController::class, 'catalogue']);
    Route::get('/formations/{id}/fiche', [FormationController::class, 'fiche']);
    Route::get('/my-formations', [FormationController::class, 'mine']);
    Route::post('/formations/{id}/enroll', [FormationController::class, 'enroll']);
    Route::delete('/formations/{id}/enroll', [FormationController::class, 'unenroll']);
    Route::post('/formations/{id}/complete-module', [FormationController::class, 'completeModule']);
    Route::get('/formations/{id}/modules', [FormationController::class, 'modules']);
    Route::post('/formations/{id}/modules/{moduleId}/complete', [FormationController::class, 'completeFormationModule']);
    // Examen final de certification (passé sur la plateforme)
    Route::get('/formations/{id}/examen', [FormationController::class, 'examen']);
    Route::post('/formations/{id}/examen', [FormationController::class, 'passerExamen'])->middleware('throttle:20,1');

    // Parcours guidés (séquences de formations à déblocage progressif)
    Route::get('/paths', [\App\Http\Controllers\Api\PathController::class, 'index']);
    Route::get('/paths/{id}', [\App\Http\Controllers\Api\PathController::class, 'show']);

    // Fil d'activité du tableau de bord
    Route::get('/my-activity', [ActivityFeedController::class, 'mine']);

    // Certificats (émis automatiquement pour les formations certifiantes terminées)
    Route::get('/my-certificates', [\App\Http\Controllers\Api\CertificateController::class, 'mine']);

    // Projets — réservés aux abonnés à jour
    Route::middleware('sub.active')->group(function () {
        Route::get('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'index']);
        Route::post('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'store']);
    });

    // Opportunités & annonces
    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::post('/opportunities', [OpportunityController::class, 'store']);

    // Groupes sectoriels (adhésion libre, multiple, gratuite)
    Route::get('/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']);
    Route::get('/groups/recherche', [\App\Http\Controllers\Api\GroupController::class, 'recherche']);
    Route::get('/groups/{id}/apercu', [\App\Http\Controllers\Api\GroupController::class, 'apercu'])->whereNumber('id');
    Route::post('/groups/{id}/join', [\App\Http\Controllers\Api\GroupController::class, 'join']);
    Route::post('/groups/{id}/leave', [\App\Http\Controllers\Api\GroupController::class, 'leave']);

    // Trombinoscope d'un groupe (fiche + coordonnées des membres) — réservé aux abonnés à jour
    Route::middleware('sub.active')->group(function () {
        Route::get('/groups/{id}/members', [\App\Http\Controllers\Api\GroupController::class, 'members']);
        Route::get('/groups/{id}/members/{userId}', [\App\Http\Controllers\Api\GroupController::class, 'fichePro'])->whereNumber(['id', 'userId']);
        // Avis des membres sur les professionnels du réseau
        Route::post('/members/{id}/avis', [\App\Http\Controllers\Api\MemberReviewController::class, 'store'])->whereNumber('id');
        Route::delete('/members/{id}/avis', [\App\Http\Controllers\Api\MemberReviewController::class, 'destroy'])->whereNumber('id');
        Route::post('/avis/{id}/signaler', [\App\Http\Controllers\Api\MemberReviewController::class, 'signaler'])->whereNumber('id');
    });

    // Marketplace : consultation libre pour tout membre connecté, publication réservée aux abonnés à jour
    Route::get('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'index']);

    Route::middleware('sub.active')->group(function () {
        Route::get('/marketplace/mine', [\App\Http\Controllers\Api\MarketplaceController::class, 'mine']);
        Route::post('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'store']);
        Route::delete('/marketplace/{id}', [\App\Http\Controllers\Api\MarketplaceController::class, 'destroy']);
    });
});

// Administration. Chaque section porte son slug de permission : un admin dont
// `permissions` est null accède à tout, sinon uniquement aux sections listées.
Route::middleware(['auth.token', 'audit.log'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats'])->middleware('auth.admin');
    Route::get('/a-traiter', [\App\Http\Controllers\Api\AdminNavController::class, 'aTraiter'])->middleware('auth.admin');
    Route::get('/recherche', [\App\Http\Controllers\Api\AdminNavController::class, 'recherche'])->middleware('auth.admin');

    // Interrupteur général des abonnements (obligatoires ou non)
    Route::get('/subscription-mode', [AdminController::class, 'subscriptionMode'])->middleware('auth.admin');
    Route::put('/subscription-mode', [AdminController::class, 'updateSubscriptionMode'])->middleware('auth.admin:membres');

    // Journal d'audit (lecture seule)
    Route::get('/audit', [AdminController::class, 'auditLog'])->middleware('auth.admin:audit');

    // Export des jeux de données
    Route::get('/export/{dataset}', [\App\Http\Controllers\Api\ExportController::class, 'data'])->middleware('auth.admin');

    // Programme de mentorat
    Route::middleware('auth.admin:mentors')->group(function () {
        Route::get('/mentorat', [\App\Http\Controllers\Api\MentoratAdminController::class, 'index']);
        Route::post('/mentorat/attribuer', [\App\Http\Controllers\Api\MentoratAdminController::class, 'attribuer']);
        Route::post('/mentorat/candidatures/{id}/accepter', [\App\Http\Controllers\Api\MentoratAdminController::class, 'accepterCandidature'])->whereNumber('id');
        Route::post('/mentorat/candidatures/{id}/refuser', [\App\Http\Controllers\Api\MentoratAdminController::class, 'refuserCandidature'])->whereNumber('id');
    });

    // Groupes sectoriels et modération des avis
    Route::middleware('auth.admin:groupes')->group(function () {
        Route::get('/groups', [\App\Http\Controllers\Api\GroupAdminController::class, 'index']);
        Route::post('/groups', [\App\Http\Controllers\Api\GroupAdminController::class, 'store']);
        Route::put('/groups/{id}', [\App\Http\Controllers\Api\GroupAdminController::class, 'update'])->whereNumber('id');
        Route::post('/groups/{id}/move', [\App\Http\Controllers\Api\GroupAdminController::class, 'move'])->whereNumber('id');
        Route::delete('/groups/{id}', [\App\Http\Controllers\Api\GroupAdminController::class, 'destroy'])->whereNumber('id');
        Route::get('/groups/{id}/members', [\App\Http\Controllers\Api\GroupAdminController::class, 'members'])->whereNumber('id');
        Route::delete('/groups/{id}/members/{userId}', [\App\Http\Controllers\Api\GroupAdminController::class, 'removeMember'])->whereNumber(['id', 'userId']);
        Route::get('/avis', [\App\Http\Controllers\Api\GroupAdminController::class, 'avis']);
        Route::put('/avis/{id}', [\App\Http\Controllers\Api\GroupAdminController::class, 'moderer'])->whereNumber('id');
        Route::delete('/avis/{id}', [\App\Http\Controllers\Api\GroupAdminController::class, 'supprimerAvis'])->whereNumber('id');
    });

    Route::middleware('auth.admin:membres')->group(function () {
        Route::get('/members', [AdminController::class, 'members']);
        Route::post('/members', [AdminController::class, 'createMember']);
        Route::get('/members/{id}', [AdminController::class, 'memberDetail']);
        Route::put('/members/{id}', [AdminController::class, 'updateMember']);
        Route::delete('/members/{id}', [AdminController::class, 'deleteMember']);
    });

    Route::middleware('auth.admin:adhesions')->group(function () {
        Route::get('/adhesions', [AdminController::class, 'adhesions']);
        Route::put('/adhesions/{id}', [AdminController::class, 'updateAdhesion']);
        Route::get('/membership-applications', [MembershipApplicationController::class, 'index']);
        Route::get('/membership-applications/{id}', [MembershipApplicationController::class, 'show']);
        Route::post('/membership-applications/{id}/accept', [MembershipApplicationController::class, 'accept']);
        Route::post('/membership-applications/{id}/reject', [MembershipApplicationController::class, 'reject']);
    });

    Route::middleware('auth.admin:contacts')->group(function () {
        Route::get('/contacts', [AdminController::class, 'contacts']);
        Route::post('/contacts/{id}/traite', [AdminController::class, 'markContactTraite']);
    });

    Route::middleware('auth.admin:formations')->group(function () {
        Route::get('/formations', [FormationController::class, 'index']);
        Route::post('/formations', [FormationController::class, 'store']);
        Route::put('/formations/{id}', [FormationController::class, 'update']);
        Route::delete('/formations/{id}', [FormationController::class, 'destroy']);

        Route::get('/formations/{id}/modules', [FormationController::class, 'adminModules']);
        Route::post('/formations/{id}/modules', [FormationController::class, 'storeModule']);
        Route::put('/formations/{id}/modules/{moduleId}', [FormationController::class, 'updateModule']);
        Route::delete('/formations/{id}/modules/{moduleId}', [FormationController::class, 'destroyModule']);

        // Parcours guidés — gérés dans la même section admin que les formations
        Route::get('/paths', [\App\Http\Controllers\Api\PathController::class, 'adminIndex']);
        Route::get('/paths/{id}', [\App\Http\Controllers\Api\PathController::class, 'adminShow']);
        Route::post('/paths', [\App\Http\Controllers\Api\PathController::class, 'store']);
        Route::put('/paths/{id}', [\App\Http\Controllers\Api\PathController::class, 'update']);
        Route::put('/paths/{id}/formations', [\App\Http\Controllers\Api\PathController::class, 'updateFormations']);
        Route::delete('/paths/{id}', [\App\Http\Controllers\Api\PathController::class, 'destroy']);
    });

    Route::middleware('auth.admin:evenements')->group(function () {
        Route::get('/events', [EventController::class, 'adminIndex']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);

        // Événements à inscription publique (module « Inscriptions »)
        Route::get('/registration-events', [\App\Http\Controllers\Api\RegistrationEventController::class, 'index']);
        Route::post('/registration-events', [\App\Http\Controllers\Api\RegistrationEventController::class, 'store']);
        Route::put('/registration-events/{id}', [\App\Http\Controllers\Api\RegistrationEventController::class, 'update']);
        Route::post('/registration-events/{id}/toggle', [\App\Http\Controllers\Api\RegistrationEventController::class, 'toggle']);
        Route::delete('/registration-events/{id}', [\App\Http\Controllers\Api\RegistrationEventController::class, 'destroy']);
        Route::get('/registration-events/{id}/participants', [\App\Http\Controllers\Api\RegistrationEventController::class, 'participants']);
    });

    Route::middleware('auth.admin:actualites')->group(function () {
        Route::get('/news', [NewsArticleController::class, 'adminIndex']);
        Route::post('/news', [NewsArticleController::class, 'store']);
        Route::put('/news/{id}', [NewsArticleController::class, 'update']);
        Route::delete('/news/{id}', [NewsArticleController::class, 'destroy']);
    });

    Route::middleware('auth.admin:opportunites')->group(function () {
        Route::get('/opportunities', [OpportunityController::class, 'index']);
        Route::put('/opportunities/{id}', [OpportunityController::class, 'adminUpdate']);
        Route::delete('/opportunities/{id}', [OpportunityController::class, 'adminDestroy']);
    });

    Route::get('/certificates', [\App\Http\Controllers\Api\CertificateController::class, 'adminIndex'])
        ->middleware('auth.admin:certificats');

    Route::middleware('auth.admin:projets')->group(function () {
        Route::get('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'adminIndex']);
        Route::put('/projects/{id}', [\App\Http\Controllers\Api\ProjectController::class, 'adminUpdate']);
        Route::delete('/projects/{id}', [\App\Http\Controllers\Api\ProjectController::class, 'adminDestroy']);
    });

    Route::middleware('auth.admin:documents')->group(function () {
        Route::get('/documents', [AdminController::class, 'documents']);
        Route::post('/documents', [AdminController::class, 'createDocument']);
        Route::put('/documents/{id}', [AdminController::class, 'updateDocument']);
        Route::delete('/documents/{id}', [AdminController::class, 'deleteDocument']);
    });

    Route::middleware('auth.admin:notifications')->group(function () {
        Route::post('/notifications/broadcast', [AdminController::class, 'broadcastNotification']);
        Route::get('/notifications/history', [AdminController::class, 'broadcastHistory']);
    });

    Route::get('/newsletter', [AdminController::class, 'newsletterSubscribers'])
        ->middleware('auth.admin:newsletter');

    Route::middleware('auth.admin:contenu')->group(function () {
        Route::get('/site-content/{type}', [\App\Http\Controllers\Api\SiteContentController::class, 'index']);
        Route::post('/site-content/{type}', [\App\Http\Controllers\Api\SiteContentController::class, 'store']);
        Route::put('/site-content/{type}/{id}', [\App\Http\Controllers\Api\SiteContentController::class, 'update']);
        Route::delete('/site-content/{type}/{id}', [\App\Http\Controllers\Api\SiteContentController::class, 'destroy']);
        Route::get('/site-settings', [\App\Http\Controllers\Api\SiteSettingsController::class, 'adminIndex']);
        Route::put('/site-settings', [\App\Http\Controllers\Api\SiteSettingsController::class, 'update']);
        Route::put('/page-sections/{page}/{section}', [\App\Http\Controllers\Api\SiteSettingsController::class, 'updateSection']);
        Route::get('/legal-pages', [\App\Http\Controllers\Api\LegalPageController::class, 'adminIndex']);
        Route::put('/legal-pages/{slug}', [\App\Http\Controllers\Api\LegalPageController::class, 'update']);
    });

    Route::middleware('auth.admin:partenariats')->group(function () {
        Route::get('/partenariats', [AdminController::class, 'partnershipRequests']);
        Route::put('/partenariats/{id}', [AdminController::class, 'updatePartnershipRequest']);
    });

    Route::middleware('auth.admin:communaute')->group(function () {
        Route::get('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'adminIndex']);
        Route::put('/marketplace/{id}/approve', [\App\Http\Controllers\Api\MarketplaceController::class, 'approve']);
        Route::put('/marketplace/{id}/reject', [\App\Http\Controllers\Api\MarketplaceController::class, 'reject']);
        Route::delete('/marketplace/{id}', [\App\Http\Controllers\Api\MarketplaceController::class, 'adminDestroy']);
    });
});
