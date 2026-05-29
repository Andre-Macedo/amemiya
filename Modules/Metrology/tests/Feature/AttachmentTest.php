<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Attachment;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    Storage::fake('public');
});

it('can upload an attachment to an instrument', function () {
    $instrument = Instrument::factory()->create();
    $file = UploadedFile::fake()->create('manual.pdf', 500);

    $response = $this->postJson('/api/v1/metrology/attachments', [
        'file' => $file,
        'attachable_type' => 'Modules\Metrology\Models\Instrument',
        'attachable_id' => $instrument->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('attachment.original_name', 'manual.pdf');

    $attachment = Attachment::first();
    expect($attachment->attachable_id)->toBe($instrument->id);
    Storage::disk('public')->assertExists($attachment->file_path);
});

it('can delete an attachment', function () {
    $instrument = Instrument::factory()->create();
    $file = UploadedFile::fake()->create('photo.jpg', 200);
    $path = $file->store('attachments', 'public');

    $attachment = Attachment::create([
        'attachable_id' => $instrument->id,
        'attachable_type' => Instrument::class,
        'file_name' => 'photo.jpg',
        'original_name' => 'photo.jpg',
        'file_path' => $path,
        'disk' => 'public',
        'uploaded_by' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/v1/metrology/attachments/{$attachment->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($path);
});

it('loads attachments when showing an instrument', function () {
    $instrument = Instrument::factory()->create();
    Attachment::factory()->create([
        'attachable_id' => $instrument->id,
        'attachable_type' => Instrument::class,
    ]);

    $response = $this->getJson("/api/v1/metrology/instruments/{$instrument->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'attachments',
            ],
        ])
        ->assertJsonCount(1, 'data.attachments');
});
