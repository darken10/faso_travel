<?php

use App\Http\Controllers\Auth\MyRegisterController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\PhoneLoginController;
use App\Http\Controllers\Auth\PhonePasswordResetController;
use App\Http\Controllers\Compagnie\CompagnieController;
use App\Http\Controllers\Divers\ConditionConfidentialiteController;
use App\Http\Controllers\Divers\NotificationsController;
use App\Http\Controllers\Ticket\Payement\PaymentController2;
use App\Http\Controllers\Voyages\VoyageInstanceController;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Ticket\Payement\OrangePayementController;
use App\Http\Controllers\Ticket\TicketController;
use App\Http\Controllers\Ticket\VoyageController;

$domain = config('app.domain', 'liptra.net');

// ════════════════════════════════════════════════════════════════════════════
// Routes SANS contrainte de domaine
// (callbacks paiement, activation de compte, dashboard post-login)
// ════════════════════════════════════════════════════════════════════════════

// ─── Dashboard post-login : redirige selon le rôle ───────────────────────────
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () use ($domain) {
    Route::get('/dashboard', function () use ($domain) {
        $user = auth()->user();
        $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';

        if (in_array($user->role, [UserRole::Admin, UserRole::Root])) {
            return redirect($scheme.'://admin.'.$domain.'/');
        }
        if ($user->compagnie_id) {
            return redirect($scheme.'://compagnie.'.$domain.'/');
        }
        return redirect($scheme.'://app.'.$domain.'/');
    })->name('dashboard');
});

// ─── Callbacks paiement (appelés par les prestataires, sans auth) ────────────
Route::prefix('/process-payment/{ticket}/{provider}')->name('controller-payment.')->group(function () {
    Route::get('/success', [PaymentController2::class, 'successFunction'])->name('success');
    Route::get('/cancel',  [PaymentController2::class, 'cancelFunction'])->name('cancel');
    Route::post('/callback', [PaymentController2::class, 'callbackFunction'])->name('callback');
});

// ─── Activation de compte (lien email, accessible depuis n'importe quel domaine)
Route::get('/account/activate/{token}',  [AccountActivationController::class, 'show'])->name('account.activate');
Route::post('/account/activate/{token}', [AccountActivationController::class, 'activate'])->name('account.activate.post');

// ─── Validation tickets par agents (QR code) ─────────────────────────────────
Route::prefix('/validation')->name('admin.validation.')->middleware('auth')
    ->controller(\App\Http\Controllers\Admin\Ticket\TicketController::class)->group(function () {
        Route::get('/verification/{ticket}',        'verification')->name('verification')->where(['ticket' => '[0-9]+']);
        Route::post('/validation/{ticket}',         'valider')->name('valider')->where(['ticket' => '[0-9]+']);
        Route::get('/verification-by-numero-code',  'searchByTelAndCodePage')->name('search-by-tel-and-code-page');
        Route::post('/verification-by-numero-code', 'searchByTelAndCode')->name('search-by-tel-and-code-page-post');
    });


