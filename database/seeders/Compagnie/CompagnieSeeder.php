<?php

namespace Database\Seeders\Compagnie;

use Illuminate\Database\Seeder;
use App\Models\Compagnie\Compagnie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use App\Enums\CompanyRole;

class CompagnieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $compagnies = [
            [ 'name' => 'Saramaya Transport', 'sigle' => 'SARAMAYA', 'slogant' => 'Toujours plus rapide', 'description' => 'Un description bref Saramaya', 'logo_uri' => '', 'user_id' => null, ],
            [ 'name' => 'Elitice Transport', 'sigle' => 'ELITISE', 'slogant' => 'Toujours dans le confort', 'description' => 'Un description bref Elitis', 'logo_uri' => '', 'user_id' => null, ],
            [ 'name' => 'Rayimo Transport', 'sigle' => 'RAYIMO', 'slogant' => 'Le confort et la rapiditer est au rendez-vous', 'description' => 'Un description bref Rayimo', 'logo_uri' => '', 'user_id' => null, ],
        ];

        $users = [
            'SARAMAYA' => [
                [
                    'first_name' => 'Ali',
                    'last_name' => 'Traore',
                    'email' => 'ali.traore@saramaya.com',
                    'password' => bcrypt('password'),
                    'roles' => ['company_admin', 'agent'],
                ],
                [
                    'first_name' => 'Fatou',
                    'last_name' => 'Ouattara',
                    'email' => 'fatou.ouattara@saramaya.com',
                    'password' => bcrypt('password'),
                    'roles' => ['bagagiste'],
                ],
            ],
            'ELITISE' => [
                [
                    'first_name' => 'Jean',
                    'last_name' => 'Kaboré',
                    'email' => 'jean.kabore@elitise.com',
                    'password' => bcrypt('password'),
                    'roles' => ['company_admin'],
                ],
                [
                    'first_name' => 'Awa',
                    'last_name' => 'Zongo',
                    'email' => 'awa.zongo@elitise.com',
                    'password' => bcrypt('password'),
                    'roles' => ['agent', 'caisse'],
                ],
            ],
            'RAYIMO' => [
                [
                    'first_name' => 'Moussa',
                    'last_name' => 'Sanou',
                    'email' => 'moussa.sanou@rayimo.com',
                    'password' => bcrypt('password'),
                    'roles' => ['company_admin'],
                ],
                [
                    'first_name' => 'Aminata',
                    'last_name' => 'Diallo',
                    'email' => 'aminata.diallo@rayimo.com',
                    'password' => bcrypt('password'),
                    'roles' => ['rh'],
                ],
            ],
        ];

        foreach ($compagnies as $compagnieData) {
            $compagnie = Compagnie::create($compagnieData);

            if (isset($users[$compagnieData['sigle']])) {
                foreach ($users[$compagnieData['sigle']] as $userData) {
                    $user = User::create([
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'email' => $userData['email'],
                        'password' => $userData['password'],
                        'compagnie_id' => $compagnie->id,
                    ]);

                    foreach ($userData['roles'] as $roleName) {
                        $roleModel = Role::where('name', $roleName)->first();
                        if ($roleModel) {
                            $user->roles()->attach($roleModel);
                        }
                    }

                    // Le premier admin de la compagnie devient son propriétaire.
                    if (!$compagnie->user_id && in_array('company_admin', $userData['roles'], true)) {
                        $compagnie->update(['user_id' => $user->id]);
                    }
                }
            }
        }
    }
}
