<?php

use App\Http\Controllers\Api\AdhesionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartenariatController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'rejcc-api']));

// Formulaires publics
Route::post('/adhesion', [AdhesionController::class, 'store']);
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
});
