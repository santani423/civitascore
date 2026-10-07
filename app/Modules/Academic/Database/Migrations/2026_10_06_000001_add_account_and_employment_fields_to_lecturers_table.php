<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a Lecturer record to its login identity in `users` (same idea as
 * students.user_id) and adds the employment / academic-identity fields SDM
 * manages. user_id is unique per tenant rather than globally — unlike a
 * student, one person can legitimately be a lecturer at two universities
 * (dosen luar biasa) with a single login, each tenant holding its own
 * lecturers row. Soft deletes replace the previous hard delete so a
 * removed lecturer's history (and audit trail) is never lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->foreignUlid('user_id')->nullable()->after('university_id')->constrained('users')->nullOnDelete();
            $table->string('nip', 50)->nullable()->after('nidn');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('employment_status', 20)->nullable()->after('phone');
            $table->string('functional_rank', 30)->nullable()->after('employment_status');
            $table->string('highest_education', 10)->nullable()->after('functional_rank');
            $table->date('hired_at')->nullable()->after('highest_education');
            $table->softDeletesTz();

            $table->unique(['university_id', 'user_id']);
            $table->index(['university_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'user_id']);
            $table->dropIndex(['university_id', 'email']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropSoftDeletesTz();
            $table->dropColumn(['nip', 'phone', 'employment_status', 'functional_rank', 'highest_education', 'hired_at']);
        });
    }
};
