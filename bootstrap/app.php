<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'               => \App\Http\Middleware\Role::class,
            'requires.compagnie' => \App\Http\Middleware\RequiresCompagnie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Gestion des erreurs pour les requêtes API
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                $statusCode = $e->getCode() >= 100 && $e->getCode() < 600 
                    ? $e->getCode() 
                    : 500;

                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'status' => $statusCode,
                ];

                // Ajouter plus de détails en environnement de développement
                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace()
                    ];
                }

                // Gestion des erreurs de validation
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $response['errors'] = $e->errors();
                    $statusCode = 422; // Unprocessable Entity
                }

                // Gestion des erreurs d'authentification
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $statusCode = 401; // Unauthorized
                    $response['message'] = 'Non authentifié';
                }

                // Gestion des erreurs 404
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                    $response['message'] = 'Ressource non trouvée';
                }

                // Gestion des erreurs d'autorisation
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $statusCode = 403;
                    $response['message'] = $e->getMessage() ?: 'Accès non autorisé';
                }

                // Gestion des erreurs de modèle non trouvé
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $statusCode = 404;
                    $response['message'] = 'Ressource demandée introuvable';
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
