<?php

namespace App\Services\Otp\Channels;

use App\Enums\OtpChannelType;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SmsOtpChannel extends AbstractOtpChannel
{
    public function type(): OtpChannelType
    {
        return OtpChannelType::Sms;
    }

    public function isAvailableFor(User $user): bool
    {
        return $this->fullPhone($user) !== null;
    }

    public function maskedDestination(User $user): ?string
    {
        return $this->maskPhone($this->fullPhone($user));
    }

    public function send(User $user, string $otp): void
    {
        $driver = config('sms.driver', 'twillo');
        Switch ($driver) {
            case 'twillo':
                $this->sendWithTwillio($user, $otp);
                break;
            case 'textbee':
                $this->sendWithTextbee($user, $otp);
                break;
            default:
                throw new \RuntimeException("Driver SMS inconnu : {$driver}");
        }
        
    }

    private function sendWithTwillio(User $user, string $otp): void
    {
        $sid   = config('sms.twillo.twilio_sid');
        $token = config('sms.twillo.twilio_token');
        $from  = config('sms.twillo.twilio_phone_number');

        if (!$sid || !$token || !$from) {
            throw new \RuntimeException('Configuration SMS Twilio manquante. Définissez TWILIO_SID, TWILIO_TOKEN, TWILIO_PHONE_NUMBER dans .env');
        }

        $client = new TwilioClient($sid, $token);
        $client->messages->create($this->fullPhone($user), [
            'from' => $from,
            'body' => "Votre code LIPTRA : {$otp}. Valable 10 minutes.",
        ]);
    }

    private function sendWithTextbee(User $user, string $otp): void
    {
        $apiKey = config('sms.textbee.textbee_api_key');
        $device_id = config('sms.textbee.textbee_device_id');
        $apiBaseUrl = config('sms.textbee.textbee_api_url');

        if (!$apiKey || !$device_id || !$apiBaseUrl) {
            Log::error('Configuration SMS Textbee manquante. Définissez TEXTBEE_API_KEY, TEXTBEE_DEVICE_ID, TEXTBEE_API_URL dans .env');
            throw new \RuntimeException('Configuration SMS Textbee manquante. Définissez TEXTBEE_API_KEY, TEXTBEE_DEVICE_ID, TEXTBEE_API_URL dans .env');
        }

        $url = $apiBaseUrl . "/gateway/devices/" . $device_id . "/send-sms";
        $response = \Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'recipients' => [$this->fullPhone($user)],
            'message' => "Votre code  de verification : {$otp}. Valable 10 minutes. Merci de ne pas partager ce code avec qui que ce soit.",
        ]
        );

        if (!$response->successful()) {
            Log::error('Erreur lors de l\'envoi du SMS via Textbee : ' . $response->body());
            throw new \RuntimeException('Erreur lors de l\'envoi du SMS via Textbee : ' . $response->body());
        }

        Log::info('SMS envoyé avec succès via Textbee à ' . $this->fullPhone($user));
        Log::info('Response : ' . $response->body());



    }
}
