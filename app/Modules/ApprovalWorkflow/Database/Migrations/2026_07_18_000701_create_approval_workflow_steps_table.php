<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->enum('approver_type', array_column(ApprovalApproverType::cases(), 'value'));
            $table->foreignUlid('approver_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignUlid('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action_on_reject', array_column(ApprovalRejectAction::cases(), 'value'))
                ->default(ApprovalRejectAction::StopWorkflow->value);
            $table->timestampsTz();

            $table->unique(['approval_workflow_id', 'sequence'], 'approval_workflow_steps_workflow_id_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};
