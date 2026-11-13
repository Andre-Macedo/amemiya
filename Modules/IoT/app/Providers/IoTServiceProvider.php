<?php

namespace Modules\IoT\Providers;

use Modules\IoT\Console\Commands\MarkOfflineDevices;
use Modules\IoT\Console\Commands\PruneSensorData;
use Modules\IoT\Console\MqttBridge;
use Modules\IoT\Services\MqttCommandPublisher;
use Nwidart\Modules\Support\ModuleServiceProvider;

class IoTServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'IoT';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'iot';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        MqttBridge::class,
        PruneSensorData::class,
        MarkOfflineDevices::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(MqttCommandPublisher::class);
    }

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
