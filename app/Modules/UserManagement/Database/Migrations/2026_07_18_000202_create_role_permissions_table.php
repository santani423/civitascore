<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignUlid('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['role_id', 'permission_id'], 'role_permissions_role_id_permission_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
