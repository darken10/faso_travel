<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Durcit la table des paramètres compagnie : une seule ligne par couple
 * (compagnie, clé), valeur en TEXT nullable pour accueillir les textes longs
 * et les listes JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicates();

        Schema::table('compagnie_settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
            $table->string('type', 20)->default('string')->change();
            $table->unique(['compagnie_id', 'key'], 'compagnie_settings_compagnie_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('compagnie_settings', function (Blueprint $table) {
            $table->dropUnique('compagnie_settings_compagnie_key_unique');
            $table->string('value')->nullable(false)->change();
        });
    }

    /** Conserve la ligne la plus récente pour chaque couple (compagnie, clé). */
    private function removeDuplicates(): void
    {
        $doublons = DB::table('compagnie_settings')
            ->select('compagnie_id', 'key', DB::raw('MAX(id) as keep_id'))
            ->groupBy('compagnie_id', 'key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublons as $doublon) {
            DB::table('compagnie_settings')
                ->where('compagnie_id', $doublon->compagnie_id)
                ->where('key', $doublon->key)
                ->where('id', '!=', $doublon->keep_id)
                ->delete();
        }
    }
};
