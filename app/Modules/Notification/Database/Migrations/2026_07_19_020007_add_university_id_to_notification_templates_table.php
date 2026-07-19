<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->foreignUlid('university_id')->nullable()->after('id')->constrained('universities')->cascadeOnDelete();
        });

        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->dropUnique(['event_key', 'channel']);
            $table->unique(['university_id', 'event_key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'event_key', 'channel']);
            $table->unique(['event_key', 'channel']);
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
    }
};
