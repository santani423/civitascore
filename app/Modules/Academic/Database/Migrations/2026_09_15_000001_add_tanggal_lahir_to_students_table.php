<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student date of birth, used as the source for the default/initial
 * password (DDMMYYYY, hashed) on NIM-based student login. Nullable because
 * existing/imported students may not have this recorded yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->date('tanggal_lahir')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('tanggal_lahir');
        });
    }
};