// ════════════════════════════════════════════════════════════════════════════
// app.{domain} — Espace client
// ════════════════════════════════════════════════════════════════════════════
Route::domain('app.'.$domain)->group(function () {

    // ─── Feed / Social ────────────────────────────────────────────────────
    Route::prefix('/')->name('post.')->middleware(['auth', 'verified'])
        ->controller(PostController::class)->group(function () {
            Route::get('/',             'index')->name('index');
            Route::get('/{post}',       'show')->name('show')->where(['post' => '[0-9]+']);
            Route::get('/tag/{tag}',    'filterByTag')->name('filterByTag')->where(['tag' => '[0-9]+']);
            Route::get('/like/list/{post}', 'likeList')->name('likeList')->where(['post' => '[0-9]+']);
        });

    // ─── Voyages ──────────────────────────────────────────────────────────
    Route::prefix('/voyage')->name('voyage.')->middleware('auth')
        ->controller(VoyageController::class)->group(function () {
            Route::get('/',                                              'index')->name('index');
            Route::get('/{voyage}',                                      'show')->name('show')->where(['voyage' => '[0-9]+']);
            Route::get('/voyage-instance/{voyageInstance}',             'showVoyageInstance')->name('instance.show');
            Route::get('/is-my-ticket/{voyageInstance}',                'is_my_ticket')->name('is_my_ticket');
            Route::post('/is-my-ticket-achat/{voyageInstance}',         'is_my_ticket_traitement')->name('is_my_ticket_traitement');
            Route::get('/voyage-instance/acheter/{voyageInstance}',     'acheterVoyageInstance')->name('instance.acheter');
            Route::get('/achete/{voyage}',                              'acheter')->name('acheter')->where(['voyage' => '[0-9]+']);
            Route::get('/my-ticket/achete/{ticket}',                   'payerAutrePersonneTicket')->name('payerAutrePersonneTicket')->where(['ticket' => '[0-9]+']);
            Route::get('/is-my-ticket/autre-ticket-info/{voyage}',     'autre_ticket_info')->name('autre-ticket-info')->where(['voyage' => '[0-9]+']);
            Route::post('/is-my-ticket/autre-ticket-info/{voyage}',    'register_autre_personne')->name('register-autre-personne')->where(['voyage' => '[0-9]+']);
            Route::get('/is-my-ticket/autre-ticket-info/{voyage}/{autre_personne}', 'payer_ticket_autre_personne')->name('payer-ticket-autre-personne')->where(['voyage' => '[0-9]+']);
        });

    // ─── Tickets ──────────────────────────────────────────────────────────
    Route::prefix('/ticket')->name('ticket.')->middleware('auth')
        ->controller(TicketController::class)->group(function () {
            Route::post('/payer/{voyage}',                              'createTicket')->name('payer')->where(['voyage' => '[0-9]+']);
            Route::post('/payer/voyage-instance/{voyage_instance}',    'createTicketWithVoyageInstance')->name('payer-with-voyage-instance');
            Route::get('/mes-tickets',                                 'myTickets')->name('myTickets');
            Route::get('/mes-tickets/{ticket}/edite',                  'editTicket')->name('editTicket');
            Route::get('/mes-tickets/{ticket}',                        'showMyTicket')->name('show-ticket')->where(['ticket' => '[0-9]+']);
            Route::get('/mes-tickets/{ticket}/pdf',                    'downloadPdf')->name('download-pdf')->where(['ticket' => '[0-9]+']);
            Route::get('/mes-tickets/{ticket}/navigation',             'navigateToGare')->name('navigate-to-gare')->where(['ticket' => '[0-9]+']);
            Route::get('/re-envoyer/{ticket}',                        'reenvoyer')->name('reenvoyer')->where(['ticket' => '[0-9]+']);
            Route::get('/regenerer/{ticket}',                          'regenerer')->name('regenerer')->where(['ticket' => '[0-9]+']);
            Route::post('/mes-tickets/{ticket}/pause',                 'mettreEnPause')->name('mettre-en-pause');
            Route::get('/mes-tickets/{ticket}/payement',               'gotoPayment')->name('goto-payment');
            Route::get('/transferer/{ticket}',                         'tranfererTicketToOtherUser')->name('transferer-ticket-to-other-user')->where(['ticket' => '[0-9]+']);
            Route::post('/transferer/{ticket}',                        'tranfererTicketToOtherUserTraitement')->name('transferer-ticket-to-other-user-traitement')->where(['ticket' => '[0-9]+']);
            Route::post('/transferer/{ticket}/traitement',             'tranfererTicketTraitement')->name('transferer-ticket-traitement')->where(['ticket' => '[0-9]+']);
        });

    // ─── Paiement Orange ──────────────────────────────────────────────────
    Route::prefix('/payement')->name('payement.')->middleware('auth')->group(function () {
        Route::prefix('/orange')->name('orange.')->controller(OrangePayementController::class)->group(function () {
            Route::get('/{ticket}',  'paymentPage')->name('paymentPage')->where(['ticket' => '[0-9]+']);
            Route::post('/{ticket}', 'payer')->name('payer')->where(['ticket' => '[0-9]+']);
        });
    });

    // ─── Paiement générique (protégé) ─────────────────────────────────────
    Route::middleware('auth')->post('/process-payment2/{ticket}/{provider}', [PaymentController2::class, 'processPayment'])
        ->name('controller2-payment.payment-process')
        ->where(['ticket' => '[0-9]+', 'provider' => '[a-zA-Z]+']);

    // ─── Notifications ────────────────────────────────────────────────────
    Route::middleware('auth')->group(function () {
        Route::get('/notifications',               [NotificationsController::class, 'allNotifications'])->name('user.notifications');
        Route::get('/notifications/{notificationId}', [NotificationsController::class, 'showNotification'])->name('user.notifications.show');
    });

    // ─── Pages compagnies (publiques) ─────────────────────────────────────
    Route::get('/compagnies',        [CompagnieController::class, 'index'])->name('client.compagnies.index');
    Route::get('/compagnies/{compagnie}', [CompagnieController::class, 'show'])->name('client.compagnie.show')->where(['compagnie' => '[0-9]+']);

    // ─── Pages statiques ──────────────────────────────────────────────────
    Route::get('/politique-de-confidentialite', [ConditionConfidentialiteController::class, 'confidentialite'])->name('divers.politique-confidentialite');
    Route::get('/termes-et-conditions',         [ConditionConfidentialiteController::class, 'condition'])->name('divers.termes-et-conditions');
    Route::get('/about-us',                     [ConditionConfidentialiteController::class, 'about'])->name('divers.about-us');
    Route::get('/contact',                      [ConditionConfidentialiteController::class, 'contact'])->name('divers.contact');

    // ─── Inscription multi-étapes ──────────────────────────────────────────
    Route::prefix('/auth/register')->name('auth.register.')->controller(MyRegisterController::class)->group(function () {
        Route::get('/step1',  'step1')->name('step1');
        Route::get('/step2',  'step2')->name('step2');
        Route::get('/step3',  'step3')->name('step3');
        Route::post('/step1', 'post_step1')->name('post_step1');
        Route::post('/step2', 'post_step2')->name('post_step2');
        Route::post('/step3', 'post_step3')->name('post_step3');
    });

    // ─── Reset mot de passe par téléphone (SMS / WhatsApp) ───────────────
    Route::prefix('/mot-de-passe-oublie/telephone')->name('password.phone.')->controller(PhonePasswordResetController::class)->group(function () {
        Route::get('/',          'showPhoneForm')->name('form');
        Route::post('/',         'sendOtp')->name('send');
        Route::get('/verifier',  'showResetForm')->name('verify-form');
        Route::post('/verifier', 'reset')->name('reset');
    });
});


