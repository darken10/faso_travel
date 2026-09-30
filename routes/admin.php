<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\VoyageTicketController;
use App\Http\Controllers\Api\Admin\TicketController;
use App\Http\Controllers\Api\Admin\SyncController;
use App\Http\Controllers\Api\Admin\AgentAuthController;


// ─── Agent login (pas de sanctum) ─────────────────────────────────────────────
Route::prefix('/admin/auth')->name('admin.auth.')->middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AgentAuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AgentAuthController::class, 'refresh'])->name('refresh');
});

Route::prefix('/admin')->name('admin.')->middleware('auth:sanctum')->group(function () {
    // ─── Voyages ──────────────────────────────────────────────────────────────
    Route::prefix('/voyages')->name('voyages.')->group(function () {
        Route::get('/', [VoyageTicketController::class, 'getVoyageInstancesByDate'])->name('instances-by-date');
        Route::get('/{voyageInstance}', [VoyageTicketController::class, 'getVoyageInstanceDetail'])->name('detail');
        Route::get('/{voyageInstance}/stats', [VoyageTicketController::class, 'getVoyageStats'])->name('stats');
        Route::get('/{voyageInstance}/tickets', [VoyageTicketController::class, 'getTicketsByVoyageInstance'])->name('tickets');
        Route::get('/{voyageInstance}/passengers', [TicketController::class, 'getPassengers'])->name('passengers');
    });

    // ─── Tickets ──────────────────────────────────────────────────────────────
    Route::prefix('/tickets')->name('tickets.')->group(function () {
        Route::get('/verify/{ticketCode}', [TicketController::class, 'verifyByQrCode'])->name('verify-qr');
        // Le code SMS ne fait que 6 chiffres : sans limite de debit, il est
        // devinable par force brute. Aucun throttle global ne s'applique a
        // /api/* (throttleApi() n'est pas appele dans bootstrap/app.php).
        Route::post('/verify-phone', [TicketController::class, 'verifyByPhoneAndCode'])
            ->middleware('throttle:20,1')
            ->name('verify-phone');
        Route::post('/validate', [TicketController::class, 'validate'])->name('validate');
        Route::post('/batch-sync', [TicketController::class, 'batchSync'])->name('batch-sync');
        Route::get('/{ticketId}', [TicketController::class, 'getTicketById'])->name('get-by-id');
        Route::post('/{ticket}/change-status', [TicketController::class, 'changeStatus'])->name('change-status');
    });

    // ─── Passagers ────────────────────────────────────────────────────────────
    Route::prefix('/passengers')->name('passengers.')->group(function () {
        Route::get('/{ticketId}', [TicketController::class, 'getPassengerByTicket'])->name('detail');
    });

    // ─── Sync ─────────────────────────────────────────────────────────────────
    Route::prefix('/sync')->name('sync.')->group(function () {
        Route::get('/pull', [SyncController::class, 'pull'])->name('pull');
    });
});
