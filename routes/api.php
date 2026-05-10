<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Voyage\VoyageApiContoller;
use App\Http\Controllers\Api\Admin\Voyage\VoyageController;
use App\Http\Controllers\Ticket\Payement\PaymentController2;
use App\Http\Controllers\Api\Admin\Ticket\TicketApiController;

// ─── Authentification V1 ──────────────────────────────────────────────────────
Route::prefix('/auth')->name('api.auth.')->middleware('throttle:api-auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn(Request $request) => $request->user())->name('me');
    });
});

// ─── Tickets admin (agent de compagnie) ──────────────────────────────────────
Route::prefix('/ticket')
    ->name('api.ticket.')
    ->controller(TicketApiController::class)
    ->middleware(['auth:sanctum', 'requires.compagnie'])
    ->group(function () {
        Route::post('/verification/with-number', 'verificationByNumber')->name('verification-by-number');
        Route::get('/verification/{ticket_code}', 'verificationByQrCode')->name('verification-by-qrcode');
        Route::post('/valider', 'validerTicket')->name('valider-ticket');
        Route::post('/{ticket}/change-status', 'changeStatus')->name('change-status');
    });

// ─── Voyages compagnie ────────────────────────────────────────────────────────
Route::prefix('/compagnie/voyages')
    ->name('api.compagnie.voyage.')
    ->controller(VoyageController::class)
    ->middleware(['auth:sanctum', 'requires.compagnie'])
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{voyage}', 'showWithPassagers')->name('show-with-passagers');
        Route::get('/instance/{voyageInstance}', 'getVoyageInstanceDetails')->name('instance-details');
    });

// ─── Voyages client ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('voyages')->name('api.user.')->group(function () {
    Route::get('/', [VoyageApiContoller::class, 'index'])->name('index');
    Route::get('/{voyageInstanceId}/details', [VoyageApiContoller::class, 'details'])->name('details')
        ->whereUuid('voyageInstanceId');
    Route::get('/{voyageInstanceId}/tickets', [VoyageApiContoller::class, 'tickets'])->name('tickets')
        ->whereUuid('voyageInstanceId');
    Route::post('/{voyageInstanceId}/book', [VoyageApiContoller::class, 'book'])->name('book')
        ->whereUuid('voyageInstanceId');
    Route::post('/{voyageInstanceId}/cancel', [VoyageApiContoller::class, 'cancel'])->name('cancel')
        ->whereUuid('voyageInstanceId');
    Route::get('/{voyageInstanceId}/available-seats', [VoyageApiContoller::class, 'availableSeats'])->name('available-seats')
        ->whereUuid('voyageInstanceId');
    Route::get('/{voyageInstanceId}/passengers', [VoyageApiContoller::class, 'passengers'])->name('passengers')
        ->whereUuid('voyageInstanceId');
    Route::get('/{voyageInstanceId}/status', [VoyageApiContoller::class, 'status'])->name('status')
        ->whereUuid('voyageInstanceId');
    Route::get('/{voyageInstanceId}', [VoyageApiContoller::class, 'show'])->name('show')
        ->whereUuid('voyageInstanceId');
});

// ─── Tickets (début d'achat) ──────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('tickets')->name('api.ticket.')->group(function () {
    Route::post('/{voyageInstance}', [TicketApiController::class, 'debutAchat'])
        ->name('debut-achat')
        ->whereUuid('voyageInstance');
});

// ─── Posts ────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('posts')->name('api.posts.')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::post('/{post}/like', [PostController::class, 'likePost'])->name('like');
    Route::delete('/{post}/like', [PostController::class, 'unlikePost'])->name('unlike');
    Route::post('/{post}/addcomment', [PostController::class, 'addComment'])->name('comment');
    Route::get('/{post}/likes', [PostController::class, 'getPostLikes'])->name('likes');
    Route::get('/{post}/comments', [PostController::class, 'getPostComments'])->name('comments');
    Route::get('/{id}', [PostController::class, 'show'])->name('show')->where('id', '[0-9]+');
});

// ─── Utilisateur authentifié ──────────────────────────────────────────────────
Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');

// ─── Paiement (protégé) ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api-payment'])->post('/process-payment/{provider}', [PaymentController2::class, 'processPayment'])
    ->name('api.payment.process');

require __DIR__ . '/api-v2.php';
require __DIR__ . '/admin.php';
