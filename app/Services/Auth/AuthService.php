<?php

namespace App\Services\Auth;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\SexeUser;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Exceptions\AuthenticationException;
use App\Mail\Auth\OtpMail;
use App\Enums\OtpChannelType;
use App\Services\Otp\OtpService;
use Laravel\Sanctum\PersonalAccessToken;
use Twilio\Rest\Client as TwilioClient;

class AuthService
{
    private const OTP_TTL_MINUTES = 10;

    public function __construct(private readonly OtpService $otp) {}

    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name'               => $dto->name,
            'email'              => $dto->email,
            'password'           => Hash::make($dto->password),
            'first_name'         => $dto->first_name ?? $dto->name,
            'last_name'          => $dto->last_name ?? '',
            'sexe'               => $dto->sexe ?? SexeUser::Homme,
            'numero'             => $dto->numero,
            'numero_identifiant' => $dto->numero_identifiant ?? '+226',
            'role'               => $dto->role ?? UserRole::User,
            'compagnie_id'       => $dto->compagnie_id,
        ]);

        // Envoi de l'OTP de vérification via le canal choisi (ou le canal par défaut).
        // On n'échoue pas l'inscription si l'envoi échoue : l'utilisateur pourra
        // renvoyer le code depuis l'écran de vérification.
        try {
            $this->otp->send($user, $dto->verification_method, 'verification');
        } catch (\Throwable $e) {
            Log::warning('Envoi OTP inscription échoué', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $accessToken  = $user->createToken('access_token', ['*'], now()->addDay())->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(30))->plainTextToken;

        return ['user' => $user, 'token' => $accessToken, 'refresh_token' => $refreshToken];
    }

    // ── Vérification de compte (OTP) ────────────────────────────────────────

    /** Canaux de vérification disponibles + canal par défaut pour l'utilisateur. */
    public function verificationChannels(User $user): array
    {
        return [
            'channels' => $this->otp->availableChannels($user),
            'default'  => $this->otp->defaultChannel($user)->value,
        ];
    }

    /** (Re)envoi d'un OTP de vérification de compte. */
    public function sendVerificationOtp(User $user, ?OtpChannelType $channel): OtpChannelType
    {
        return $this->otp->send($user, $channel, 'verification');
    }

    /** Vérifie l'OTP et marque le compte (email ou téléphone) comme vérifié. */
    public function verifyAccount(User $user, string $otp): bool
    {
        $channel = $this->otp->verify($user, $otp, 'verification');

        if ($channel === null) {
            return false;
        }

        $user->forceFill([$channel->verifiedColumn() => now()])->save();

        return true;
    }

    public function login(LoginDTO $dto): array
    {
        if ($dto->email) {
            // ── Connexion par email ─────────────────────────────────────────
            if (!Auth::attempt(['email' => $dto->email, 'password' => $dto->password])) {
                throw AuthenticationException::invalidCredentials();
            }
            $user = User::where('email', $dto->email)->firstOrFail();
        } else {
            // ── Connexion par numéro de téléphone ───────────────────────────
            // Normalise le numéro : on ne garde que les chiffres puis on tente
            // plusieurs formes (avec/sans indicatif +226)
            $digits = preg_replace('/\D/', '', $dto->phone);

            // Tente d'abord le numéro local (8 derniers chiffres si >8 chiffres)
            $local  = strlen($digits) > 8 ? ltrim(substr($digits, -8), '0') : $digits;

            $user = User::where('numero', $digits)
                ->orWhere('numero', (int) $digits)
                ->orWhere('numero', $local)
                ->orWhere('numero', (int) $local)
                ->first();

            if (!$user || !Hash::check($dto->password, $user->password)) {
                throw AuthenticationException::invalidCredentials();
            }

            Auth::login($user);
        }

        // Révocation des anciens tokens pour éviter l'accumulation
        $user->tokens()->delete();
        $accessToken  = $user->createToken('access_token', ['*'], now()->addDay())->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(30))->plainTextToken;

        return ['user' => $user, 'token' => $accessToken, 'refresh_token' => $refreshToken];
    }

    public function refresh(?string $rawToken): array
    {
        if (!$rawToken) {
            throw new AuthenticationException('Refresh token manquant', 401);
        }

        $tokenModel = PersonalAccessToken::findToken($rawToken);

        if (!$tokenModel || $tokenModel->name !== 'refresh_token' || $tokenModel->isExpired()) {
            throw new AuthenticationException('Refresh token invalide ou expiré', 401);
        }

        /** @var User $user */
        $user = $tokenModel->tokenable;

        // Rotation : supprimer l'ancien refresh token + access tokens
        $tokenModel->delete();
        $user->tokens()->where('name', 'access_token')->delete();

        $accessToken  = $user->createToken('access_token', ['*'], now()->addDay())->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(30))->plainTextToken;

        return ['token' => $accessToken, 'refresh_token' => $refreshToken];
    }

    public function logout(): bool
    {
        if (request()->user()) {
            request()->user()->currentAccessToken()->delete();
        }

        return true;
    }

    public function sendOtp(string $phoneOrEmail): bool
    {
        $field = filter_var($phoneOrEmail, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero';
        $user  = User::where($field, $phoneOrEmail)->first();

        if (!$user) {
            throw AuthenticationException::userNotFound();
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpCacheKey($user->id), $otp, Carbon::now()->addMinutes(self::OTP_TTL_MINUTES));

        if ($field === 'email') {
            Mail::to($user->email)->send(new OtpMail($otp));
        } else {
            $this->sendSmsTwilio($user->numero, $otp);
        }

        return true;
    }

    private function sendSmsTwilio(string $phoneNumber, string $otp): void
    {
        $sid   = config('sms.twillo.twilio_sid');
        $token = config('sms.twillo.twilio_token');
        $from  = config('sms.twillo.twilio_phone_number');

        if (!$sid || !$token || !$from) {
            throw new \RuntimeException('Configuration SMS Twilio manquante. Définissez TWILIO_SID, TWILIO_TOKEN, TWILIO_PHONE_NUMBER dans .env');
        }

        $client = new TwilioClient($sid, $token);
        $client->messages->create($phoneNumber, [
            'from' => $from,
            'body' => "Votre code LIPTRA : {$otp}. Valable 10 minutes.",
        ]);
    }

    public function verifyOtp(VerifyOtpDTO $dto): bool
    {
        $field = filter_var($dto->phone_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero';
        $user  = User::where($field, $dto->phone_or_email)->first();

        if (!$user) {
            return false;
        }

        $storedOtp = Cache::get($this->otpCacheKey($user->id));

        if (!$storedOtp || !hash_equals($storedOtp, $dto->otp)) {
            return false;
        }

        Cache::forget($this->otpCacheKey($user->id));

        return true;
    }

    private function otpCacheKey(int $userId): string
    {
        return 'otp_user_' . $userId;
    }

    public function forgotPassword(string $email): bool
    {
        $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT;
    }

    public function resetPassword(ResetPasswordDTO $dto): bool
    {
        $status = Password::reset(
            [
                'email' => $dto->email,
                'password' => $dto->password,
                'password_confirmation' => $dto->password,
                'token' => $dto->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    // ── Réinitialisation du mot de passe par OTP (app mobile) ───────────────

    /** Retrouve un utilisateur par email ou numéro de téléphone. */
    public function findByIdentifier(string $identifier): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        $digits = preg_replace('/\D/', '', $identifier);
        $local  = strlen($digits) > 8 ? ltrim(substr($digits, -8), '0') : $digits;

        return User::where('numero', $digits)
            ->orWhere('numero', (int) $digits)
            ->orWhere('numero', $local)
            ->orWhere('numero', (int) $local)
            ->first();
    }

    /** Envoi d'un OTP de réinitialisation de mot de passe. */
    public function sendPasswordResetOtp(User $user, ?OtpChannelType $channel): OtpChannelType
    {
        return $this->otp->send($user, $channel, 'password_reset');
    }

    /** Vérifie l'OTP puis change le mot de passe. */
    public function resetPasswordWithOtp(User $user, string $otp, string $password): bool
    {
        if ($this->otp->verify($user, $otp, 'password_reset') === null) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($password),
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        return true;
    }
}
