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

// Met en pause les tickets payés jamais scannés après le départ (chaque heure).
// Le battement est réglé par compagnie depuis le panel admin (groupe « Avancé ») :
// aucune valeur n'est fixée ici, la commande lit le paramétrage de chacune.
// Le statut « Pause » permet ensuite au voyageur de reporter son trajet.
Schedule::command('tickets:pause-non-consommes')
    ->hourly()
    ->withoutOverlapping();

// ── Rapports automatiques par email aux gérants ──────────────────────────────
// Journalier : chaque soir à 20h (activité du jour)
Schedule::command('reports:send daily')->dailyAt('20:00')->withoutOverlapping();
// Hebdomadaire : lundi 7h (semaine écoulée)
Schedule::command('reports:send weekly')->weeklyOn(1, '07:00')->withoutOverlapping();
// Mensuel : le 1er à 7h (mois précédent)
Schedule::command('reports:send monthly')->monthlyOn(1, '07:00')->withoutOverlapping();
// Annuel : le 1er janvier à 8h (année écoulée)
Schedule::command('reports:send yearly')->yearlyOn(1, 1, '08:00')->withoutOverlapping();

// Rappels de départ (push/email/in-app), tous paliers confondus.
// Les paliers actifs et leur avance de tir sont réglés par chaque compagnie
// depuis le panel ; la commande se contente de vérifier ce qui est dû.
Schedule::command('notifications:departure-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Message « bon voyage » aux passagers scannés à l'embarquement.
Schedule::command('notifications:bon-voyage')
    ->everyFiveMinutes()
    ->withoutOverlapping();
