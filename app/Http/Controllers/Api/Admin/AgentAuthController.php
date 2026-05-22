<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class AgentAuthController extends Controller
{
    /**
     * Login d'un agent par email OU numéro de téléphone.
     *
     * Payload: { "credential": "email@example.com|+22670000000", "password": "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'credential' => 'required|string',
            'password'   => 'required|string',
        ]);

        $credential = trim($request->input('credential'));
        $password   = $request->input('password');

        // Determine lookup field
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero';

        $user = User::where($field, $credential)
            ->whereNotNull('compagnie_id')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez contacter votre administrateur.',
            ], 401);
        }

        // Revoke old tokens to avoid accumulation
        $user->tokens()->delete();
        $token = $user->createToken('agent_mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->numero,
                'avatar'     => $user->photo ?? null,
                'compagnie'  => $user->compagnie ? [
                    'id'   => $user->compagnie->id,
                    'name' => $user->compagnie->name,
                    'logo' => $user->compagnie->logo ?? null,
                ] : null,
            ],
            'token' => $token,
        ]);
    }
}
