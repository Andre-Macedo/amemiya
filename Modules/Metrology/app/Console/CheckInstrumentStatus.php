<?php

namespace Modules\Metrology\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Emails\CalibrationStatusReport;
use Modules\Metrology\Models\Instrument;

class CheckInstrumentStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'metrology:check-status';

    /**
     * The console command description.
     */
    protected $description = 'Verifica instrumentos vencidos e atualiza o status automaticamente.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificação...');

        Instrument::where('calibration_due', '<', now()->startOfDay())
            ->whereNotIn('status', ['expired', 'in_calibration', 'lost'])
            ->update(['status' => 'expired']);

        $expired = Instrument::where('status', 'expired')->get();

        $dueSoon = Instrument::where('status', 'active')
            ->whereBetween('calibration_due', [now(), now()->addDays(30)])
            ->get();

        if ($expired->count() > 0 || $dueSoon->count() > 0) {

            // Define quem recebe email
            $recipients = ['andrluis@proton.me'];

            if (!$recipients) {
                $this->warn('Nenhum usuário encontrado para enviar o e-mail.');
                return;
            }

            // Envia o e-mail
            Mail::to($recipients)->send(new CalibrationStatusReport($expired, $dueSoon));

            $this->info('Relatório enviado por e-mail para ' . $recipients[0]. ' usuários.');
        } else {
            $this->info('Tudo em ordem. Nenhum e-mail enviado.');
        }
    }
}
