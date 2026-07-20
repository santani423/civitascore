<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Announcement\Enums\AnnouncementTargetScope;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('target_scope', array_column(AnnouncementTargetScope::cases(), 'value'));
            // Interpreted based on target_scope, pointing at either
            // faculties.id or study_programs.id — a formal FK constraint
            // isn't possible since it can reference either of two tables.
            $table->ulid('target_id')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestampTz('published_at');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['university_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
