<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PhonePasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    // GET /mot-de-passe-oublie/telephone
    public function showPhoneForm()
    {
        return view('auth.forgot-password-phone');
    }

    // POST /mot-de-passe-oublie/telephone
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone'   => ['required', 'string', 'regex:/^[0-9]{8,15}$/'],
            'channel' => ['required', 'in:sms,whatsapp'],
        ], [
            'phone.required' => 'Le numéro de téléphone est requis.',
            'phone.regex'    => 'Numéro invalide (8 à 15 chiffres, sans espaces).',
            'channel.in'     => 'Canal invalide.',
        ]);

        $key = 'pwd-reset-otp.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Trop de tentatives. Réessayez dans {$seconds}s."]);
        }
        RateLimiter::hit($key, 300);

        $phone = $request->phone;
        $user  = User::where('numero', (int) $phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'Aucun compte associé à ce numéro.'])->withInput();
        }

        $this->otp->send($phone, $request->channel);

        session(['pwd_reset_phone' => $phone, 'pwd_reset_channel' => $request->channel]);

        return redirect()->route('password.phone.verify-form')
            ->with('status', 'Code envoyé ! Vérifiez votre téléphone.');
    }

    // GET /mot-de-passe-oublie/telephone/verifier
    public function showResetForm(Request $request)
    {
        if (!session('pwd_reset_phone')) {
            return redirect()->route('password.phone.form');
        }

        return view('auth.reset-password-phone', [
            'phone'      => session('pwd_reset_phone'),
            'channel'    => session('pwd_reset_channel', 'sms'),
            'simulation' => config('app.env') !== 'production' ? session('otp_simulation_code') : null,
        ]);
    }

    // POST /mot-de-passe-oublie/telephone/verifier
    public function reset(Request $request)
    {
        $request->validate([
            'code'                  => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'code.required'         => 'Veuillez saisir le code reçu.',
            'code.size'             => 'Le code doit contenir exactement 6 chiffres.',
            'code.regex'            => 'Le code ne doit contenir que des chiffres.',
            'password.required'     => 'Le nouveau mot de passe est requis.',
            'password.min'          => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'    => 'Les mots de passe ne correspondent pas.',
        ]);

        $phone = session('pwd_reset_phone');
        if (!$phone) {
            return redirect()->route('password.phone.form');
        }

        $key = 'pwd-reset-verify.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Trop de tentatives incorrectes. Réessayez plus tard.']);
        }

        if (!PhoneOtp::consume($phone, $request->code)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => 'Code incorrect ou expiré.'])->withInput(['code' => '']);
        }

        RateLimiter::clear($key);

        $user = User::where('numero', (int) $phone)->firstOrFail();
        $user->forceFill(['password' => Hash::make($request->password)])->save();

        session()->forget(['pwd_reset_phone', 'pwd_reset_channel', 'otp_simulation_code']);

        $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';
        return redirect($scheme.'://app.'.config('app.domain').'/login')
            ->with('status', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
    }
}
