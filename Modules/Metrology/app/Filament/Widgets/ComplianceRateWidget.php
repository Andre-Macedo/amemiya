<?php

namespace Modules\Metrology\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Modules\Metrology\Models\Calibration;

class ComplianceRateWidget extends ChartWidget
{
    protected ?string $heading = 'Compliance Rate Widget';

    protected function getData(): array
    {
        $data = Trend::model(Calibration::class)
            ->between(now()->subDays(30), now())
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Approved Calibrations',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#10b981',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
