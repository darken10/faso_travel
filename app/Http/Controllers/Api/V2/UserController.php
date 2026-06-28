<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Services\V2\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\V2\AuthResource\UserResource;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get authenticated user profile
     */
    public function getProfile()
    {
        $user = $this->userService->getProfile();
        return new UserResource($user);
    }

    /** Enregistre/actualise le token push Expo de l'appareil. */
    public function registerPushToken(Request $request)
    {
        $validated = $request->validate([
            'token'    => 'required|string|max:255',
            'platform' => 'nullable|string|in:ios,android',
        ]);

        \App\Models\PushToken::updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => Auth::id(), 'platform' => $validated['platform'] ?? null],
        );

        return response()->json(['success' => true]);
    }

    /** Supprime le token push (à la déconnexion). */
    public function deletePushToken(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);
        \App\Models\PushToken::where('token', $validated['token'])->where('user_id', Auth::id())->delete();

        return response()->json(['success' => true]);
    }

    /** Synthèse fidélité + historique des points. */
    public function getLoyalty(Request $request)
    {
        $user = $request->user();
        $summary = app(\App\Services\Loyalty\LoyaltyService::class)->summary($user);

        $transactions = $user->loyaltyTransactions()->take(50)->get()->map(fn ($t) => [
            'points'     => $t->points,
            'reason'     => $t->reason,
            'created_at' => $t->created_at?->toIso8601String(),
        ]);

        return response()->json(array_merge($summary, ['transactions' => $transactions]));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . (Auth::check() ? Auth::id() : ''),
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8',
        ]);
        $user = $this->userService->updateProfile($validated);
        return new UserResource($user);
    }

    /**
     * Update user profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);
        $user = $this->userService->updateProfilePicture($request->file('photo'));
        return new UserResource($user);
    }

    /**
     * Get user travel history
     */
    public function getTravelHistory()
    {
        $history = $this->userService->getTravelHistory();
        return response()->json($history);
    }

    /**
     * Get user favorite routes
     */
    public function getFavoriteRoutes()
    {
        $favorites = $this->userService->getFavoriteRoutes();
        return response()->json($favorites);
    }

    /**
     * Get user statistics
     */
    public function getUserStats()
    {
        $stats = $this->userService->getUserStats();
        return response()->json($stats);
    }
}
