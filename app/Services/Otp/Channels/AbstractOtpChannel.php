<?php

namespace App\Services\Otp\Channels;

use App\Contracts\OtpChannel;
use App\Models\User;

abstract class AbstractOtpChannel implements OtpChannel
{
    /** Numéro complet au format E.164 (indicatif + numéro), ou null. */
    protected function fullPhone(User $user): ?string
    {
        if (empty($user->numero)) {
            return null;
        }

        $prefix = $user->numero_identifiant ?: '+226';

        return $prefix . preg_replace('/\D/', '', (string) $user->numero);
    }

    protected function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, 1);

        return $visible . str_repeat('*', max(1, mb_strlen($name) - 1)) . '@' . $domain;
    }

    protected function maskPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $len = mb_strlen($phone);
        if ($len <= 4) {
            return $phone;
        }

        return mb_substr($phone, 0, 4) . str_repeat('*', $len - 6) . mb_substr($phone, -2);
    }
}
