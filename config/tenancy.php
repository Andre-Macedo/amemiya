<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Tenant;
use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Features\CrossDomainRedirect;
use Stancl\Tenancy\Features\UserImpersonation;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => null, // We use HasUlids trait in the model

    'domain_model' => Domain::class,

    /**
     * The list of domains hosting your central app.
     */
    'central_domains' => [
        '127.0.0.1',
        'localhost',
        'amemiya.test',
        'admin.amemiya.test',
        'leantech.andremacedo.dev.br',
    ],

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     */
    'bootstrappers' => [
        // Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class, // Disabled for Single Database
        CacheTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
    ],

    /**
     * Database tenancy config. (Not used in single-database mode)
     */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => '',
        'suffix' => '',
        'managers' => [],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/tenants/%tenant_id%/',
            'public' => '%storage_path%/app/public/tenants/%tenant_id%/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [
        UserImpersonation::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class,
        CrossDomainRedirect::class,
        // Stancl\Tenancy\Features\ViteBundler::class,
    ],

    'routes' => true,

    /**
     * Parameters used by the tenants:migrate command.
     * (Not used in single-database mode)
     */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
