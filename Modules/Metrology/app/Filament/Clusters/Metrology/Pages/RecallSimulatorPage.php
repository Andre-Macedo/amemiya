<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Metrology\Actions\GenerateStandardImpactReportAction;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\StandardRecallSimulatorService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecallSimulatorPage extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $title = 'Simulador de Recall Metrológico';

    protected static ?string $navigationLabel = 'Simulador de Recall';

    protected static ?int $navigationSort = 40;

    protected static string $routePath = '/recall-simulator';

    protected string $view = 'metrology::filament.pages.recall-simulator';

    public ?string $standard_id = null;

    public float $drift_magnitude = 0.01;

    public string $drift_direction = 'worst_case';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public float $additional_uncertainty = 0.0;

    public string $reason = 'Simulação preventiva de deriva metrológica na janela de recalibração';

    public string $activeFilter = 'all';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $simulation = null;

    public bool $hasExecuted = false;

    public function mount(): void
    {
        $this->end_date = now()->format('Y-m-d');

        if (request()->has('standard_id')) {
            $this->standard_id = (string) request()->query('standard_id');
            $this->loadStandardDefaults();
            $this->runSimulation();
        }
    }

    public function updatedStandardId(): void
    {
        $this->loadStandardDefaults();
        $this->simulation = null;
        $this->hasExecuted = false;
    }

    public function runSimulation(): void
    {
        if (! $this->standard_id) {
            Notification::make()
                ->title('Selecione um padrão')
                ->body('É necessário selecionar um padrão de referência para executar a simulação.')
                ->warning()
                ->send();

            return;
        }

        $standard = ReferenceStandard::find($this->standard_id);

        if (! $standard) {
            Notification::make()
                ->title('Padrão não encontrado')
                ->danger()
                ->send();

            return;
        }

        $simulator = app(StandardRecallSimulatorService::class);

        $this->simulation = $simulator->simulate(
            standard: $standard,
            driftMagnitude: (float) $this->drift_magnitude,
            driftDirection: $this->drift_direction,
            startDate: $this->start_date,
            endDate: $this->end_date,
            additionalUncertainty: (float) $this->additional_uncertainty
        );

        $this->hasExecuted = true;

        $invalidated = $this->simulation['summary']['invalidated_count'];
        $severity = $this->simulation['summary']['severity_level'];

        if ($severity === 'CRITICAL') {
            Notification::make()
                ->title('Simulação Concluída - Risco Crítico!')
                ->body("Foram detectadas {$invalidated} calibrações invalidadas pela deriva simulada.")
                ->danger()
                ->send();

            return;
        }

        if ($severity === 'WARNING') {
            Notification::make()
                ->title('Simulação Concluída - Alerta Metrológico')
                ->body('Instrumentos com margem de tolerância reduzida (TUR < 3).')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Simulação Concluída com Sucesso')
            ->body('Todas as calibrações permanecem dentro das tolerâncias especificadas.')
            ->success()
            ->send();
    }

    public function downloadPdf(): StreamedResponse
    {
        if (! $this->standard_id) {
            abort(400, 'Padrão não selecionado.');
        }

        $standard = ReferenceStandard::findOrFail($this->standard_id);
        $action = app(GenerateStandardImpactReportAction::class);

        $pdfContent = $action->execute(
            standard: $standard,
            startDate: $this->start_date,
            endDate: $this->end_date,
            reason: $this->reason
        );

        $filename = "Laudo_Recall_Simulado_{$standard->stock_number}.pdf";

        return response()->streamDownload(
            fn () => print ($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    /**
     * @return Collection<int, ReferenceStandard>
     */
    public function getStandardsProperty(): Collection
    {
        return ReferenceStandard::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFilteredItemsProperty(): array
    {
        if (! $this->simulation) {
            return [];
        }

        $items = $this->simulation['items'] ?? [];

        if ($this->activeFilter === 'invalidated') {
            return array_values(array_filter($items, fn ($i) => ! empty($i['is_invalidated'])));
        }

        if ($this->activeFilter === 'critical') {
            return array_values(array_filter($items, fn ($i) => ($i['risk'] ?? '') === 'CRITICAL'));
        }

        if ($this->activeFilter === 'moderate') {
            return array_values(array_filter($items, fn ($i) => ($i['risk'] ?? '') === 'MODERATE'));
        }

        return $items;
    }

    private function loadStandardDefaults(): void
    {
        if (! $this->standard_id) {
            return;
        }

        $standard = ReferenceStandard::find($this->standard_id);

        if (! $standard) {
            return;
        }

        if ($standard->last_calibration_date) {
            $this->start_date = $standard->last_calibration_date->format('Y-m-d');
        } else {
            $this->start_date = now()->subYear()->format('Y-m-d');
        }
    }
}
