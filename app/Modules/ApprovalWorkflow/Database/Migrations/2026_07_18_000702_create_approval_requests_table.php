<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_workflow_id')->constrained('approval_workflows')->restrictOnDelete();
            $table->ulidMorphs('requestable');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            // References approval_request_steps.id (the per-request step
            // snapshot, not the template step) — that table doesn't exist
            // yet in this migration, so the FK constraint itself is added
            // at the end of the next migration to avoid a circular
            // create-table dependency between the two tables.
            $table->ulid('current_step_id')->nullable();
            $table->enum('status', array_column(ApprovalRequestStatus::cases(), 'value'));
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
