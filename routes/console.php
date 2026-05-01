<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Génère les instances de voyages pour les 7 prochains jours (chaque soir à 22h)
Schedule::command('voyages:generate-instances --days=7')
    ->dailyAt('22:00')
    ->withoutOverlapping()
    ->runInBackground();

// Annule les tickets non payés après 24h (chaque heure)
Schedule::command('tickets:clean-expired --hours=24')
    ->hourly()
    ->withoutOverlapping();

// Synchronise les places disponibles (chaque nuit à 23h)
Schedule::command('voyages:sync-seats')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->runInBackground();
