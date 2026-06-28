<?php

namespace Database\Seeders;

use App\Models\Statut;
use Illuminate\Database\Seeder;

/**
 * Données de référence minimales requises par les tests pour respecter
 * les contraintes de clés étrangères (statuts, rôles). Seedé UNE fois par
 * RefreshDatabase (hors transaction), donc partagé par tous les tests.
 *
 * Idempotent : peut être ré-exécuté sans créer de doublons.
 */
class TestReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Statuts (Compagnie.statut_id, etc. — défaut DB = 2)
        foreach (['Activer', 'Désactiver', 'Pause', 'Bloquer'] as $name) {
            Statut::firstOrCreate(['name' => $name]);
        }

        // Rôles (déjà idempotent)
        $this->call(RoleSeeder::class);
    }
}
