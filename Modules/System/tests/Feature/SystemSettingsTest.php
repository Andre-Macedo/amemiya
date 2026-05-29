<?php

use Laravel\Sanctum\Sanctum;
use Modules\System\Models\Setting;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list specific system settings', function () {
    Setting::setValue('test_key', 'test_value');

    $response = $this->getJson('/api/v1/system/settings?keys[]=test_key');

    $response->assertStatus(200)
        ->assertJsonPath('test_key', 'test_value');
});

it('can update multiple system settings', function () {
    $data = [
        'settings' => [
            'strict_competence_enforcement' => 'true',
            'another_setting' => '123',
        ],
    ];

    $response = $this->putJson('/api/v1/system/settings', $data);

    $response->assertStatus(200);

    expect(Setting::getValue('strict_competence_enforcement'))->toBe('true')
        ->and(Setting::getValue('another_setting'))->toBe('123');
});
