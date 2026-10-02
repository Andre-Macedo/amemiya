<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Metrology\Actions\GenerateCertificatePdfAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;

test('generating certificate calculates and updates sha256 pdf_hash', function () {
    $instrument = Instrument::factory()->create();
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => CalibrationResult::Approved,
        'status' => 'published',
        'pdf_hash' => null,
    ]);

    $pdfAction = app(GenerateCertificatePdfAction::class);
    $pdfContent = $pdfAction->execute($calibration);

    $calibration->refresh();

    expect($calibration->pdf_hash)->not->toBeNull()
        ->and(strlen((string) $calibration->pdf_hash))->toBe(64)
        ->and($calibration->pdf_hash)->toBe(hash('sha256', $pdfContent));
});

test('public verify endpoint returns certificate and expected sha256 hash', function () {
    $instrument = Instrument::factory()->create();
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => CalibrationResult::Approved,
        'status' => 'published',
        'verification_hash' => 'hash-test-12345',
        'pdf_hash' => hash('sha256', 'fake-pdf-content'),
    ]);

    $response = $this->getJson('/api/v1/public/certificates/verify/hash-test-12345');

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'certificate' => [
                'verification_hash' => 'hash-test-12345',
                'pdf_hash' => hash('sha256', 'fake-pdf-content'),
                'instrument' => [
                    'name' => $instrument->name,
                ],
            ],
        ]);
});

test('verify file endpoint verifies authentic pdf file', function () {
    $pdfPayload = '%PDF-1.4 official untouched content';
    $pdfHash = hash('sha256', $pdfPayload);

    $instrument = Instrument::factory()->create();
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => CalibrationResult::Approved,
        'status' => 'published',
        'certificate_code' => 'CAL-2026-TEST01',
        'verification_hash' => 'hash-valid-123',
        'pdf_hash' => $pdfHash,
    ]);

    $uploadedFile = UploadedFile::fake()->createWithContent('laudo.pdf', $pdfPayload);

    $response = $this->postJson('/api/v1/public/certificates/verify-file', [
        'file' => $uploadedFile,
        'code' => 'hash-valid-123',
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'authentic' => true,
            'tampered' => false,
            'uploaded_sha256' => $pdfHash,
            'expected_sha256' => $pdfHash,
        ]);
});

test('verify file endpoint detects tampered pdf file', function () {
    $originalPayload = '%PDF-1.4 official untouched content';
    $tamperedPayload = '%PDF-1.4 official MODIFIED content with fake tolerance';

    $originalHash = hash('sha256', $originalPayload);
    $tamperedHash = hash('sha256', $tamperedPayload);

    $instrument = Instrument::factory()->create();
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => CalibrationResult::Approved,
        'status' => 'published',
        'verification_hash' => 'hash-tamper-test',
        'pdf_hash' => $originalHash,
    ]);

    $tamperedFile = UploadedFile::fake()->createWithContent('laudo_modificado.pdf', $tamperedPayload);

    $response = $this->postJson('/api/v1/public/certificates/verify-file', [
        'file' => $tamperedFile,
        'code' => 'hash-tamper-test',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'valid' => false,
            'authentic' => false,
            'tampered' => true,
            'uploaded_sha256' => $tamperedHash,
            'expected_sha256' => $originalHash,
        ]);
});

test('verify file endpoint performs reverse lookup by sha256 when no code provided', function () {
    $pdfPayload = '%PDF-1.4 official reverse lookup content';
    $pdfHash = hash('sha256', $pdfPayload);

    $instrument = Instrument::factory()->create();
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => CalibrationResult::Approved,
        'status' => 'published',
        'certificate_code' => 'CAL-2026-REV01',
        'pdf_hash' => $pdfHash,
    ]);

    $uploadedFile = UploadedFile::fake()->createWithContent('laudo_reverso.pdf', $pdfPayload);

    $response = $this->postJson('/api/v1/public/certificates/verify-file', [
        'file' => $uploadedFile,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'authentic' => true,
            'tampered' => false,
            'uploaded_sha256' => $pdfHash,
            'certificate' => [
                'certificate_code' => 'CAL-2026-REV01',
            ],
        ]);
});
