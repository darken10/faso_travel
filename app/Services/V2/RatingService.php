<?php

namespace App\Services\V2;

use App\Models\Compagnie\Compagnie;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;

class RatingService
{
    /**
     * Retourne les statistiques et la liste des avis d'une compagnie.
     */
    public function getStats(int $compagnieId): array
    {
        $compagnie = Compagnie::findOrFail($compagnieId);

        $ratings = Rating::with('user:id,name,first_name,last_name,profile_photo_path')
            ->where('compagnie_id', $compagnieId)
            ->orderByDesc('created_at')
            ->get();

        $total    = $ratings->count();
        $average  = $total > 0 ? round($ratings->avg('stars'), 1) : 0;

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($ratings as $r) {
            $distribution[$r->stars] = ($distribution[$r->stars] ?? 0) + 1;
        }

        $userRating = Auth::check()
            ? $ratings->firstWhere('user_id', Auth::id())
            : null;

        return [
            'average'      => $average,
            'total'        => $total,
            'distribution' => $distribution,
            'user_rating'  => $userRating ? $this->formatRating($userRating) : null,
            'ratings'      => $ratings->map(fn($r) => $this->formatRating($r))->values(),
        ];
    }

    /**
     * Crée une note pour une compagnie.
     */
    public function create(int $compagnieId, int $stars, ?string $comment = null, ?int $ticketId = null): Rating
    {
        Compagnie::findOrFail($compagnieId);

        $rating = Rating::create([
            'user_id'      => Auth::id(),
            'compagnie_id' => $compagnieId,
            'ticket_id'    => $ticketId,
            'stars'        => $stars,
            'comment'      => $comment,
        ]);

        return $rating->load('user:id,name,first_name,last_name,profile_photo_path');
    }

    /**
     * Met à jour la note d'un utilisateur.
     */
    public function update(int $ratingId, int $stars, ?string $comment = null): Rating
    {
        $rating = Rating::where('id', $ratingId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $rating->update([
            'stars'   => $stars,
            'comment' => $comment,
        ]);

        return $rating->load('user:id,name,first_name,last_name,profile_photo_path');
    }

    private function formatRating(Rating $rating): array
    {
        return [
            'id'          => $rating->id,
            'user_id'     => $rating->user_id,
            'compagnie_id' => $rating->compagnie_id,
            'ticket_id'   => $rating->ticket_id,
            'stars'       => $rating->stars,
            'comment'     => $rating->comment,
            'created_at'  => $rating->created_at?->toISOString(),
            'user'        => $rating->user ? [
                'id'                => $rating->user->id,
                'name'              => $rating->user->name,
                'profile_photo_url' => $rating->user->profile_photo_url,
            ] : null,
        ];
    }
}
