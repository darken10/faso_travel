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

// ── Rapports automatiques par email aux gérants ──────────────────────────────
// Journalier : chaque soir à 20h (activité du jour)
Schedule::command('reports:send daily')->dailyAt('20:00')->withoutOverlapping();
// Hebdomadaire : lundi 7h (semaine écoulée)
Schedule::command('reports:send weekly')->weeklyOn(1, '07:00')->withoutOverlapping();
// Mensuel : le 1er à 7h (mois précédent)
Schedule::command('reports:send monthly')->monthlyOn(1, '07:00')->withoutOverlapping();
