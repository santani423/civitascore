<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            // Token opaque yang dipakai di URL akses publik ujian
            // (/exam/{access_token}) — bukan credential mahasiswa, hanya
            // pengganti ID ujian yang tidak mudah ditebak. Lihat
            // ExamService::generateAccessToken()/findByAccessToken().
            $table->string('access_token', 64)->nullable()->unique()->after('is_published');
            $table->timestampTz('access_token_generated_at')->nullable()->after('access_token');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn(['access_token', 'access_token_generated_at']);
        });
    }
};
