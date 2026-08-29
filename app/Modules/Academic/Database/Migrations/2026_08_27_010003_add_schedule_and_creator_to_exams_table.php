<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->timestampTz('starts_at')->nullable()->after('duration_minutes');
            $table->timestampTz('ends_at')->nullable()->after('starts_at');
            $table->foreignUlid('created_by')->nullable()->after('ends_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
