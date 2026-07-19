<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The grant's tenant: null = a global grant (applies in every tenant
 * context, e.g. the platform super_admin), filled = the grant only counts
 * while TenantContext resolves to that same university. See
 * PermissionRegistry::contextForUser() for how this is applied.
 *
 * Deliberately a dedicated column rather than reusing the existing
 * scope_type/scope_id polymorphic pair — those stay available for a
 * *sub*-tenant scope (faculty/study-program) once those tables exist,
 * composing with university_id rather than being repurposed to mean it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreignUlid('university_id')->nullable()->after('role_id')->constrained('universities')->cascadeOnDelete();
            $table->index('university_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
    }
};
