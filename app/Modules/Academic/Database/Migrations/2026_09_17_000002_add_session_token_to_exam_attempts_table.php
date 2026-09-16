<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            // Token sesi ujian publik (akses via NIM, tanpa login) — disimpan
            // sebagai hash (pola sama seperti personal access token Sanctum),
            // plaintext-nya hanya pernah ada di response satu kali saat
            // dibuat. Lihat ExamService::startPublicAttempt()/findAttemptBySessionToken().
            $table->string('session_token_hash', 64)->nullable()->unique()->after('score');
            $table->timestampTz('session_expires_at')->nullable()->after('session_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn(['session_token_hash', 'session_expires_at']);
        });
    }
};
