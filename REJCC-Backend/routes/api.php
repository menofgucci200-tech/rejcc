<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdhesionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EventController;
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
Route::get('/news', [NewsArticleController::class, 'index']);
Route::get('/news/{slug}', [NewsArticleController::class, 'show']);
Route::get('/public-events', [EventController::class, 'publicIndex']);
Route::get('/public-events/{slug}', [EventController::class, 'publicShow']);

// Formulaires publics
Route::post('/adhesion', [AdhesionController::class, 'store']);
Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);
Route::post('/membership-applications/status', [MembershipApplicationController::class, 'status']);
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/newsletter', [NewsletterController::class, 'store']);
Route::post('/partenariat', [PartenariatController::class, 'store']);

// Authentification — espace membre
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

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

    // Opportunités & annonces
    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::post('/opportunities', [OpportunityController::class, 'store']);
});

// Administration — réservé aux admins
Route::middleware(['auth.token', 'auth.admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);

    // Membres
    Route::get('/members', [AdminController::class, 'members']);
    Route::post('/members', [AdminController::class, 'createMember']);
    Route::put('/members/{id}', [AdminController::class, 'updateMember']);
    Route::delete('/members/{id}', [AdminController::class, 'deleteMember']);

    // Adhésions
    Route::get('/adhesions', [AdminController::class, 'adhesions']);
    Route::put('/adhesions/{id}', [AdminController::class, 'updateAdhesion']);

    // Candidatures d'adhésion (formulaire enrichi)
    Route::get('/membership-applications', [MembershipApplicationController::class, 'index']);
    Route::get('/membership-applications/{id}', [MembershipApplicationController::class, 'show']);
    Route::post('/membership-applications/{id}/accept', [MembershipApplicationController::class, 'accept']);
    Route::post('/membership-applications/{id}/reject', [MembershipApplicationController::class, 'reject']);

    // Contacts
    Route::get('/contacts', [AdminController::class, 'contacts']);
    Route::post('/contacts/{id}/traite', [AdminController::class, 'markContactTraite']);

    // Documents
    Route::get('/documents', [AdminController::class, 'documents']);
    Route::post('/documents', [AdminController::class, 'createDocument']);
    Route::put('/documents/{id}', [AdminController::class, 'updateDocument']);
    Route::delete('/documents/{id}', [AdminController::class, 'deleteDocument']);

    // Notifications broadcast
    Route::post('/notifications/broadcast', [AdminController::class, 'broadcastNotification']);
});
