<?php

namespace Database\Seeders;

use App\Enums\CompanyRole;
use App\Models\Permission;
use App\Models\Role;
use App\Rbac\RoleGabarits;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Crée les dix-neuf rôles gabarits et leurs permissions.
     *
     * Les anciens rôles (`user`, `admin`, et les six valeurs de `CompanyRole`) sont
     * conservés : ils portent encore les affectations en base. Leur retrait n'aura lieu
     * qu'après validation en production de la commande `rbac:migrate-roles`.
     */
    public function run(): void
    {
        $this->seedRolesHistoriques();
        $this->seedGabarits();
    }

    private function seedRolesHistoriques(): void
    {
        $historiques = [
            ['name' => 'user', 'label' => 'Utilisateur', 'scope' => 'platform'],
            ['name' => 'admin', 'label' => 'Administrateur', 'scope' => 'platform'],
        ];

        foreach (CompanyRole::cases() as $companyRole) {
            $historiques[] = [
                'name' => $companyRole->value,
                'label' => $companyRole->label(),
                'scope' => 'compagnie',
            ];
        }

        foreach ($historiques as $role) {
            Role::updateOrCreate(
                ['name' => $role['name'], 'compagnie_id' => null],
                $role + ['is_system' => true]
            );
        }
    }

    private function seedGabarits(): void
    {
        $idsParNom = Permission::pluck('id', 'name');

        foreach (RoleGabarits::tous() as $gabarit) {
            $role = Role::updateOrCreate(
                ['name' => $gabarit['name'], 'compagnie_id' => null],
                [
                    'label' => $gabarit['label'],
                    'scope' => $gabarit['scope'],
                    'rang' => $gabarit['rang'],
                    'description' => $gabarit['description'],
                    'is_system' => true,
                ]
            );

            $aAttacher = [];

            foreach ($gabarit['permissions'] as $nom => $portee) {
                if (! isset($idsParNom[$nom])) {
                    continue;
                }

                $aAttacher[$idsParNom[$nom]] = ['portee' => $portee];
            }

            // `sync` et non `syncWithoutDetaching` : un gabarit doit converger sur le
            // catalogue du code, sinon une permission retirée d'un rôle resterait
            // attachée en base. Les rôles propres à une compagnie, eux, ne sont jamais
            // touchés par ce seeder — ils portent un `compagnie_id`.
            $role->permissions()->sync($aAttacher);
        }
    }
}
