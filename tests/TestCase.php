<?php

namespace Tests;

use Database\Seeders\TestReferenceSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seede les données de référence (statuts, rôles) indispensables à
     * l'intégrité des FK. Avec RefreshDatabase, le seeding s'exécute UNE
     * fois après migrate:fresh (hors transaction), donc persiste pour
     * tous les tests.
     */
    protected bool $seed = true;

    protected string $seeder = TestReferenceSeeder::class;
}
