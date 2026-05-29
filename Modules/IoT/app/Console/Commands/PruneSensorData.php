<?php

namespace Modules\IoT\Console\Commands;

use Illuminate\Console\Command;
use Modules\IoT\Models\IoTSensorData;

class PruneSensorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:prune-data {--days=30 : Quantos dias de dados manter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove dados antigos de sensores para manter o banco saudável.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $date = now()->subDays($days);

        $count = IoTSensorData::where('measured_at', '<', $date)->delete();

        $this->info("Limpeza concluída: {$count} registros antigos removidos.");
    }
}
