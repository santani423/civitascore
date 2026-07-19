<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * null university_id = global template role (super_admin, staff, ...),
 * visible/usable from every tenant but only editable via the platform API.
 * A filled value = a role custom-defined by that specific university.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignUlid('university_id')->nullable()->after('id')->constrained('universities')->cascadeOnDelete();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->unique(['university_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'slug']);
            $table->unique('slug');
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
    }
};
