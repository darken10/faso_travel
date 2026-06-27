<?php

namespace App\Services\Otp\Channels;

use App\Enums\OtpChannelType;
use App\Mail\Auth\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MailOtpChannel extends AbstractOtpChannel
{
    public function type(): OtpChannelType
    {
        return OtpChannelType::Email;
    }

    public function isAvailableFor(User $user): bool
    {
        return !empty($user->email);
    }

    public function maskedDestination(User $user): ?string
    {
        return $this->maskEmail($user->email);
    }

    public function send(User $user, string $otp): void
    {
        Mail::to($user->email)->send(new OtpMail($otp));
    }
}
