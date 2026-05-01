<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users|max:255',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $dto    = new RegisterDTO(...$validated);
            $result = $this->authService->register($dto);

            return response()->json([
                'status'  => 'success',
                'message' => 'Compte créé avec succès.',
                'user'    => $result['user'],
                'token'   => $result['token'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        try {
            $dto    = new LoginDTO($validated['email'], $validated['password']);
            $result = $this->authService->login($dto);

            return response()->json([
                'status'  => 'success',
                'message' => 'Connexion réussie.',
                'user'    => $result['user'],
                'token'   => $result['token'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout();

        return response()->json(['status' => 'success', 'message' => 'Déconnexion réussie.']);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email|exists:users,email']);

        try {
            $this->authService->sendOtp($validated['email']);
            return response()->json(['status' => 'success', 'message' => 'OTP envoyé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ]);

        try {
            $dto    = new VerifyOtpDTO($validated['email'], $validated['otp']);
            $result = $this->authService->verifyOtp($dto);

            return response()->json(['status' => 'success', 'verified' => $result]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email|exists:users,email']);

        try {
            $this->authService->forgotPassword($validated['email']);
            return response()->json(['status' => 'success', 'message' => 'Lien de réinitialisation envoyé.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $dto    = new ResetPasswordDTO($validated['email'], $validated['token'], $validated['password']);
            $result = $this->authService->resetPassword($dto);

            return response()->json(['status' => 'success', 'reset' => $result]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
