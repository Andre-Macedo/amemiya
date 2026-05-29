<?php

namespace Modules\Metrology\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Notifications\CalibrationDueNotification;
use Modules\System\Models\User;

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
    protected $description = 'Check for instruments due for calibration, update statuses, and notify users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily calibration check...');

        // 1. Auto-expire instruments
        $expiredCount = Instrument::where('calibration_due', '<', now()->startOfDay())
            ->whereNotIn('status', ['expired', 'in_calibration', 'lost', 'rejected'])
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            $this->info("Updated {$expiredCount} instruments to 'expired' status.");
        }

        // 2. Check for upcoming dues (Notification Logic)
        $intervals = [30, 7, 0];
        $users = User::all(); // TODO: Filter by permission 'receive_alerts'

        foreach ($intervals as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $instruments = Instrument::whereDate('calibration_due', $targetDate)
                ->whereIn('status', ['active', 'due']) // Include 'due' status if you use it
                ->get();

            if ($instruments->isNotEmpty()) {
                $this->info("Found {$instruments->count()} instruments due in {$days} days.");

                // Send Notification (Database + Mail)
                Notification::send($users, new CalibrationDueNotification($instruments, $days));
            }
        }

        $this->info('Daily check completed.');
    }
}
