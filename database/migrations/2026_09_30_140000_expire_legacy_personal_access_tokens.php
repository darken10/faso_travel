<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Donne une échéance aux jetons émis avant la désactivation du plafond global.
 *
 * config('sanctum.expiration') valait 24 h et coupait tout jeton, quelle que soit
 * sa propre échéance. Il est désormais désactivé : la durée de vie est portée par
 * chaque jeton. Or les jetons déjà émis n'ont pas d'échéance propre (expires_at
 * est NULL, le plafond suffisait) : sans cette migration, ils deviendraient
 * éternels au déploiement.
 *
 * Ils reçoivent l'échéance qu'ils avaient de fait : création + 24 h. Un jeton plus
 * ancien est donc expiré dès maintenant, exactement comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->update(['expires_at' => DB::raw('DATE_ADD(created_at, INTERVAL 24 HOUR)')]);
    }

    public function down(): void
    {
        // Irréversible sans perte : on ne sait plus quels jetons avaient une échéance NULL.
    }
};
