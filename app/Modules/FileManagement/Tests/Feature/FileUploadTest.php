<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;
use Modules\SystemSetting\Models\FeatureFlag;

beforeEach(function () {
    Storage::fake('local');
});

test('an authenticated user can upload a file and it is marked clean', function () {
    $user = actingAsUserWithPermissions([]);

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->postJson('/api/v1/file-uploads', ['file' => $file]);

    $response->assertApiSuccess(201);
    expect($response->json('data.status'))->toBe(FileUploadStatus::Clean->value);

    $upload = FileUpload::query()->find($response->json('data.id'));
    expect($upload->uploaded_by)->toBe($user->id);
    Storage::disk('local')->assertExists($upload->path);
});

test('with virus scanning enabled, a clean file still ends up Clean via the scanner branch', function () {
    FeatureFlag::factory()->create(['key' => 'file_upload.virus_scan_enabled', 'is_enabled' => true]);
    actingAsUserWithPermissions([]);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/api/v1/file-uploads', ['file' => $file]);

    $response->assertApiSuccess(201);
    expect($response->json('data.status'))->toBe(FileUploadStatus::Clean->value);
});

test('an oversized file is rejected by validation', function () {
    actingAsUserWithPermissions([]);

    $file = UploadedFile::fake()->create('huge.pdf', 20_000, 'application/pdf');

    $response = $this->postJson('/api/v1/file-uploads', ['file' => $file]);

    $response->assertApiError(422);
});

test('a disallowed mime type is rejected by validation', function () {
    actingAsUserWithPermissions([]);

    $file = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

    $response = $this->postJson('/api/v1/file-uploads', ['file' => $file]);

    $response->assertApiError(422);
});

test('a non-owner without permission cannot view a private file', function () {
    $owner = actingAsUserWithPermissions([]);
    $upload = FileUpload::factory()->create(['uploaded_by' => $owner->id, 'is_public' => false]);

    $intruder = actingAsUserWithPermissions([]);

    $response = $this->getJson("/api/v1/file-uploads/{$upload->id}");

    $response->assertApiError(403);
});

test('a soft-deleted file is no longer accessible', function () {
    $owner = actingAsUserWithPermissions(['file_uploads.delete']);
    $upload = FileUpload::factory()->create(['uploaded_by' => $owner->id]);

    $this->deleteJson("/api/v1/file-uploads/{$upload->id}")->assertApiSuccess();

    $response = $this->getJson("/api/v1/file-uploads/{$upload->id}");

    $response->assertApiError(404);
});
