<?php

use App\Http\Controllers\ActivityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\TemplateController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/*
|--------------------------------------------------------------------------
| Middleware stack for protected routes:
|   auth:sanctum  — verifies Bearer token, hydrates Auth::user()
|   tenant.scope  — confirms tenant is active, aborts if not
|--------------------------------------------------------------------------
*/

// ─── Public ──────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ─── Authenticated ────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'tenant.scope'])->group(function () {

    // Auth utilities
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user',    [AuthController::class, 'user']);
    });

    Route::get('user/activity', [ActivityController::class, 'index']);

    // Clients CRUD
    // Authorization is checked in each FormRequest::authorize() and controller
    Route::apiResource('clients', ClientController::class);

    // Records — nested under clients
    Route::prefix('clients/{client}/records')->group(function () {
        Route::get('/',         [RecordController::class, 'index'])->middleware('can:view_records');
        Route::post('/',        [RecordController::class, 'store'])->middleware('can:create_records');
        Route::get('/{record}', [RecordController::class, 'show'])->middleware('can:view_records');
    });

    // Templates — no destroy (use is_active=false to deactivate)
    Route::apiResource('templates', TemplateController::class)->except(['destroy']);

    // Notes
    Route::prefix('clients/{client}/notes')->group(function () {
        Route::get('/',        [\App\Http\Controllers\ClientNoteController::class, 'index'])->middleware('can:view_records');
        Route::post('/',       [\App\Http\Controllers\ClientNoteController::class, 'store'])->middleware('can:create_records');
        Route::put('/{note}',    [\App\Http\Controllers\ClientNoteController::class, 'update'])->middleware('can:create_records');
        Route::delete('/{note}', [\App\Http\Controllers\ClientNoteController::class, 'destroy'])->middleware('can:create_records');
    });

// Files
    Route::prefix('clients/{client}/files')->group(function () {
        Route::get('/',              [\App\Http\Controllers\ClientFileController::class, 'index'])->middleware('can:view_records');
        Route::post('/',             [\App\Http\Controllers\ClientFileController::class, 'store'])->middleware('can:create_records');
        Route::delete('/{file}',     [\App\Http\Controllers\ClientFileController::class, 'destroy'])->middleware('can:create_records');
        Route::get('/{file}/download', [\App\Http\Controllers\ClientFileController::class, 'download'])->middleware('can:view_records');
        Route::get('/{file}/preview',    [\App\Http\Controllers\ClientFileController::class, 'preview'])->middleware('can:view_records');  // ← ADD// ← ADD THIS
    });

// Interactions
    Route::prefix('clients/{client}/interactions')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\ClientInteractionController::class, 'index'])->middleware('can:view_records');
        Route::post('/',                   [\App\Http\Controllers\ClientInteractionController::class, 'store'])->middleware('can:create_records');
        Route::delete('/{interaction}',    [\App\Http\Controllers\ClientInteractionController::class, 'destroy'])->middleware('can:create_records');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/',             [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::patch('/read-all',   [\App\Http\Controllers\NotificationController::class, 'markAllRead']);
        Route::patch('/{notification}/read',    [\App\Http\Controllers\NotificationController::class, 'markRead']);
        Route::delete('/{notification}',        [\App\Http\Controllers\NotificationController::class, 'destroy']);
    });
});
