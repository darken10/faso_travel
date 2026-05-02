<?php

namespace App\Services\Auth;

use App\Models\PhoneOtp;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function send(string $phone, string $channel = 'sms'): string
    {
        $otp = PhoneOtp::generate($phone);

        if (config('app.env') === 'production') {
            match ($channel) {
                'whatsapp' => $this->sendViaWhatsApp($phone, $otp->code),
                default    => $this->sendViaSms($phone, $otp->code),
            };
        } else {
            Log::info("[OTP SIMULATION] Phone: {$phone} Channel: {$channel} → Code: {$otp->code}");
            session(['otp_simulation_code' => $otp->code]);
        }

        return $otp->code;
    }

    private function sendViaSms(string $phone, string $code): void
    {
        // Twilio SMS
        // $client = new \Twilio\Rest\Client(config('sms.twillo.twilio_sid'), config('sms.twillo.twilio_token'));
        // $client->messages->create('+226'.$phone, [
        //     'from' => config('sms.twillo.twilio_phone_number'),
        //     'body' => "Votre code LIPTRA : {$code}. Valide 5 minutes.",
        // ]);
    }

    private function sendViaWhatsApp(string $phone, string $code): void
    {
        // Twilio WhatsApp
        // $client = new \Twilio\Rest\Client(config('sms.twillo.twilio_sid'), config('sms.twillo.twilio_token'));
        // $client->messages->create('whatsapp:+226'.$phone, [
        //     'from' => 'whatsapp:'.config('sms.twillo.twilio_phone_number'),
        //     'body' => "Votre code LIPTRA : {$code}. Valide 5 minutes.",
        // ]);
    }
}
