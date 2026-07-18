<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('scope', array_column(PermissionScope::cases(), 'value'));
            $table->enum('action', array_column(PermissionAction::cases(), 'value'));
            $table->string('resource')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestampsTz();

            $table->index(['scope', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
