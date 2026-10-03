<?php

namespace Database\Seeders;

use App\Enums\SexeUser;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\Compagnie\CompagnieSeeder;
use Database\Seeders\Finance\CategorieDepenseSeeder;
use Database\Seeders\Ville\PaysSeeder;
use Database\Seeders\Ville\RegionSeeder;
use Database\Seeders\Ville\VilleSeeder;
use Database\Seeders\Voyage\ClasseSeeder;
use Database\Seeders\Voyage\ConfortSeeder;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();

        /*User::factory()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/

        User::create([
            'first_name' => 'Darken',
            'last_name' => 'Dark',
            'email' => 'darken@darken.com',
            'sexe' => SexeUser::Homme,
            'numero' => 70707070,
            'numero_identifiant' => '+226',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'role' => UserRole::User,
        ]);
        User::create([
            'first_name' => 'Client',
            'last_name' => 'Dark',
            'sexe' => SexeUser::Homme,
            'numero' => 70707071,
            'numero_identifiant' => '+226',
            'email' => 'client@client.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'role' => UserRole::User,
        ]);
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'Dark',
            'sexe' => SexeUser::Homme,
            'numero' => 70707072,
            'numero_identifiant' => '+226',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'role' => UserRole::Admin,
        ]);
        User::create([
            'first_name' => 'Root',
            'last_name' => 'Dark',
            'sexe' => SexeUser::Homme,
            'numero' => 70707073,
            'numero_identifiant' => '+226',
            'email' => 'root@root.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'role' => UserRole::Root,
        ]);
        User::create([
            'first_name' => 'Compagnie',
            'last_name' => 'Bosse',
            'sexe' => SexeUser::Homme,
            'numero' => 70707074,
            'numero_identifiant' => '+226',
            'email' => 'comapgnie@bosse.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'role' => UserRole::CompagnieBosse,
        ]);

        $this->call([
            StatutSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            PaysSeeder::class,
            RegionSeeder::class,
            VilleSeeder::class,
            CompagnieSeeder::class,
            CategorieDepenseSeeder::class,
            ConfortSeeder::class,
            ClasseSeeder::class,
        ]);
    }
}
