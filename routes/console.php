<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches planifiées (LWS : cron `* * * * * php artisan schedule:run`)
|--------------------------------------------------------------------------
*/

// Sauvegarde quotidienne (base + preuves de paiement).
Schedule::command('backup:run')->dailyAt('03:00')->onOneServer()->runInBackground();

// Purge des vieux journaux d'activité (spatie ; garde 365 jours, cf. config).
Schedule::command('activitylog:clean')->weeklyOn(1, '03:30');

// Referme les paiements/commandes jamais justifiés (stock réintégré, client prévenu).
Schedule::command('payments:expire-pending')->dailyAt('04:00');

// Besoins acheteur dont la date souhaitée est dépassée → statut "expiré".
Schedule::command('needs:expire-outdated')->dailyAt('04:30');
