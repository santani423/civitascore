<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * approval-request-steps/{step}/approve|reject|delegate route-model-binds
 * directly on this table (not through its parent approval_requests), so it
 * needs its own tenant scope for defense-in-depth rather than relying only
 * on the parent's isolation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_request_steps', function (Blueprint $table): void {
            $table->foreignUlid('university_id')->nullable()->after('id')->constrained('universities')->cascadeOnDelete();
            $table->index('university_id');
        });
    }

    public function down(): void
    {
        Schema::table('approval_request_steps', function (Blueprint $table): void {
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
    }
};
