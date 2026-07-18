<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();

            $table->unique(['user_id', 'role_id'], 'user_roles_user_id_role_id_unique');
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
