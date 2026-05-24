<?php

namespace App\Http\Controllers\Api\V2;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Exceptions\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'first_name'            => 'nullable|string|max:255',
            'last_name'             => 'nullable|string|max:255',
            'sexe'                  => 'nullable|string',
            'numero'                => 'nullable|integer',
            'numero_identifiant'    => 'nullable|string|max:10',
            'role'                  => 'nullable|string',
            'compagnie_id'          => 'nullable|exists:compagnies,id',
        ]);

        $result = $this->authService->register(RegisterDTO::fromRequest($validated));

        return response()->json([
            'success'       => true,
            'user'          => $result['user'],
            'token'         => $result['token'],
            'refresh_token' => $result['refresh_token'],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required_without:phone|nullable|string|email|max:255',
            'phone'    => 'required_without:email|nullable|string|max:20',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->authService->login(LoginDTO::fromRequest($validated));
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return response()->json([
            'success'       => true,
            'user'          => $result['user'],
            'token'         => $result['token'],
            'refresh_token' => $result['refresh_token'],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh($request->bearerToken());
        } catch (\App\Exceptions\AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return response()->json([
            'success'       => true,
            'token'         => $result['token'],
            'refresh_token' => $result['refresh_token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
        ]);

        try {
            $this->authService->sendOtp($validated['email']);
        } catch (AuthenticationException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 404);
        }

        return response()->json(['sent' => true]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'otp'   => 'required|string|size:6',
        ]);

        $dto    = new VerifyOtpDTO(phone_or_email: $validated['email'], otp: $validated['otp']);
        $result = $this->authService->verifyOtp($dto);

        return response()->json(['verified' => $result]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
        ]);

        $sent = $this->authService->forgotPassword($validated['email']);

        if (!$sent) {
            return response()->json(['error' => true, 'message' => "Impossible d'envoyer le lien de réinitialisation"], 400);
        }

        return response()->json(['sent' => true]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|string|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $reset = $this->authService->resetPassword(ResetPasswordDTO::fromRequest($validated));

        if (!$reset) {
            return response()->json(['error' => true, 'message' => 'Impossible de réinitialiser le mot de passe'], 400);
        }

        return response()->json(['reset' => true]);
    }
}
