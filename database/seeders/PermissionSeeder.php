<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Rbac\PermissionCatalogue;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Aligne la table `permissions` sur le catalogue du code.
     *
     * Le seeder ne supprime rien : une permission retirée du catalogue laisse sa ligne en
     * place, car la supprimer cascaderait sur `permission_role` et retirerait
     * silencieusement un droit à des comptes en production. Le retrait est une décision
     * explicite, pas un effet de bord de seeder.
     */
    public function run(): void
    {
        foreach (PermissionCatalogue::toutes() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'domaine' => $permission['domaine'],
                    'label' => $permission['label'],
                    'description' => $permission['description'] ?? null,
                    'scope' => $permission['scope'],
                    'is_sensitive' => $permission['is_sensitive'] ?? false,
                ]
            );
        }
    }
}
