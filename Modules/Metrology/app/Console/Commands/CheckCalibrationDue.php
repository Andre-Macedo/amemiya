<?php

namespace Modules\Metrology\Console\Commands;

use Illuminate\Console\Command;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Notifications\CalibrationDueNotification;
use App\Models\User; // Assumindo que User está no App principal
use Illuminate\Support\Facades\Notification;

class CheckCalibrationDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrology:check-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for instruments due for calibration and notify users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due calibrations...');

        // Define os intervalos de notificação
        $intervals = [30, 7, 0];

        foreach ($intervals as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $instruments = Instrument::whereDate('calibration_due', $targetDate)
                ->where('status', 'active') // Apenas ativos
                ->get();

            if ($instruments->isNotEmpty()) {
                $this->info("Found {$instruments->count()} instruments due in {$days} days.");

                // Encontrar quem deve receber (ex: Admins ou Metrologistas)
                // Por enquanto, vamos mandar para todos os usuários com permissão (simplificado)
                // Idealmente: User::permission('receive_alerts')->get()
                $users = User::all();

                Notification::send($users, new CalibrationDueNotification($instruments, $days));
            }
        }

        $this->info('Done.');
    }
}
