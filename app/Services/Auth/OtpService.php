<?php

namespace App\Services\Auth;

use App\Models\PhoneOtp;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function send(string $phone): string
    {
        $otp = PhoneOtp::generate($phone);

        if (config('app.env') === 'production' && config('services.sms.api_key')) {
            $this->sendViaSms($phone, $otp->code);
        } else {
            // Mode simulation : affichage dans les logs et dans la session
            Log::info("[OTP SIMULATION] Phone: {$phone} → Code: {$otp->code}");
            session(['otp_simulation_code' => $otp->code]);
        }

        return $otp->code;
    }

    private function sendViaSms(string $phone, string $code): void
    {
        // Brancher ici le prestataire SMS (Orange, Twilio, etc.)
        // \Http::post(config('services.sms.url'), [
        //     'to'      => $phone,
        //     'message' => "Votre code de connexion LIPTRA : {$code}. Valide 5 minutes.",
        // ]);
    }
}
