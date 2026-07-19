<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->foreignUlid('university_id')->nullable()->after('id')->constrained('universities')->cascadeOnDelete();
        });

        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->dropUnique(['workflowable_type', 'name']);
            $table->unique(['university_id', 'workflowable_type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'workflowable_type', 'name']);
            $table->unique(['workflowable_type', 'name']);
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
    }
};
