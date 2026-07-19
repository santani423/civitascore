<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Enums\UniversityStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('education_institution_type')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', array_column(UniversityStatus::cases(), 'value'))->default(UniversityStatus::Draft->value);
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('locale')->default('id');
            $table->string('currency')->default('IDR');
            $table->string('date_format')->default('d/m/Y');
            // No FK to file_uploads: a tenant's logo may be replaced/deleted
            // independently, and file_uploads rows are themselves tenant-
            // scoped once this migration's own university_id backfill runs
            // (see UserManagement/FileManagement add_university_id
            // migrations) — a hard FK here would create a bootstrap cycle.
            $table->ulid('logo_file_id')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};
