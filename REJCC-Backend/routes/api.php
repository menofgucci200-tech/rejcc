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
Route::get('/gallery', fn () => response()->json(['ok' => true, 'photos' => \App\Models\GalleryPhoto::orderBy('ordre')->orderBy('id')->get()]));
Route::get('/news', [NewsArticleController::class, 'index']);
Route::get('/news/{slug}', [NewsArticleController::class, 'show']);
Route::get('/public-events', [EventController::class, 'publicIndex']);
Route::get('/public-events/{slug}', [EventController::class, 'publicShow']);

// Carte membre publique (cible des QR codes), limitée contre l'énumération
Route::get('/member-card/{code}', [\App\Http\Controllers\Api\MemberCardController::class, 'show'])
    ->middleware('throttle:30,1');

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
    Route::get('/members', [AuthController::class, 'directory']);

    // Messagerie
    Route::get('/messages', [MessageController::class, 'conversations']);
    Route::get('/messages/{userId}', [MessageController::class, 'thread']);
    Route::post('/messages', [MessageController::class, 'send']);

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
    Route::get('/my-formations', [FormationController::class, 'mine']);
    Route::post('/formations/{id}/enroll', [FormationController::class, 'enroll']);
    Route::post('/formations/{id}/complete-module', [FormationController::class, 'completeModule']);

    // Fil d'activité du tableau de bord
    Route::get('/my-activity', [ActivityFeedController::class, 'mine']);

    // Ressources
    Route::get('/resources', [\App\Http\Controllers\Api\ResourceController::class, 'index']);
    Route::post('/resources/{id}/download', [\App\Http\Controllers\Api\ResourceController::class, 'download']);

    // Certificats (émis automatiquement pour les formations certifiantes terminées)
    Route::get('/my-certificates', [\App\Http\Controllers\Api\CertificateController::class, 'mine']);

    // Projets & incubateur
    Route::get('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'index']);
    Route::post('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'store']);
    Route::get('/incubator', [\App\Http\Controllers\Api\ProjectController::class, 'incubator']);

    // Opportunités & annonces
    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::post('/opportunities', [OpportunityController::class, 'store']);

    // Groupes sectoriels (adhésion libre, multiple)
    Route::get('/groups', [\App\Http\Controllers\Api\GroupController::class, 'index']);
    Route::post('/groups/{id}/join', [\App\Http\Controllers\Api\GroupController::class, 'join']);
    Route::post('/groups/{id}/leave', [\App\Http\Controllers\Api\GroupController::class, 'leave']);

    // Marketplace (annonces validées par l'administration avant publication)
    Route::get('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'index']);
    Route::get('/marketplace/mine', [\App\Http\Controllers\Api\MarketplaceController::class, 'mine']);
    Route::post('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'store']);
    Route::delete('/marketplace/{id}', [\App\Http\Controllers\Api\MarketplaceController::class, 'destroy']);
});

// Administration. Chaque section porte son slug de permission : un admin dont
// `permissions` est null accède à tout, sinon uniquement aux sections listées.
Route::middleware(['auth.token', 'audit.log'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats'])->middleware('auth.admin');

    // Journal d'audit (lecture seule)
    Route::get('/audit', [AdminController::class, 'auditLog'])->middleware('auth.admin:audit');

    // Export des jeux de données
    Route::get('/export/{dataset}', [\App\Http\Controllers\Api\ExportController::class, 'data'])->middleware('auth.admin');

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
    });

    Route::middleware('auth.admin:evenements')->group(function () {
        Route::get('/events', [EventController::class, 'adminIndex']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
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

    Route::middleware('auth.admin:ressources')->group(function () {
        Route::get('/resources', [\App\Http\Controllers\Api\ResourceController::class, 'adminIndex']);
        Route::post('/resources', [\App\Http\Controllers\Api\ResourceController::class, 'store']);
        Route::put('/resources/{id}', [\App\Http\Controllers\Api\ResourceController::class, 'update']);
        Route::delete('/resources/{id}', [\App\Http\Controllers\Api\ResourceController::class, 'destroy']);
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
        Route::put('/site-settings', [\App\Http\Controllers\Api\SiteSettingsController::class, 'update']);
        Route::put('/page-sections/{page}/{section}', [\App\Http\Controllers\Api\SiteSettingsController::class, 'updateSection']);
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
