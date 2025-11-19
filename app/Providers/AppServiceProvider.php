<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => [
                50  => 'oklch(0.97 0.01 255)',
                100 => 'oklch(0.94 0.02 255)',
                200 => 'oklch(0.88 0.04 255)',
                300 => 'oklch(0.80 0.07 255)',
                400 => 'oklch(0.65 0.11 255)',
                500 => 'oklch(0.50 0.13 255)',
                600 => 'oklch(0.40 0.12 255)',
                700 => 'oklch(0.30 0.10 255)',
                800 => 'oklch(0.25 0.08 255)',
                900 => 'oklch(0.18 0.06 255)',
                950 => 'oklch(0.12 0.04 255)',
                ],
        ]);

        FilamentColor::register([
            'success' => [
                50  => 'oklch(0.98 0.02 155)',
                100 => 'oklch(0.95 0.05 155)',
                200 => 'oklch(0.88 0.09 155)',
                300 => 'oklch(0.80 0.13 155)',
                400 => 'oklch(0.70 0.16 155)',
                500 => 'oklch(0.60 0.17 155)',
                600 => 'oklch(0.50 0.15 155)',
                700 => 'oklch(0.40 0.12 155)',
                800 => 'oklch(0.30 0.09 155)',
                900 => 'oklch(0.22 0.06 155)',
                950 => 'oklch(0.15 0.04 155)',
            ],
        ]);


    }
}
