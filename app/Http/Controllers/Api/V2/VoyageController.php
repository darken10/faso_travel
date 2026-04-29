<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\V2\VoyageService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiV2\TripResource;

class VoyageController extends Controller
{
    protected VoyageService $voyageService;

    public function __construct(VoyageService $voyageService)
    {
        $this->voyageService = $voyageService;
    }

    /**
     * Get all available trips with optional filters
     *
     * @param Request $request
     */
    public function getTrips(Request $request)
    {
        try {
            $filters = [
                'departureCity' => $request->query('departureCity'),
                'arrivalCity' => $request->query('arrivalCity'),
                'date' => $request->query('date'),
                'company' => $request->query('company'),
                'passengers' => $request->query('passengers'),
            ];

            $trips = $this->voyageService->getTrips($filters);

            return TripResource::collection($trips);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trip details by ID
     *
     * @param string $id
     */
    public function getTripDetails(string $id)
    {
        try {
            $trip = $this->voyageService->getTripDetails($id);

            return TripResource::make($trip);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get seats availability for a specific trip
     *
     * @param string $id
     */
    public function getTripSeats(string $id)
    {
        try {
            $seats = $this->voyageService->getTripSeats($id);
            return response()->json($seats);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'total_seats' => 0,
                'available_seats' => 0,
                'occupied_seats' => 0,
                'seats' => [],
                'message' => 'Voyage introuvable'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'total_seats' => 0,
                'available_seats' => 0,
                'occupied_seats' => 0,
                'seats' => [],
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for trips with advanced filters
     */
    public function searchTrips(Request $request)
    {
        $filters = $request->validate([
            'departureCity' => 'nullable|integer|exists:villes,id',
            'arrivalCity' => 'nullable|integer|exists:villes,id',
            'date' => 'nullable|date_format:Y-m-d',
            'company' => 'nullable|integer|exists:compagnies,id',
            'passengers' => 'nullable|integer|min:1',
            'vehicleType' => 'nullable|string|in:bus,train,ferry',
        ]);
        $trips = $this->voyageService->searchTrips($filters);
        return response()->json($trips);
    }


    /**
     * Get payment modes list
     */
    public function getPaymentModesList()
    {
        $paymentModes = $this->voyageService->getPaymentModesList();
        return response()->json($paymentModes);
    }
}
