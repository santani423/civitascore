<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @internal Test-only fixture proving the generic polymorphic approval
 * engine end-to-end (submit -> approve/reject through real HTTP + DB), since
 * Phase 1 ships no real business approvable yet (Cuti Mahasiswa etc. arrive
 * in a later phase). Loaded only when APP_ENV=testing (see
 * App\Providers\ModuleServiceProvider) — never migrated in a real
 * environment, so it never reaches production schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_demo_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_demo_items');
    }
};
