<?php

namespace App\Services\V2;

use Carbon\Carbon;
use App\Enums\TypeTicket;
use App\Models\Ville;
use App\Models\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Voyage\VoyageInstance;
use App\Http\Resources\ApiV2\TripResource;

class VoyageService
{
    /**
     * Get all available trips with optional filters
     *
     * @param array $filters
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getTrips(array $filters = [])
    {
        $query = VoyageInstance::query()
            ->with([
                'voyage.trajet.depart',
                'voyage.trajet.arriver',
                'voyage.compagnie',
                'voyage.classe',
                'care'
            ])
            ->whereHas('voyage', function ($q) {
                $q->where('is_quotidient', true);
            });


        // Filter by departure city
        if (isset($filters['departureCity']) && !empty($filters['departureCity'])) {
            $query->whereHas('voyage.trajet.depart', function ($q) use ($filters) {
                $q->where('name', "LIKE", '%' . $filters['departureCity'] . '%');
            });
        }

        // Filter by arrival city
        if (isset($filters['arrivalCity']) && !empty($filters['arrivalCity'])) {
            $query->whereHas('voyage.trajet.arriver', function ($q) use ($filters) {
                $q->where('name', "LIKE", '%' . $filters['arrivalCity'] . '%');
            });
        }

        // Filter by date
        if (isset($filters['date']) && !empty($filters['date'])) {
            $date = Carbon::parse($filters['date'])->format('Y-m-d');
            $query->whereDate('date', $date);
        } else {
            // Default to future dates
            $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
        }

        // Filter by company
        if (isset($filters['company']) && !empty($filters['company'])) {
            $query->whereHas('voyage.compagnie', function ($q) use ($filters) {
                $q->where('name', "LIKE", '%' . $filters['company'] . '%');
            });
        }

        // Filter by minimum seats if needed
        if (isset($filters['passengers']) && is_numeric($filters['passengers'])) {
            $query->whereHas('care', function($q) use ($filters) {
                $q->where('number_place', '>=', $filters['passengers']);
            });
        }

        // Order by departure time
        $query->orderBy('date')
              ->orderBy(DB::raw("TIME(heure)"));

        // Get results and apply resource transformation
        $voyageInstances = $query->paginate(20);

        return $voyageInstances;
    }

    /**
     * Get trip details by ID
     *
     * @param string $tripId
     */
    public function getTripDetails(string $tripId) :VoyageInstance
    {
        $instance = VoyageInstance::with([
            'voyage.trajet.depart',
            'voyage.trajet.arriver',
            'voyage.compagnie',
            'voyage.classe',
            'care'
        ])->findOrFail($tripId);

        return $instance;
    }

    /**
     * Get seats availability for a specific trip
     *
     * @param string $tripId
     * @return array
     */
    public function getTripSeats(string $tripId): array
    {
        $instance = VoyageInstance::with('care')->findOrFail($tripId);
        $totalSeats = $instance->care?->number_place ?? 50;
        $occupiedSeats = Ticket::where('voyage_instance_id', $tripId)
            ->where('statut', '!=', 'annuler')
            ->get();
        $availableSeats = $totalSeats - $occupiedSeats->count();
        
        // Generate seats information
        $seats = [];
        for ($i = 1; $i <= $totalSeats; $i++) {
            $occupiedSeat = $occupiedSeats->firstWhere('numero_place', $i);
            $status = $occupiedSeat ? 'occupied' : 'available';

            $seats[] = [
                'id' => (string) $i,
                'name' => (string) $i,
                'status' => $status,
                'price' => $instance->getPrix(TypeTicket::AllerSimple),
                'type' => 'standard', // Default to standard, can be enhanced later
                'is_available' => $status === 'available'
            ];
        }


        return [
            'total_seats' => $totalSeats,
            'available_seats' => $availableSeats,
            'occupied_seats' => $occupiedSeats->count(),
            'seats' => $seats


        ];
    }


    /**
     * Search for trips with advanced filters
     *
     * @param array $filters
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function searchTrips(array $filters)
    {
        return $this->getTrips($filters);
    }


    /**
     * Get payment modes list
     *
     * @return array
     */
    public function getPaymentModesList(): array
    {
        // This method should return the list of available payment modes
        // For now, we return a static list as an example
        return [
            ['id' => 1, 'type' => 'orange_money', 'name' => 'Orange Money', 'redirect_url' => 'https://example.com/orange-money', 'icon' => 'https://imgs.search.brave.com/D4wsN7sPcXMsTHLG9zhQd9f8mhKv8jFQB2U1MjRvW00/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9sb2dv/cy13b3JsZC5uZXQv/d3AtY29udGVudC91/cGxvYWRzLzIwMjMv/MDIvT3JhbmdlLU1v/bmV5LUxvZ28tNTAw/eDI4MS5wbmc'],
            ['id' => 2, 'type' => 'moov_money', 'name' => 'Moov Money', 'redirect_url' => 'https://example.com/moov-money', 'icon' => 'https://imgs.search.brave.com/TSqEM5On-zjKkjF3MaeuLcbuNBmUpMgkWYrUZ0cMjjE/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jaGFu/Z2Uuc24vYXNzZXRz/L2ltYWdlcy9tb292/X2NpLnBuZw'],
            ['id' => 3, 'type' => 'telecel_money', 'name' => 'Telecel Money', 'redirect_url' => 'https://example.com/telecel-money', 'icon' => 'https://imgs.search.brave.com/UX_z0Ax8m515zy9Kbp8TJL7WU50CpAqaJ1rC0ugHHNM/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly93d3cu/aW1laS5pbmZvL21l/ZGlhL29wL1RlbGVj/ZWxfYnVya2luYWZh/c28uanBn'],
            ['id' => 4, 'type' => 'coris_money', 'name' => 'Coris Money', 'redirect_url' => 'https://example.com/coris-money', 'icon' => 'https://imgs.search.brave.com/HYdpQ-KeNi98bGyvuCKZHqB2E-pjll1LIGzUCpHRKVc/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9wbGF5/LWxoLmdvb2dsZXVz/ZXJjb250ZW50LmNv/bS9WMS0waGhyVVo2/T3lZR1h4WktJRGU0/SkxWZDJPcHhqUFpB/cUZ4cFNKd3lkNThH/TTFlMlpNVk5nMWVE/bjhxVTlYdXA0PXc0/MTYtaDIzNS1ydw==/bWVkaWEvcG5n'],
            ['id' => 5, 'type' => 'visa', 'name' => 'Visa', 'redirect_url' => 'https://example.com/visa', 'icon' => 'https://imgs.search.brave.com/rzq1AJ4EalILI8sadO2gmtM7kRXiioeatBtfKwHG5Uo/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly93d3cu/ZnJlZXBuZ2xvZ29z/LmNvbS91cGxvYWRz/L3Zpc2EtY2FyZC1h/bmQtbWFzdGVyY2Fy/ZC1sb2dvLXBuZy0y/OC5wbmc']
        ];
    }
}
