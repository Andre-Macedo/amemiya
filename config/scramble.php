<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\OpenApiVersion;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [
    /*
     * Which routes to document. String or array form; use Scramble::routes() for custom selection.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    /*
     * Cache configuration for the generated OpenAPI document.
     */
    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    /*
     * OpenAPI version of the resulting document.
     */
    'openapi_version' => OpenApiVersion::V3_1,

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Documentação Técnica da API REST da Plataforma Metrológica Lean Tech & Telemetria Industrial Amemiya.',
    ],

    'ui' => [
        'title' => 'Amemiya Metrology & IoT API',
    ],

    /*
     * Load Scramble's development tools on documentation pages.
     */
    'dev_tools' => [
        'enabled' => env('SCRAMBLE_DEV_TOOLS', env('APP_DEBUG', false)),
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
     * Automatically document API security based on route middleware.
     */
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*', 'auth:sanctum'],
            'scheme' => SecurityScheme::http('bearer'),
        ],
    ],
];
