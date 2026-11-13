<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamento de Verificação de Calibração
Schedule::command('metrology:check-due')->dailyAt('08:00');

// IoT: Watchdog de dispositivos silenciosos
Schedule::command('iot:mark-offline-devices')->everyMinute();
