<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ApprovalWorkflow\Enums\ApprovalHistoryEvent;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignUlid('approval_request_step_id')->nullable()->constrained('approval_request_steps')->nullOnDelete();
            $table->enum('event', array_column(ApprovalHistoryEvent::cases(), 'value'));
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
