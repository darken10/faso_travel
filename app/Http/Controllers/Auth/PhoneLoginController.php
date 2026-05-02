<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class PhoneLoginController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    // GET /connexion/telephone
    public function showPhoneForm()
    {
        return view('auth.phone-login');
    }

    // POST /connexion/telephone/demander
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{8,15}$/'],
        ], [
            'phone.required' => 'Le numéro de téléphone est requis.',
            'phone.regex'    => 'Numéro invalide (8 à 15 chiffres sans espaces).',
        ]);

        $key = 'otp.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Trop de tentatives. Réessayez dans {$seconds}s."]);
        }
        RateLimiter::hit($key, 60);

        $phone = $request->phone;
        $user  = User::where('numero', (int) $phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'Aucun compte associé à ce numéro.'])->withInput();
        }

        $this->otp->send($phone);

        session(['otp_phone' => $phone]);

        return redirect()->route('auth.phone.otp-form')
            ->with('status', 'Code envoyé ! Vérifiez votre téléphone.');
    }

    // GET /connexion/telephone/otp
    public function showOtpForm(Request $request)
    {
        if (!session('otp_phone')) {
            return redirect()->route('auth.phone.form');
        }
        return view('auth.phone-otp', [
            'phone'       => session('otp_phone'),
            'simulation'  => config('app.env') !== 'production' ? session('otp_simulation_code') : null,
        ]);
    }

    // POST /connexion/telephone/verifier
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ], [
            'code.required' => 'Veuillez saisir le code reçu.',
            'code.size'     => 'Le code doit contenir exactement 6 chiffres.',
            'code.regex'    => 'Le code ne doit contenir que des chiffres.',
        ]);

        $phone = session('otp_phone');
        if (!$phone) {
            return redirect()->route('auth.phone.form')
                ->withErrors(['code' => 'Session expirée. Recommencez.']);
        }

        $key = 'otp.verify.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Trop de tentatives incorrectes.']);
        }

        if (!PhoneOtp::consume($phone, $request->code)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

        RateLimiter::clear($key);

        $user = User::where('numero', (int) $phone)->firstOrFail();

        Auth::login($user, remember: true);

        $request->session()->regenerate();
        session()->forget(['otp_phone', 'otp_simulation_code']);

        $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';
        return redirect($scheme.'://app.'.config('app.domain').'/');
    }
}
