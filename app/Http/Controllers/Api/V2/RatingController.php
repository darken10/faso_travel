<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Compagnie\Compagnie;
use App\Services\V2\RatingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(protected RatingService $service)
    {
    }

    /**
     * GET /v2/companies
     * Liste toutes les compagnies avec leur note moyenne.
     */
    public function listCompanies(): JsonResponse
    {
        $companies = Compagnie::select(['id', 'name', 'sigle', 'description', 'logo_uri'])
            ->withCount('ratings')
            ->withAvg('ratings', 'stars')
            ->orderByDesc('ratings_avg_stars')
            ->orderBy('name')
            ->get()
            ->map(fn (Compagnie $c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'sigle'        => $c->sigle,
                'description'  => $c->description,
                'logo'         => $c->logo_uri,
                'avg_rating'   => round((float) ($c->ratings_avg_stars ?? 0), 1),
                'total_ratings' => $c->ratings_count,
            ]);

        return response()->json(['data' => $companies]);
    }

    /**
     * GET /v2/companies/{id}/ratings
     * Statistiques et liste des avis d'une compagnie.
     */
    public function index(int $id): JsonResponse
    {
        $stats = $this->service->getStats($id);

        return response()->json(['data' => $stats]);
    }

    /**
     * POST /v2/companies/{id}/ratings
     * Crée une note pour la compagnie.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stars'     => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string|max:500',
            'ticket_id' => 'nullable|exists:tickets,id',
        ]);

        try {
            $rating = $this->service->create(
                $id,
                $validated['stars'],
                $validated['comment'] ?? null,
                $validated['ticket_id'] ?? null,
            );
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Vous avez déjà noté cette compagnie. Utilisez la mise à jour.',
            ], 422);
        }

        return response()->json(['data' => $rating], 201);
    }

    /**
     * PUT /v2/ratings/{id}
     * Met à jour la note de l'utilisateur.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = $this->service->update($id, $validated['stars'], $validated['comment'] ?? null);

        return response()->json(['data' => $rating]);
    }
}
