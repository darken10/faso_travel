<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tous les comptes déjà existants sont considérés comme vérifiés depuis
     * leur date de création (created_at), afin de ne pas les forcer à passer
     * par la nouvelle vérification OTP.
     */
    public function up(): void
    {
        // Comptes avec email → email vérifié à la date de création
        DB::table('users')
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);

        // Comptes restants (sans email vérifié) → téléphone vérifié à la création
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Réversion : on remet à null les vérifications qui correspondent
        // exactement à la date de création (posées par ce backfill).
        DB::table('users')
            ->whereColumn('email_verified_at', 'created_at')
            ->update(['email_verified_at' => null]);

        DB::table('users')
            ->whereColumn('phone_verified_at', 'created_at')
            ->update(['phone_verified_at' => null]);
    }
};
