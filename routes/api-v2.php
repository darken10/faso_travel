<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AuthController as AuthControllerV2;
use App\Http\Controllers\Api\V2\UserController as UserControllerV2;
use App\Http\Controllers\Api\V2\TicketController as TicketControllerV2;
use App\Http\Controllers\Api\V2\VoyageController as VoyageControllerV2;
use App\Http\Controllers\Api\V2\ArticleController as ArticleControllerV2;
use App\Http\Controllers\Api\V2\BuyVoyageController as BuyVoyageControllerV2;
use App\Http\Controllers\Api\V2\NotificationController as NotificationControllerV2;

 // API V2 - Nouvelle version avec rétrocompatibilité
Route::prefix('v2')->group(function () {
    // Routes d'authentification V2
    Route::prefix('/auth')->controller(AuthControllerV2::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware('auth:sanctum');
        Route::post('/send-otp', 'sendOtp');
        Route::post('/verify-otp', 'verifyOtp');
        Route::post('/forgot-password', 'forgotPassword');
        Route::post('/reset-password', 'resetPassword');
    });

    // Routes utilisateur V2
    Route::prefix('/user')->middleware('auth:sanctum')->controller(UserControllerV2::class)->name('api.v2.user.')->group(function () {
        Route::get('/profile', 'getProfile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('update-profile');
        Route::post('/profile/photo', 'updateProfilePicture')->name('update-photo');
        Route::get('/travel-history', 'getTravelHistory')->name('travel-history');
        Route::get('/favorite-routes', 'getFavoriteRoutes')->name('favorite-routes');
        Route::get('/stats', 'getUserStats')->name('stats');
    });

    // Routes articles V2
    Route::prefix('/articles')->controller(ArticleControllerV2::class)->name('api.v2.articles.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/categories', 'getCategories')->name('categories');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/similar', 'similar')->name('similar');
        
        // Routes protégées par authentification
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/{id}/like', 'toggleLike')->name('toggle-like');
            Route::get('/{id}/comment', 'getAllComments')->name('get-all-comment');
            Route::post('/{id}/comment', 'addComment')->name('add-comment');
            Route::delete('/comment/{commentId}/delete', 'deleteComment')->name('delete-comment');
        });
    });

    // Routes notifications V2
    Route::prefix('/notifications')->middleware('auth:sanctum')->controller(NotificationControllerV2::class)->name('api.v2.notifications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/{id}/read', 'markAsRead')->name('mark-as-read');
        Route::patch('/read-all', 'markAllAsRead')->name('mark-all-as-read');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::delete('/', 'destroyAll')->name('destroy-all');
    });

    // Routes tickets V2
    Route::prefix('/tickets')->middleware('auth:sanctum')->controller(TicketControllerV2::class)->name('api.v2.tickets.')->group(function () {
        Route::get('/', 'getUserTickets')->name('index');
        Route::get('/{ticketId}', 'getUserTicketDetails')->name('show');
        Route::post('/', 'createTicket')->name('store');
        Route::patch('/{ticketId}/cancel', 'cancelTicket')->name('cancel');
        Route::patch('/{ticketId}/transfer', 'transferTicket')->name('transfer');
        Route::get('/{ticketId}/qr-code', 'getTicketQrCode')->name('qr-code');
    });
    
    // Routes voyages V2
    Route::prefix('/trips')->controller(VoyageControllerV2::class)->name('api.v2.trips.')->group(function () {
        Route::get('/', 'getTrips')->name('index');
        Route::get('/{id}', 'getTripDetails')->name('show');
        Route::get('/{id}/seats', 'getTripSeats')->name('seats');
        Route::post('/search', 'searchTrips')->name('search');
    });

    // Routes acheter V2
    Route::prefix('/trips')->middleware('auth:sanctum')->controller(BuyVoyageControllerV2::class)->name('api.v2.buy.')->group(function () {
        Route::post('/reservation/{id}', 'reservation')->name('reservation');
    });

    Route::get('/payement/mode-list', [VoyageControllerV2::class, 'getPaymentModesList'])->name('api.v2.payment.modes.list');
});



/* Route::prefix('/admin')->controller(AdminVoyageTicketController::class)->middleware('auth:sanctum')->name('api.admin.voyage.ticket.')->group(function () {
    Route::post('/ticket', 'payement')->name('payement');
});
 */


