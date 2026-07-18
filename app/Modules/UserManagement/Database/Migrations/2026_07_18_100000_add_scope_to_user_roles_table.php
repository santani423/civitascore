<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropUnique('user_roles_user_id_role_id_unique');
            $table->string('scope_type')->nullable()->after('role_id');
            $table->ulid('scope_id')->nullable()->after('scope_type');
            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_roles_user_id_role_id_scope_unique');
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropUnique('user_roles_user_id_role_id_scope_unique');
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->dropColumn(['scope_type', 'scope_id']);
            $table->unique(['user_id', 'role_id'], 'user_roles_user_id_role_id_unique');
        });
    }
};
