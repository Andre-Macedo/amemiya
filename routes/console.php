<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamento de Verificação de Calibração
Schedule::command('metrology:check-due')->dailyAt('08:00');

// IoT: Efetua o flush da telemetria do Redis para o MySQL a cada 5 segundos
Schedule::command('iot:flush-buffer')->everyFiveSeconds();
