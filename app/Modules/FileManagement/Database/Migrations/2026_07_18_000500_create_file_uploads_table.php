<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\FileManagement\Enums\FileUploadStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableUlidMorphs('fileable');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->enum('status', array_column(FileUploadStatus::cases(), 'value'));
            $table->boolean('is_public')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uploaded_by');
            $table->index('status');
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
