<?php

namespace App\Http\Controllers\Api\V2;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Enums\OtpChannelType;
use App\Exceptions\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Auth\LoginRequest;
use App\Http\Requests\Api\V2\Auth\RegisterRequest;
use App\Http\Resources\Api\V2\Auth\AuthResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validate();

        try {
            $result = $this->authService->register(RegisterDTO::fromRequest($validated));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
       
        return response()->json(new AuthResource($result), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->authService->login(LoginDTO::fromRequest($validated));
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return response()->json(new AuthResource($result));
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

    // ── Vérification de compte (utilisateur authentifié) ────────────────────

    /** Liste des canaux de vérification disponibles + canal par défaut. */
    public function verificationChannels(Request $request): JsonResponse
    {
        return response()->json($this->authService->verificationChannels($request->user()));
    }

    /** (Re)envoi d'un OTP de vérification de compte via le canal choisi. */
    public function sendVerificationOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', Rule::in(OtpChannelType::values())],
        ]);

        $channel = isset($validated['channel']) ? OtpChannelType::from($validated['channel']) : null;

        try {
            $used = $this->authService->sendVerificationOtp($request->user(), $channel);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['sent' => true, 'channel' => $used->value]);
    }

    /** Confirme l'OTP et marque le compte comme vérifié. */
    public function verifyAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $verified = $this->authService->verifyAccount($request->user(), $validated['otp']);

        if (!$verified) {
            return response()->json(['error' => true, 'message' => 'Code invalide ou expiré.'], 422);
        }

        return response()->json([
            'success'  => true,
            'verified' => true,
            'user'     => $request->user()->refresh(),
        ]);
    }

    // ── Mot de passe oublié ─────────────────────────────────────────────────

    public function forgotPassword(Request $request): JsonResponse
    {
        // Sur l'app mobile (header X-Client: mobile) → réinitialisation par OTP.
        // Sur le web → comportement inchangé (lien de réinitialisation par email).
        if (!$this->isMobile($request)) {
            $validated = $request->validate(['email' => 'required|string|email']);

            $sent = $this->authService->forgotPassword($validated['email']);

            if (!$sent) {
                return response()->json(['error' => true, 'message' => "Impossible d'envoyer le lien de réinitialisation"], 400);
            }

            return response()->json(['sent' => true]);
        }

        $validated = $request->validate([
            'identifier' => 'required|string',
            'channel'    => ['nullable', Rule::in(OtpChannelType::values())],
        ]);

        $user = $this->authService->findByIdentifier($validated['identifier']);

        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Aucun compte ne correspond à cet identifiant.'], 404);
        }

        $channel = isset($validated['channel']) ? OtpChannelType::from($validated['channel']) : null;

        try {
            $used = $this->authService->sendPasswordResetOtp($user, $channel);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['sent' => true, 'channel' => $used->value]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        // Web : flux par token inchangé. Mobile : flux par OTP.
        if (!$this->isMobile($request)) {
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

        $validated = $request->validate([
            'identifier' => 'required|string',
            'otp'        => 'required|string|size:6',
            'password'   => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $user = $this->authService->findByIdentifier($validated['identifier']);

        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Aucun compte ne correspond à cet identifiant.'], 404);
        }

        $reset = $this->authService->resetPasswordWithOtp($user, $validated['otp'], $validated['password']);

        if (!$reset) {
            return response()->json(['error' => true, 'message' => 'Code invalide ou expiré.'], 422);
        }

        return response()->json(['reset' => true]);
    }

    /** Détecte si la requête provient de l'app mobile. */
    private function isMobile(Request $request): bool
    {
        return strtolower((string) $request->header('X-Client')) === 'mobile';
    }
}
