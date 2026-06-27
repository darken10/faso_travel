<?php

namespace App\Services\Otp\Channels;

use App\Enums\OtpChannelType;
use App\Models\User;
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
}
