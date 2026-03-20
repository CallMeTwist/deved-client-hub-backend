<?php

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
});
