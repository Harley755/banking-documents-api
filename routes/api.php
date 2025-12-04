<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Gestion documentaire sécurisée
|--------------------------------------------------------------------------
*/

// ========== ROUTES PUBLIQUES (pas d'authentification) ==========

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->name('auth.register');
    
    Route::post('login', [AuthController::class, 'login'])
        ->name('auth.login');
});

// Partage public de documents (accès par token)
Route::prefix('documents/share')->group(function () {
    Route::get('{token}', [SharingController::class, 'show'])
        ->name('documents.share.download');
    
    Route::get('{token}/info', [SharingController::class, 'info'])
        ->name('documents.share.info');
});

// ========== ROUTES PROTÉGÉES PAR AUTHENTIFICATION ==========
// Minimal group protégé : logout + user info (ajoute d'autres routes protégées plus tard)
Route::middleware('auth:sanctum')->group(function () {
    // Déconnexion : révoque tous les tokens
    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');


     // Gestion des documents
    Route::prefix('documents')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])
            ->name('documents.store');
        
        Route::get('/', [DocumentController::class, 'index'])
            ->name('documents.index');
        
        Route::get('{id}', [DocumentController::class, 'show'])
            ->name('documents.show');
        
        Route::get('{id}/download', [DocumentController::class, 'download'])
            ->name('documents.download');
        
        Route::delete('{id}', [DocumentController::class, 'destroy'])
            ->name('documents.destroy');
        
        // Partage de documents
        Route::post('{id}/share', [DocumentController::class, 'share'])
            ->name('documents.share.create');
        
        Route::get('{id}/shares', [DocumentController::class, 'shares'])
            ->name('documents.shares.list');
    });
    
    // Logs d'audit (RGPD)
    Route::prefix('audit')->group(function () {
        Route::get('/', [AuditController::class, 'index'])
            ->name('audit.index');
        
        Route::get('actions', [AuditController::class, 'actions'])
            ->name('audit.actions');
        
        Route::get('export', [AuditController::class, 'export'])
            ->name('audit.export');
    });

    // Récupérer l'utilisateur connecté
    Route::get('auth/user', function () {
        return response()->json(auth()->user());
    })->name('auth.user');



});

// Route de secours pour 404
Route::fallback(function () {
    return response()->json([
        'message' => 'Route not found',
    ], 404);
});
