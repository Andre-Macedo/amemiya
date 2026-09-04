<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Modules\System\Models\User;
use Sentry\State\Scope;
use Spatie\Backup\Tasks\Monitor\HealthChecks\BackupCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

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
        // Registrar modelo customizado do Sanctum para suportar ULIDs
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Resolução dinâmica de factories para os módulos
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if (str_starts_with($modelName, 'Modules\\')) {
                $parts = explode('\\', $modelName);
                $module = $parts[1] ?? '';
                $model = end($parts);

                return "Modules\\{$module}\\Database\\Factories\\{$model}Factory";
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });

        // Registro de Morph Map para relações polimórficas (Sanctum, ActivityLog, etc)
        Relation::morphMap([
            'User' => User::class,
        ]);

        // Bypassa todas as verificações de permissão para super-admin
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Rate Limiting Global da API
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        // Autorização para Visualizador de Logs (Opcodes)
        Gate::define('viewLogViewer', function ($user) {
            return $user->hasRole('super-admin');
        });

        // Autorização para Laravel Pulse
        Gate::define('viewPulse', function ($user) {
            return $user->hasRole('super-admin');
        });

        // Registro de Saúde do Servidor (Spatie Health)
        Health::checks([
            DatabaseCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            CacheCheck::new(),
            OptimizedAppCheck::new(),
            // BackupCheck::new(), // Monitora se o backup rodou e se está saudável
        ]);

        // Contexto do Tenant para o Sentry/GlitchTip
        if (app()->bound('sentry')) {
            \Sentry\configureScope(function (Scope $scope): void {
                if (tenancy()->initialized) {
                    $scope->setTag('tenant_id', (string) tenancy()->tenant->id);
                    $scope->setTag('tenant_slug', tenancy()->tenant->slug);
                }
            });
        }

        // Configuração de Cores do Filament
        FilamentColor::register([
            'primary' => [
                50 => 'oklch(0.97 0.01 255)',
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
            'success' => [
                50 => 'oklch(0.98 0.02 155)',
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
