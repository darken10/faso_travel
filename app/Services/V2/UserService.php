<?php

namespace App\Services\V2;

use Carbon\Carbon;
use App\Enums\StatutTicket;
use App\Http\Resources\ApiV2\TravelHistoryResource;
use App\Models\User;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Trajet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * Get authenticated user profile
     *
     * @return User
     */
    public function getProfile(): User
    {
        return Auth::user();
    }

    /**
     * Update user profile
     *
     * @param array $data
     * @return User
     */
    public function updateProfile(array $data): User
    {
        $user = Auth::user();
        
        $user->name = $data['name'] ?? $user->name;
        $user->email = $data['email'] ?? $user->email;
        $user->phone = $data['phone'] ?? $user->phone;
        
        if (isset($data['password']) && !empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        
        $user->save();
        
        return $user;
    }

    /**
     * Update user profile picture
     *
     * @param UploadedFile $file
     * @return User
     */
    public function updateProfilePicture(UploadedFile $file): User
    {
        $user = Auth::user();
        
        // Supprimer l'ancienne image si elle existe
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }
        
        // Enregistrer la nouvelle image
        $path = $file->store('users/profile', 'public');
        $user->photo = $path;
        $user->save();
        
        return $user;
    }

    /**
     * Historique de voyages du client, mis en forme pour l'application mobile.
     *
     * @return array{trips: array<int, mixed>, count: int, stats: array<string, mixed>}
     */
    public function getTravelHistory(): array
    {
        $tickets = Ticket::query()
            ->with([
                'voyageInstance.voyage.trajet.depart',
                'voyageInstance.voyage.trajet.arriver',
                'voyageInstance.voyage.compagnie',
                'voyageInstance.voyage.gareDepart',
                'voyageInstance.voyage.gareArriver',
            ])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $trips = TravelHistoryResource::collection($tickets)->resolve();

        return [
            'trips' => $trips,
            'count' => count($trips),
            'stats' => $this->buildTravelStats($trips),
        ];
    }

    /**
     * Quelques repères affichés en tête de l'historique.
     *
     * @param  array<int, array<string, mixed>>  $trips
     * @return array<string, mixed>
     */
    private function buildTravelStats(array $trips): array
    {
        $realises = array_filter(
            $trips,
            fn (array $trip) => ($trip['is_past'] ?? false)
                && in_array($trip['status'] ?? null, [StatutTicket::Valider->value, StatutTicket::Payer->value], true),
        );

        $villes = [];
        foreach ($realises as $trip) {
            foreach ([$trip['departure']['city'] ?? null, $trip['arrival']['city'] ?? null] as $ville) {
                if ($ville) {
                    $villes[$ville] = true;
                }
            }
        }

        return [
            'total_trips'     => count($trips),
            'completed_trips' => count($realises),
            'cities_visited'  => count($villes),
            'total_spent'     => (float) array_sum(array_column($realises, 'price')),
        ];
    }

    /**
     * Get user favorite routes
     *
     * @return array
     */
    public function getFavoriteRoutes(): array
    {
        $user = Auth::user();
        
        $favoriteRoutes = $user->favoris()
            ->with(['depart', 'arriver'])
            ->get();
        
        return [
            'count' => $favoriteRoutes->count(),
            'routes' => $favoriteRoutes
        ];
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getUserStats(): array
    {
        $user = Auth::user();
        
        $totalTickets = Ticket::where('user_id', $user->id)->count();
        
        $ticketsThisMonth = Ticket::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        $favoriteRoutes = $user->favoris()->count();
        
        $mostVisitedRoute = Ticket::with(['voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('voyageInstance.voyage.trajet_id')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'trajet' => $group->first()->voyageInstance->voyage->trajet
                ];
            })
            ->sortByDesc('count')
            ->first();
        
        return [
            'total_tickets' => $totalTickets,
            'tickets_this_month' => $ticketsThisMonth,
            'favorite_routes' => $favoriteRoutes,
            'most_visited_route' => $mostVisitedRoute ?? null
        ];
    }
}