// ════════════════════════════════════════════════════════════════════════════
// Route publique — QR code image (utilisée dans les emails, pas d'auth)
// Le code_qr est un jeton aléatoire 32 chars, c'est lui le secret.
// ════════════════════════════════════════════════════════════════════════════
Route::get('/qr/{code}', [\App\Http\Controllers\Ticket\QrCodeImageController::class, '__invoke'])
    ->middleware('throttle:60,1')
    ->name('ticket.qrcode.image')
    ->where('code', '[A-Za-z0-9_\-]{10,64}');

// ════════════════════════════════════════════════════════════════════════════
// admin.{domain} — Administration
// ════════════════════════════════════════════════════════════════════════════
Route::domain('admin.'.$domain)->name('panel.admin.')->middleware(['auth', 'verified', 'panel.admin'])->group(function () {
    Route::get('/',           \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/pays',       \App\Livewire\Admin\PaysManager::class)->name('pays');
    Route::get('/regions',    \App\Livewire\Admin\RegionManager::class)->name('regions');
    Route::get('/villes',     \App\Livewire\Admin\VilleManager::class)->name('villes');
    Route::get('/compagnies', \App\Livewire\Admin\CompagnieManager::class)->name('compagnies');
    Route::get('/settings',   \App\Livewire\Admin\SettingsManager::class)->name('settings');

    // Génération des instances voyages
    Route::get('/create-voyages-instances', [VoyageInstanceController::class, 'createAllInstance'])->name('create-all-voyages-instances');
});


// ════════════════════════════════════════════════════════════════════════════
// compagnie.{domain} — Espace compagnie
// ════════════════════════════════════════════════════════════════════════════
Route::domain('compagnie.'.$domain)->name('panel.compagnie.')->middleware(['auth', 'verified', 'panel.compagnie'])->group(function () {
    Route::get('/', \App\Livewire\Compagnie\Dashboard::class)->name('dashboard');

    // ─── Voyage ───────────────────────────────────────────────────────────
    Route::get('/trajets',   \App\Livewire\Compagnie\Voyage\TrajetManager::class)->name('trajets');
    Route::get('/voyages',   \App\Livewire\Compagnie\Voyage\VoyageManager::class)->name('voyages');
    Route::get('/voyages/create',          \App\Livewire\Compagnie\Voyage\VoyageForm::class)->name('voyages.create');
    Route::get('/voyages/{voyageId}/edit', \App\Livewire\Compagnie\Voyage\VoyageForm::class)->name('voyages.edit');
    Route::get('/voyages/{voyageId}',      \App\Livewire\Compagnie\Voyage\VoyageShow::class)->name('voyages.show');
    Route::get('/classes',   \App\Livewire\Compagnie\Voyage\ClasseManager::class)->name('classes');
    Route::get('/instances', \App\Livewire\Compagnie\Voyage\VoyageInstanceManager::class)->name('instances');
    Route::get('/instances/{instanceId}', \App\Livewire\Compagnie\Voyage\VoyageInstanceShow::class)->name('instances.show');

    // ─── Guichet ──────────────────────────────────────────────────────────
    Route::get('/vente-ticket',       \App\Livewire\Compagnie\Ticket\VenteTicket::class)->name('vente-ticket');
    Route::get('/tickets',            \App\Livewire\Compagnie\Ticket\TicketManager::class)->name('tickets');
    Route::get('/tickets/{ticketId}', \App\Livewire\Compagnie\Ticket\TicketShow::class)->name('tickets.show');
    Route::get('/caisse',             \App\Livewire\Compagnie\Caisse\GestionCaisse::class)->name('caisse');
    Route::get('/caisse/{caisse}',    \App\Livewire\Compagnie\Caisse\DetailCaisse::class)->name('caisse.detail');
    Route::get('/caisses-historique', \App\Livewire\Compagnie\Caisse\HistoriqueCaisses::class)->name('caisses-historique');

    // ─── Ressources ───────────────────────────────────────────────────────
    Route::get('/gares',     \App\Livewire\Compagnie\Compagnie\GareManager::class)->name('gares');
    Route::get('/cares',     \App\Livewire\Compagnie\Compagnie\CareManager::class)->name('cares');
    Route::get('/cares/{careId}', \App\Livewire\Compagnie\Compagnie\CareShow::class)->name('cares.show');
    Route::get('/chauffeurs', \App\Livewire\Compagnie\Compagnie\ChauffeurManager::class)->name('chauffeurs');
    Route::get('/chauffeurs/{chauffeurId}', \App\Livewire\Compagnie\Compagnie\ChauffeurShow::class)->name('chauffeurs.show');
    Route::get('/users',     \App\Livewire\Compagnie\Compagnie\UserManager::class)->name('users');

    // ─── Contenu ──────────────────────────────────────────────────────────
    Route::get('/posts',              \App\Livewire\Compagnie\Post\PostManager::class)->name('posts');
    Route::get('/posts/create',       \App\Livewire\Compagnie\Post\PostForm::class)->name('posts.create');
    Route::get('/posts/{postId}/edit', \App\Livewire\Compagnie\Post\PostForm::class)->name('posts.edit');
    Route::get('/documents', \App\Livewire\Compagnie\Document\DocumentManager::class)->name('documents');

    // ─── Comptabilité ─────────────────────────────────────────────────────
    Route::get('/bilan',      \App\Livewire\Compagnie\Finance\BilanFinancier::class)->name('bilan');
    Route::get('/depenses',   \App\Livewire\Compagnie\Finance\DepenseManager::class)->name('depenses');
    Route::get('/recettes',   \App\Livewire\Compagnie\Finance\RecetteManager::class)->name('recettes');
    Route::get('/categories', \App\Livewire\Compagnie\Finance\CategorieManager::class)->name('categories');
});
