<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ApprovalWorkflow\Enums\ApprovalActionType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_request_step_id')->constrained('approval_request_steps')->cascadeOnDelete();
            $table->foreignUlid('acted_by')->constrained('users')->restrictOnDelete();
            $table->enum('action', array_column(ApprovalActionType::cases(), 'value'));
            $table->text('comment')->nullable();
            $table->foreignUlid('delegated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
