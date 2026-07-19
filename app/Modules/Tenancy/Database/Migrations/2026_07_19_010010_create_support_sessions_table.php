<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for Super Admin "support mode" access into a tenant — every
 * such session must be recorded with a reason and (ideally) is time-boxed
 * via started_at/ended_at. See EnsureUniversityAccessMiddleware for the
 * super_admin bypass this accounts for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('super_admin_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->text('reason');
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('ended_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('actions_performed')->nullable();
            $table->string('approval_reference')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_sessions');
    }
};
