<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiV2\StationResource;
use App\Models\Compagnie\Gare;
use App\Models\Ville\Ville;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Annuaire des gares pour l'application voyageur.
 *
 * Sert l'écran « Gares & itinéraire » : trouver son point de départ, puis
 * ouvrir la carte pour s'y rendre.
 */
class StationController extends Controller
{
    use ApiResponse;

    /** Liste des gares, filtrable par texte libre ou par ville. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'       => 'nullable|string|max:100',
            'ville_id'     => 'nullable|integer|exists:villes,id',
            'compagnie_id' => 'nullable|integer|exists:compagnies,id',
            'with_coords'  => 'nullable|boolean',
        ]);

        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $gares = Gare::query()
            ->withoutGlobalScopes()
            ->with(['ville.region', 'compagnie'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhereHas('ville', fn ($v) => $v->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('compagnie', fn ($c) => $c->where('name', 'like', "%{$search}%")
                            ->orWhere('sigle', 'like', "%{$search}%"));
                });
            })
            ->when($filters['ville_id'] ?? null, fn ($q, $villeId) => $q->where('ville_id', $villeId))
            ->when($filters['compagnie_id'] ?? null, fn ($q, $id) => $q->where('compagnie_id', $id))
            ->when($filters['with_coords'] ?? false, fn ($q) => $q->whereNotNull('lat')->whereNotNull('lng')
                ->where('lat', '!=', 0)->where('lng', '!=', 0))
            ->orderBy('name')
            ->get();

        // Les villes proposées en filtre sont celles qui possèdent réellement une gare.
        $villes = Ville::query()
            ->whereHas('gares')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Ville $ville) => ['id' => $ville->id, 'name' => $ville->name]);

        return $this->successResponse([
            'stations' => StationResource::collection($gares)->resolve(),
            'cities'   => $villes,
            'count'    => $gares->count(),
        ]);
    }

    /** Détail d'une gare. */
    public function show(Gare $station): JsonResponse
    {
        $station->loadMissing(['ville.region', 'compagnie']);

        return $this->successResponse([
            'station' => (new StationResource($station))->resolve(),
        ]);
    }
}
