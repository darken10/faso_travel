<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneOtp extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function generate(string $phone): self
    {
        // Purge expired codes for this phone
        static::where('phone', $phone)->delete();

        return static::create([
            'phone'      => $phone,
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    public static function verify(string $phone, string $code): bool
    {
        return static::where('phone', $phone)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public static function consume(string $phone, string $code): bool
    {
        $otp = static::where('phone', $phone)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) return false;

        $otp->delete();
        return true;
    }
}
