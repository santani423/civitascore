<?php

namespace Modules\FileManagement\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @extends Factory<FileUpload>
 */
class FileUploadFactory extends Factory
{
    protected $model = FileUpload::class;

    public function definition(): array
    {
        return [
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'uploads/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => fake()->numberBetween(1_000, 500_000),
            'checksum' => hash('sha256', fake()->uuid()),
            'status' => FileUploadStatus::Clean,
            'is_public' => false,
        ];
    }
}
