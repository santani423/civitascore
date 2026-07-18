<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_request_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignUlid('approval_workflow_step_id')->constrained('approval_workflow_steps')->restrictOnDelete();
            // Copied from the template step at submission time — later
            // edits to the template never retroactively alter in-flight
            // requests.
            $table->unsignedSmallInteger('sequence');
            $table->foreignUlid('assigned_approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', array_column(ApprovalRequestStepStatus::cases(), 'value'));
            $table->timestampTz('acted_at')->nullable();
            $table->timestampsTz();

            $table->unique(['approval_request_id', 'sequence'], 'approval_request_steps_request_id_sequence_unique');
        });

        // Completes the circular FK from approval_requests.current_step_id,
        // deferred here since this table didn't exist yet in that migration.
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->foreign('current_step_id')
                ->references('id')->on('approval_request_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropForeign(['current_step_id']);
        });

        Schema::dropIfExists('approval_request_steps');
    }
};
