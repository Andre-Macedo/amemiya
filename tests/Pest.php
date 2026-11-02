<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit', '../Modules/*/tests/Feature', '../Modules/*/tests/Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeBetween', function (float $min, float $max) {
    return $this->toBeGreaterThanOrEqual($min)->toBeLessThanOrEqual($max);
});
