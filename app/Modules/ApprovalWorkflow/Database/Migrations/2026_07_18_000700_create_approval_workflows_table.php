<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            // Stable business-request-type key (e.g. later "cuti_mahasiswa").
            // Phase 1 has no business approvables yet, so this stays generic.
            $table->string('workflowable_type');
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['workflowable_type', 'name'], 'approval_workflows_workflowable_type_name_unique');
            $table->index('workflowable_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
