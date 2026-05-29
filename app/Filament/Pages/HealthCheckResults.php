<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Modules\System\Filament\Clusters\System\SystemCluster;
use Spatie\Health\ResultStores\ResultStore;

class HealthCheckResults extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Saúde do Sistema';

    protected ?string $heading = 'Status da Infraestrutura';

    protected static ?string $cluster = SystemCluster::class;

    protected string $view = 'filament.pages.health-check-results';

    public function getViewData(): array
    {
        /** @var ResultStore $store */
        $store = app(ResultStore::class);

        $checkResults = $store->latestResults();

        return [
            'lastRanAt' => $checkResults?->finishedAt,
            'checkResults' => $checkResults,
        ];
    }
}
