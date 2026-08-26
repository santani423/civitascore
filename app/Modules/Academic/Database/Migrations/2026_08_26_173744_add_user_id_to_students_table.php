<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a Student record to its login identity in `users`. Nullable +
 * nullOnDelete because not every student necessarily has a login account
 * yet (e.g. freshly imported records before the backfill command runs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->foreignUlid('user_id')->nullable()->after('id')->unique()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
