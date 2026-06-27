<?php

namespace App\Services\Otp\Channels;

use App\Enums\OtpChannelType;
use App\Models\User;
use Twilio\Rest\Client as TwilioClient;

class WhatsappOtpChannel extends AbstractOtpChannel
{
    public function type(): OtpChannelType
    {
        return OtpChannelType::Whatsapp;
    }

    public function isAvailableFor(User $user): bool
    {
        return $this->fullPhone($user) !== null
            && !empty(config('sms.twillo.twilio_whatsapp_from'));
    }

    public function maskedDestination(User $user): ?string
    {
        return $this->maskPhone($this->fullPhone($user));
    }

    public function send(User $user, string $otp): void
    {
        $sid   = config('sms.twillo.twilio_sid');
        $token = config('sms.twillo.twilio_token');
        $from  = config('sms.twillo.twilio_whatsapp_from');

        if (!$sid || !$token || !$from) {
            throw new \RuntimeException('Configuration WhatsApp Twilio manquante. Définissez TWILIO_SID, TWILIO_TOKEN, TWILIO_WHATSAPP_FROM dans .env');
        }

        $client = new TwilioClient($sid, $token);
        $client->messages->create('whatsapp:' . $this->fullPhone($user), [
            'from' => 'whatsapp:' . $from,
            'body' => "Votre code LIPTRA : {$otp}. Valable 10 minutes.",
        ]);
    }
}
